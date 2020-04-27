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



//On construit les comptages et on les met dans $data
//Idée : construire un geojson compatible avec http://bl.ocks.org/gisminister/10001728


//Voir si on peut remplacer les données X, Y par les polygones : 
// https://public.opendatasoft.com/explore/dataset/correspondance-code-insee-code-postal/table/


$data_xy = [];
$data_color = [];

$sql = "SELECT SP.display_order as sp_id, SP.first_name as first_name, SP.name as name, CD.Libelle_commune_coord_structure_ as commune, 'Point' as type, GD.x as x, GD.y as y
        FROM rpps_971_sales_pro SP
        INNER JOIN rpps_971_doctor_sales_pro_link DSP on DSP.sales_pro_id = SP.sales_pro_id
        INNER JOIN rpps_971_current_data CD on CD.Identifiant_PP = DSP.identifiant_pp
        INNER JOIN rpps_mstr_geo_data GD on GD.code_insee = CD.Code_commune_coord_structure_
        ORDER BY SP.display_order";

if ($sql_result = mysqli_query($conn, $sql))
{
    
    while ($line = mysqli_fetch_assoc($sql_result))
    {
        $data_xy[] = $line;
    }
    //var_dump($data);
    //On valide les données pour envoyer la bonne réponse au client et limiter les tests côté JS
    if(count($data_xy) > 0)
    {
        /*
        $sql = "SELECT DRV.sp_id, DRV.first_name as first_name, DRV.name as name, CA.html_code as color_code
                FROM (
                    SELECT @tmp:=@tmp+1 as rowid, sp.sales_pro_id as sp_id, sp.first_name as first_name, sp.name as name, sales_pro_id
                    FROM (SELECT @tmp:=0) z, rpps_971_sales_pro sp
                    ORDER BY sp.sales_pro_id
                ) DRV
                INNER JOIN rpps_971_color_array CA on CA.display_order = DRV.rowid";
        */
        $sql = "SELECT SP.display_order as sp_id, SP.first_name as first_name, SP.name as name, SP.display_order as display_order, SP.color as color_code
                FROM rpps_971_sales_pro SP
                ORDER BY SP.display_order";    
        if ($sql_result = mysqli_query($conn, $sql))
        {
            while ($line = mysqli_fetch_assoc($sql_result))
            {
                $data_color[] = $line;
            }
            if(count($data_color) > 0)
            {
                $data['type'] = 'FeatureCollection'; //OK

                for($i = 0; $i < count($data_xy); $i++)
                {
                    //features
                    $data['features'][$i]['geometry']['type'] = 'Point'; 
                    $data['features'][$i]['geometry']['coordinates'] = array($data_xy[$i]['x'], $data_xy[$i]['y']);
                    $data['features'][$i]['type'] = 'Feature';
                    $data['features'][$i]['properties']['sp_id'] = $data_xy[$i]['sp_id'];
                }
                
                for($i = 0; $i < count($data_color); $i++)
                {
                    //properties
                    /*
                    $data['properties']['fields']['sp_id']['lookup'][$data_color[$i]['sp_id']] =  $data_color[$i]['first_name'] . ' ' . $data_color[$i]['name'];
                    $data['properties']['fields']['sp_id']['name'] = "Nom commercial";
                    
                    $data['properties']['fields']['color']['lookup'][$data_color[$i]['sp_id']] =  $data_color[$i]['color_code'] ;
                    $data['properties']['fields']['color']['name'] = "Couleur commercial";
                    */
                    $data['properties']['fields']['sp_id']['lookup'][$data_color[$i]['sp_id']] =  array($data_color[$i]['first_name'], $data_color[$i]['name'], $data_color[$i]['display_order'], $data_color[$i]['color_code']);
                    $data['properties']['fields']['sp_id']['name'] = "Nom commercial";
                }
                
                //$data['color_array'] = $data_color;
                
                
                //var_dump($data);
                
                
                //response(200, 'data_for_map_creation', $data);
                
                response(200, 'data_for_map_creation', $data);
            }
            else
            {
                response(200, 'no_color_array', NULL);
            }
        }
        else
        {
            response(200, 'error_getting_color_data_for_map', NULL);
        }

    }
    else
    {
        response(200, 'no_data_for_map_creation', NULL);
    }
}
else
{
    response(200, 'error_getting_geo_data_for_map', NULL);
}

?>