<?php

include_once __DIR__ .  '/User.php';
include_once __DIR__ .  '/Agency.php';
include_once __DIR__ .  '/RPPS.php';
include_once __DIR__ .  '/Process.php';
include_once __DIR__ .  '/Region.php';
//use Exception;


use Classes\User;
use Classes\Agency;
use Classes\RPPS;
use Classes\Process;
use Classes\Region;

//CLASSE USER


$User = new User();


$login = 'emailarmelle@gmail.com';
$token = $login . rand() . time() . rand();
$token = hash("sha256", $token);
echo(nl2br($login . ' : ' . $token . "\n"));

$password_orig = 'mdp1234';
$password = hash("sha256", $password_orig);
echo($password_orig . ' : ' . $password);
/**/

/*
$result = $User->existsUser($login);

if($result)
{
    echo('ok');
}
else 
{
    echo('ko');
}
*/

/*
$token = '1f2bf514291016cb9a4d3fa38f789fdf31a54239297fdb17c08a543cc0704a17';
$result = $User->getUserData($token);

switch($result) {
    case 0:
        var_dump($User->export());
        break;
    case -1:
        echo("error id inconnu");
        break;
    case -2:
        echo("rq 1 erreur");
        break;
}
*/

/*
$email = 'baert.xavier@gmail.com';
$password = "test";

$result = $User->userLogin($email, $password);

switch($result) {
    case 0:
        echo($User->__get('token') . ' ' . $User->__get('first_login'));
        break;
    case -1:
        echo("login impossible");
        break;
    case -2:
        echo("rq 1 erreur");
        break;
}
*/

/*
$token = '1f2bf514291016cb9a4d3fa38f789fdf31a54239297fdb17c08a543cc0704a17';
$password = "test";

$result = $User->updateUserPassword($token, $password);

switch($result) {
    case 0:
        echo("mdp changé");
        break;
    case -1:
        echo("rq 1 erreur");
        break;
}
*/

//CLASSE AGENCY

$Agency = new Agency();

/*
$agency_id = 1;

$result = $Agency->getAgencyInfo($agency_id);

switch($result) {
    case 0:
        var_dump($Agency->export());
        break;
    case -1:
        echo("error id inconnu");
        break;
    case -2:
        echo("rq 1 erreur");
        break;
    case -3:
        echo("rq 2 erreur");
        break;
}

//$agency_token = 'b2oDfnZeQpGwoG9GBapglCHUcw=='; //OK
$agency_token = '2oDfnZeQpGwoG9GBapglCHUcw=='; //KO
$user_token = '1f2bf514291016cb9a4d3fa38f789fdf31a54239297fdb17c08a543cc0704a17';

$result = $Agency->getAgencyIdByToken($agency_token, $user_token);
switch($result) {
    case 0:
        var_dump($Agency->__get('agency_id'));
        break;
    case -99:
        echo("erreur grave");
        break;
    case -2:
        echo("rq 1 erreur");
        break;
}
*/

/*
$agency_id = 1;
$result = $Agency->getMaxDisplayOrder($agency_id);
 
switch($result) {
    case 0:
        var_dump($Agency->__get('max_display_order'));
        break;
    case -2:
        echo("rq erreur");
        break;
}
*/



/*
$agency_id = 1;
$result = $Agency->getSalesProByAgency($agency_id);

echo($result);

*/
      
$RPPS = new RPPS();
/*
$region_id = 97;

$RPPS->compareRPPS($region_id);

var_dump($RPPS->export());
*/

$LocalProcess = new Process();

/*
$result = $LocalProcess->existsLocalProcessTable(98);
echo($result);
*/

$Region = new Region();

/*
//$region_token = '34Qe3pQSJfAog==';
$region_token = 'a34Qe3pQSJfAog==';
$user_token = '1f2bf514291016cb9a4d3fa38f789fdf31a54239297fdb17c08a543cc0704a17';

$result = $Region->getRegionIdByToken($region_token, $user_token);
echo($result);
*/

/*
$result = $Region->getRegionCode(215);
echo($result);
*/

/*
$region_id = 97;

$result = $Region->getRegionDoctors($region_id);

echo($result);

var_dump($Region->__get('region_doctors_array'));
*/
    
?>