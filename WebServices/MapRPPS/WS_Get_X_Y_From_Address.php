<?php
/*
On utilise mapquest avec la clé nJyW9TUdujsjlrMUeZRSYVarPTa2WzC3
https://developer.mapquest.com/documentation/geocoding-api/quality-codes/
https://developer.mapquest.com/documentation/geocoding-api/
*/

/*A FINIR SI BESOIN*/

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

$key = "key=" . "nJyW9TUdujsjlrMUeZRSYVarPTa2WzC3";

$maxResult = 1; //renvoie le geocodage le plus qualitatif par défaut

$addressRd = "171+Rue+Aurelie+Nanky";
$addressCity = "Les+Abymes";
$addressState = "Guadeloupe";
$addressPostalCode = "97139";

$addressCall = "&location=" . $addressRd . ',' . $addressCity . ',' . $addressState . ',' . $addressPostalCode;

$paramCall = "&maxResults=" . strval($maxResult);



//echo(nl2br("http://www.mapquestapi.com/geocoding/v1/address?" . $key . $addressCall . $paramCall . "\n"));
$result = file_get_contents("http://www.mapquestapi.com/geocoding/v1/address?" . $key . $addressCall . $paramCall);


//$result = file_get_contents("http://www.mapquestapi.com/geocoding/v1/address?key=nJyW9TUdujsjlrMUeZRSYVarPTa2WzC3&location=171+Rue+Aurelie+Nanky,Les+Abymes,Guadeloupe,97139");


//$result = file_get_contents("http://www.mapquestapi.com/geocoding/v1/address?key=nJyW9TUdujsjlrMUeZRSYVarPTa2WzC3&location=&street=171+Rue+Aurelie+Nanky&city=Les+Abymes&state=Guadeloupe&postalCode=97139");


//echo(nl2br($result . "\n"));

$result = json_decode($result, TRUE);

/*
if (json_last_error() === JSON_ERROR_NONE) {
    echo(nl2br("JSON Valide" . "\n"));
}
else
{
    echo(nl2br("JSON Invalide" . "\n"));
}
*/
//var_dump($result);
//echo(nl2br("\n"));

/**/
$lat = $result['results'][0]['locations'][0]['latLng']['lat'];
$lng = $result['results'][0]['locations'][0]['latLng']['lng'];
$geocodeQualityCode = $result['results'][0]['locations'][0]['geocodeQualityCode'];

$typeResponse = substr($geocodeQualityCode, 0, 2);
$qualityResponseFullStreetConfidenceLevel = substr($geocodeQualityCode, 2, 1);
$qualityResponseAreaConfidenceLevel = substr($geocodeQualityCode, 3, 1);
$qualityResponsePostalCodeConfidenceLevel = substr($geocodeQualityCode, 4, 1);


switch ($typeResponse) {
    case "P1":
        $typeResponseValue = "POINT";
        break;
    case "L1":
        $typeResponseValue = "ADDRESS";
        break;
    case "I1":
        $typeResponseValue = "INTERSECTION";
        break;
    case "B1":
        $typeResponseValue = "STREET";
        break;
    case "B2":
        $typeResponseValue = "STREET";
        break;
    case "B3":
        $typeResponseValue = "STREET";
        break;
    case "A1":
        $typeResponseValue = "COUNTRY";
        break;
    case "A3":
        $typeResponseValue = "STATE";
        break;
    case "A4":
        $typeResponseValue = "COUNTY";
        break;
    case "A5":
        $typeResponseValue = "CITY";
        break;
    case "A6":
        $typeResponseValue = "NEIGHBORHOOD";
        break;
    case "Z1":
        $typeResponseValue = "ZIP";
        break;
    case "Z2":
        $typeResponseValue = "ZIP_EXTENDED";
        break;
    case "Z3":
        $typeResponseValue = "ZIP_EXTENDED";
        break;
    case "Z4":
        $typeResponseValue = "ZIP";
        break;
};


switch ($qualityResponseFullStreetConfidenceLevel) {
    case "A":
        $qualityResponseFullStreetConfidenceLevelValue = "EXACT";
        break;
    case "B":
        $qualityResponseFullStreetConfidenceLevelValue = "GOOD";
        break;
    case "C":
        $qualityResponseFullStreetConfidenceLevelValue = "APPROX";
        break;
    case "X":
        $qualityResponseFullStreetConfidenceLevelValue = "NO_MEANING";
        break;
}


switch ($qualityResponseAreaConfidenceLevel) {
    case "A":
        $qualityResponseAreaConfidenceLevelValue = "EXACT";
        break;
    case "B":
        $qualityResponseAreaConfidenceLevelValue = "GOOD";
        break;
    case "C":
        $qualityResponseAreaConfidenceLevelValue = "APPROX";
        break;
    case "X":
        $qualityResponseAreaConfidenceLevelValue = "NO_MEANING";
        break;
}



switch ($qualityResponsePostalCodeConfidenceLevel) {
    case "A":
        $qualityResponsePostalCodeConfidenceLevelValue = "EXACT";
        break;
    case "B":
        $qualityResponsePostalCodeConfidenceLevelValue = "GOOD";
        break;
    case "C":
        $qualityResponsePostalCodeConfidenceLevelValue = "APPROX";
        break;
    case "X":
        $qualityResponsePostalCodeConfidenceLevelValue = "NO_MEANING";
        break;
}




$data['lat'] = $lat;
$data['lng'] = $lng;
$data['quality'] = $geocodeQualityCode;
$data['qualityTypeResponseValue'] = $typeResponseValue;
$data['qualityResponseFullStreetConfidenceLevelValue'] = $qualityResponseFullStreetConfidenceLevelValue;
$data['qualityResponseAreaConfidenceLevel'] = $qualityResponseAreaConfidenceLevelValue;
$data['qualityResponsePostalCodeConfidenceLevel'] = $qualityResponsePostalCodeConfidenceLevelValue;



/*
echo(nl2br("latitude : " . $lat . "\n"));
echo(nl2br("longitude : " . $lng . "\n"));
echo(nl2br("geocodeQualityCode : " . $geocodeQualityCode . "\n"));
*/

response(200, 'geocodage_ok', $data);

?>