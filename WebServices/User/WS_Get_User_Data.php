<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

//https://shareurcodes.com/blog/creating%20a%20simple%20rest%20api%20in%20php

//http://phppot.com/php/php-restful-web-service/

require_once dirname(__FILE__) . '/../../Classes/User.php';
use Classes\User;

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
}

$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($user_token))
{
    $User = new User();
    $result = $User->getUserData($user_token);
    
    switch($result) {
        case 0:
            $data = $User->export();
            response(200, "user_data", $data);
            break;
        case -1:
            response(200, "unknown_token", NULL);
            break;
        case -2:
            response(200, "database_error", NULL);
            break;
        case -3:
            response(200, "get_user_region_error", NULL);
            break;
        case -4:
            response(200, "get_user_agency_error", NULL);
            break;
        case -5:
            response(200, "get_user_can_create_roles_error", NULL);
            break;
    }
    
}
else
{
    $msg='error';
    $sep='|';
    if (empty($user_token)) {
        $msg = $msg . $sep . 'empty_user_token';
    }
    response(200, $msg, NULL);
}