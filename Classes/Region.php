<?php
namespace Classes;
use Exception; //à réactiver

include_once dirname(__FILE__) . '/db_connect.php';
include_once dirname(__FILE__) . '/SalesPro.php';



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
                FROM rpps_mstr_region RE
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
    
    public function updateDoctorSalesProLink($region_id, $dspl, $user_id, $process_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        $result = $this->getRegionCode($region_id);
        
        //var_dump($dspl);
        
        switch($result) {
            case 0:
                $region_code = $this->__get('code');
                break;
            case -1:
                return -1;
                break;
            case -2:
                return -2;
                break;
        }
        
        //echo(nl2br($region_code . "\n"));
        //$dspl = mysqli_real_escape_string($conn, $dspl);
        
        //echo('udspl');
        //var_dump($dspl);
        
        $lst_identifiant_delete = "'" . implode( "', '", array_column($dspl,'doc_identifiant'))  . "'";
        
        //echo($lst_identifiant_delete);
        
        
        $sql = "START TRANSACTION";
        //echo(nl2br($sql . "\n"));
        /*        */
        mysqli_query($conn, $sql);

        
        $sql = "DELETE FROM rpps_" . $region_code . "_doctor_sales_pro_link 
                WHERE identifiant_PP in (" .$lst_identifiant_delete . ")";
        
        //echo(nl2br($sql . "\n"));
        /**/
        if (! $result = mysqli_query($conn, $sql))
        {
            $sql = "ROLLBACK";
            mysqli_query($conn, $sql);
            return -3;
        }
        
        $SalesPro = new SalesPro();
        
        
        for($i = 0; $i < count($dspl); $i++)
        {
            //echo($dspl[$i]['doc_identifiant']);
            $identifiant_pp = $dspl[$i]['doc_identifiant'];
            
            $result = $SalesPro->getSalesProIdByToken($dspl[$i]['sales_pro_token_new'], $region_id);
            
            switch ($result) {
                case 0:
                    //response(200, "region_tables_ok", NULL);
                    $sales_pro_id = $SalesPro->__get('sales_pro_id');
                    break;
                case -1:
                    return -1; //proposer de relancer
                    break;
                case -2:
                    return -2; //proposer de relancer
                    break;
                case -3:
                    return -6; //proposer de relancer
                    break;
                case -4:
                    return -7; //proposer de relancer
                    break;
            }
            $sql = "INSERT INTO rpps_" . $region_code . "_doctor_sales_pro_link
                    (identifiant_pp, sales_pro_id, link_type_id, process_id)
                    SELECT
                    '$identifiant_pp', $sales_pro_id, link_type_id, $process_id
                    FROM rpps_" . $region_code . "_link_type
                    WHERE default_link_type = 2";
            
            //echo(nl2br($sql . "\n"));
            /**/
            if (! $result = mysqli_query($conn, $sql))
            {
                $sql = "ROLLBACK";
                mysqli_query($conn, $sql);
                return -8;
            }
            
        }
        
        $sql = "COMMIT";
        //echo(nl2br($sql . "\n"));
        /**/
        mysqli_query($conn, $sql);
        return 0;
        
    }
    
    public function getRegionDoctors($region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        //Une requête par région de l'agence
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        $result = $this->getRegionCode($region_id);
        
        switch ($result) {
            case 0:
                $region_code = $this->__get('code');
                break;
            case -1:
                return -1;
                break;
            case -2:
                return -2;
                break;
        }
        
        $sql = "SELECT DISTINCT
                    	CD.Identifiant_PP as doc_identifiant,
                    	CD.Code_civilite as doc_civilite,
                        CD.Nom_d_exercice as doc_name,
                        CD.Prenom_d_exercice as doc_first_name,
                        PF.profession_id as profession_id,
                        CD.Libelle_savoir_faire as doc_speciality,
                        SP.sales_pro_token
                    FROM rpps_" . $region_code . "_current_data CD
                    INNER JOIN rpps_" . $region_code . "_doctor_sales_pro_link DSP on DSP.identifiant_pp = CD.Identifiant_PP
                    INNER JOIN rpps_" . $region_code . "_sales_pro SP on SP.sales_pro_id = DSP.sales_pro_id
                    INNER JOIN rpps_" . $region_code . "_profession_filter PF on PF.label = CD.Libelle_savoir_faire
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