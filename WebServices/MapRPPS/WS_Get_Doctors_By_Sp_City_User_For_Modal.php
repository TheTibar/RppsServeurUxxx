<?php

use Classes\Doctor;

header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
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


$city_id = isset($_GET['city_id']) ? $_GET['city_id'] : "";
$speciality = isset($_GET['speciality']) ? $_GET['speciality'] : "";
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : "";


if (! empty($city_id) && ! empty($speciality) && ! empty($user_id))
{


    $Doctor = new Doctor();
    
    $result = $Doctor->getDoctorsBySpecialityUserCity($speciality, $user_id, $city_id);
    
    switch($result) {
        case 0:
            $data = $Doctor->__get('doctors_by_sp_ci_user');
            response(200, 'data_doctors_sp_city_user', $data);
            break;
        case 1:
            response(200, 'no_data', NULL);
            break;
        case -1:
            response(200, 'error_getting_data_doctors_sp_city_user', NULL);
            break;
    }
}
else 
{
    $msg='error';
    $sep='|';
    if (empty($city_id)) {
        $msg = $msg . $sep . 'empty_city_id';
    }
    if (empty($speciality)) {
        $msg = $msg . $sep . 'empty_speciality';
    }
    if (empty($user_id)) {
        $msg = $msg . $sep . 'empty_user_id';
    }
    
    response(200, $msg, NULL);
}
    
    
    

?>