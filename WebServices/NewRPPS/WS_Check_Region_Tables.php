<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

//https://shareurcodes.com/blog/creating%20a%20simple%20rest%20api%20in%20php

//http://phppot.com/php/php-restful-web-service/

require_once dirname(__FILE__) . '/../../Classes/RPPS.php';


use Classes\RPPS;


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

$region_id = isset($_GET['region_id']) ? $_GET['region_id'] : "";

if (! empty($region_id))
{
    $RPPS = new RPPS();
    $result = $RPPS->checkRegionTables($region_id);
    
    switch($result) {
        case 0:
            response(200, "region_tables_ok", NULL);
            break;
        case -1:
            response(200, "unknown_region_code", NULL);
            break;
        case -2:
            response(200, "get_region_code_error", NULL);
            break;
        case -3:
            response(200, "unknown_param", NULL);
            break;
        case -4:
            response(200, "get_param_error", NULL);
            break;
        case -5:
            response(200, "incomplete_region_tables", NULL);
            break;
        case -6:
            response(200, "check_exists_table_error", NULL);
            break;
    }
    
}
else
{
    $msg='error';
    $sep='|';
    if (empty($region_id)) {
        $msg = $msg . $sep . 'empty_region_id';
    }
    response(400, $msg, NULL);
}
?>