<?php 
session_start();

include_once dirname(__FILE__) .  '/Classes/db_connect.php';

$instance = \ConnectDB::getInstance();
$conn = $instance->getConnection();

$sql = "select param_value from param where param_name = 'tmp_rpps_path'";
$sql_result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($sql_result);

//var_dump($data);

$_SESSION["tmp_rpps_path"]=$data["param_value"];

//echo($_SESSION["tmp_rpps_file"]);



echo(nl2br("Etape (" . date('Y-m-d H:i:s') . ") : " . "test" . "\n")); //à supprimer


?>