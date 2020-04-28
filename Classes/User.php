<?php
namespace Classes;
use Exception;

include_once dirname(__FILE__) . '/db_connect.php';
include_once dirname(__FILE__) . '/Log.php';
include_once dirname(__FILE__) . '/Process.php';
include_once dirname(__FILE__) . '/Region.php';

class User
{
    private $user_id;
    private $token;
    private $email;
    private $first_name;
    private $last_name;
    private $agency_id;
    private $first_login;
    private $role_id;
    private $region_array = [];
    private $agency_array = [];
    private $can_create_roles = [];
    private $display_order;
    
    private $password_orig;
    

    
    public function test()
    {
        var_dump(get_object_vars($this));
    }
    
    public function export()
    {
        return get_object_vars($this);
    }
    
    public function __construct()
    {}
    
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
    
    public function createUser($email, $first_name, $last_name, $role_id, $user_id_creation, $region_array, $agency_id, $color, $display_order, $process_id)  
    //Crée un utilistaur et l'affecte à une agence et à un ensemble de régions
    {

        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        //tentative prepared statement : 
        
        $Log = new Log();
        
        //echo($region_array[0]);
        $first_region = $region_array[0];
        
        $Log->writeLogNoEcho($first_region, "Début création nouvel utilisateur : " . $email, $process_id);
        $Log->writeLogNoEcho($first_region, "Id_utilisateur de création : " . $user_id_creation, $process_id);
        
        $token = $email . rand() . time() . rand();
        $token = hash("sha256", $token);
        
        $password_orig = $color .rand() . time() . rand();
        $Log->writeLogNoEcho($first_region, "Code origine : " . $password_orig, $process_id);
        //echo(nl2br("\n" . "password_orig : " . $password_orig));
        $password = hash("sha256", $password_orig);
        
        //echo(nl2br("\n" . "display_order : " . $display_order));
        
        $sql = "INSERT INTO rpps_user (token, email, password, first_name, last_name, color, role_id, first_login, user_id_creation, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        mysqli_begin_transaction($conn);
        
        $sql_error = -2;
        
        if($stmt = mysqli_prepare($conn, $sql))
        {
            if(mysqli_stmt_bind_param($stmt, "ssssssiiii", $sql_token, $sql_email, $sql_password, $sql_first_name, $sql_last_name, $sql_color, $sql_role_id, $sql_first_login, $sql_user_id_creation, $sql_display_order))
            {
                $sql_token = $token;
                $sql_email = $email;
                $sql_password = $password;
                $sql_first_name = $first_name;
                $sql_last_name = $last_name;
                $sql_color = $color;
                $sql_role_id = $role_id;
                $sql_first_login = 1;
                $sql_user_id_creation = $user_id_creation;
                $sql_display_order = $display_order;
                
                if (mysqli_stmt_execute($stmt))
                {
                    
                    $new_user_id = mysqli_stmt_insert_id($stmt);
                    $this->password_orig = $password_orig;
                    $Log->writeLogNoEcho($first_region, "Création OK, id_user : " . $new_user_id, $process_id);
                    // Close statement
                    mysqli_stmt_close($stmt);
                }
                else 
                {
                    $Log->writeLogNoEcho($first_region, "Création KO", $process_id);
                    $Log->writeLogNoEcho($first_region, "Fin création utilisateur : " . $email, $process_id);
                    
                    mysqli_rollback($conn);
                    
                    // Close statement
                    mysqli_stmt_close($stmt);
                    // Close connection
                    mysqli_close($conn);
                    return $sql_error;
                }
            }
            else 
            {
                $Log->writeLogNoEcho($first_region, "Création KO", $process_id);
                $Log->writeLogNoEcho($first_region, "Fin création utilisateur : " . $email, $process_id);
                mysqli_rollback($conn);
                // Close statement
                mysqli_stmt_close($stmt);
                // Close connection
                mysqli_close($conn);
                return $sql_error;
            }
        }
        else
        {
            $Log->writeLogNoEcho($first_region, "Création KO", $process_id);
            $Log->writeLogNoEcho($first_region, "Fin création utilisateur : " . $email, $process_id);
            mysqli_rollback($conn);
            // Close statement
            mysqli_stmt_close($stmt);
            // Close connection
            mysqli_close($conn);
            return $sql_error;
        }
        
        $Log->writeLogNoEcho($first_region, "Création lien vers agence : " . $agency_id, $process_id);
        
        $sql = "INSERT INTO rpps_user_agency (user_id, agency_id, user_id_creation)
                VALUES (?, ?, ?)";
        
        $sql_error = -3;
        
        if($stmt = mysqli_prepare($conn, $sql))
        {
            if(mysqli_stmt_bind_param($stmt, "iii", $sql_user_id, $sql_agency_id, $sql_user_id_creation))
            {
                $sql_user_id = $new_user_id;
                $sql_agency_id = $agency_id;
                $sql_user_id_creation = $user_id_creation;
                
                if (mysqli_stmt_execute($stmt))
                {
                    // Close statement
                    mysqli_stmt_close($stmt);
                    $Log->writeLogNoEcho($first_region, "Création OK", $process_id);
                    //On a d'autres requêtes
                    // Close connection
                    //mysqli_close($conn);
                    //return 0;
                }
                else 
                {
                    $Log->writeLogNoEcho($first_region, "Création KO", $process_id);
                    $Log->writeLogNoEcho($first_region, "Fin création utilisateur : " . $email, $process_id);
                    mysqli_rollback($conn);
                    // Close statement
                    mysqli_stmt_close($stmt);
                    // Close connection
                    mysqli_close($conn);
                    return $sql_error;
                }
            }
            else
            {
                $Log->writeLogNoEcho($first_region, "Création KO", $process_id);
                $Log->writeLogNoEcho($first_region, "Fin création utilisateur : " . $email, $process_id);
                mysqli_rollback($conn);
                // Close statement
                mysqli_stmt_close($stmt);
                // Close connection
                mysqli_close($conn);
                return $sql_error;
            }
        }
        else 
        {
            $Log->writeLogNoEcho($first_region, "Création KO", $process_id);
            $Log->writeLogNoEcho($first_region, "Fin création utilisateur : " . $email, $process_id);
            mysqli_rollback($conn);
            // Close statement
            mysqli_stmt_close($stmt);
            // Close connection
            mysqli_close($conn);
            return $sql_error;
        }
        
        
        /*insérer dans les tables de région*/
        $Log->writeLogNoEcho($first_region, "Création lien(s) vers " . strval(count($region_array)) . " région(s)", $process_id);
        
        $sql = "INSERT INTO rpps_user_region (user_id, region_id, user_id_creation)
                VALUES (?, ?, ?)";
        
        $sql_error = -4;
        
        if($stmt = mysqli_prepare($conn, $sql))
        {
            if(mysqli_stmt_bind_param($stmt, "iii", $sql_user_id, $sql_region_id, $sql_user_id_creation))
            {
                for ($i = 0; $i < count($region_array); $i++)
                {
                    $sql_user_id = $new_user_id;
                    $sql_region_id = $region_array[$i];
                    $sql_user_id_creation = $user_id_creation;
                    $Log->writeLogNoEcho($region_array[$i], "Création lien vers région : " . $region_array[$i], $process_id);
                    if (mysqli_stmt_execute($stmt))
                    {
                        $Log->writeLogNoEcho($region_array[$i], "Création OK", $process_id);
                        $last_region = $region_array[$i];
                    }
                    else 
                    {
                        $Log->writeLogNoEcho($region_array[$i], "Création KO", $process_id);
                        $Log->writeLogNoEcho($region_array[$i], "Fin création utilisateur : " . $email, $process_id);
                        mysqli_rollback($conn);
                        // Close statement
                        mysqli_stmt_close($stmt);
                        // Close connection
                        mysqli_close($conn);
                        return $sql_error;
                    }
                    
                }
                //$DBProcess->finalStep($process_id);
                $Log->writeLogNoEcho($last_region, "Fin création lien(s) vers région(s) OK", $process_id);
                mysqli_commit($conn);
                // Close statement
                mysqli_stmt_close($stmt);
                $Log->writeLogNoEcho($last_region, "Création OK", $process_id);
                $Log->writeLogNoEcho($last_region, "Fin création utilisateur : " . $email, $process_id);

                // Close connection
                // mysqli_close($conn);
                return 0;
            }
            else 
            {
                $Log->writeLogNoEcho($region_array[$i], "Création KO", $process_id);
                $Log->writeLogNoEcho($region_array[$i], "Fin création utilisateur : " . $email, $process_id);
                mysqli_rollback($conn);
                // Close statement
                mysqli_stmt_close($stmt);
                // Close connection
                mysqli_close($conn);
                return $sql_error;
            }
        }
        else 
        {
            $Log->writeLogNoEcho($first_region, "Création KO", $process_id);
            $Log->writeLogNoEcho($first_region, "Fin création utilisateur : " . $email, $process_id);
            mysqli_rollback($conn);
            // Close statement
            mysqli_stmt_close($stmt);
            // Close connection
            mysqli_close($conn);
            return $sql_error;
        }
        

        
        
    }
                             //$user_id, $first_name, $last_name, $role_id, $user_id_creation, $region_array, $agency_id
    public function updateUser($user_id, $first_name, $last_name, $role_id, $user_id_creation, $region_array, $agency_id, $process_id)
    {
        //tentative prepared statement : 
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $sql = "";
        
        $email = mysqli_real_escape_string($conn, $email);
        $email = mysqli_real_escape_string($conn, $email);
        
        //penser au display_order
    }
    
