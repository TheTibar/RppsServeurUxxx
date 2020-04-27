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
$tmp_rpps_path = $_SESSION["tmp_rpps_path"];


// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) {
    $target_file = $tmp_rpps_path . basename($_FILES["fileToUpload"]["name"]);
    echo($target_file);
    $fileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    if($fileType == "csv") {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "Le fichier ". basename( $_FILES["fileToUpload"]["name"]). " a �t� t�l�charg�.";
            $_SESSION["fichier_import"] = $tmp_rpps_path . basename($_FILES["fileToUpload"]["name"]);
            $fichier = $_SESSION["fichier_import"];
            
            //header("location: import_file.php");
        } else {
            echo "Erreur de chargement n� : ".$_FILES["fileToUpload"]["error"];
        }
    } else {
        echo "Fichier non valide.";
    }
    
}
?>


<html>
<body>

<form action="upload_csv.php" method="post" enctype="multipart/form-data">
	Sélectionner le fichier RPPS à uploader : 
    <input type="file" name="fileToUpload" id="fileToUpload" accept=".csv">
    <input type="submit" value="Uploader" name="submit">
</form>

</body>
</html>