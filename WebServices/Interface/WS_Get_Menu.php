<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/WebInterface.php';

use Classes\WebInterface;

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

$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($user_token))
{
    //sleep(3);
    $Interface = new WebInterface;
    //On récupère l'id région
    $result = $Interface->getMenu($user_token);
    
    switch ($result) {
        case 0:
            response(200, 'user_menu', $Interface->__get('menu'));
            break;
        case -1:
            response(200, 'unknown_user', NULL);
            break;
        case -2:
            response(200, 'get_user_role_error', NULL);
            break;
        case -3:
            response(200, 'no_user_menu', NULL);
            break;
        case -4:
            response(200, 'get_user_menu_error', NULL);
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
?>