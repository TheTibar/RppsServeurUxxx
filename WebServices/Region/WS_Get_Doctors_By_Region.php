<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

//https://shareurcodes.com/blog/creating%20a%20simple%20rest%20api%20in%20php

//http://phppot.com/php/php-restful-web-service/


include_once dirname(__FILE__) . '/../../Classes/User.php';
include_once dirname(__FILE__) . '/../../Classes/Region.php';
include_once dirname(__FILE__) . '/../../Classes/LocalProcess.php';


use \Classes\Region;

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


if (! empty($region_id))
{

    
    $Region = new Region();
    
    $result = $Region->getRegionDoctors($region_id);
    
    switch($result) {
        case 0:
            $data = $Region->__get('region_doctors_array');
            response(200, "region_doctors", $data);
            break;
        case -1:
            response(200, "unknown_region_code", NULL);
            break;
        case -2:
            response(200, "get_region_code_error", NULL);
            break;
        case -3:
            response(200, "get_region_doctors_error", NULL);
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
    response(200, $msg, NULL);
}