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


//Renvoie la liste des libellés, avec la valeur de leur filtre "keep" :
// 0 : on ne garde pas
// 1 : on garde
// 2 : nouveau
$sql = "SELECT distinct ND.Code_postal_coord_structure_ as unknown_zipcode
        FROM rpps_971_new_data ND
        WHERE ND.Code_commune_coord_structure_ = '' or Code_commune_coord_structure_ is null        
";

if ($sql_result = mysqli_query($conn, $sql))
{
    while ($line = mysqli_fetch_assoc($sql_result))
    {
        $data[] = $line;
    }
    response(200, 'insee_code_error_zipcode_list', $data); 
}
else
{
    response(200, 'error_retrieving_data_from_new_data', NULL);
}

?>