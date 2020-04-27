<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/Region.php';
include_once dirname(__FILE__) .  '/../../Classes/SalesPro.php';
include_once dirname(__FILE__) .  '/../../Classes/Doctor.php';
include_once dirname(__FILE__) .  '/../../Classes/User.php';

use Classes\Region;
use Classes\SalesPro;
use Classes\Doctor;
use Classes\User;

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

$sales_pro_token = isset($_GET['sales_pro_token']) ? $_GET['sales_pro_token'] : ""; //correspond au user_token 
$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";
$process_id = isset($_GET['process_id']) ? $_GET['process_id'] : "";

if (! empty($sales_pro_token) && ! empty($region_token) && ! empty($user_token) && ! empty($process_id))
{
    
    $Region = new Region();
    $SalesPro = new SalesPro();
    $User = new User();
    
    $result = $Region->getRegionIdByToken($region_token, $user_token);
    
    //On récupère region_id
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
    
    //On récupère le sales_pro_id
    $result = $User->getUserId($sales_pro_token);
    
    switch($result) {
        case 0:
            //response(200, "region_tables_ok", NULL);
            $user_id = $User->__get('user_id');
            break;
        case -1:
            response(200, "unknown_user", NULL); //proposer de relancer
            break;
        case -2:
            response(200, "get_user_id_error", NULL); //proposer de relancer
            break;
    }
    
    $Doctor = new Doctor();
    
    $result = $Doctor->getLeavingDoctorsDetailByUserId($user_id, $process_id, $region_id);
    
    switch($result) {
        case 0:
            $data = $Doctor->__get('remove_doctors_detail_array');
            response(200, 'leaving_doctors_detail_by_sales_pro', $data);
            break;
        case 1:
            response(200, 'leaving_doctors_detail_by_sales_pro_already_deleted', NULL);
            break;
        case -3:
            response(200, "error_retrieving_data", NULL); //proposer de relancer
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
    if (empty($sales_pro_token)) {
        $msg = $msg . $sep . 'empty_sales_pro_token';
    }
    if (empty($process_id)) {
        $msg = $msg . $sep . 'empty_process_id';
    }
    response(200, $msg, NULL);
}



?>