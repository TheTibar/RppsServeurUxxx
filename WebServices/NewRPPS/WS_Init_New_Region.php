<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

//https://shareurcodes.com/blog/creating%20a%20simple%20rest%20api%20in%20php

//http://phppot.com/php/php-restful-web-service/

require_once dirname(__FILE__) . '/../../Classes/RPPS.php';
require_once dirname(__FILE__) . '/../../Classes/LocalProcess.php';
require_once dirname(__FILE__) . '/../../Classes/Region.php';
require_once dirname(__FILE__) . '/../../Classes/LocalLog.php';

use Classes\RPPS;
use Classes\LocalProcess;
use Classes\Region;
use Classes\LocalLog;

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

$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($region_token) && ! empty($user_token))
{
    $data_result = [];
    $RPPS = new RPPS();
    $LocalProcess = new LocalProcess();
    $Region = new Region();
    $Log = new LocalLog();
    

    
    //On récupère l'id région
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
    
    
    //On crée la table Log locale si besoin
    $result = $Log->existsLocalLogTable($region_id);
    
    switch($result) {
        case 0:
            $data_result['0']['table_name'] = 'log';
            $data_result['0']['check'] = 'absent';
            $data_result['0']['result'] = 'created';
            break;
        case 1:
            $data_result['0']['table_name'] = 'log';
            $data_result['0']['check'] = 'present';
            $data_result['0']['result'] = 'existed';
            break;
        case -5:
            $data_result['0']['table_name'] = 'log';
            $data_result['0']['check'] = 'absent';
            $data_result['0']['result'] = 'error';
            response(200, "table_creation_error", $data_result); //proposer de relancer
            break;
        case -1:
            response(200, "unknown_region_code", NULL); //proposer de relancer
            break;
        case -2:
            response(200, "get_region_code_error", NULL); //proposer de relancer
            break;
        case -3:
            response(200, "unknown_param_db_schema", NULL); //proposer de relancer
            break;
        case -4:
            response(200, "get_param_db_schema_error", NULL); //proposer de relancer
            break;
        case -6:
            response(200, "check_exists_table_error", NULL); //proposer de relancer
            break;
    }
    
    
    $result = $LocalProcess->existsLocalProcessTable($region_id);
    
    switch($result) {
        case 0:
            $data_result['1']['table_name'] = 'process';
            $data_result['1']['check'] = 'absent';
            $data_result['1']['result'] = 'created';
            break;
        case 1: 
            $data_result['1']['table_name'] = 'process';
            $data_result['1']['check'] = 'present';
            $data_result['1']['result'] = 'existed';
            break;
        case -5:
            $data_result['1']['table_name'] = 'process';
            $data_result['1']['check'] = 'absent';
            $data_result['1']['result'] = 'error';
            response(200, "table_creation_error", $data_result); //proposer de relancer
            break;
        case -1:
            response(200, "unknown_region_code", NULL); //proposer de relancer
            break;
        case -2:
            response(200, "get_region_code_error", NULL); //proposer de relancer
            break;
        case -3:
            response(200, "unknown_param_db_schema", NULL); //proposer de relancer
            break;
        case -5:
            response(200, "get_param_db_schema_error", NULL); //proposer de relancer
            break;
        case -6:
            response(200, "check_exists_table_error", NULL); //proposer de relancer
            break;
    }
    
    
    $result = $LocalProcess->newLocalProcess($region_id, "INIT", 0);
    
    switch($result) {
        case 0:
            $process_id = $LocalProcess->__get('process_id');
            break;
        case -1:
            response(200, "unknown_region_code", NULL);
            break;
        case -2:
            response(200, "get_region_code_error", NULL);
            break;
        case -3:
            response(200, "create_process_error", NULL);
            break;
    }
    
    $Log->writeLog($region_id, "Début Initialisation région", $process_id);
    
    $result = $RPPS->initRegion($region_id, $process_id);
    switch($result) {
        case 0:
            $data_init = $RPPS->__get('creation_result');
            break;
        case -1:
            response(200, "unknown_region_code", NULL);
            break;
        case -2:
            response(200, "get_region_code_error", NULL);
            break;
        case -3:
            response(200, "unknown_param_Itables", NULL);
            break;
        case -4:
            response(200, "get_param_Itables_error", NULL);
            break;
        case -5:
            response(200, "unknown_param_db_schema", NULL);
            break;
        case -6:
            response(200, "get_param_db_schema_error", NULL);
            break;
        case -7:
            $data_init = $RPPS->__get('creation_result');
            $data_result = array_merge($data_result, $data_init);
            response(200, "table_creation_error", $data_result);
            break;
        case -8:
            response(200, "check_region_structure_error", NULL);
            break;
        case -9:
            response(200, "default_sales_force_creation_error", NULL);
            break;
        case -10:
            response(200, "non_supported_sales_force_creation_error", NULL);
            break;
        case -11:
            response(200, "default_link_type_creation_error", NULL);
            break;
    }
    
    $data_result = array_merge($data_result, $data_init);
    

    $Log->writeLog($region_id, "Début passage process en final", $process_id);
    
    $result = $LocalProcess->finalStepLocalProcess($region_id, $process_id);
    
    switch($result) {
        case 0:
            $Log->writeLog($region_id, "Fin passage process en final", $process_id);
            break;
        case -1:
            response(200, "unknown_region_code", NULL);
            break;
        case -2:
            response(200, "get_region_code_error", NULL);
            break;
        case -3:
            response(200, "update_process_error", NULL);
            break;
    }
    
    $Log->writeLog($region_id, "Fin Initialisation région", $process_id);
    
    response(200, "new_region_created", $data_result);
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
    response(200, $msg, NULL);
}
?>