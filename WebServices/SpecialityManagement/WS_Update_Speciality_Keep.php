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

if (! empty($speciality) && ($keep == 0 || $keep == 1))
{
    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    /*3 cas : 
    1 : on passe de vrai à faux
    2 : on passe de faux à vrai
    3 : on crée une nouvelle profession avec sa valeur    
    */
    
    $sql = "SELECT count(*) as exists_speciality 
            FROM rpps_971_profession_filter PR 
            WHERE PR.label = '$speciality'
            AND PR.type = 'specialite'";
    
    
    
    if ($sql_result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($sql_result);
        
        $exists_speciality = intval($data['exists_speciality']);

        if ($exists_speciality > 0)
        {
            //on update
            $sql = "UPDATE profession_filter
            SET keep = $keep
            WHERE label = '$speciality'
            AND type = 'specialite'";
            if (mysqli_query($conn, $sql))
            {
                $data_return['speciality'] = $speciality;
                $data_return['keep'] = $keep;
                response(200, 'update_speciality_keep_ok', $data_return);
                
            }
            else
            {
                response(200, 'error_update_speciality_keep', $sql);
            }
        }
        else 
        {
            $sql = "INSERT INTO profession_filter
            (type, label, keep)
            VALUES ('specialite', '$speciality', $keep)"; 

            if (mysqli_query($conn, $sql))
            {
                $data_return['speciality'] = $speciality;
                $data_return['keep'] = $keep;
                response(200, 'create_speciality_keep_ok', $data_return);
                
            }
            else
            {
                response(200, 'error_create_speciality_keep', $sql);
            }
        }
        
    }
    else
    {
        response(200, 'error_retrieving_speciality_keep', $sql);
    }
}
else
{
    response(200, 'error_updating_keep_no_data', NULL);
}



?>