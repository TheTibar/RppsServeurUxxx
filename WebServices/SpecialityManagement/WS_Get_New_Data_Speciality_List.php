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
$sql = "SELECT ND.Libelle_savoir_faire, COALESCE(PR.keep, 2) as keep 
        FROM rpps_971_new_data ND
        inner join rpps_971_profession_filter PR1 on PR1.label = ND.Libelle_profession and PR1.type = 'profession' and PR1.keep = 1
        left outer join rpps_971_profession_filter PR on PR.label = ND.Libelle_savoir_faire 
            and PR.type = 'specialite'
        group by ND.Libelle_savoir_faire
        order by ND.Libelle_savoir_faire";

if ($sql_result = mysqli_query($conn, $sql))
{
    while ($line = mysqli_fetch_assoc($sql_result))
    {
        $data[] = $line;
    }
    response(200, 'specialities_from_new_data_with_default_filter', $data);
}
else
{
    response(200, 'error_retrieving_data_from_new_data', NULL);
}

?>