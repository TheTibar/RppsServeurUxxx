<?php
header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';


function response($status, $status_message, $data)
{
    header("HTTP/1.1 ".$status);
    //header("Content-Type:application/json;charset=utf-8", false);
    $response['status']=$status;
    $response['status_message']=$status_message;
    $response['data']=$data;
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    echo $json_response;
}

$instance = \ConnectDB::getInstance();
$conn = $instance->getConnection();

$sql = "select count(distinct identifiant_pp) as row_number from rpps_971_new_data";


if ($sql_result = mysqli_query($conn, $sql))
{
    $data = mysqli_fetch_assoc($sql_result);
    response(200, 'row_number', $data);
}
else
{
    response(200, 'error_retrieving_row_number', NULL);
}

?>