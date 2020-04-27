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

$speciality = isset($_GET['speciality']) ? $_GET['speciality'] : "";
$keep = isset($_GET['keep']) ? $_GET['keep'] : "";


    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    
//on update les code_communes manquants à partir de la table CP_CI
$sql = "update rpps_971_new_data ND 
        set Code_commune_coord_structure_ = (
            select CP.code_insee 
            from rpps_971_cp_ci CP 
            where CP.code_postal = ND.Code_postal_coord_structure_
        )";
if (mysqli_query($conn, $sql))
{
    response(200, 'update_insee_code_ok', NULL); 
}
else
{
    response(200, 'error_update_insee_code', $sql);
}




?>
