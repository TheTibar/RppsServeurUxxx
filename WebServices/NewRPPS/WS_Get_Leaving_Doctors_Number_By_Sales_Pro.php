<?php
use Classes\Region;
use Classes\Doctor;

header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/Region.php';
include_once dirname(__FILE__) .  '/../../Classes/SalesPro.php';
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

$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";
$process_id = isset($_GET['process_id']) ? $_GET['process_id'] : "";

if (! empty($region_token) && ! empty($user_token) && ! empty($process_id))
{
    
    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    $Region = new Region();
    
    $region_token = mysqli_real_escape_string($conn, $region_token);
    $user_token = mysqli_real_escape_string($conn, $user_token);
    $process_id = mysqli_real_escape_string($conn, $process_id);
    
    
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
    
    $result = $Doctor->getLeavingDoctors($process_id, $region_id);
    switch($result) {
        case 0:
            response(200, 'leaving_doctors_number_by_sales_pro', $Doctor->__get('remove_doctors_array'));
            break;
        case 1:
            response(200, 'no_more_leaving_doctors', NULL);
            break;
        case -3:
            response(200, 'error_retrieving_leaving_doctors', NULL);
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