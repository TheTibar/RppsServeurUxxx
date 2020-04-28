<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

//https://shareurcodes.com/blog/creating%20a%20simple%20rest%20api%20in%20php

//http://phppot.com/php/php-restful-web-service/


include_once dirname(__FILE__) . '/../../Classes/User.php';
include_once dirname(__FILE__) . '/../../Classes/Region.php';
include_once dirname(__FILE__) . '/../../Classes/Process.php';


use \Classes\User;
use \Classes\Region;
use \Classes\Process;

function response($status, $status_message, $data)
{
    header("HTTP/1.1 ".$status);
    header('content-type:application/json');
    //header("Content-Type:application/json;charset=utf-8", false);
    $response['status']=$status;
    $response['status_message']=$status_message;
    $response['data']=$data;
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    echo $json_response;
    die();
}

$region_id = isset($_GET['region_id']) ? $_GET['region_id'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";
$dspl = isset($_GET['dspl']) ? $_GET['dspl'] : "";

if (! empty($region_id) && ! empty($user_token) && ! empty($dspl))
{


    $User = new User();
    
    $result = $User->getUserId($user_token);
    
    switch($result) {
        case 0:
            $user_id = $User->__get('user_id');
            break;
        case -1:
            response(200, "unknown_user", NULL);
            break;
        case -2:
            response(200, "get_user_id_error", NULL);
            break;
    }
    
    $Process = new Process();
    
    $result = $Process->newProcess($region_id, "UPDATE DOCTOR USER", 0);
    switch($result) {
        case 0:
            $process_id = $Process->__get('process_id');
            break;
        case -1:
            response(200, "create_process_error", NULL);
            break;
    }
    
    //var_dump($dspl);
    $dspl = json_decode($dspl, true);
    //var_dump($dspl);
    
    //var_dump(array_column($dspl,'doc_identifiant'));
    
    //var_dump($array = implode(',', array_column($dspl,'doc_identifiant')));
    
    $Region = new Region();
    
    $result = $Region->updateDoctorUser($region_id, $dspl, $user_id, $process_id);
    
    switch($result) {
        case 0:
            //$end_process = 1;
            //response(200, "update_complete", NULL);
            break;
        case -3:
            response(200, "error_deletting_old_links_rollback", NULL);
            break;
        case -6:
            response(200, "unknown_sales_pro", NULL);
            break;
        case -7:
            response(200, "get_sales_pro_id_error", NULL);
            break;
        case -8:
            response(200, "error_inserting_new_links_rollback", NULL);
            break;
    }
    
    
    $result = $Process->finalStep($process_id);
    
    switch($result) {
        case 0:
            response(200, "update_complete", NULL);
            break;
        case -1:
            response(200, "final_process_step_ko", NULL);
            break;
    }
    
    //$last_names = array_column($a, 'last_name');
}
else
{
    $msg='error';
    $sep='|';
    if (empty($region_id)) {
        $msg = $msg . $sep . 'empty_region_id';
    }
    if (empty($user_token)) {
        $msg = $msg . $sep . 'empty_user_token';
    }
    if (empty($dspl)) {
        $msg = $msg . $sep . 'empty_doctor_sales_pro_link';
    }
    response(200, $msg, NULL);
}