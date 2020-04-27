<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

//https://shareurcodes.com/blog/creating%20a%20simple%20rest%20api%20in%20php

//http://phppot.com/php/php-restful-web-service/


include_once dirname(__FILE__) . '/../../Classes/Agency.php';
include_once dirname(__FILE__) . '/../../Classes/SalesPro.php';


use \Classes\Agency;

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

$agency_token = isset($_GET['agency_token']) ? $_GET['agency_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($agency_token) && ! empty($user_token))
{

    $Agency = new Agency();
    $result = $Agency->getAgencyIdByToken($agency_token, $user_token);
    
    
    switch($result) {
        case 0:
            $agency_id = $Agency->__get('agency_id');
            break;
        case -99:
            response(200, "fatal_error_agency_id", NULL);
            break;
        case -2:
            response(200, "get_agency_id_error", NULL);
            break;
    }
    

    $result = $Agency->getDoctorsByAgency($agency_id);
    
    switch($result) {
        case 0:
            $data['region_array'] = $Agency->__get('region_array');
            $data['region_doctors_array'] = $Agency->__get('region_doctors_array');
            $data['region_speciality'] = $Agency->__get('region_speciality');
            $data['region_sales_pro'] = $Agency->__get('region_sales_pro');

            response(200, "agency_doctors", $data);
            break;
        case 1:
            response(200, "no_region_agency", NULL);
            break;
        case -1:
            response(200, "get_agency_region_error", NULL);
            break;
        case -2:
            response(200, "get_agency_doctors_error", NULL);
            break;
        case -3:
            response(200, "get_agency_speciality_error", NULL);
            break;
        case -4:
            response(200, "get_agency_sales_pro_error", NULL);
            break;
    }
    
    
    
}
else
{
    $msg='error';
    $sep='|';
    if (empty($agency_token)) {
        $msg = $msg . $sep . 'empty_agency_token';
    }
    if (empty($user_token)) {
        $msg = $msg . $sep . 'empty_user_token';
    }
    response(200, $msg, NULL);
}