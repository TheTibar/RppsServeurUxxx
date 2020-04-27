<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/Region.php';
include_once dirname(__FILE__) .  '/../../Classes/Doctor.php';

use Classes\Doctor;
use Classes\Region;

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


$profession_id = isset($_GET['profession_id']) ? $_GET['profession_id'] : "";
$process_id = isset($_GET['process_id']) ? $_GET['process_id'] : "";
$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($profession_id) && ! empty($process_id) && ! empty($region_token) && ! empty($user_token))
{
    //sleep(4);
    
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
    
    $Doctor = new Doctor();
    $result = $Doctor->getArrivingDoctorDetailBySpeciality($process_id, $profession_id, $region_id);
    
    switch($result) {
        case 0:
            $data = $Doctor->__get('create_doctors_detail_array');
            response(200, "new_doctor_speciality_detail", $data);
            break;
        case 1:
            response(200, 'no_new_doctor', NULL);
            break;
        case -3:
            response(200, 'error_retrieving_data_from_new_data', NULL);
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
    if (empty($profession_id)) {
        $msg = $msg . $sep . 'empty_profession_id';
    }
    if (empty($process_id)) {
        $msg = $msg . $sep . 'empty_process_id';
    }
    response(200, $msg, NULL);
}

?>