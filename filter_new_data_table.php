<?php 
session_start();

include_once dirname(__FILE__) .  '/Classes/db_connect.php';

if(isset($_POST["submit"])) {
    $instance = \ConnectDB::getInstance();
    $conn = $instance->getConnection();
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo(nl2br("Vérification des codes postaux"));
    $sql = 
    "UPDATE 
         new_data ND, 
         cp_ci CP
    SET 
         ND.Code_commune_coord_structure_ = CP.code_insee
    WHERE
         ND.Code_postal_coord_structure_ = CP.code_postal
         and ND.Code_commune_coord_structure_ = ''
    ";
    
    if (mysqli_query($conn, $sql))
    {
        echo("Nombre de données mises à jour : " . mysql_affected_rows());
    }
    else
    {
        echo("Echec de la mise à jour");
    }

}


?>

<html>
<body>

<form action="filter_new_data_table.php" method="post" enctype="multipart/form-data">
	Fichier importé correctement<br><br>
    <input type="submit" value="Etape suivante : validation des données" name="submit">
</form>

</body>
</html>