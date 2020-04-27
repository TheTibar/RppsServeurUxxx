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

    $Doctor = new Doctor();
    
    $result = $Doctor->getAllSpecialities();
    
    switch($result) {
        case 0:
            $data = $Doctor->__get('speciality_array');
            response(200, 'data_speciality', $data);
            break;
        case 1:
            response(200, 'no_speciality', $data);
            break;
        case -1:
            response(200, 'error_getting_speciality', $data);
            break;
    }
    
    
    

?>