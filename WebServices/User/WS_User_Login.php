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


$email = isset($_GET['email']) ? $_GET['email'] : "";
$password = isset($_GET['password']) ? $_GET['password'] : "";

if (! empty($email) && ! empty($password))
{
    $User = new User();
    $result = $User->userLogin($email, $password);
    
    switch($result) {
        case 0:
            $data['token'] = $User->__get('token');
            $data['first_login'] = $User->__get('first_login');
            response(200, "user_connected", $data);
            break;
        case -1:
            response(200, "login_error", NULL);
            break;
        case -2:
            response(200, "database_error", NULL);
            break;
    }
    
}
else
{
    $msg='error';
    $sep='|';
    if (empty($login)) {
        $msg = $msg . $sep . 'empty_login';
    }
    if (empty($password)) {
        $msg = $msg . $sep . 'empty_password';
    }
    response(400,$msg, NULL);
}