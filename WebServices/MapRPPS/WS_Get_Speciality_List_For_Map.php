<?php

use Classes\Region;

header("Content-Type:application/json;charset=utf-8", false);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, Autorization, X-Auth-Token');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');

include_once dirname(__FILE__) .  '/../../Classes/db_connect.php';
include_once dirname(__FILE__) .  '/../../Classes/Region.php';


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

$region_map = isset($_GET['region_map']) ? $_GET['region_map'] : "";

if (! empty($region_map))
{
    $data_sp = [];
    
    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    
    $Region = new Region();
    
    $region_map = json_decode($region_map, true);
    
    
    
    for ($i = 0; $i < count($region_map); $i++)
    {
        $data = [];
        
        $result = $Region->getRegionCode($region_map[$i]);
        $region_code = $Region->__get('code');
        //echo($region_code);
        
        $sql = "SELECT label as label
                FROM rpps_" . $region_code . "_profession_filter";
        
        //echo(nl2br($sql . "\n"));
        
        if ($sql_result = mysqli_query($conn, $sql))
        {
            
            while ($line = mysqli_fetch_assoc($sql_result))
            {
                $data[] = $line;
            }
            //var_dump($data_xy);
            //On valide les données pour envoyer la bonne réponse au client et limiter les tests côté JS
            if(count($data) > 0)
            {
                //var_dump($data);
                //$data_sp['speciality'][$region_map[$i]] = $data;
                $data_sp = array_merge($data_sp, $data);
                
            }
        }
        else
        {
            response(200, 'error_getting_speciality_data_for_map', NULL);
        }
    }
    
    //var_dump($data_sp);
    $data_fin = [];
    
    for($i = 0; $i < count($data_sp); $i++)
    {
        $label = $data_sp[$i]['label'];
        $data_fin[$i] = $label;
    }
    $data_fin = array_unique($data_fin);
    
    //var_dump($data_fin);
    
    //$data_fin = mysqli_real_escape_string($conn, $data_fin); 
    
    //var_dump($data_fin);
    
    response(200, 'data_speciality', $data_fin);
}
else
{
    $msg='error';
    $sep='|';
    if (empty($region_map)) {
        $msg = $msg . $sep . 'empty_region_map';
    }
    response(200, $msg, NULL);
}



?>