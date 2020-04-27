<?php
namespace Classes;
//use Exception;

include_once dirname(__FILE__) . '/db_connect.php';
include_once dirname(__FILE__) . '/Region.php';
include_once dirname(__FILE__) . '/Log.php';

use \Classes\Region;

class Doctor 
{
    private $sales_pro_id;
    private $name;
    private $first_name;
    private $email;
    private $sales_pro_token;
    private $send_email;
    private $display_order;
    private $color;
    private $is_new;
    private $user_id;
    private $user_id_creation;
    private $nb_remove;
    private $nb_create;
    private $remove_doctors_array = [];
    private $remove_doctors_detail_array = [];
    private $create_doctors_array = [];
    private $create_doctors_detail_array = [];
    private $movement_summary_array = [];
    private $speciality_array = [];

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
    
    public function createRemoveDoctors($process_id, $region_id)
    {
        $Log = new Log();
        
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);

        
        $Log->writeLogNoEcho($region_id, "Début de la synchronisation", $process_id);
        $Log->writeLogNoEcho($region_id, "Début création des lignes REMOVE", $process_id);
        
        $sql = "INSERT INTO rpps_tmp_identifiant_pp (identifiant_pp, user_id, process_id, region_id, histo_type)
                    SELECT DISTINCT
                	CD.Identifiant_PP, DU.user_id, $process_id, $region_id, 'REMOVE'
                	FROM rpps_doctor_user DU
                	INNER JOIN rpps_current_data CD on CD.identifiant_pp = DU.identifiant_pp and CD.region_id = DU.region_id
                	LEFT OUTER JOIN rpps_new_data ND on ND.Identifiant_PP = DU.identifiant_pp
                	WHERE ND.Identifiant_PP is null
                    AND DU.region_id = $region_id";
        
        
        if ($sql_result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Fin création des lignes REMOVE OK", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Fin création des lignes REMOVE KO", $process_id);
            return -3;
        }
        
        $Log->writeLogNoEcho($region_id, "Début comptage des mouvements REMOVE", $process_id);
        $sql = "SELECT histo_type, count(*) as nb_movement
                    FROM rpps_tmp_identifiant_pp
                    WHERE process_id = $process_id
                    AND region_id = $region_id
                    AND histo_type = 'REMOVE'";
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = mysqli_fetch_assoc($result);
            if ($data['nb_movement'] > 0)
            {
                $Log->writeLogNoEcho($region_id, "Nb REMOVE : " . $data['nb_movement'], $process_id);
                $this->nb_remove = $data['nb_movement'];
                $Log->writeLogNoEcho($region_id, "Fin comptage des mouvements REMOVE", $process_id);
                return 0;
            }
            else 
            {
                $Log->writeLogNoEcho($region_id, "Pas de mouvement REMOVE", $process_id);
                $Log->writeLogNoEcho($region_id, "Fin comptage des mouvements REMOVE", $process_id);
                return 1;
            }
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Erreur comptage mouvements REMOVE", $process_id);
            return -4;
        }
    }
    
    public function createCreateDoctors($process_id, $region_id)
    {
        $Log = new Log();
        
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        
        $Log->writeLogNoEcho($region_id, "Début de la synchronisation", $process_id);
        $Log->writeLogNoEcho($region_id, "Début création des lignes CREATE", $process_id);
        
        $sql = "INSERT INTO rpps_tmp_identifiant_pp (identifiant_pp, user_id, process_id, region_id, histo_type)
                SELECT ND.Identifiant_PP, US.user_id, $process_id, $region_id, 'CREATE'
                FROM rpps_new_data ND
                INNER JOIN rpps_user_region UR on UR.region_id = ND.region_id
                INNER JOIN rpps_user US on US.user_id = UR.user_id and US.email = 'NA'
                LEFT OUTER JOIN rpps_current_data CD on CD.Identifiant_PP = ND.Identifiant_PP and CD.region_id = ND.region_id
                WHERE ND.region_id = $region_id
                AND CD.Identifiant_PP is null
                ORDER BY ND.Identifiant_PP";
        

        
        if ($sql_result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Fin création des lignes CREATE OK", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Fin création des lignes CREATE KO", $process_id);
            return -3;
        }
        
        $Log->writeLogNoEcho($region_id, "Début comptage des mouvements CREATE", $process_id);
        $sql = "SELECT histo_type, count(*) as nb_movement
                    FROM rpps_tmp_identifiant_pp
                    WHERE process_id = $process_id
                    AND region_id = $region_id
                    AND histo_type = 'CREATE'";
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = mysqli_fetch_assoc($result);
            if ($data['nb_movement'] > 0)
            {
                $Log->writeLogNoEcho($region_id, "Nb CREATE : " . $data['nb_movement'], $process_id);
                $this->nb_create = $data['nb_movement'];
                $Log->writeLogNoEcho($region_id, "Fin comptage des mouvements CREATE", $process_id);
                return 0;
            }
            else
            {
                $Log->writeLogNoEcho($region_id, "Pas de mouvement CREATE", $process_id);
                $Log->writeLogNoEcho($region_id, "Fin comptage des mouvements CREATE", $process_id);
                return 1;
            }
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Erreur comptage mouvements CREATE", $process_id);
            return -4;
        }
    }
    
    public function getLeavingDoctors($process_id, $region_id)
    {
        //pas encore testé
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        
        $sql = "SELECT 
                    US.user_id as user_id, 
                    US.token as token, 
                    US.last_name as last_name, 
                    US.first_name as first_name, 
                    count(*) as nb_leaving_doctors
                FROM rpps_doctor_user DU
                INNER JOIN rpps_user US on US.user_id = DU.user_id
                INNER JOIN rpps_tmp_identifiant_pp TMP on TMP.identifiant_pp = DU.identifiant_pp
                WHERE TMP.process_id = $process_id
                AND TMP.region_id = $region_id
                AND TMP.histo_type = 'REMOVE'
                GROUP BY US.user_id, US.token, US.last_name, US.first_name
                ORDER BY US.display_order";
        
        if ($sql_result = mysqli_query($conn, $sql))
        {
            $data = [];
            while ($line = mysqli_fetch_assoc($sql_result))
            {
                $data[] = $line;
            }
            if (count($data) > 0)
            {
                $this->remove_doctors_array = $data;
                return 0;
                //response(200, 'leaving_doctors_number_by_sales_pro', $data);
            }
            else
            {
                return 1;
                //response(200, 'no_more_leaving_doctors', NULL);
            }
        }
        else
        {
            return -3;
            //response(200, 'error_retrieving_leaving_doctors', NULL);
        }
        
    }
    
    public function getLeavingDoctorsDetailByUserId($user_id, $process_id, $region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        $user_id = mysqli_real_escape_string($conn, $user_id);
        

        
        //Renvoie ID, NOM, PRENOM
        $sql = "SELECT DISTINCT
                coalesce(CD.identifiant_pp, '') as identifiant_pp,
            	coalesce(CD.Nom_d_exercice, '') as name,
                coalesce(CD.Prenom_d_exercice, '') as first_name,
                COALESCE(CD.Libelle_savoir_faire, '') as speciality,
                coalesce(CD.Libelle_commune_coord_structure_, '') as city
                FROM rpps_doctor_user DU
                inner join rpps_current_data CD on CD.identifiant_pp = DU.identifiant_pp
                inner join rpps_user US on US.user_id = DU.user_id
                inner join rpps_tmp_identifiant_pp TMP on TMP.identifiant_pp = DU.identifiant_pp
                where TMP.process_id = $process_id
                and TMP.region_id = $region_id
                and TMP.histo_type = 'REMOVE'
                and US.user_id = $user_id
                order by coalesce(CD.Nom_d_exercice, ''), coalesce(CD.Prenom_d_exercice, '')";
        
        if ($sql_result = mysqli_query($conn, $sql))
        {
            $data = [];
            while ($line = mysqli_fetch_assoc($sql_result))
            {
                $data[] = $line;
            }
            if (count($data) > 0)
            {
                $this->remove_doctors_detail_array = $data;
                return 0;
                
            }
            else
            {
                return 1;
            }
            
        }
        else
        {
            return -3;
        }
    }
    
    public function getArrivingDoctors($process_id, $region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);


        
        $sql = "SELECT ND.libelle_savoir_faire, PF.profession_id, count(distinct ND.Identifiant_PP) as nb_new_doctors
                FROM rpps_new_data ND
                INNER JOIN rpps_profession_filter PF on PF.label = ND.Libelle_savoir_faire
                INNER JOIN rpps_tmp_identifiant_pp TMP on TMP.Identifiant_PP = ND.Identifiant_PP
                WHERE TMP.process_id = $process_id
                AND TMP.region_id = $region_id
                AND TMP.histo_type = 'CREATE'
                GROUP BY ND.Libelle_savoir_faire, PF.profession_id";
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = [];
            while ($line = mysqli_fetch_assoc($result))
            {
                $data[] = $line;
            }
            if (count($data) > 0)
            {
                $this->create_doctors_array = $data;
                return 0;
            }
            else
            {
                return 1;
            }
        }
        else
        {
            return -4;
        }
        
    }
    
    public function deleteDoctorsBySalesProId($sales_pro_id, $process_id, $region_id)
    {
        $Log = new Log();
        
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        $sales_pro_id = mysqli_real_escape_string($conn, $sales_pro_id);
        
        
        $Log->writeLogNoEcho($region_id, "Début de la suppression", $process_id);
        $Log->writeLogNoEcho($region_id, "Début de la sauvegarde", $process_id);
        $Log->writeLogNoEcho($region_id, "Sauvegarde DATA", $process_id);
        
        $sql = "INSERT INTO rpps_histo_data(Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, process_id, region_id, histo_type)
                    SELECT Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, $process_id, $region_id, 'REMOVE'
                    FROM rpps_current_data CD
                    WHERE CD.Identifiant_PP IN
                    (
                    SELECT DISTINCT TMP.Identifiant_PP
                    FROM rpps_tmp_identifiant_pp TMP
                    where TMP.user_id = $sales_pro_id
                    and TMP.process_id = $process_id
                    and TMP.region_id = $region_id
                    and TMP.histo_type = 'REMOVE')
                    and CD.region_id = $region_id";
        
        mysqli_begin_transaction($conn);
        
        if($result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Sauvegarde DATA OK", $process_id);
        }
        else 
        {
            $Log->writeLogNoEcho($region_id, "Sauvegarde DATA KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la sauvegarde", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la suppression", $process_id);
            mysqli_rollback($conn);
            return -3;
        }
        
        $Log->writeLogNoEcho($region_id, "Sauvegarde DOCTOR_USER", $process_id);
        $sql = "INSERT into rpps_histo_doctor_user (identifiant_pp, user_id, link_type_id, created_on, updated_on, process_id, region_id, histo_type)
                    SELECT identifiant_pp, user_id, link_type_id, created_on, updated_on, $process_id, $region_id, 'REMOVE'
                    FROM rpps_doctor_user DU
                    WHERE DU.Identifiant_PP IN
                    (
                    SELECT DISTINCT TMP.Identifiant_PP
                    FROM rpps_tmp_identifiant_pp TMP
                    WHERE TMP.user_id = $sales_pro_id
                    AND TMP.process_id = $process_id
                    AND TMP.region_id = $region_id
                    AND histo_type = 'REMOVE')
                    AND DU.region_id = $region_id";
        
        if($result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Sauvegarde DOCTOR_USER OK", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Sauvegarde DOCTOR_USER KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la sauvegarde", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la suppression", $process_id);
            mysqli_rollback($conn);
            return -4;
        }
        $Log->writeLogNoEcho($region_id, "Fin de la sauvegarde", $process_id);
        $Log->writeLogNoEcho($region_id, "Début de la suppression des données", $process_id);
        $Log->writeLogNoEcho($region_id, "Suppression DATA", $process_id);

        $sql = "DELETE FROM rpps_current_data 
                    WHERE Identifiant_PP IN
                        (
                        SELECT DISTINCT TMP.Identifiant_PP
                        FROM rpps_tmp_identifiant_pp TMP
                        where TMP.user_id = $sales_pro_id
                        and TMP.process_id = $process_id
                        and TMP.region_id = $region_id
                        and TMP.histo_type = 'REMOVE')
                        and region_id = $region_id";
        //echo($sql);

        if($result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Suppression DATA OK", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Suppression DATA KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la suppression des données", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la suppression", $process_id);
            mysqli_rollback($conn);
            return -5;
        }
        
        $Log->writeLogNoEcho($region_id, "Suppression DOCTOR_USER", $process_id);
        $sql = "DELETE FROM rpps_doctor_user
                    WHERE Identifiant_PP IN
                        (
                        SELECT DISTINCT TMP.Identifiant_PP
                        FROM rpps_tmp_identifiant_pp TMP
                        WHERE TMP.user_id = $sales_pro_id
                        AND TMP.process_id = $process_id
                        AND TMP.region_id = $region_id
                        AND histo_type = 'REMOVE')
                        AND region_id = $region_id";
        
        if($result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Suppression DOCTOR_USER OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la suppression des données", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la suppression", $process_id);
            mysqli_commit($conn);
            return 0;
            
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Suppression DOCTOR_USER KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la suppression des données", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la suppression", $process_id);
            mysqli_rollback($conn);
            return -6;
        }
    }

    public function getArrivingDoctorDetailBySpeciality($process_id, $profession_id, $region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        $profession_id = mysqli_real_escape_string($conn, $profession_id);
        
        
        $sql = "SELECT ND.Identifiant_PP as identifiant_pp, ND.Libelle_civilite as libelle_civilite, ND.Nom_d_exercice as nom_exercice, ND.Prenom_d_exercice as prenom_exercice, ND.Libelle_commune_coord_structure_ as commune
            FROM rpps_new_data ND
            INNER JOIN rpps_profession_filter PF on PF.label = ND.Libelle_savoir_faire and PF.profession_id = $profession_id
    		INNER JOIN rpps_tmp_identifiant_pp TMP on TMP.Identifiant_PP = ND.Identifiant_PP AND TMP.region_id = ND.region_id
    		WHERE TMP.process_id = $process_id
    		AND TMP.histo_type = 'CREATE'
            AND TMP.region_id = $region_id
            ORDER BY ND.Nom_d_exercice";
        
        if ($sql_result = mysqli_query($conn, $sql))
        {
            while ($line = mysqli_fetch_assoc($sql_result))
            {
                $data[] = $line;
            }
            if (count($data) > 0)
            {
                $this->create_doctors_detail_array = $data;
                return 0;
            }
            else
            {
                return 1;
            }
        }
        else
        {
            return -3;
        }
    }
    
    public function createDoctorsByProcessId($process_id, $region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        $Region = new Region();
        $Log = new Log();


        $Log->writeLogNoEcho($region_id, "Début de la création", $process_id);
        $Log->writeLogNoEcho($region_id, "Début de la sauvegarde", $process_id);
        $Log->writeLogNoEcho($region_id, "Sauvegarde DATA", $process_id);
        
        mysqli_begin_transaction($conn);
        
        $sql = "INSERT INTO rpps_histo_data(Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, process_id, region_id, histo_type)
                    SELECT Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, $process_id, $region_id, 'CREATE'
                    FROM rpps_new_data ND
                    WHERE ND.Identifiant_PP IN
                    (
                    SELECT DISTINCT TMP.Identifiant_PP
                    FROM rpps_tmp_identifiant_pp TMP
                    WHERE TMP.process_id = $process_id
                    AND TMP.region_id = $region_id
                    AND histo_type = 'CREATE')
                    AND ND.region_id = $region_id";
        
        if($result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Sauvegarde DATA OK", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Sauvegarde DATA KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la sauvegarde", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la création", $process_id);
            mysqli_rollback($conn);
            return -3;
        }
        
        $Log->writeLogNoEcho($region_id, "Sauvegarde DOCTOR_USER", $process_id);
        $sql = "INSERT INTO rpps_histo_doctor_user (identifiant_pp, user_id, link_type_id, created_on, updated_on, process_id, region_id, histo_type)
                    SELECT DISTINCT identifiant_pp, user_id, link_type_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, $process_id, $region_id, 'CREATE'
                    FROM rpps_tmp_identifiant_pp TMP
                    INNER JOIN rpps_link_type LT ON 1=1 and LT.default_link_type = 1
                    WHERE TMP.process_id = $process_id
                    AND TMP.region_id = $region_id
                    AND histo_type = 'CREATE'";
        

        
        if($result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Sauvegarde DOCTOR_USER OK", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Sauvegarde DOCTOR_USER KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la sauvegarde", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la création", $process_id);
            mysqli_rollback($conn);
            return -4;
        }
        $Log->writeLogNoEcho($region_id, "Fin de la sauvegarde", $process_id);
        
        $Log->writeLogNoEcho($region_id, "Début de la création des données", $process_id);
        $Log->writeLogNoEcho($region_id, "Création DATA", $process_id);
        
        $sql = "INSERT INTO rpps_current_data(Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, region_id, process_id)
                    SELECT Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, $region_id, $process_id
                    FROM rpps_histo_data HD
                    WHERE HD.process_id = $process_id
                    AND HD.region_id = $region_id
                    AND HD.histo_type = 'CREATE'";
        
        if($result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Création DATA OK", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Création DATA KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la création des données", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la création", $process_id);
            mysqli_rollback($conn);
            return -5;
        }
        
        $Log->writeLogNoEcho($region_id, "Création DOCTOR_USER", $process_id);
        $sql = "INSERT INTO rpps_doctor_user (identifiant_pp, user_id, link_type_id, region_id, process_id)
                    SELECT DISTINCT identifiant_pp, user_id, link_type_id, region_id, process_id
                    FROM rpps_histo_doctor_user DU
                    WHERE DU.process_id = $process_id
                    AND DU.region_id = $region_id
                    AND DU.histo_type = 'CREATE'";
        
        if($result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Création DOCTOR_USER OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la création des données", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la création", $process_id);
            mysqli_commit($conn);
            return 0;
            
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Création DOCTOR_USER KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la création des données", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin de la création", $process_id);
            mysqli_rollback($conn);
            return -6;
        }
    }
    
    public function makeDetailsDoctorsConsistent($process_id, $region_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        $Log = new Log();
        
        
        $Log->writeLogNoEcho($region_id, "Début cohérence DETAILS DOCTEURS", $process_id);
        $Log->writeLogNoEcho($region_id, "Début création OLD DETAIL dans HISTO", $process_id);
        
        mysqli_begin_transaction($conn);
        
        $sql = "INSERT INTO rpps_histo_data(Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, process_id, region_id, histo_type)
            SELECT Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, $process_id, $region_id, 'OLD_DETAIL_DATA'
            from rpps_current_data CD
            where not exists
            (
            select *
            from rpps_new_data ND
            where
            ND.Type_d_identifiant_PP=CD.Type_d_identifiant_PP
            and ND.Identifiant_PP=CD.Identifiant_PP
            and ND.Identification_nationale_PP=CD.Identification_nationale_PP
            and ND.Code_civilite_d_exercice=CD.Code_civilite_d_exercice
            and ND.Libelle_civilite_d_exercice=CD.Libelle_civilite_d_exercice
            and ND.Code_civilite=CD.Code_civilite
            and ND.Libelle_civilite=CD.Libelle_civilite
            and ND.Nom_d_exercice=CD.Nom_d_exercice
            and ND.Prenom_d_exercice=CD.Prenom_d_exercice
            and ND.Code_profession=CD.Code_profession
            and ND.Libelle_profession=CD.Libelle_profession
            and ND.Code_categorie_professionnelle=CD.Code_categorie_professionnelle
            and ND.Libelle_categorie_professionnelle=CD.Libelle_categorie_professionnelle
            and ND.Code_type_savoir_faire=CD.Code_type_savoir_faire
            and ND.Libelle_type_savoir_faire=CD.Libelle_type_savoir_faire
            and ND.Code_savoir_faire=CD.Code_savoir_faire
            and ND.Libelle_savoir_faire=CD.Libelle_savoir_faire
            and ND.Code_mode_exercice=CD.Code_mode_exercice
            and ND.Libelle_mode_exercice=CD.Libelle_mode_exercice
            and ND.Numero_SIRET_site=CD.Numero_SIRET_site
            and ND.Numero_SIREN_site=CD.Numero_SIREN_site
            and ND.Numero_FINESS_site=CD.Numero_FINESS_site
            and ND.Numero_FINESS_etablissement_juridique=CD.Numero_FINESS_etablissement_juridique
            and ND.Identifiant_technique_de_la_structure=CD.Identifiant_technique_de_la_structure
            and ND.Raison_sociale_site=CD.Raison_sociale_site
            and ND.Enseigne_commerciale_site=CD.Enseigne_commerciale_site
            and ND.Complement_destinataire_coord_structure_=CD.Complement_destinataire_coord_structure_
            and ND.Complement_point_geographique_coord_structure_=CD.Complement_point_geographique_coord_structure_
            and ND.Numero_Voie_coord_structure_=CD.Numero_Voie_coord_structure_
            and ND.Indice_repetition_voie_coord_structure_=CD.Indice_repetition_voie_coord_structure_
            and ND.Code_type_de_voie_coord_structure_=CD.Code_type_de_voie_coord_structure_
            and ND.Libelle_type_de_voie_coord_structure_=CD.Libelle_type_de_voie_coord_structure_
            and ND.Libelle_Voie_coord_structure_=CD.Libelle_Voie_coord_structure_
            and ND.Mention_distribution_coord_structure_=CD.Mention_distribution_coord_structure_
            and ND.Bureau_cedex_coord_structure_=CD.Bureau_cedex_coord_structure_
            and ND.Code_postal_coord_structure_=CD.Code_postal_coord_structure_
            and ND.Code_commune_coord_structure_=CD.Code_commune_coord_structure_
            and ND.Libelle_commune_coord_structure_=CD.Libelle_commune_coord_structure_
            and ND.Code_pays_coord_structure_=CD.Code_pays_coord_structure_
            and ND.Libelle_pays_coord_structure_=CD.Libelle_pays_coord_structure_
            and ND.Telephone_coord_structure_=CD.Telephone_coord_structure_
            and ND.Telephone_2_coord_structure_=CD.Telephone_2_coord_structure_
            and ND.Telecopie_coord_structure_=CD.Telecopie_coord_structure_
            and ND.Adresse_e_mail_coord_structure_=CD.Adresse_e_mail_coord_structure_
            and ND.Code_Departement_structure_=CD.Code_Departement_structure_
            and ND.Libelle_Departement_structure_=CD.Libelle_Departement_structure_
            and ND.Ancien_identifiant_de_la_structure=CD.Ancien_identifiant_de_la_structure
            and ND.Autorite_d_enregistrement=CD.Autorite_d_enregistrement
            and ND.Code_secteur_d_activite=CD.Code_secteur_d_activite
            and ND.Libelle_secteur_d_activite=CD.Libelle_secteur_d_activite
            and ND.Code_section_tableau_pharmaciens=CD.Code_section_tableau_pharmaciens
            and ND.Libelle_section_tableau_pharmaciens=CD.Libelle_section_tableau_pharmaciens
            and ND.region_id=CD.region_id
            )
            and CD.region_id = $region_id";
        
        if($sql_result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Création OLD DETAIL dans HISTO OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin création OLD DETAIL dans HISTO", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Création OLD DETAIL dans HISTO KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin création OLD DETAIL", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin cohérence DETAILS DOCTEURS", $process_id);
            mysqli_rollback($conn);
            return -3;
        }
        
        $Log->writeLogNoEcho($region_id, "Début suppression OLD DETAIL dans CURRENT_DATA", $process_id);
        $sql = "DELETE FROM rpps_current_data
                WHERE EXISTS
                (
                    SELECT *
                    FROM rpps_histo_data HD
                    WHERE
                    HD.Type_d_identifiant_PP=rpps_current_data.Type_d_identifiant_PP
                    and HD.Identifiant_PP=rpps_current_data.Identifiant_PP
                    and HD.Identification_nationale_PP=rpps_current_data.Identification_nationale_PP
                    and HD.Code_civilite_d_exercice=rpps_current_data.Code_civilite_d_exercice
                    and HD.Libelle_civilite_d_exercice=rpps_current_data.Libelle_civilite_d_exercice
                    and HD.Code_civilite=rpps_current_data.Code_civilite
                    and HD.Libelle_civilite=rpps_current_data.Libelle_civilite
                    and HD.Nom_d_exercice=rpps_current_data.Nom_d_exercice
                    and HD.Prenom_d_exercice=rpps_current_data.Prenom_d_exercice
                    and HD.Code_profession=rpps_current_data.Code_profession
                    and HD.Libelle_profession=rpps_current_data.Libelle_profession
                    and HD.Code_categorie_professionnelle=rpps_current_data.Code_categorie_professionnelle
                    and HD.Libelle_categorie_professionnelle=rpps_current_data.Libelle_categorie_professionnelle
                    and HD.Code_type_savoir_faire=rpps_current_data.Code_type_savoir_faire
                    and HD.Libelle_type_savoir_faire=rpps_current_data.Libelle_type_savoir_faire
                    and HD.Code_savoir_faire=rpps_current_data.Code_savoir_faire
                    and HD.Libelle_savoir_faire=rpps_current_data.Libelle_savoir_faire
                    and HD.Code_mode_exercice=rpps_current_data.Code_mode_exercice
                    and HD.Libelle_mode_exercice=rpps_current_data.Libelle_mode_exercice
                    and HD.Numero_SIRET_site=rpps_current_data.Numero_SIRET_site
                    and HD.Numero_SIREN_site=rpps_current_data.Numero_SIREN_site
                    and HD.Numero_FINESS_site=rpps_current_data.Numero_FINESS_site
                    and HD.Numero_FINESS_etablissement_juridique=rpps_current_data.Numero_FINESS_etablissement_juridique
                    and HD.Identifiant_technique_de_la_structure=rpps_current_data.Identifiant_technique_de_la_structure
                    and HD.Raison_sociale_site=rpps_current_data.Raison_sociale_site
                    and HD.Enseigne_commerciale_site=rpps_current_data.Enseigne_commerciale_site
                    and HD.Complement_destinataire_coord_structure_=rpps_current_data.Complement_destinataire_coord_structure_
                    and HD.Complement_point_geographique_coord_structure_=rpps_current_data.Complement_point_geographique_coord_structure_
                    and HD.Numero_Voie_coord_structure_=rpps_current_data.Numero_Voie_coord_structure_
                    and HD.Indice_repetition_voie_coord_structure_=rpps_current_data.Indice_repetition_voie_coord_structure_
                    and HD.Code_type_de_voie_coord_structure_=rpps_current_data.Code_type_de_voie_coord_structure_
                    and HD.Libelle_type_de_voie_coord_structure_=rpps_current_data.Libelle_type_de_voie_coord_structure_
                    and HD.Libelle_Voie_coord_structure_=rpps_current_data.Libelle_Voie_coord_structure_
                    and HD.Mention_distribution_coord_structure_=rpps_current_data.Mention_distribution_coord_structure_
                    and HD.Bureau_cedex_coord_structure_=rpps_current_data.Bureau_cedex_coord_structure_
                    and HD.Code_postal_coord_structure_=rpps_current_data.Code_postal_coord_structure_
                    and HD.Code_commune_coord_structure_=rpps_current_data.Code_commune_coord_structure_
                    and HD.Libelle_commune_coord_structure_=rpps_current_data.Libelle_commune_coord_structure_
                    and HD.Code_pays_coord_structure_=rpps_current_data.Code_pays_coord_structure_
                    and HD.Libelle_pays_coord_structure_=rpps_current_data.Libelle_pays_coord_structure_
                    and HD.Telephone_coord_structure_=rpps_current_data.Telephone_coord_structure_
                    and HD.Telephone_2_coord_structure_=rpps_current_data.Telephone_2_coord_structure_
                    and HD.Telecopie_coord_structure_=rpps_current_data.Telecopie_coord_structure_
                    and HD.Adresse_e_mail_coord_structure_=rpps_current_data.Adresse_e_mail_coord_structure_
                    and HD.Code_Departement_structure_=rpps_current_data.Code_Departement_structure_
                    and HD.Libelle_Departement_structure_=rpps_current_data.Libelle_Departement_structure_
                    and HD.Ancien_identifiant_de_la_structure=rpps_current_data.Ancien_identifiant_de_la_structure
                    and HD.Autorite_d_enregistrement=rpps_current_data.Autorite_d_enregistrement
                    and HD.Code_secteur_d_activite=rpps_current_data.Code_secteur_d_activite
                    and HD.Libelle_secteur_d_activite=rpps_current_data.Libelle_secteur_d_activite
                    and HD.Code_section_tableau_pharmaciens=rpps_current_data.Code_section_tableau_pharmaciens
                    and HD.Libelle_section_tableau_pharmaciens=rpps_current_data.Libelle_section_tableau_pharmaciens
                    and HD.region_id=rpps_current_data.region_id
                    and HD.process_id = $process_id
                    and HD.histo_type = 'OLD_DETAIL_DATA'
                    )
                and region_id=$region_id";
        
        if($sql_result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Suppression OLD DETAIL dans CURRENT_DATA OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin suppression OLD DETAIL dans CURRENT_DATA", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Suppression OLD DETAIL dans CURRENT_DATA KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin suppression OLD DETAIL dans CURRENT_DATA", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin cohérence DETAILS DOCTEURS", $process_id);
            mysqli_rollback($conn);
            return -4;
        }
        
        $Log->writeLogNoEcho($region_id, "Début création NEW DETAIL dans HISTO", $process_id);
        
        $sql = "INSERT INTO rpps_histo_data(Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, process_id, region_id, histo_type)
                SELECT Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, $process_id, $region_id, 'NEW_DETAIL_DATA'
                from rpps_new_data ND
                where not exists
                (
                select *
                from rpps_current_data CD
                where
                ND.Type_d_identifiant_PP=CD.Type_d_identifiant_PP
                and ND.Identifiant_PP=CD.Identifiant_PP
                and ND.Identification_nationale_PP=CD.Identification_nationale_PP
                and ND.Code_civilite_d_exercice=CD.Code_civilite_d_exercice
                and ND.Libelle_civilite_d_exercice=CD.Libelle_civilite_d_exercice
                and ND.Code_civilite=CD.Code_civilite
                and ND.Libelle_civilite=CD.Libelle_civilite
                and ND.Nom_d_exercice=CD.Nom_d_exercice
                and ND.Prenom_d_exercice=CD.Prenom_d_exercice
                and ND.Code_profession=CD.Code_profession
                and ND.Libelle_profession=CD.Libelle_profession
                and ND.Code_categorie_professionnelle=CD.Code_categorie_professionnelle
                and ND.Libelle_categorie_professionnelle=CD.Libelle_categorie_professionnelle
                and ND.Code_type_savoir_faire=CD.Code_type_savoir_faire
                and ND.Libelle_type_savoir_faire=CD.Libelle_type_savoir_faire
                and ND.Code_savoir_faire=CD.Code_savoir_faire
                and ND.Libelle_savoir_faire=CD.Libelle_savoir_faire
                and ND.Code_mode_exercice=CD.Code_mode_exercice
                and ND.Libelle_mode_exercice=CD.Libelle_mode_exercice
                and ND.Numero_SIRET_site=CD.Numero_SIRET_site
                and ND.Numero_SIREN_site=CD.Numero_SIREN_site
                and ND.Numero_FINESS_site=CD.Numero_FINESS_site
                and ND.Numero_FINESS_etablissement_juridique=CD.Numero_FINESS_etablissement_juridique
                and ND.Identifiant_technique_de_la_structure=CD.Identifiant_technique_de_la_structure
                and ND.Raison_sociale_site=CD.Raison_sociale_site
                and ND.Enseigne_commerciale_site=CD.Enseigne_commerciale_site
                and ND.Complement_destinataire_coord_structure_=CD.Complement_destinataire_coord_structure_
                and ND.Complement_point_geographique_coord_structure_=CD.Complement_point_geographique_coord_structure_
                and ND.Numero_Voie_coord_structure_=CD.Numero_Voie_coord_structure_
                and ND.Indice_repetition_voie_coord_structure_=CD.Indice_repetition_voie_coord_structure_
                and ND.Code_type_de_voie_coord_structure_=CD.Code_type_de_voie_coord_structure_
                and ND.Libelle_type_de_voie_coord_structure_=CD.Libelle_type_de_voie_coord_structure_
                and ND.Libelle_Voie_coord_structure_=CD.Libelle_Voie_coord_structure_
                and ND.Mention_distribution_coord_structure_=CD.Mention_distribution_coord_structure_
                and ND.Bureau_cedex_coord_structure_=CD.Bureau_cedex_coord_structure_
                and ND.Code_postal_coord_structure_=CD.Code_postal_coord_structure_
                and ND.Code_commune_coord_structure_=CD.Code_commune_coord_structure_
                and ND.Libelle_commune_coord_structure_=CD.Libelle_commune_coord_structure_
                and ND.Code_pays_coord_structure_=CD.Code_pays_coord_structure_
                and ND.Libelle_pays_coord_structure_=CD.Libelle_pays_coord_structure_
                and ND.Telephone_coord_structure_=CD.Telephone_coord_structure_
                and ND.Telephone_2_coord_structure_=CD.Telephone_2_coord_structure_
                and ND.Telecopie_coord_structure_=CD.Telecopie_coord_structure_
                and ND.Adresse_e_mail_coord_structure_=CD.Adresse_e_mail_coord_structure_
                and ND.Code_Departement_structure_=CD.Code_Departement_structure_
                and ND.Libelle_Departement_structure_=CD.Libelle_Departement_structure_
                and ND.Ancien_identifiant_de_la_structure=CD.Ancien_identifiant_de_la_structure
                and ND.Autorite_d_enregistrement=CD.Autorite_d_enregistrement
                and ND.Code_secteur_d_activite=CD.Code_secteur_d_activite
                and ND.Libelle_secteur_d_activite=CD.Libelle_secteur_d_activite
                and ND.Code_section_tableau_pharmaciens=CD.Code_section_tableau_pharmaciens
                and ND.Libelle_section_tableau_pharmaciens=CD.Libelle_section_tableau_pharmaciens
                and ND.region_id=CD.region_id
                )
                and ND.region_id = $region_id";
        
        if($sql_result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Création NEW DETAIL dans HISTO OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin création NEW DETAIL dans HISTO", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Création NEW DETAIL dans HISTO KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin création NEW DETAIL dans HISTO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin cohérence DETAILS DOCTEURS", $process_id);
            mysqli_rollback($conn);
            return -5;
        }
        
        $sql = "INSERT INTO rpps_current_data(Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, process_id, region_id)
                    SELECT Type_d_identifiant_PP,Identifiant_PP,Identification_nationale_PP,Code_civilite_d_exercice,Libelle_civilite_d_exercice,Code_civilite,Libelle_civilite,Nom_d_exercice,Prenom_d_exercice,Code_profession,Libelle_profession,Code_categorie_professionnelle,Libelle_categorie_professionnelle,Code_type_savoir_faire,Libelle_type_savoir_faire,Code_savoir_faire,Libelle_savoir_faire,Code_mode_exercice,Libelle_mode_exercice,Numero_SIRET_site,Numero_SIREN_site,Numero_FINESS_site,Numero_FINESS_etablissement_juridique,Identifiant_technique_de_la_structure,Raison_sociale_site,Enseigne_commerciale_site,Complement_destinataire_coord_structure_,Complement_point_geographique_coord_structure_,Numero_Voie_coord_structure_,Indice_repetition_voie_coord_structure_,Code_type_de_voie_coord_structure_,Libelle_type_de_voie_coord_structure_,Libelle_Voie_coord_structure_,Mention_distribution_coord_structure_,Bureau_cedex_coord_structure_,Code_postal_coord_structure_,Code_commune_coord_structure_,Libelle_commune_coord_structure_,Code_pays_coord_structure_,Libelle_pays_coord_structure_,Telephone_coord_structure_,Telephone_2_coord_structure_,Telecopie_coord_structure_,Adresse_e_mail_coord_structure_,Code_Departement_structure_,Libelle_Departement_structure_,Ancien_identifiant_de_la_structure,Autorite_d_enregistrement,Code_secteur_d_activite,Libelle_secteur_d_activite,Code_section_tableau_pharmaciens,Libelle_section_tableau_pharmaciens, $process_id, $region_id
                    FROM rpps_histo_data
                    WHERE process_id = $process_id
                    AND region_id = $region_id
                    AND histo_type = 'NEW_DETAIL_DATA'";
        
        
        $Log->writeLogNoEcho($region_id, "Début création NEW DETAIL dans CURRENT_DATA", $process_id);
        
        if($sql_result = mysqli_query($conn, $sql))
        {
            $Log->writeLogNoEcho($region_id, "Création NEW DETAIL dans CURRENT_DATA OK", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin création NEW DETAIL dans CURRENT_DATA", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin cohérence DETAILS DOCTEURS", $process_id);
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Création NEW DETAIL dans CURRENT_DATA KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin création NEW DETAIL dans CURRENT_DATA", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin cohérence DETAILS DOCTEURS", $process_id);
            mysqli_rollback($conn);
            return -6;
        }
        
        
        $sql = "SELECT histo_type, count(*) as nb_movement
                    FROM rpps_histo_data
                    WHERE process_id = $process_id
                    AND region_id = $region_id
                    GROUP BY histo_type";
        
        $Log->writeLogNoEcho($region_id, "Début comptage mouvements", $process_id);
        
        if($result = mysqli_query($conn, $sql))
        {
            $data = [];
            while ($line = mysqli_fetch_assoc($result))
            {
                $data[] = $line;
            }
            if (count($data) > 0)
            {
                $Log->writeLogNoEcho($region_id, "Comptage mouvements OK", $process_id);
                $Log->writeLogNoEcho($region_id, "Fin comptage mouvements", $process_id);
                $this->movement_summary_array = $data;
                mysqli_commit($conn);
                return 0;
            }
            else
            {
                $Log->writeLogNoEcho($region_id, "Comptage mouvements OK", $process_id);
                $Log->writeLogNoEcho($region_id, "Fin comptage mouvements", $process_id);
                mysqli_commit($conn);
                return 1;
            }
            
        }
        else
        {
            $Log->writeLogNoEcho($region_id, "Comptage mouvements KO", $process_id);
            $Log->writeLogNoEcho($region_id, "Fin comptage mouvements", $process_id);
            mysqli_rollback($conn);
            return -7;
        }
        

        
    }
    
    public function getAllSpecialities() {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $sql = "SELECT label as label
                FROM rpps_profession_filter";
        
        if ($sql_result = mysqli_query($conn, $sql))
        {
            $data = [];
            while ($line = mysqli_fetch_assoc($sql_result))
            {
                $data[] = $line;
            }
            if (count($data) > 0)
            {
                $this->speciality_array = $data;
                return 0;
            }
            else
            {
                return 1;
            }
        }
        else
        {
            return -1;
        }
    }
}

?>