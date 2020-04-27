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
        $sql = "
            delete from current_data
            where col1 = (select col1 from delta where id = $ligne)
                and col2 = (select col2 from delta where id = $ligne)
                and col3 = (select col3 from delta where id = $ligne)
                and col4 = (select col4 from delta where id = $ligne)
                and col5 = (select col5 from delta where id = $ligne)
                and col6 = (select col6 from delta where id = $ligne)
                and col7 = (select col7 from delta where id = $ligne)

";
        
        
        if (mysqli_query($conn, $sql))
        {
            header('Location: import_file.php');
        }
        else
        {
            echo($sql);
        }
        
    }


} else {
    echo("not_get");
}
?>