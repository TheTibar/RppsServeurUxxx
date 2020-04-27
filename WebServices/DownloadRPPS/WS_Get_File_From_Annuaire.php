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


$page = file_get_contents('https://annuaire.sante.fr/web/site-pro/extractions-publiques');

$doc = new DOMDocument;
libxml_use_internal_errors(true);
$doc->loadHTML($page);

//var_dump($page);
//echo(nl2br("page length : " . strlen($page) . "\n"));

$pos_PS_LibreAcces_ = strpos($page, 'PS_LibreAcces_');
//echo(nl2br("pos_PS_LibreAcces_ : " . $pos_PS_LibreAcces_ . "\n"));

$page_deb = substr($page, 0, $pos_PS_LibreAcces_);
//echo(nl2br("page_deb length : " . strlen($page_deb) . "\n"));

$page_fin = substr($page, $pos_PS_LibreAcces_, strlen($page));
//echo(nl2br("page_fin length : " . strlen($page_fin) . "\n"));

$pos_HTTPS = strripos($page_deb, 'https');
//echo(nl2br("pos_HTTPS : " . $pos_HTTPS . "\n"));

$pos_ZIP = strpos($page_fin, '.zip');
//echo(nl2br("pos_ZIP : " . $pos_ZIP . "\n"));

$link = substr($page_deb, $pos_HTTPS, strlen($page_deb));
//echo(nl2br("lien complet debut : " . $link . "\n"));

$link = $link . substr($page_fin, 0, $pos_ZIP + 4);
//echo(nl2br("lien complet : " . $link . "\n"));
$data['link'] = $link;
$data['filename'] = substr($page_fin, 0, $pos_ZIP + 4);

response(200, 'link_to_rpps_file', $data);

//echo "<a href='".$link."'>Rpps</a>";

?>