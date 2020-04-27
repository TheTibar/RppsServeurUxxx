<?php
namespace Classes;
use Exception;

include_once dirname(__FILE__) . '/db_connect.php';
include_once dirname(__FILE__) . '/Log.php';
include_once dirname(__FILE__) . '/Region.php';

class RPPS
{
    private $current_line_count;
    private $new_data_line_count;
    private $current_distinct_count;
    private $new_data_distinct_count;
    private $current_last_update;
    private $new_data_last_update;
    private $creation_result;
    private $check_data_consistency = [];
    private $merge_summary_city = [];
    private $merge_summary_speciality = [];
    private $merge_summary_count_data = [];
    private $merge_summary_count_rep = [];
    
    
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
      
    public function compareRPPS($region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $region_id = mysqli_real_escape_string($conn, $region_id);
        //On va chercher à compter les données dans les bases new et current de region_id
        
        
        $sql = "SELECT count(*) as current_line_count 
                FROM rpps_current_data
                WHERE region_id = $region_id";
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                $this->current_line_count = $data['current_line_count'];
            }
            else
            {
                return -3;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
        $sql = "SELECT count(*) as new_data_line_count
                FROM rpps_new_data
                WHERE region_id = $region_id";

        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                $this->new_data_line_count = $data['new_data_line_count'];
            }
            else
            {
                return -4;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
        $sql = "SELECT count(distinct identifiant_pp) as current_distinct_count
                FROM rpps_current_data
                WHERE region_id = $region_id"
                ;
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                $this->current_distinct_count = $data['current_distinct_count'];
            }
            else
            {
                return -5;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
        $sql = "SELECT count(distinct identifiant_pp) as new_data_distinct_count
                FROM rpps_new_data
                WHERE region_id = $region_id";
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                $this->new_data_distinct_count = $data['new_data_distinct_count'];
            }
            else
            {
                return -6;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
        
        
        $sql = "SELECT DATE_FORMAT(max(updated_on), '%d/%m/%Y') as current_last_update
                FROM rpps_process PR 
                WHERE 
                PR.is_ok = 1
                AND step = 99
                AND name LIKE '%UPDATE COMPLETE%'
                AND PR.region_id = $region_id";

        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                $this->current_last_update = $data['current_last_update'];
            }
            else
            {
                return -7;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
        
        $sql = "SELECT DATE_FORMAT(max(updated_on), '%d/%m/%Y') as new_data_last_update
                FROM rpps_process PR 
                WHERE PR.is_ok = 1 
                AND PR.step = 99
                AND PR.name like '%CRON_Import_RPPS_Complet%'";
        
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                $this->new_data_last_update = $data['new_data_last_update'];
                return 0;
            }
            else
            {
                return -8;
            }
        }
        catch (Exception $e)
        {
            return ($e->getMessage());
        }
    }
    
    public function checkDataConsistency($region_id, $process_id)
    {
        
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $Log = new Log();
        
        $region_id = mysqli_real_escape_string($conn, $region_id);
        $process_id = mysqli_real_escape_string($conn, $process_id);
 
        $Log->writeLogNoEcho($region_id, "Début contrôle cohérence", $process_id);
        $Log->writeLogNoEcho($region_id, "Début comptage distincts Identifiant PP dans CD", $process_id);
        
        
        $sql = "SELECT count(distinct identifiant_pp) as distinct_cd
                FROM rpps_current_data CD
                WHERE CD.region_id = $region_id";
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = mysqli_fetch_assoc($result);
            $this->check_data_consistency['distinct_cd'] = $data['distinct_cd'];
            $Log->writeLogNoEcho($region_id, "Comptage distincts Identifiant PP dans CD OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage distincts Identifiant PP dans CD", $process_id);
        }
        else 
        {
            $Log->writeLogNoEcho($region_id, "Comptage distincts Identifiant PP dans CD KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage distincts Identifiant PP dans CD", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin contrôle cohérence", $process_id);
            return -3;
        }
        
        
        $sql = "SELECT count(distinct identifiant_pp) as distinct_dsp
                FROM rpps_doctor_user DU
                WHERE DU.region_id = $region_id";
        
        $Log->writeLogNoEcho($region_id, "Début comptage distincts Identifiant PP dans DU", $process_id);
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = mysqli_fetch_assoc($result);
            $this->check_data_consistency['distinct_dsp'] = $data['distinct_dsp'];
            $Log->writeLogNoEcho($region_id, "Comptage distincts Identifiant PP dans DU OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage distincts Identifiant PP dans DU", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Comptage distincts Identifiant PP dans DU KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage distincts Identifiant PP dans DU", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin contrôle cohérence", $process_id);
            return -4;
        }
        
        
        $sql = "SELECT count(*) as dsp
                FROM rpps_doctor_user DU
                WHERE DU.region_id = $region_id";
        
        $Log->writeLogNoEcho($region_id, "Début comptage Identifiant PP dans DU", $process_id);
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = mysqli_fetch_assoc($result);
            $this->check_data_consistency['dsp'] = $data['dsp'];
            $Log->writeLogNoEcho($region_id, "Comptage Identifiant PP dans DU OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage Identifiant PP dans DU", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Comptage Identifiant PP dans DSP KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage Identifiant PP dans DU", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin contrôle cohérence", $process_id);
            return -5;
        }
        
        $sql = "SELECT count(distinct CD.Identifiant_PP) as nb_cd_not_in_dsp
                FROM rpps_current_data CD
                LEFT OUTER JOIN rpps_doctor_user DU on DU.identifiant_pp = CD.Identifiant_PP and DU.region_id = CD.region_id
                WHERE 
                    DU.identifiant_pp is null
                    and CD.region_id = $region_id";
        
        $Log->writeLogNoEcho($region_id, "Début comptage Identifiant PP dans CD pas dans DU", $process_id);
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = mysqli_fetch_assoc($result);
            $this->check_data_consistency['nb_cd_not_in_dsp'] = $data['nb_cd_not_in_dsp'];
            $Log->writeLogNoEcho($region_id, "Comptage Identifiant PP dans CD pas dans DU OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage Identifiant PP dans CD pas dans DU", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Comptage Identifiant PP dans CD pas dans DU KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage Identifiant PP dans CD pas dans DU", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin contrôle cohérence", $process_id);
            return -6;
        }
        
        
        $sql = "SELECT count(distinct DU.Identifiant_PP) as nb_dsp_not_in_cd
                FROM rpps_doctor_user DU
                LEFT OUTER JOIN rpps_current_data CD on CD.identifiant_pp = DU.Identifiant_PP and CD.region_id = DU.region_id
                where CD.identifiant_pp is null
                AND DU.region_id = $region_id";
        
        $Log->writeLogNoEcho($region_id, "Début comptage Identifiant PP dans DU pas dans CD", $process_id);
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = mysqli_fetch_assoc($result);
            $this->check_data_consistency['nb_dsp_not_in_cd'] = $data['nb_dsp_not_in_cd'];
            $Log->writeLogNoEcho($region_id, "Comptage Identifiant PP dans DU pas dans CD OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage Identifiant PP dans DU pas dans CD", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Comptage Identifiant PP dans DSP pas dans CD KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage Identifiant PP dans DSP pas dans CD", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin contrôle cohérence", $process_id);
            return -7;
        }
        
        if($this->check_data_consistency['distinct_cd'] == $this->check_data_consistency['distinct_dsp'] && $this->check_data_consistency['distinct_dsp'] == $this->check_data_consistency['dsp'])
        {
            if ($this->check_data_consistency['nb_cd_not_in_dsp'] == 0 && $this->check_data_consistency['nb_dsp_not_in_cd'] == 0)
            {
                return 0;
            }
            else
            {
                return -8;
            }
        }
        else
        {
            return -9;
        }
        
        
        
    }
    
    public function checkFinalConsistency($region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        $Region = new Region();
        
        $result = $Region->getRegionCode($region_id);
        
        switch($result) {
            case 0:
                $region_code = $Region->__get('code');
                break;
            case -1:
                return -1;
            case -2:
                return -2;
        }
        
        $data_compt_data = [];
        $data_compt_rep = [];
        
        /*
        $sql = "SELECT *
                FROM
                (
                    SELECT
                    Libelle_commune_coord_structure_ as commune, count(Identifiant_PP) as nb_office, count(distinct Identifiant_PP) as nb_doctors, 'cd' as src
                    FROM rpps_" . $region_code . "_current_data CD
                    GROUP BY Libelle_commune_coord_structure_
                    UNION ALL
                    SELECT
                    Libelle_commune_coord_structure_ as commune, count(Identifiant_PP) as nb_office, count(distinct Identifiant_PP) as nb_doctors, 'nb' as src
                    FROM rpps_" . $region_code . "_new_data CD
                    GROUP BY Libelle_commune_coord_structure_
                ) DRV1
                ORDER BY DRV1.commune
                ";
        
        if ($result = mysqli_query($conn, $sql))
        {
            
            while ($line = mysqli_fetch_assoc($result))
            {
                $data_comm[] = $line;
            }
            if (count($data_comm) > 0)
            {
                $this->merge_summary_city_detail = $data_comm;
            }
            else 
            {
                $this->merge_summary_city_detail = NULL;
            }
        }
        else 
        {
            return -3;
        }
        
        $sql = "
                SELECT *
                FROM
                (
                    SELECT
                    Libelle_savoir_faire as Specialite, count(Identifiant_PP) as Nb_office, count(distinct Identifiant_PP) as Nb_doctors
                    FROM rpps_" . $region_code . "_current_data CD
                    GROUP BY Libelle_savoir_faire
                    UNION ALL
                    SELECT
                    Libelle_savoir_faire as Specialite, count(Identifiant_PP) as Nb_office, count(distinct Identifiant_PP) as Nb_doctors
                    FROM rpps_" . $region_code . "_new_data CD
                    GROUP BY Libelle_savoir_faire
                ) DRV1
                ORDER BY DRV1.Specialite
                ";
        
        
        if ($result = mysqli_query($conn, $sql))
        {
            
            while ($line = mysqli_fetch_assoc($result))
            {
                $data_spe[] = $line;
            }
            if (count($data_spe) > 0)
            {
                $this->merge_summary_speciality_detail = $data_spe;
            }
            else
            {
                $this->merge_summary_speciality_detail = NULL;
            }
        }
        else
        {
            return -4;
        }
        */
        
        $sql = "
                SELECT 'Données' as type, count(CD.Identifiant_PP) as nb_offices, count(DISTINCT CD.Identifiant_PP) as nb_doctors
                FROM rpps_" . $region_code . "_current_data CD
                ";
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data_compt_data = mysqli_fetch_assoc($result);
            if (count($data_compt_data) > 0)
            {
                $this->merge_summary_count_data = $data_compt_data;
            }
            else
            {
                $this->merge_summary_count_data = NULL;
            }
        }
        else
        {
            return -3;
        }
        
        $sql = "
                SELECT 'Référentiel' as type, count(ND.Identifiant_PP) as nb_offices, count(DISTINCT ND.Identifiant_PP) as nb_doctors
                FROM rpps_" . $region_code . "_new_data ND
                ";
        
        if ($result = mysqli_query($conn, $sql))
        {
            
            $data_compt_rep = mysqli_fetch_assoc($result);
            if (count($data_compt_rep) > 0)
            {
                $this->merge_summary_count_rep = $data_compt_rep;
            }
            else
            {
                $this->merge_summary_count_rep = NULL;
            }
        }
        else
        {
            return -4;
        }
        
        
        if (($data_compt_data['nb_offices'] === $data_compt_rep['nb_offices']) && ($data_compt_data['nb_doctors'] === $data_compt_rep['nb_doctors']))
        {
            return 0;
        }
        else 
        {
            return -5;
        }
        
        
    }
}
?>