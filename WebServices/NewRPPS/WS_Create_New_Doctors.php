<?php
use Classes\Region;
use Classes\Doctor;

header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/Region.php';
include_once dirname(__FILE__) .  '/../../Classes/Doctor.php';


function response($status, $status_message, $data)
{
    header("HTTP/1.1 ".$status);
    //header("Content-Type:application/json;charset=utf-8", false);
    $response['status']=$status;
    $response['status_message']=$status_message;
    $response['data']=$data;
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    echo $json_response;
}

/*
 on ne supprime pas le contenu de la table tmp_identifiant_pp, ça peut servir d'historique des mouvements
 on sauvegarde les données identifiant_pp que l'on va devoir créer, liées au commercial "is_new"
 on écrit les données de new_data dans histo_data en type 'CREATE'
 on écrit les données de tmp_identifiant_pp dans histo_doctor_sales_pro_link en type 'CREATE'
 on écrit les données de histo_data dans current_data
 on écrit les données de histo_doctor_sales_pro_link dans doctor_sales_pro_link
 
 */

$process_id = isset($_GET['process_id']) ? $_GET['process_id'] : "";
$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($process_id) && ! empty($region_token) && ! empty($user_token))
{
    
    $Region = new Region();
    
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
    
    
    $Doctor = new Doctor();
    $result = $Doctor->createDoctorsByProcessId($process_id, $region_id);
    
    switch($result) {
        case 0:
            response(200, "new_doctors_creation_ok", NULL);
            break;
        case -3:
            response(200, "error_historizing_current_data", NULL);
            break;
        case -4:
            response(200, "error_historizing_histo_doctor_user", NULL);
            break;
        case -5:
            response(200, "error_creating_current_data", NULL);
            break;
        case -6:
            response(200, "error_creating_doctor_user", NULL);
            break;
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
    if (empty($process_id)) {
        $msg = $msg . $sep . 'empty_process_id';
    }
    response(400, $msg, NULL);
}


?>