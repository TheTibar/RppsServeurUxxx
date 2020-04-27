<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

//https://shareurcodes.com/blog/creating%20a%20simple%20rest%20api%20in%20php

//http://phppot.com/php/php-restful-web-service/

require_once dirname(__FILE__) . '/../../Classes/User.php';
require_once dirname(__FILE__) . '/../../Classes/Agency.php';
use Classes\User;
use Classes\Agency;

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


$new_user_data = isset($_GET['new_user_data']) ? $_GET['new_user_data'] : "";

if (! empty($new_user_data))
{
    
    $data = json_decode($new_user_data, TRUE);
    
  
    
    $email = $data["email"];
    $last_name = $data["last_name"];
    $first_name = $data["first_name"];
    $role_id = $data["role_id"];
    $user_token_creation = $data["user_token"];
    $region_array = $data["new_sp_region"];
    $agency_token = $data["agency_token"];
    $color = $data["color"];
    
    $UserCreation = new User();
    $result = $UserCreation->getUserId($user_token_creation);
    switch($result) {
        case 0: //utilisateur déja existant
            $user_id_creation = $UserCreation->__get('user_id');
            break;
        case -1: //utilisateur n'existe pas
            response(200, "user_creation_unknown", NULL);
            break;
        case -2: //impossible de vérifier l'existance de l'utilisateur, on provoque une erreur et on propose de relancer
            response(200, "get_user_creation_id_error", NULL);
            break;
    }
    
    $Agency = new Agency();
    $result = $Agency->getAgencyIdByToken($agency_token, $user_token_creation);
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
    
    $result = $Agency->getMaxDisplayOrder($agency_id);
    switch($result) {
        case 0: 
            $display_order = $Agency->__get('max_display_order');
            $display_order = $display_order + 1;
            break;
        case -1: 
            response(200, "get_max_display_order_error", NULL);
            break;
    }
    
    //echo(nl2br("user_creation_id : " . $user_id_creation . "\n"));
    //echo(nl2br("agency_id : " . $agency_id . "\n"));
    

    $User = new User();

    //On vérifie si l'utilisateur existe, si oui, on met juste à jour ses données, si non, on le crée entièrement
    
    $result = $User->existsUser($email);
    //echo($result);
    
    switch($result) {
        case 0: //utilisateur déja existant
            $user_id = $User->__get('user_id');
            $create = FALSE;
            break;
        case -1: //utilisateur n'existe pas
            $create = TRUE;
            break;
        case -2: //impossible de vérifier l'existance de l'utilisateur, on provoque une erreur et on propose de relancer
            response(200, "check_user_exists_error", NULL);
            break;
    }
    
    if($create)
    {
        //c'est une création
        $result = $User->createUser($email, $first_name, $last_name, $role_id, $user_id_creation, $region_array, $agency_id, $color, $display_order);
    }
    else
    {
        //c'est une mise à jour
        $result = $User->updateUser($user_id, $first_name, $last_name, $role_id, $user_id_creation, $region_array, $agency_id, $color);
    }
    
    //echo($result);
    
    if($create)
    {
        switch($result) {
            case 0:
                response(200, "create_user_completed", NULL);
                break;
            case -1:
                response(200, "create_process_id_error", NULL);
                break;
            case -2:
                response(200, "create_user_error", NULL);
                break;
            case -3:
                response(200, "create_user_agency_link_error", NULL);
                break;
            case -4:
                response(200, "create_user_region_link_error", NULL);
                break;
        }
    }
    else
    {
        switch($result) {
            
        }
    }
}
else
{
    $msg='error';
    $sep='|';
    if (empty($login)) {
        $msg = $msg . $sep . 'empty_new_user';
    }
    response(200,$msg, NULL);
}