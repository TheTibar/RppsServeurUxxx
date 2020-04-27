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

if (! empty($region_map) && ! empty($speciality_map))
{
    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    
    $Region = new Region();
    
    $region_map = json_decode($region_map, true);
    $speciality_map = json_decode($speciality_map, true);
    
    $data_xy = [];
    $data_color = [];
    //var_dump($speciality_map);
    
    for($i = 0; $i < count($speciality_map); $i++)
    {
        $speciality_map[$i] = mysqli_real_escape_string($conn, $speciality_map[$i]); 
    }
    
    //var_dump($speciality_map);
    
    
    $filter_speciality = "'" . implode("','", $speciality_map) . "'";
    
    //var_dump($filter_speciality);
    
    //$filter_speciality = mysqli_real_escape_string($conn, $filter_speciality); 
    //echo("SM : " . $speciality_map);
    
    for ($i = 0; $i < count($region_map); $i++)
    {
        $result = $Region->getRegionCode($region_map[$i]);
        $region_code = $Region->__get('code');

        // On récupère les données avec les 0 pour avoir toutes les combinaisons "Commercial / Commune"
        
        $sql = "SELECT 
                	SPX.display_order as display_order,
                    SPX.first_name as first_name,
                    SPX.name as name, 
                    SPX.color as color,
                    LC1 as commune,
                    CC1 as code_commune,
                    'Point' as type,
                    GDX.x as x,
                    GDX.y as y,
                    COALESCE(weight, 0) as weight
                FROM
                	/*données à 0 à générer*/
                	(
                		SELECT DISTINCT SP.sales_pro_id as SPID1
                		FROM rpps_" . $region_code . "_sales_pro SP
                	) drv1
                	CROSS JOIN
                	(
                		SELECT DISTINCT CD.Code_commune_coord_structure_ as CC1, CD.Libelle_commune_coord_structure_ as LC1
                		FROM rpps_" . $region_code . "_current_data CD
                	) drv2
                LEFT OUTER JOIN 
                	(
                	/*données présentes*/
                	SELECT 
                		SP.sales_pro_id as SPID2,
                		CD.Libelle_commune_coord_structure_ as LC2,
                		CD.Code_commune_coord_structure_ as CC2,
                		COALESCE(count(CD.Identifiant_PP), 0) as weight
                	FROM rpps_" . $region_code . "_current_data CD
                	INNER JOIN rpps_" . $region_code . "_doctor_sales_pro_link DSP on DSP.identifiant_pp = CD.Identifiant_PP
                	INNER JOIN rpps_" . $region_code . "_sales_pro SP on SP.sales_pro_id = DSP.sales_pro_id
                	INNER JOIN rpps_mstr_geo_data GD on GD.code_insee = CD.Code_commune_coord_structure_
                    WHERE CD.Libelle_savoir_faire in ($filter_speciality)
                	GROUP BY SPID2, LC2
                	) drv3 on drv3.SPID2 = drv1.SPID1 and drv3.CC2 = drv2.CC1
                INNER JOIN rpps_" . $region_code . "_sales_pro SPX on SPX.sales_pro_id = SPID1
                INNER JOIN rpps_mstr_geo_data GDX on GDX.code_insee = CC1";
        
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
                $data['geo'][$region_map[$i]] = $data_xy;
                
            }
            /*
            else
            {
                response(200, 'no_data_for_map_creation', NULL);
            }
            */
        }
        else
        {
            response(200, 'error_getting_geo_data_for_map', NULL);
        }
        
        // On récupère les vrais données pour détailler les données dans la popup de chaque marker de la carte
        
        $sql = "SELECT 
                	SP.display_order as display_order, 
                    CD.Code_commune_coord_structure_ as code_commune, 
                    CD.Libelle_savoir_faire as speciality, 
                    COALESCE(count(CD.Identifiant_PP), 0) as nb_spec_by_sp
                FROM rpps_" . $region_code . "_sales_pro SP
                INNER JOIN rpps_" . $region_code . "_doctor_sales_pro_link DSP on DSP.sales_pro_id = SP.sales_pro_id
                INNER JOIN rpps_" . $region_code . "_current_data CD on CD.Identifiant_PP = DSP.identifiant_pp
                WHERE CD.Libelle_savoir_faire in  ($filter_speciality)
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
            if(count($data_xy) > 0)
            {
                $data['detail_geo'][$region_map[$i]] = $data_detail;
                
            }
            /*
             else
             {
             response(200, 'no_data_for_map_creation', NULL);
             }
             */
        }
        else
        {
            response(200, 'error_getting_geo_data_for_map', NULL);
        }
        
        
        
    }
    
    
    
   /* 
    $sql = "SELECT
                SP.display_order as display_order
            FROM rpps_971_sales_pro SP
            order by SP.display_order
        ";
    */
    /*on récupère tout le monde sauf les admins, et on ajoute à la main les utilisateurs par défaut (9998 et 9999)*/
    $sql = "SELECT
                US.display_order as display_order
            FROM rpps_mstr_user US
            INNER JOIN rpps_mstr_user_agency UA on UA.user_id = US.user_id
            INNER JOIN rpps_mstr_role RO on RO.role_id = US.role_id
            WHERE UA.agency_id = $agency_id
            AND RO.label <> 'ADMIN'

            UNION ALL
        	SELECT DEFSP.display_order as display_order
        	FROM rpps_mstr_default_sales_pro DEFSP

            ORDER BY display_order
        ";
    
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
    
    $sql = "SELECT
                US.display_order as display_order,
                US.last_name as name,
                US.first_name as first_name,
                US.color as color
            FROM rpps_mstr_user US
            INNER JOIN rpps_mstr_user_agency UA on UA.user_id = US.user_id
            INNER JOIN rpps_mstr_role RO on RO.role_id = US.role_id
            WHERE UA.agency_id = $agency_id
            AND RO.label <> 'ADMIN'
            
            UNION ALL
        	SELECT 
                DEFSP.display_order as display_order,
                DEFSP.last_name as name,
                DEFSP.first_name as first_name,
                DEFSP.color as color
        	FROM rpps_mstr_default_sales_pro DEFSP

            order by display_order
        ";
    
    
        
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
    
    $sql = "SELECT
                US.display_order as display_order,
                US.color as color
            FROM rpps_mstr_user US
            INNER JOIN rpps_mstr_user_agency UA on UA.user_id = US.user_id
            INNER JOIN rpps_mstr_role RO on RO.role_id = US.role_id
            WHERE UA.agency_id = $agency_id
            AND RO.label <> 'ADMIN'
                
            UNION ALL
        	SELECT 
                DEFSP.display_order as display_order,
                DEFSP.color as color
        	FROM rpps_mstr_default_sales_pro DEFSP
            ORDER BY display_order
        ";
    
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
    imagecolorset($im, $index, $r, $g, $b); // SET COLOR TO $color
    
    $index = imagecolorclosest ($im, 255, 255, 255); // GET WHITE COLOR
    imagecolorset($im, $index, 255, 255, 255, 127); // SET COLOR WHITE transparent
    
    $name = basename($imgname);
    //var_dump($im);
    
    imagepng($im, getcwd() . "/../../Img/" . $color . ".png"); // save image as png
    //echo(nl2br(getcwd() . "/../../Img/" . $color . ".png". "\n"));
    //imagedestroy($im);
}


?>

