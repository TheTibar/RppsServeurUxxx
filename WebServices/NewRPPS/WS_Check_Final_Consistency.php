<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

require_once dirname(__FILE__) . '/../../Classes/RPPS.php';
require_once dirname(__FILE__) . '/../../Classes/Region.php';
use Classes\RPPS;
use Classes\Region;


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


$region_token = isset($_GET['region_token']) ? $_GET['region_token'] : "";
$user_token = isset($_GET['user_token']) ? $_GET['user_token'] : "";

if (! empty($region_token) && ! empty($user_token))
{
    $Region = new Region();
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
    
    $RPPS = new RPPS();
    $data = [];
    $result = $RPPS->checkFinalConsistency($region_id);
    
    switch($result) {
        case 0:
            $data['merge_summary_count_data'] = $RPPS->__get('merge_summary_count_data');
            $data['merge_summary_count_rep'] = $RPPS->__get('merge_summary_count_rep');
            
            response(200, "consistency_counts_ok", $data);
            break;
        case -1:
            response(200, "unknown_region_code", NULL); //proposer de relancer
            break;
        case -2:
            response(200, "get_region_code_error", NULL); //proposer de relancer
            break;
        case -3:
            response(200, "current_data_count_error", NULL); //proposer de relancer
            break;
        case -4:
            response(200, "new_data_count_error", NULL); //proposer de relancer
            break;
        case -5:
            response(200, "consistency_counts_error", NULL); //envoyer un message
            break;
    }
    
    
    
    
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
    response(400, $msg, NULL);
}

?>