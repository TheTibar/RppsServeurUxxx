<?php 

$servername = "localhost";
$username = "root";
$password = "";
$mysql_db_name = "rpps";
$mysql_port = "3306";

$conn = new mysqli($servername, $username, $password, $mysql_db_name, $mysql_port);
    
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "GET")
{
    if (! empty($_GET['ligne']))
    {
        $ligne = $_GET['ligne'];
        $sql = "insert into current_data(col1, col2, col3, col4, col5, col6, col7) select col1, col2, col3, col4, col5, col6, col7 from delta where id = $ligne";
        if (mysqli_query($conn, $sql))
        {
            header('Location: import_file.php');
        }
        else
        {
            header('Location: import_file.php');
        }
    }


} else {
    echo("not_get");
}
?>