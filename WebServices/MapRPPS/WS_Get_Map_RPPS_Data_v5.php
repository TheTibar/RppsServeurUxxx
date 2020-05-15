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
$speciality_map = isset($_GET['speciality_map']) ? $_GET['speciality_map'] : "";
$agency_id = isset($_GET['agency_id']) ? $_GET['agency_id'] : "";

if (! empty($region_map) && ! empty($speciality_map) && ! empty($agency_id))
{
    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    
    $Region = new Region();
    
    $region_map = json_decode($region_map, true);
    $speciality_map = json_decode($speciality_map, true);
    

    //var_dump($speciality_map);
    
    for($i = 0; $i < count($speciality_map); $i++)
    {
        $speciality_map[$i] = mysqli_real_escape_string($conn, $speciality_map[$i]); 
    }
    
    //var_dump($speciality_map);
    
    $filter_region = implode(", ", $region_map);
    $filter_speciality = "'" . implode("','", $speciality_map) . "'";
    
    //var_dump($filter_speciality);
    
    //$filter_speciality = mysqli_real_escape_string($conn, $filter_speciality); 
    
    $sql = "SELECT 
                DRV.user_id as user_id,
            	DRV.display_order as display_order,
                DRV.first_name as first_name,
                DRV.name as name,
                DRV.color as color,
                GD.label as commune,
                GD.code_insee as code_commune,
                DRV.type as type,
                GD.x as x,
                GD.y as y,
                COALESCE(count(DRV.Identifiant_PP), 0) as weight
            FROM rpps_geo_data GD
            INNER JOIN
            (
            SELECT
                US.user_id as user_id,
            	US.display_order as display_order,
            	US.first_name as first_name,
            	US.last_name as name,
            	US.color as color,
            	CD.Identifiant_PP as Identifiant_PP,
            	MIN(GD.code_insee) as code_commune,
                'Point' AS type
            FROM rpps_user US
            INNER JOIN rpps_doctor_user DU ON DU.user_id = US.user_id
            INNER JOIN rpps_current_data CD ON CD.Identifiant_PP = DU.identifiant_pp
            	AND CD.region_id = DU.region_id
            INNER JOIN rpps_geo_data GD ON GD.code_insee = CD.Code_commune_coord_structure_
            WHERE 1 = 1
            	AND CD.Libelle_savoir_faire IN ($filter_speciality)
            	AND CD.region_id IN ($filter_region)
            GROUP BY user_id, display_order, first_name, name, color, Identifiant_PP, type
            ) DRV ON DRV.code_commune = GD.code_insee
            GROUP BY 
            user_id, display_order, first_name, name, color, commune, code_commune, type, x, y";
    
    //echo(nl2br($sql . "\n"));
    $data_xy = [];
    if ($sql_result = mysqli_query($conn, $sql))
    {
        
        while ($line = mysqli_fetch_assoc($sql_result))
        {
            $data_xy[] = $line;
        }
        //var_dump($data_xy);
        //On valide les données pour envoyer la bonne réponse au client et limiter les tests côté JS
        if(count($data_xy) > 0)
        {
            $data['geo'] = $data_xy;
            
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
    
    // On récupère les infos pour détailler les données dans la popup de chaque marker de la carte
    
    $sql = "SELECT 
            	DRV.display_order as display_order,
                DRV.code_commune as code_commune,
                DRV.speciality as speciality,
                COALESCE(count(DRV.Identifiant_PP), 0) as nb_spec_by_sp
            FROM
            (
            	SELECT 
            		US.display_order as display_order, 
            		MIN(CD.Code_commune_coord_structure_) as code_commune, 
            		CD.Libelle_savoir_faire as speciality, 
            		CD.Identifiant_PP as Identifiant_PP
            	FROM rpps_user US
            	INNER JOIN rpps_doctor_user DU on DU.user_id = US.user_id
            	INNER JOIN rpps_current_data CD on CD.Identifiant_PP = DU.identifiant_pp
            		AND CD.region_id = DU.region_id
            	WHERE 1 = 1
            		AND CD.Libelle_savoir_faire in  ($filter_speciality)
            		AND CD.region_id IN ($filter_region)
            	GROUP BY display_order, speciality, Identifiant_PP
            ) DRV
            GROUP BY display_order, code_commune, speciality";
    
    $data_detail = [];
    
    if ($sql_result = mysqli_query($conn, $sql))
    {
        
        while ($line = mysqli_fetch_assoc($sql_result))
        {
            $data_detail[] = $line;
        }
        //var_dump($data_xy);
        //On valide les données pour envoyer la bonne réponse au client et limiter les tests côté JS
        if(count($data_detail) > 0)
        {
            $data['detail_geo'] = $data_detail;
            
        }
         else
         {
         response(200, 'no_details_for_map_creation', NULL);
         }
    }
    else
    {
        response(200, 'error_getting_details_for_map', NULL);
    }


    /*on récupère tout le monde sauf les admins*/
    $sql = "SELECT DISTINCT 
                US.display_order as display_order
            FROM rpps_user US
            INNER JOIN rpps_user_agency UA on UA.user_id = US.user_id
            INNER JOIN rpps_user_region UR on UR.user_id = US.user_id
            INNER JOIN rpps_role RO on RO.role_id = US.role_id
            WHERE 1 = 1
                AND UA.agency_id = $agency_id
                AND UR.region_id IN ($filter_region)
                AND RO.label <> 'ADMIN'
            ORDER BY display_order
        ";


    $data_order = [];
    if ($sql_result = mysqli_query($conn, $sql))
    {
        while ($line = mysqli_fetch_assoc($sql_result))
        {
            $data_order[] = $line;
        }
        //var_dump($data);
        //On valide les données pour envoyer la bonne réponse au client et limiter les tests côté JS
        if(count($data_order) > 0)
        {
            $data['order'] = $data_order;
        }
        else
        {
            response(200, 'no_order_for_map_creation', NULL);
        }
    }
    else
    {
        response(200, 'error_getting_order_for_map', NULL);
    }
    
    $sql = "SELECT DISTINCT 
                US.display_order as display_order,
                US.last_name as name,
                US.first_name as first_name,
                US.color as color
            FROM rpps_user US
            INNER JOIN rpps_user_agency UA on UA.user_id = US.user_id
            INNER JOIN rpps_user_region UR on UR.user_id = US.user_id
            INNER JOIN rpps_role RO on RO.role_id = US.role_id
            WHERE 1 = 1
                AND UA.agency_id = $agency_id
                AND UR.region_id IN ($filter_region)
                AND RO.label <> 'ADMIN'
            ORDER BY display_order
        ";
    
    
    $data_title = [];   
    if ($sql_result = mysqli_query($conn, $sql))
    {
        while ($line = mysqli_fetch_assoc($sql_result))
        {
            $data_title[] = $line;
        }
        //var_dump($data);
        //On valide les données pour envoyer la bonne réponse au client et limiter les tests côté JS
        if(count($data_title) > 0)
        {
            $data['title'] = $data_title;
        }
        else
        {
            response(200, 'no_title_for_map_creation', NULL);
        }
    }
    else
    {
        response(200, 'error_getting_title_for_map', NULL);
    }
    
    $sql = "SELECT DISTINCT 
                US.display_order as display_order,
                US.color as color
            FROM rpps_user US
            INNER JOIN rpps_user_agency UA on UA.user_id = US.user_id
            INNER JOIN rpps_user_region UR on UR.user_id = US.user_id
            INNER JOIN rpps_role RO on RO.role_id = US.role_id
            WHERE 1 = 1 
                AND UA.agency_id = $agency_id
                AND UR.region_id IN ($filter_region)
                AND RO.label <> 'ADMIN'
            ORDER BY display_order
        ";
    
    $data_color = [];
    
    if ($sql_result = mysqli_query($conn, $sql))
    {
        
        while ($line = mysqli_fetch_assoc($sql_result))
        {
            $data_color[] = $line;
        }
        //var_dump($data);
        //On valide les données pour envoyer la bonne réponse au client et limiter les tests côté JS
        if(count($data_color) > 0)
        {
            $data['color'] = $data_color;
            
            /*On génère les png pour chaque couleur    */
            $dir = getcwd() . "/../../Img/";
            $image = $dir . "marker-icon_tst2.png";
            
            
            for ($i = 0; $i < count($data_color); $i++)
            {
                LoadPNG($image, $data_color[$i]['color']);
            }
            
            response(200, 'data_for_map_creation', $data);
        }
        else
        {
            response(200, 'no_color_for_map_creation', NULL);
        }
    }
    else
    {
        response(200, 'error_getting_color_for_map', NULL);
    }

}
else
{
    $msg='error';
    $sep='|';
    if (empty($region_map)) {
        $msg = $msg . $sep . 'empty_region_map';
    }
    if (empty($speciality_map)) {
        $msg = $msg . $sep . 'empty_speciality_map';
    }
    if (empty($agency_id)) {
        $msg = $msg . $sep . 'empty_agency_id';
    }
    
    response(200, $msg, NULL);
}


/**/
function LoadPNG($imgname, $color)
{
    $color = substr($color, 1, strlen($color) - 1);
    //echo($color . " : ");
    
    $split_hex_color = str_split( $color, 2 ); //on supprime le # pour le router 
    $r = hexdec( $split_hex_color[0] ); 
    $g = hexdec( $split_hex_color[1] ); 
    $b = hexdec( $split_hex_color[2] );
    
    
    $im = imagecreatefrompng ($imgname);
    imagetruecolortopalette($im, false, 255);
    
    imagealphablending($im, false);
    imagesavealpha($im, true);
    
    $index = imagecolorclosest ($im, 0, 0, 0); // GET BLACK COLOR
    imagecolorset($im, $index, $r, $g, $b, 179); // SET COLOR TO $color
    
    $index = imagecolorclosest ($im, 255, 255, 255); // GET WHITE COLOR
    imagecolorset($im, $index, 255, 255, 255, 127); // SET COLOR WHITE transparent
    
    $name = basename($imgname);
    //var_dump($im);
    
    imagepng($im, getcwd() . "/../../Img/" . $color . ".png"); // save image as png
    //echo(nl2br(getcwd() . "/../../Img/" . $color . ".png". "\n"));
    //imagedestroy($im);
}


?>

