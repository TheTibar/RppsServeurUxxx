<?php
namespace Classes;

use Exception; //à réactiver

include_once dirname(__FILE__) . '/db_connect.php';
include_once dirname(__FILE__) . '/User.php';
include_once dirname(__FILE__) . '/Log.php';



class Region 
{
    private $region_id;
    private $code;
    private $libelle;
    private $region_doctors_array = [];
    
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
       
    public function getRegionIdByToken($region_token, $user_token)
    {

        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $region_token = mysqli_real_escape_string($conn, $region_token);
        $user_token = mysqli_real_escape_string($conn, $user_token);
        
        $method = "aes-256-ctr";
        $password = $user_token;
        $option = FALSE;
        
        $region_libelle = openssl_decrypt($region_token, $method, $password, $option);
        $region_libelle = mysqli_real_escape_string($conn, $region_libelle);
        
        $sql = "SELECT RE.region_id as region_id
                FROM rpps_region RE
                WHERE RE.libelle = _utf8mb4'$region_libelle' COLLATE utf8mb4_unicode_ci";
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                //echo(count($data));
                if($data['region_id'] === NULL)
                {
                    return -99; //erreur grave, le code js a été changé
                }
                else
                {
                    $this->region_id = $data['region_id'];
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
       
    public function getRegionCode($region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
       
        $region_id = mysqli_real_escape_string($conn, $region_id);
        //On va chercher à compter les données dans les bases new et current de region_id
        $sql = "SELECT RE.code as region_code, RE.region_id as region_id, RE.libelle as region_libelle
                FROM rpps_region RE
                WHERE RE.region_id = $region_id";
        //echo($sql);
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                if($data['region_code'] ===  NULL)
                {
                    return -1;
                }
                else
                {
                    $this->code = $data['region_code'];
                    $this->region_id = $data['region_id'];
                    $this->libelle = $data['region_libelle'];
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
    
    public function updateDoctorUser($region_id, $dspl, $user_id, $process_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $Log = new Log();
        $Log->writeLogNoEcho($region_id, "Début mise à jour DOCTEUR - UTILISATEUR", $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        
        $lst_identifiant_delete = "'" . implode( "', '", array_column($dspl,'doc_identifiant'))  . "'";
        
        //echo($lst_identifiant_delete);
        
        
        

        $Log->writeLogNoEcho($region_id, "Suppression des données obsolètes", $process_id);
        $sql = "DELETE FROM rpps_doctor_user 
                WHERE identifiant_PP in (" . $lst_identifiant_delete . ")
                    AND region_id = $region_id";
        
        mysqli_begin_transaction($conn);
        
        //echo(nl2br($sql . "\n"));
        
        //return -1;

        if (! $result = mysqli_query($conn, $sql))
        {
            mysqli_rollback($conn);
            $Log->writeLogNoEcho($region_id, "Erreur suppression", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin mise à jour  DOCTEUR - UTILISATEUR", $process_id);
            return -3;
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Suppression OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Création des nouveaux liens : " . count($dspl), $process_id);
        }
        

        $User = new User();
        
        
        for($i = 0; $i < count($dspl); $i++)
        {
            //echo($dspl[$i]['doc_identifiant']);
            $identifiant_pp = $dspl[$i]['doc_identifiant'];
            $user_token = $dspl[$i]['sales_pro_token_new'];
            
            $Log->writeLogNoEcho($region_id, "Récupération user_id", $process_id);
            $result = $User->getUserId($user_token);
            
            switch ($result) {
                case 0:
                    //response(200, "region_tables_ok", NULL);
                    $sales_pro_id = $User->__get('user_id');
                    $Log->writeLogNoEcho($region_id, "Récupération user_id OK", $process_id);
                    break;
                case -1:
                    $Log->writeLogNoEcho($region_id, "Récupération user_id KO", $process_id);
                    $Log->writeLogNoEcho($region_id, "Fin mise à jour  DOCTEUR - UTILISATEUR", $process_id);
                    return -6; //proposer de relancer
                    break;
                case -2:
                    $Log->writeLogNoEcho($region_id, "Récupération user_id KO", $process_id);
                    $Log->writeLogNoEcho($region_id, "Fin mise à jour  DOCTEUR - UTILISATEUR", $process_id);
                    return -7; //proposer de relancer
                    break;
            }
            
            $lien = $i + 1;
            
            $Log->writeLogNoEcho($region_id, "Création lien " . $lien, $process_id);
            
            $sql = "INSERT INTO rpps_doctor_user
                    (user_id, identifiant_pp, link_type_id, region_id, process_id)
                    SELECT
                    $sales_pro_id, '$identifiant_pp', link_type_id, $region_id, $process_id
                    FROM rpps_link_type
                    WHERE default_link_type = 2";
            
            //echo(nl2br($sql . "\n"));

            if (! $result = mysqli_query($conn, $sql))
            {
                $Log->writeLogNoEcho($region_id, "Création lien " . $lien . " KO", $process_id);
                $Log->writeLogNoEcho($region_id, "Fin mise à jour  DOCTEUR - UTILISATEUR", $process_id);
                mysqli_rollback($conn);
                return -8;
            }
            else
            {
                $Log->writeLogNoEcho($region_id, "Création lien " . $lien . " OK (" . $identifiant_pp . "->" . $sales_pro_id . ")", $process_id);
            }   
        }
        $Log->writeLogNoEcho($region_id, "Fin mise à jour  DOCTEUR - UTILISATEUR", $process_id);
        mysqli_commit($conn);
        return 0;

    }
    
    public function getRegionDoctors($region_id, $region_code)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        //Une requête par région de l'agence
        $region_id = mysqli_real_escape_string($conn, $region_id);
        $region_code = mysqli_real_escape_string($conn, $region_code);
        
        


        
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
                return -3;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
    
    $this->region_doctors_array = $data_doctors;
    return 0;
 
    }
    
}
?>