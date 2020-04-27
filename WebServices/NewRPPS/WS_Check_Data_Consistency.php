<?php



header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/Region.php';
include_once dirname(__FILE__) .  '/../../Classes/Process.php';
include_once dirname(__FILE__) .  '/../../Classes/RPPS.php';
use Classes\Region;
use Classes\Process;
use Classes\RPPS;

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

$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($region_token) && ! empty($user_token))
{
    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    //sleep(5);
    
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
    
    $Process = new Process();
    $result = $Process->newProcess($region_id, "CHECK DATA CONSISTENCY", 0);
    
    switch($result) {
        case 0:
            $process_id = $Process->__get('process_id');
            break;
        case -1:
            response(200, "create_process_error", NULL);
            break;
    }
    
    $RPPS = new RPPS();
    
    $result = $RPPS->checkDataConsistency($region_id, $process_id);
    
    switch($result) {
        case 0:
            $data = $RPPS->__get('check_data_consistency');
            break;
        case -3:
            response(200, "error_counting_distinct_CD", NULL);
            break;
        case -4:
            response(200, "error_counting_distinct_DSP", NULL);
            break;
        case -5:
            response(200, "error_counting_DSP", NULL);
            break;
        case -6:
            response(200, "error_counting_Nb_CD_not_in_DSP", NULL);
            break;
        case -7:
            response(200, "error_counting_Nb_DSP_not_in_CD", NULL);
            break;
        case -8:
            $data = $RPPS->__get('check_data_consistency');
            response(200, "data_base_not_consistent_CD_DSP", $data);
            break;
        case -9:
            $data = $RPPS->__get('check_data_consistency');
            response(200, "data_base_not_consistent_counts", $data);
            break;
    }
    
    $result = $Process->finalStep($process_id);
    switch($result) {
        case 0:
            response(200, "all_data_ok", $data);
            break;
        case -1:
            response(200, "final_process_step_ko", NULL);
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
    response(200, $msg, NULL);
}




?>