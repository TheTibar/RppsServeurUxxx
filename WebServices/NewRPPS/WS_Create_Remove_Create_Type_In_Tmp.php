<?php
use Classes\LocalProcess;
use Classes\LocalLog;
use Classes\Region;

header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/LocalProcess.php';
include_once dirname(__FILE__) .  '/../../Classes/LocalLog.php';
include_once dirname(__FILE__) .  '/../../Classes/Region.php';


function response($status, $status_message, $data)
{
    header("HTTP/1.1 ".$status);
    //header("Content-Type:application/json;charset=utf-8", false);
    $response['status']=$status;
    $response['status_message']=$status_message;
    $response['data']=$data;
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    echo $json_response;
    die();
}

$LocalProcess = new LocalProcess();
$Log = new LocalLog();
$Region = new Region();

$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($region_token) && ! empty($user_token))
{
    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    //On récupère l'id région
    $result = $Region->getRegionIdByToken($region_token, $user_token);
    
    switch($result) {
        case 0:
            //response(200, "region_tables_ok", NULL);
            $region_id = $Region->__get('region_id');
            break;
        case -99:
            response(200, "fatal_error_region_code", NULL); //proposer de relancer
            break;
        case -2:
            response(200, "get_region_code_error", NULL); //proposer de relancer
            break;
    }
    

    //On récupère le code région
    $result = $Region->getRegionCode($region_id);
    switch($result) {
        case 0:
            $region_code = $Region->__get('code');
            break;
        case -1:
            response(200, "unknown_region_code", NULL);
            break;
        case -2:
            response(200, "get_region_code_error", NULL);
            break;
    }
    
    
    $result = $LocalProcess->newLocalProcess($region_id, "SYNCHRO " . $region_code, 0);
    switch($result) {
        case 0:
            $process_id = $LocalProcess->__get('process_id');
            break;
        case -1:
            response(200, "unknown_region_code", NULL);
            break;
        case -2:
            response(200, "get_region_code_error", NULL);
            break;
        case -3:
            response(200, "create_process_error", NULL);
            break;
    }
    
    $data = [];
    $data['process_id'] = $process_id;
    
    
    
    $Log->writeLog($region_id, "Début de la synchronisation", $process_id);
    $Log->writeLog($region_id, "Début création des lignes REMOVE", $process_id);
    
    //on sauvegarde les données identifiant_pp <-> sales_pro_id que l'on va devoir supprimer (on le fait le plus tôt possible pour
    // travailler le moins longtemps sur NEW_DATA
    $sql = "INSERT INTO rpps_" . $region_code . "_tmp_identifiant_pp (identifiant_pp, sales_pro_id, process_id, histo_type)
                    SELECT DISTINCT
                	CD.Identifiant_PP, DSP.sales_pro_id, $process_id, 'REMOVE'
                	FROM rpps_" . $region_code . "_doctor_sales_pro_link DSP
                	inner join rpps_" . $region_code . "_current_data CD on CD.identifiant_pp = DSP.identifiant_pp
                	inner join rpps_" . $region_code . "_sales_pro SP on SP.sales_pro_id = DSP.sales_pro_id
                	left outer join rpps_" . $region_code . "_new_data ND on ND.Identifiant_PP = DSP.identifiant_pp
                	where ND.Identifiant_PP is null";
    
    if ($sql_result = mysqli_query($conn, $sql))
    {
        $Log->writeLog($region_id, "Fin création des lignes REMOVE", $process_id);
        $Log->writeLog($region_id, "Début création des lignes CREATE", $process_id);
        $sql = "INSERT INTO rpps_" . $region_code . "_tmp_identifiant_pp (identifiant_pp, sales_pro_id, process_id, histo_type)
                SELECT DISTINCT ND.Identifiant_PP, SP.sales_pro_id, $process_id, 'CREATE'
                from rpps_" . $region_code . "_new_data ND
                left outer join rpps_" . $region_code . "_sales_pro SP on 1=1 and SP.is_new = 1
                left outer join rpps_" . $region_code . "_current_data CD on CD.Identifiant_PP = ND.Identifiant_PP
                where CD.Identifiant_PP is null";
        if ($sql_result = mysqli_query($conn, $sql))
        {
            $Log->writeLog($region_id, "Fin création des lignes CREATE", $process_id);
            $Log->writeLog($region_id, "Début comptage des mouvements", $process_id);
            $sql = "SELECT histo_type, count(*) as nb_movement
                    FROM rpps_" . $region_code . "_tmp_identifiant_pp
                    WHERE process_id = $process_id
                    GROUP BY histo_type";
            if ($sql_result = mysqli_query($conn, $sql))
            {
                
                while ($line = mysqli_fetch_assoc($sql_result))
                {
                    $Log->writeLog($region_id, "Nb " . $line['histo_type'] . " : " . $line['nb_movement'], $process_id);
                    $data['detail_result'][] = $line;
                }
                if (count($data) > 0)
                {
                    $Log->writeLog($region_id, "Fin comptage des mouvements", $process_id);
                    $LocalProcess->nextStepLocalProcess($region_id, $process_id);
                    response(200, 'creation_remove_counts', $data);
                }
                else
                {
                    $Log->writeLog($region_id, "Fin comptage des mouvements", $process_id);
                    $LocalProcess->nextStepLocalProcess($region_id, $process_id);
                    response(200, 'no_data_in_counts', NULL);
                }
            }
            else
            {
                $Log->writeLog($region_id, "Erreur comptage des mouvements", $process_id);
                response(200, 'error_getting_counts_from_tmp_identifiant_pp', NULL);
            }
        }
        else
        {
            $Log->writeLog($region_id, "Erreur création des lignes CREATE", $process_id);
            response(200, 'error_creating_add_tmp_identifiant_pp', NULL);
        }    
    }
    else
    {
        $Log->writeLog($region_id, "Erreur création des lignes REMOVE", $process_id);
        response(200, 'error_creating_remove_tmp_identifiant_pp', NULL);
    }
}
else
{
    $msg='error';
    $sep='|';
    if (empty($region_token)) {
        $msg = $msg . $sep . 'empty_region_token';
    }
    if (empty($user_token)) {
        $msg = $msg . $sep . 'empty_user_token';
    }
    response(400, $msg, NULL);
}
?>