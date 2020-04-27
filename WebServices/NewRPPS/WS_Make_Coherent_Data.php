<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/Region.php';
include_once dirname(__FILE__) .  '/../../Classes/Doctor.php';
include_once dirname(__FILE__) .  '/../../Classes/Process.php';

use Classes\Region;
use Classes\Doctor;
use Classes\Process;


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
    
    $result = $Process->newProcess($region_id, "DETAILS DOCTORS", 0);
    
    switch($result) {
        case 0:
            $process_id = $Process->__get('process_id');
            break;
        case -1:
            response(200, "create_process_error", NULL);
            break;
    }
    
    //echo($process_id);
    
    
    
    
    $Doctor = new Doctor();
    
    $result = $Doctor->makeDetailsDoctorsConsistent($process_id, $region_id);
    
    switch($result) {
        case 0:
            $data = $Doctor->__get('movement_summary_array');
            $resp = 0;
            break;
        case 1:
            $resp = 1;
            break;
        case -3:
            response(200, "error_historizing_old_data_detail", NULL);
            break;
        case -4:
            response(200, "error_deleting_current_data_detail", NULL);
            break;
        case -5:
            response(200, "error_historizing_new_data_detail", NULL);
            break;
        case -6:
            response(200, "error_creating_new_data_detail", NULL);
            break;
        case -7:
            response(200, "error_getting_counts_from_tmp_identifiant_pp", NULL);
            break;
    }
    
    //echo($process_id);
    
    $result = $Process->finalStep($process_id);
    switch($result) {
        case 0:
            if ($resp == 0)
            {
                response(200, "updates_counts", $data);
            }
            else 
            {
                response(200, "no_data_in_counts", NULL);
            }
            break;
        case -1:
            response(200, "unknown_region_code", NULL);
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
