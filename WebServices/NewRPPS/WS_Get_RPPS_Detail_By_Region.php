<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

//https://shareurcodes.com/blog/creating%20a%20simple%20rest%20api%20in%20php

//http://phppot.com/php/php-restful-web-service/

require_once dirname(__FILE__) . '/../../Classes/RPPS.php';
require_once dirname(__FILE__) . '/../../Classes/Region.php';
require_once dirname(__FILE__) . '/../../Classes/Process.php';
use Classes\RPPS;
use Classes\Region;
use Classes\Process;

function response($status, $status_message, $data)
{
    header("HTTP/1.1 ".$status);
    header('content-type:application/json');
    //header("Content-Type:application/json;charset=utf-8", false);
    $response['status']=$status;
    $response['status_message']=$status_message;
    $response['data']=$data;
    /*pour moker le WS
    $response['status']=200;
    $response['status_message']="get_current_line_count_error";
    $response['data']=NULL;
    fin moke*/
    
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    echo $json_response;
    die();
}

$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($region_token) && ! empty($user_token))
{

    //sleep(3);
    //On récupère l'id région
    $Region = new Region();
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
    $result = $Process->newProcess($region_id, "GET RPPS DETAILS", 0);
    
    switch($result) {
        case 0:
            $process_id = $Process->__get('process_id');
            break;
        case -1:
            response(200, "create_process_error", NULL);
            break;
    }
    
    
    $structureOk = 0;
    
    $RPPS = new RPPS();

    $result = $RPPS->compareRPPS($region_id);
    
    switch($result) {
        case 0:
            $data = $RPPS->export();
            break;
        case -1:
            response(200, "unknown_region_code", NULL); //proposer de relancer
            break;
        case -2:
            response(200, "get_region_code_error", NULL); //proposer de relancer
            break;
        case -3:
            response(200, "get_current_line_count_error", NULL); //proposer de relancer
            break;
        case -4:
            response(200, "get_new_data_line_count_error", NULL); //erreur envoi email
            break;
        case -5:
            response(200, "get_current_distinct_count_error", NULL); //proposer de relancer
            break;
        case -6:
            response(200, "get_new_data_distinct_count_error", NULL); //erreur envoi email
            break;
        case -7:
            response(200, "get_current_last_update_error", NULL); //proposer de relancer
            break;
        case -8:
            response(200, "get_new_data_last_update_error", NULL); //erreur envoi email
            break;
    }
    
    $result = $Process->finalStep($process_id);
    switch($result) {
        case 0:
            response(200, "RPPS_overview_data", $data);
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