    public function existsUser($email) 
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $email = mysqli_real_escape_string($conn, $email);
        
        $sql = "SELECT user_id, token 
                FROM rpps_user 
                WHERE email = '$email'";
        
        //echo($sql);
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                if ($data['token'] === NULL)
                {
                    
                    return -1; //l'utilisateur n'existe pas
                }
                else
                {
                    $this->token = $data['token'];
                    $this->user_id = $data['user_id'];
                    return 0; 
                }
            }
            else 
            {
                return -2;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
    }
    
    public function getRoleId($user_token)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $user_token = mysqli_real_escape_string($conn, $user_token);
        
        $sql = "SELECT role_id as user_role_id
                FROM rpps_user US
                WHERE US.token = '$user_token'";
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                if($data['role_id'] === NULL)
                {
                    return -1;
                }
                else
                {
                    $this->role_id = $data['user_role_id'];
                    return 0;

                }
            }
            else
            {
                return -2;
            }
        }
        catch (Exception $e)
        {
            echo ("Erreur : " . $e);
        }
        
    }
    
    public function getUserId($user_token)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $user_token = mysqli_real_escape_string($conn, $user_token);
        
        $sql = "SELECT user_id as user_id
                FROM rpps_user US
                WHERE US.token = '$user_token'";
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                if($data['user_id'] === NULL)
                {
                    return -1;
                }
                else
                {
                    $this->user_id = $data['user_id'];
                    return 0;
                    
                }
            }
            else
            {
                return -2;
            }
        }
        catch (Exception $e)
        {
            echo ("Erreur : " . $e);
        }
    }
    
    public function getUserData($token)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $token = mysqli_real_escape_string($conn, $token);
        
        $sql = "SELECT 
                    US.token as user_token, 
                    US.email as user_email, 
                    US.first_name as user_first_name, 
                    US.last_name as user_last_name, 
                    UA.agency_id as user_agency_id, 
                    US.role_id as user_role_id, 
                    US.first_login as user_first_login
                FROM rpps_user US
                INNER JOIN rpps_user_agency UA on UA.user_id = US.user_id
                WHERE US.token = '$token'";

        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                if($data['user_email'] === NULL)
                {
                    return -1;
                }
                else
                {
                    $this->token = $data['user_token'];
                    $this->email = $data['user_email'];
                    $this->first_name = $data['user_first_name'];
                    $this->last_name = $data['user_last_name'];
                    //$this->agency_id = $data['user_agency_id'];
                    $this->role_id = $data['user_role_id'];
                    $role_id = $data['user_role_id']; //pour construire le tableau des roles possibles à créer
                    $this->first_login = $data['user_first_login'];
                    mysqli_free_result($result);
                }
            }
            else
            {
                return -2;
            }
        }
        catch (Exception $e)
        {
            echo ("Erreur : " . $e);
        }
        
        
        
        $sql = "SELECT
                    RE.region_id as region_id,
                    RE.code as code,
                    RE.libelle as libelle,
                    AR.agency_id
                FROM rpps_region RE
                INNER JOIN rpps_user_region UR on UR.region_id = RE.region_id
                INNER JOIN rpps_agency_region AR on AR.region_id = RE.region_id
                INNER JOIN rpps_user US on US.user_id = UR.user_id
                WHERE US.token = '$token'";

        $data = null;
        
        try {
            if ($result = mysqli_query($conn, $sql))
            {
                while ($line = mysqli_fetch_assoc($result))
                {
                    $data[] = $line;
                }
            }
            else
            {
                return -3;
            }
        }
        catch (Exception $e)
        {
            echo ("Erreur : " . $e);
        }
        
        $method = "aes-256-ctr";
        $password = $token;
        $option = FALSE;
        
        //$qrcode = openssl_encrypt($qrcode, $method, $password, $option);

        if (count($data) > 0)
        {
            for($i = 0; $i < count($data); $i++)
            {
                $data[$i]['region_token'] = openssl_encrypt($data[$i]['libelle'], $method, $password, $option);

            }
            $this->region_array = $data;
            mysqli_free_result($result);
            //return 0;
        }
        else
        {
            $this->region_array = NULL;
            mysqli_free_result($result);
            //return 1;
        }
        
        
        //Génère le tableau des agences de l'utilisateur
        $sql = "SELECT 
                    AG.agency_id as agency_id, 
                    AG.agency_name as agency_name
                FROM rpps_agency AG
                INNER JOIN rpps_user_agency UA on UA.agency_id = AG.agency_id
                INNER JOIN rpps_user US on US.user_id = UA.user_id
                WHERE US.token = '$token'";
        
        $data = null;
        
        try {
            if ($result = mysqli_query($conn, $sql))
            {
                while ($line = mysqli_fetch_assoc($result))
                {
                    $data[] = $line;
                }
            }
            else
            {
                return -4;
            }
        }
        catch (Exception $e)
        {
            echo ("Erreur : " . $e);
        }
        
        $method = "aes-256-ctr";
        $password = $token;
        $option = FALSE;
        
        //$qrcode = openssl_encrypt($qrcode, $method, $password, $option);
        
        if (count($data) > 0)
        {
            for($i = 0; $i < count($data); $i++)
            {
                $data[$i]['agency_token'] = openssl_encrypt($data[$i]['agency_name'], $method, $password, $option);
                
            }
            $this->agency_array = $data;
            mysqli_free_result($result);
            //return 0;
        }
        else
        {
            $this->agency_array = NULL;
            mysqli_free_result($result);
            //return 0;
        }
        
        
        $sql = "SELECT 
                    RC.creatable_role_id,
                    RO.label
                FROM rpps_user US
                INNER JOIN rpps_role_can_create RC ON RC.role_id = US.role_id
                INNER JOIN rpps_role RO ON RO.role_id = RC.creatable_role_id
                WHERE US.token = '$token'";
        
        $data = null;
        
        try {
            if ($result = mysqli_query($conn, $sql))
            {
                while ($line = mysqli_fetch_assoc($result))
                {
                    $data[] = $line;
                }
            }
            else
            {
                return -5;
            }
        }
        catch (Exception $e)
        {
            echo ("Erreur : " . $e);
        }
        
        
        //$qrcode = openssl_encrypt($qrcode, $method, $password, $option);
        
        if (count($data) > 0)
        {
            $this->can_create_roles = $data;
            mysqli_free_result($result);
            return 0;
        }
        else
        {
            $this->can_create_roles = NULL;
            mysqli_free_result($result);
            return 0;
        }
        
        
        
        
        
    }
       
    public function userLogin($email, $password) //Renvoie le token de l'utilisateur si login Ok
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $email = mysqli_real_escape_string($conn, $email);
        $password = mysqli_real_escape_string($conn, $password);
        
        $password = hash("sha256", $password);
        
        $sql = "SELECT US.token as user_token, US.first_login as user_first_login
                FROM rpps_user US
                WHERE US.email = '$email'
                    and US.password = '$password'";
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                if ($data['user_token'] === NULL)
                {
                    return -1; //login impossible
                }
                else
                {
                    $this->token = $data['user_token'];
                    $this->first_login = $data['user_first_login'];
                    return 0;
                }
            }
            else
            {
                return -2;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
    }
    
    public function updateUserPassword($token, $password) // Change le mot de passe de l'utilisateur
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $token = mysqli_real_escape_string($conn, $token);
        $password = mysqli_real_escape_string($conn, $password);
        
        $password = hash("sha256", $password);
        
        $sql = "UPDATE rpps_user SET 
                PASSWORD = '$password' 
                WHERE TOKEN = '$token'";

        try
        {
            if (mysqli_query($conn, $sql))
            {
                return 0;
            }
            else
            {
                return -1;
            }
        }
        catch (Exception $e)
        {
            echo ("Erreur : " . $e->getMessage());
        }
    }
    
}
?>