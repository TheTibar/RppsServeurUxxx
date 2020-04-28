<?php
namespace Classes;
use Exception;

include_once dirname(__FILE__) . '/db_connect.php';

class Agency
{
    private $agency_id;
    private $agency_name;
    private $region_array = [];
    private $region_doctors_array = [];
    private $region_speciality = [];
    private $region_sales_pro = [];

    private $sales_pro_array = [];
    private $max_display_order;
    
    
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
    
    public function createAgency($agency_name) //Crée un agence avec le nom passé en paramètre
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $agency_name = mysqli_real_escape_string($conn, $agency_name);
        
        $sql = "INSERT INTO agency (agency_name) 
                VALUES ('$agency_name')";
            //echo $sql;
            try
            {
                if (mysqli_query($mysql_db_conn, $sql))
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
                echo ("Erreur : " . $e);
            }
        
    }
       
    public function createAgencyRegionLink($agency_id, $region_id, $user_id_creation) //Crée un lien entre une agence et une région, en identifiant le créateur du lien
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $agency_id = mysqli_real_escape_string($conn, $agency_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        $user_id_creation = mysqli_real_escape_string($conn, $user_id_creation);
        
       
        $sql = "INSERT INTO rpps_mstr_agency_region (agency_id, region_id, user_id_creation)
                VALUES ($agency_id, $region_id, $user_id_creation)";
            //echo $sql;
            try
            {
                if (mysqli_query($mysql_db_conn, $sql))
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
                echo ("Erreur : " . $e);
            }
    }
    
    public function getAgencyInfo($agency_id)  //Renvoie l'id, le nom et la liste des régions de l'agence identifiée
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $agency_id = mysqli_real_escape_string($conn, $agency_id);
        
        $sql = "SELECT AG.agency_name as agency_name
                FROM rpps_mstr_agency AG
                WHERE AG.agency_id = $agency_id";

        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                if($data['agency_name'] === NULL)
                {
                    return -1;
                }
                else
                {
                    $this->agency_name = $data['agency_name'];
                    $this->agency_id = $agency_id;
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
            
        $sql = "SELECT RE.region_id as region_id, RE.code as region_code, RE.libelle as region_libelle
                FROM rpps_mstr_agency AG
                INNER JOIN rpps_mstr_agency_region AR on AR.agency_id = AG.agency_id
                INNER JOIN rpps_mstr_region RE on RE.region_id = AR.region_id
                WHERE AG.agency_id = $agency_id";
        
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
        
        if (count($data) > 0)
        {
            $this->region_array = $data;
            mysqli_free_result($result);
            return 0;
        }
        else
        {
            $this->region_array = NULL;
            mysqli_free_result($result);
            return 0;
        }
            
            
        
    }
    
    public function getAgencyIdByToken($agency_token, $user_token)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $agency_token = mysqli_real_escape_string($conn, $agency_token);
        $user_token = mysqli_real_escape_string($conn, $user_token);
        
        $method = "aes-256-ctr";
        $password = $user_token;
        $option = FALSE;
        
        $agency_name = openssl_decrypt($agency_token, $method, $password, $option);
        $agency_name = mysqli_real_escape_string($conn, $agency_name);
        
        $sql = "SELECT AG.agency_id as agency_id
                FROM rpps_agency AG
                WHERE AG.agency_name = _utf8mb4'$agency_name' COLLATE utf8mb4_unicode_ci";

        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                //echo(count($data));
                if($data['agency_id'] === NULL)
                {
                    return -99; //erreur grave, le code js a été changé
                }
                else
                {
                    $this->agency_id = $data['agency_id'];
                    return 0;
                }
            }
            else
            {
                //echo(mysqli_error($conn));
                return -2;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
    }

    public function getSalesProByAgency($agency_id) 
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $agency_id = mysqli_real_escape_string($conn, $agency_id);

        //On récupère tous les utilisateurs de l'agence
        //tout utilisateur est commercial potentiel
        $sql = "SELECT
                    US.user_id as user_id,
                    US.token as user_token,
                    US.first_name as first_name,
                    US.last_name as last_name,
                    US.email as email,
                    RE.libelle as region,
                    US.color as color
                FROM rpps_user US
                INNER JOIN rpps_user_agency UA on UA.user_id = US.user_id
                INNER JOIN rpps_user_region UR on UR.user_id = US.user_id
                INNER JOIN rpps_agency_region AR on AR.region_id = UR.region_id and AR.agency_id = UA.agency_id
                INNER JOIN rpps_region RE on RE.region_id = UR.region_id
                INNER JOIN rpps_role RO on RO.role_id = US.role_id
                WHERE UA.agency_id = $agency_id
                AND RO.label <> 'ADMIN' 
                ORDER BY US.display_order";

        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = [];
                while ($line = mysqli_fetch_assoc($result))
                {
                    $data[] = $line;
                }
                if (count($data) > 0)
                {
                    $this->sales_pro_array = $data;
                    return 0;
                }
                else
                {
                    return 1;
                }
            }
            else
            {
                //echo(mysqli_error($conn));
                return -1;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
    }

    public function getMaxDisplayOrder($agency_id) //avec prepared statement
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        
        $sql = "SELECT coalesce(max(display_order), 0)
                FROM rpps_user US
                INNER JOIN rpps_user_agency UA on UA.user_id = US.user_id
                WHERE UA.agency_id = ?
                AND display_order < 999900";
        
        //echo($sql);
        //echo($agency_id);
        //prepared statement
        if($stmt = mysqli_prepare($conn, $sql))
        {
            if(mysqli_stmt_bind_param($stmt, "i", $agency_id))
            {
                if(mysqli_stmt_execute($stmt))
                {
                    if(mysqli_stmt_bind_result($stmt, $max_display_order))
                    {
                        if(mysqli_stmt_fetch($stmt))
                        {
                            //echo("agency : ");
                            //var_dump($max_display_order);
                            $this->max_display_order = $max_display_order;
                            return 0;
                            // Close statement
                            mysqli_stmt_close($stmt);
                            // Close connection
                            mysqli_close($conn);
                        }
                        else
                        {
                            return -2;
                            // Close statement
                            mysqli_stmt_close($stmt);
                            // Close connection
                            mysqli_close($conn);
                        }
                    }
                    else
                    {
                        return -2;
                        // Close statement
                        mysqli_stmt_close($stmt);
                        // Close connection
                        mysqli_close($conn);
                    }
                }
                else
                {
                    return -2;
                    // Close statement
                    mysqli_stmt_close($stmt);
                    // Close connection
                    mysqli_close($conn);
                }
            }
            else 
            {
                return -2;
                // Close statement
                mysqli_stmt_close($stmt);
                // Close connection
                mysqli_close($conn);
            }
        }
        else
        {
            return -2;
            // Close statement
            mysqli_stmt_close($stmt);
            // Close connection
            mysqli_close($conn);
        }
        
        
    }

    public function getDoctorsByAgency($agency_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        //Une requête par région de l'agence
        $agency_id = mysqli_real_escape_string($conn, $agency_id);
        
        //Récupérer la liste des régions de l'agence filtré par utilisateur
        $sql = "SELECT
                	RE.region_id as region_id,
                    RE.libelle as libelle,
                    RE.code as code
                FROM rpps_region RE
                INNER JOIN rpps_agency_region AR on AR.region_id = RE.region_id
                WHERE AR.agency_id = $agency_id";
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data_region = [];
                while ($line = mysqli_fetch_assoc($result))
                {
                    $data_region[] = $line;
                }
                if (count($data_region) > 0)
                {
                    $this->region_array = $data_region;
                    //return 0;
                }
                else
                {
                    return 1;
                }
            }
            else
            {
                //echo(mysqli_error($conn));
                return -1;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
        
        
        $data_doctors = [];
        
        for($i = 0; $i < count($data_region) ; $i++)
        {
            $region_code = $data_region[$i]['code'];
            $region_id = $data_region[$i]['region_id'];
            $sql = "SELECT DISTINCT
                    	CD.Identifiant_PP as doc_identifiant, 
                    	CD.Code_civilite as doc_civilite, 
                        CD.Nom_d_exercice as doc_name, 
                        CD.Prenom_d_exercice as doc_first_name, 
                        PF.profession_id as profession_id,
                        CD.Libelle_savoir_faire as doc_speciality,
                        US.token
                    FROM rpps_current_data CD
                    INNER JOIN rpps_doctor_user DU on DU.identifiant_pp = CD.Identifiant_PP
                    INNER JOIN rpps_user US on US.user_id = DU.user_id
                    INNER JOIN rpps_profession_filter PF on PF.label = CD.Libelle_savoir_faire
                    WHERE CD.region_id = $region_id
                    ORDER BY CD.Libelle_savoir_faire, CD.Nom_d_exercice, CD.Prenom_d_exercice";
            
            //echo(nl2br($sql . "\n"));
            
            try
            {
                if ($result = mysqli_query($conn, $sql))
                {
                    
                    while ($line = mysqli_fetch_assoc($result))
                    {
                        $data_doctors[$region_code]['doctors'][] = $line;
                    }
                }
                else
                {
                    //echo(mysqli_error($conn));
                    return -2;
                }
            }
            catch (Exception $e)
            {
                return ($e->getMessage());
            }
        }
        
        $this->region_doctors_array = $data_doctors;
        
        $data_speciality = [];
        
        for($i = 0; $i < count($data_region) ; $i++)
        {
            $region_code = $data_region[$i]['code'];
            $region_id = $data_region[$i]['region_id'];
            $sql = "SELECT DISTINCT
                        PF.profession_id,
                        CD.Libelle_savoir_faire as doc_speciality
                    FROM rpps_current_data CD
                    INNER JOIN rpps_profession_filter PF on PF.label = CD.Libelle_savoir_faire
                    WHERE CD.region_id = $region_id
                    ORDER BY CD.Libelle_savoir_faire";
            
            try
            {
                if ($result = mysqli_query($conn, $sql))
                {
                    
                    while ($line = mysqli_fetch_assoc($result))
                    {
                        $data_speciality[$region_code]['speciality'][] = $line;
                    }
                }
                else
                {
                    //echo(mysqli_error($conn));
                    return -3;
                }
            }
            catch (Exception $e)
            {
                return ($e->getMessage());
            }
        }
        
        $this->region_speciality = $data_speciality;
        
        $data_sales_pro = [];
        
        for($i = 0; $i < count($data_region) ; $i++)
        {
            $region_code = $data_region[$i]['code'];
            $region_id = $data_region[$i]['region_id'];
            $sql = "SELECT 
                        US.last_name,
                        US.first_name,
                        US.token
                    FROM rpps_user US
                    INNER JOIN rpps_user_region UR on UR.user_id = US.user_id
                    INNER JOIN rpps_role RO on RO.role_id = US.role_id
                    WHERE UR.region_id = $region_id
                        AND RO.label <> 'ADMIN'
                    ORDER BY US.last_name, US.first_name";
            
            //echo(nl2br($sql . "\n"));
            
            try
            {
                if ($result = mysqli_query($conn, $sql))
                {
                    
                    while ($line = mysqli_fetch_assoc($result))
                    {
                        $data_sales_pro[$region_code]['sales_pro'][] = $line;
                    }
                }
                else
                {
                    //echo(mysqli_error($conn));
                    return -4;
                }
            }
            catch (Exception $e)
            {
                return ($e->getMessage());
            }
        }
        
        $this->region_sales_pro = $data_sales_pro;
        
        
    }
    
    
    public function getDoctorsByAgencyAndUser($agency_id, $user_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        //Une requête par région de l'agence
        $agency_id = mysqli_real_escape_string($conn, $agency_id);
        
        //Récupérer la liste des régions de l'agence filtré par utilisateur
        $sql = "SELECT
                	RE.region_id as region_id,
                    RE.libelle as libelle,
                    RE.code as code
                FROM rpps_region RE
                INNER JOIN rpps_agency_region AR on AR.region_id = RE.region_id
                INNER JOIN rpps_user_region UR on UR.region_id = AR.region_id
                WHERE AR.agency_id = $agency_id
                    AND UR.user_id = $user_id";
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data_region = [];
                while ($line = mysqli_fetch_assoc($result))
                {
                    $data_region[] = $line;
                }
                if (count($data_region) > 0)
                {
                    $this->region_array = $data_region;
                    //return 0;
                }
                else
                {
                    return 1;
                }
            }
            else
            {
                //echo(mysqli_error($conn));
                return -1;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
        
        
        $data_doctors = [];
        
        for($i = 0; $i < count($data_region) ; $i++)
        {
            $region_code = $data_region[$i]['code'];
            $region_id = $data_region[$i]['region_id'];
            $sql = "SELECT DISTINCT
                    	CD.Identifiant_PP as doc_identifiant,
                    	CD.Code_civilite as doc_civilite,
                        CD.Nom_d_exercice as doc_name,
                        CD.Prenom_d_exercice as doc_first_name,
                        PF.profession_id as profession_id,
                        CD.Libelle_savoir_faire as doc_speciality,
                        US.token
                    FROM rpps_current_data CD
                    INNER JOIN rpps_doctor_user DU on DU.identifiant_pp = CD.Identifiant_PP AND DU.region_id = CD.region_id
                    INNER JOIN rpps_user US on US.user_id = DU.user_id
                    INNER JOIN rpps_profession_filter PF on PF.label = CD.Libelle_savoir_faire
                    WHERE CD.region_id = $region_id
                    ORDER BY CD.Libelle_savoir_faire, CD.Nom_d_exercice, CD.Prenom_d_exercice";
            
            //echo(nl2br($sql . "\n"));
            
            try
            {
                if ($result = mysqli_query($conn, $sql))
                {
                    
                    while ($line = mysqli_fetch_assoc($result))
                    {
                        $data_doctors[$region_code]['doctors'][] = $line;
                    }
                }
                else
                {
                    //echo(mysqli_error($conn));
                    return -2;
                }
            }
            catch (Exception $e)
            {
                return ($e->getMessage());
            }
        }
        
        $this->region_doctors_array = $data_doctors;
        
        $data_speciality = [];
        
        for($i = 0; $i < count($data_region) ; $i++)
        {
            $region_code = $data_region[$i]['code'];
            $region_id = $data_region[$i]['region_id'];
            $sql = "SELECT DISTINCT
                        PF.profession_id,
                        CD.Libelle_savoir_faire as doc_speciality
                    FROM rpps_current_data CD
                    INNER JOIN rpps_profession_filter PF on PF.label = CD.Libelle_savoir_faire
                    WHERE CD.region_id = $region_id
                    ORDER BY CD.Libelle_savoir_faire";
            
            try
            {
                if ($result = mysqli_query($conn, $sql))
                {
                    
                    while ($line = mysqli_fetch_assoc($result))
                    {
                        $data_speciality[$region_code]['speciality'][] = $line;
                    }
                }
                else
                {
                    //echo(mysqli_error($conn));
                    return -3;
                }
            }
            catch (Exception $e)
            {
                return ($e->getMessage());
            }
        }
        
        $this->region_speciality = $data_speciality;
        
        $data_sales_pro = [];
        
        for($i = 0; $i < count($data_region) ; $i++)
        {
            $region_code = $data_region[$i]['code'];
            $region_id = $data_region[$i]['region_id'];
            $sql = "SELECT
                        US.last_name,
                        US.first_name,
                        US.token
                    FROM rpps_user US
                    INNER JOIN rpps_user_region UR on UR.user_id = US.user_id
                    INNER JOIN rpps_role RO on RO.role_id = US.role_id
                    WHERE UR.region_id = $region_id
                        AND RO.label <> 'ADMIN'
                    ORDER BY US.last_name, US.first_name";
            
            //echo(nl2br($sql . "\n"));
            
            try
            {
                if ($result = mysqli_query($conn, $sql))
                {
                    
                    while ($line = mysqli_fetch_assoc($result))
                    {
                        $data_sales_pro[$region_code]['sales_pro'][] = $line;
                    }
                }
                else
                {
                    //echo(mysqli_error($conn));
                    return -4;
                }
            }
            catch (Exception $e)
            {
                return ($e->getMessage());
            }
        }
        
        $this->region_sales_pro = $data_sales_pro;
        
        
    }
    
    
}