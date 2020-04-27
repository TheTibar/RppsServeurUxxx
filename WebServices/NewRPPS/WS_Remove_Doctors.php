<?php
use Classes\Process;
use Classes\Log;
use Classes\Region;
use Classes\Doctor;

header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/Process.php';
include_once dirname(__FILE__) .  '/../../Classes/Log.php';
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
    die();
}

$Process = new Process();
$Log = new Log();
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
            $region_id = $Region->__get('region_id');
            break;
        case -99:
            response(200, "fatal_error_region_code", NULL); //proposer de relancer
            break;
        case -2:
            response(200, "get_region_code_error", NULL); //proposer de relancer
            break;
    }
    


    
    //On crée un processus
    $result = $Process->newProcess($region_id, "REMOVE DOCTORS", 0);
    switch($result) {
        case 0:
            $process_id = $Process->__get('process_id');
            break;
        case -1:
            response(200, "create_process_error", NULL);
            break;
    }
    
    $data = [];
    $data['process_id'] = $process_id;
    
    $Doctor = new Doctor(); 
    //$SalesPro = new SalesPro();
    
    $result = $Doctor->createRemoveDoctors($process_id, $region_id);
    switch($result) {
        case 0:
            $data['nb_remove'] = $Doctor->__get('nb_remove');
            $resp = 0;
            break;
        case 1:
            $resp = 1;
            break;
        case -3:
            response(200, "create_remove_data_error", NULL);
            break;
        case -4:
            response(200, "count_remove_data_error", NULL);
            break;
    }
    
    $result = $Process->finalStep($process_id);
    switch($result) {
        case 0:
            if ($resp == 0)
            {
                response(200, "remove_count", $data);
            }
            else 
            {
                response(200, "no_remove", NULL);
            }
            
            break;
        case -1:
            response(200, "process_final_step_error", NULL);
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