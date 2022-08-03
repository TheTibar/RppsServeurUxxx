<?php

require_once __DIR__ . '/db_connect.php';

class ConnectDB
{
    private static $instance = null;
    private $mysql_db_conn;

    //A rendre indépendant du code
	/* LOCAL */
    private $mysql_db_host = 'localhost';
    private $mysql_db_user = 'root';
    private $mysql_db_pass = '';
    private $mysql_db_name = 'rppsuxxx';
    private $mysql_db_port = '3306';
	
   
	/* OVH 
voir Drive Developpement/Login BDD/github.txt
	*/

    // The db connection is established in the private constructor.
    private function __construct()
    {
        error_reporting(E_ALL ^ E_WARNING); 
        try
        {
            if ($this->mysql_db_conn = mysqli_connect($this->mysql_db_host, $this->mysql_db_user, $this->mysql_db_pass, $this->mysql_db_name, $this->mysql_db_port))
            {
                mysqli_set_charset($this->mysql_db_conn, 'utf8');
            }
            else
            {
                //throw new Exception("Database Connection KO");
                $this->errorConnection();
            }
        }
        catch(Exception $e)
        {
        	//echo("|Dans le catch");
            //echo nl2br($e->getMessage());
            $this->mysql_db_conn = null;
        }
    }
    
    public static function getInstance()
    {
    	//On utilise self car $instance est static
        if (! self::$instance) {
        	//echo("|new db");
            self::$instance = new ConnectDb();
        }
        //echo(var_dump(self::$instance));
        return self::$instance;
    }

    public function getConnection()
    {
    	//usleep(4000000);
		//echo("|getConnection");
		if ($this->mysql_db_conn)
		{
    		return $this->mysql_db_conn;
		}
		else
		{
			$this->errorConnection();
		}
    }
    
    public function closeConnection()
    {
        //Permet de fermer les connexions. A voir si des traitements longs peuvent laisser trop de connexions ouvertes.
        mysqli_close($this->mysql_db_conn);
    }
    
    private function errorConnection()
    {
    	$status=500;
    	header("HTTP/1.1 ".$status);
    	//http_response_code(503);
    	$response['status']=$status;
    	$response['status_message']="database_connection_fatal_error";
    	$response['data']=null;
    	$json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    	echo $json_response;
    	exit(1);
    }
    
}

?>
