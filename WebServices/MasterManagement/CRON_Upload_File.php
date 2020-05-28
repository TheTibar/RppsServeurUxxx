<?php


include_once __DIR__ .  '/../../Classes/db_connect.php';
include_once __DIR__ .  '/../../Classes/Log.php';
include_once __DIR__ .  '/../../Classes/Process.php';
use Classes\Log;
use Classes\Process;



$stillValid = TRUE;
$Log = new Log();
$Process = new Process();

$instance = \ConnectDB::getInstance();
$conn = $instance->getConnection();


/* A REACTIVER */


$create_process = $Process->newProcess(0, 'CRON_Import_RPPS_Complet', 0);


if ($stillValid && $create_process == 0)
{
    $stillValid = TRUE;
    $process_id = $Process->__get('process_id');
    $Log->writeLog("Création du process : " . $process_id, $process_id);
}
else
{
    $Log->writeLog("Erreur création du process", -1);
    $stillValid = FALSE;
    die();
}


//echo("step1");
/* A REACTIVER */
if ($stillValid && $Log->writeLog("Début récupération chemin RPPSFiles", $process_id))
{
    $sql = "SELECT param_value 
            FROM rpps_param 
            WHERE param_name = 'tmp_rpps_path'";
    $sql_result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($sql_result);
    
    if($data['param_value'] != "") 
    {
        $target_directory = __DIR__ . $data['param_value'];
        $Log->writeLog("Fin récupération chemin RPPSFiles : " . $target_directory, $process_id);
        $stillValid = TRUE;
    }
    else 
    {
        $Log->writeLog("Erreur récupération chemin RPPSFiles", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $Log->writeLog("Erreur récupération chemin RPPSFiles", $process_id);
    $stillValid = FALSE;
    die();
}



//echo("step2");
/* A REACTIVER */
if ($stillValid && $Log->writeLog("Début nettoyage répertoire RPPSFiles", $process_id))
{
    $files = glob($target_directory . '*'); // get all file names
    foreach($files as $file){ // iterate files
        if(is_file($file))
            unlink($file); // delete file
    }
    $Log->writeLog("Fin nettoyage répertoire RPPSFiles", $process_id);
    $stillValid = TRUE;
}
else
{
    $Log->writeLog("Erreur nettoyage répertoire RPPSFiles", $process_id);
    $stillValid = FALSE;
    die();
}


//echo("step3");

/* A REACTIVER */
if ($stillValid && $Log->writeLog("Début récupération chemin RPPS Annuaire santé", $process_id))
{
    //On récupère l'emplacement et le nom du fichier à télécharger
    $page = file_get_contents('https://annuaire.sante.fr/web/site-pro/extractions-publiques');
    
    $doc = new DOMDocument;
    libxml_use_internal_errors(true);
    $doc->loadHTML($page);
    //var_dump($page);
    //echo(nl2br("page length : " . strlen($page) . "\n"));
    $pos_PS_LibreAcces_ = strpos($page, 'PS_LibreAcces_');
    //echo(nl2br("pos_PS_LibreAcces_ : " . $pos_PS_LibreAcces_ . "\n"));
    $page_deb = substr($page, 0, $pos_PS_LibreAcces_);
    //echo(nl2br("page_deb length : " . strlen($page_deb) . "\n"));
    $page_fin = substr($page, $pos_PS_LibreAcces_, strlen($page));
    //echo(nl2br("page_fin length : " . strlen($page_fin) . "\n"));
    $pos_HTTPS = strripos($page_deb, 'https');
    //echo(nl2br("pos_HTTPS : " . $pos_HTTPS . "\n"));
    $pos_ZIP = strpos($page_fin, '.zip');
    //echo(nl2br("pos_ZIP : " . $pos_ZIP . "\n"));
    $url = substr($page_deb, $pos_HTTPS, strlen($page_deb));
    //echo(nl2br("lien complet debut : " . $link . "\n"));
    $url = $url . substr($page_fin, 0, $pos_ZIP + 4);
    //echo(nl2br("lien complet : " . $link . "\n"));
    $file_name = substr($page_fin, 0, $pos_ZIP + 4);
    
    $Log->writeLog("Fin récupération chemin RPPS Annuaire santé : " . $url, $process_id);
    $stillValid = TRUE;
}
else
{
    $Log->writeLog("Erreur récupération chemin RPPS Annuaire santé", $process_id);
    $stillValid = FALSE;
    die();
}


//echo("step4");

        
/* A REACTIVER */
if($stillValid && $Log->writeLog("Début téléchargement RPPS", $process_id) && file_put_contents($target_directory . $file_name, file_get_contents($url)))
{
    $Log->writeLog("Fin téléchargement RPPS : " . $file_name, $process_id);
    $stillValid = TRUE;
}
else 
{
    $Log->writeLog("Erreur téléchargement RPPS", $process_id);
    $stillValid = FALSE;
    die();
}


//echo("step5");
/* A REACTIVER */
if($stillValid && $Log->writeLog("Début dezippage RPPS", $process_id))
{
    $zip = new ZipArchive;
    $Log->writeLog("Fichier : " . $target_directory . $file_name, $process_id);
    if ($zip->open($target_directory . $file_name) === TRUE) 
    {
        $zip->extractTo($target_directory);
        $zip->close();
        $Log->writeLog("Fin dezippage RPPS", $process_id);
    }
    else 
    {
        $Log->writeLog("Erreur dezippage RPPS", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $Log->writeLog("Erreur dezippage RPPS", $process_id);
    die();
}


/* !!! A SUPPRIMER !!! 
$file_name = 'PS_LibreAcces_Personne_activite_202003181109.txt';
$target_directory = 'C:/wamp64/www/RppsServeur/RPPSFiles/';

/* !!! FIN A SUPPRIMER !!! */
        
/* A REACTIVER */ 
if($stillValid && $Log->writeLog('Début sauvegarde nom fichier', $process_id))
{
    $sql = "INSERT INTO rpps_file_history (file_name, process_id) 
            VALUES ('$file_name', $process_id)";
    if(mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin sauvegarde nom fichier : " . $file_name, $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur sauvegarde nom fichier", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $Log->writeLog("Erreur sauvegarde nom fichier", $process_id);
    $stillValid = FALSE;
    die();
}
      


if ($stillValid && $Log->writeLog("Début récupération nom fichier RPPS", $process_id))
{
    $finalFile = glob($target_directory . '*Personne_activite*.txt', GLOB_BRACE);
    if (count($finalFile) == 1)
    {
        $finalFile = strval($finalFile[0]);
        $Log->writeLog('Fin récupération nom fichier RPPS : ' . $finalFile, $process_id);
        $stillValid = TRUE;
    }
    else 
    {
        $Log->writeLog('Erreur récupération nom fichier RPPS (plusieurs fichiers)', $process_id);
        $stillValid = FALSE;
        die();
    }
}
else 
{
    $Log->writeLog('Erreur récupération nom fichier RPPS (plusieurs fichiers)', $process_id);
    $stillValid = FALSE;
    die();
}


$newDataTableName = 'rpps_new_data'; //ne pas commenter pour les tests

/* A REACTIVER */
if ($stillValid && $Log->writeLog("Début suppression table temporaire : " . $newDataTableName, $process_id))
{
    $sql = "DROP TABLE IF EXISTS $newDataTableName";
    $sql_result = mysqli_query($conn, $sql);
    
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin suppression table temporaire : " . $newDataTableName, $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur suppression table temporaire : " . $newDataTableName, $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $Log->writeLog("Erreur suppression table temporaire", $process_id);
    $stillValid = FALSE;
    die();
}



/* A REACTIVER */
if ($stillValid && $Log->writeLog("Début récupération structure RPPS", $process_id))
{
    ini_set('auto_detect_line_endings',TRUE);
    $handle = fopen($finalFile,'r');
    $data = fgetcsv($handle);
    if (!$data)
    {
        $Log->writeLog("Erreur récupération structure RPPS", $process_id);
        $stillValid = FALSE;
        die();
    }
    else
    {
        $str_lst_fields = $data[0];
        $str_lst_fields = str_replace("|", ",", $str_lst_fields);
        $Log->writeLog("Fin récupération structure RPPS", $process_id);
        $stillValid = TRUE;
    }
}
else
{
    $Log->writeLog("Erreur récupération structure RPPS", $process_id);
    $stillValid = FALSE;
    die();
}


/* A REACTIVER */
if ($stillValid && $Log->writeLog("Début suppression caractères spéciaux", $process_id))
{
    $str_lst_fields = remove_accents($str_lst_fields);
    $str_lst_fields = preg_replace ('/[^0-9a-zA-Z,]/', '_', $str_lst_fields);
    $str_lst_fields = str_replace("__", "_", $str_lst_fields);
    $str_lst_fields = str_replace(",", " varchar(100), ", $str_lst_fields); //on ne prend que les 100 prmeiers caractères
    $str_lst_fields = substr($str_lst_fields, 0, -2);
    $Log->writeLog("Fin suppression caractères spéciaux", $process_id);
    $stillValid = TRUE;
}
else
{
    $stillValid = FALSE;
    die();
}


/* A REACTIVER */
if($stillValid && $Log->writeLog("Début création de la table temporaire", $process_id))
{
    $sql = "CREATE TABLE $newDataTableName (" . $str_lst_fields . ") ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    echo($sql);
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin création de la table temporaire", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur création de la table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else 
{
    $Log->writeLog("Erreur création de la table temporaire", $process_id);
    $stillValid = FALSE;
    die();
}



if($stillValid && $Log->writeLog("Début chargement fichier en table temporaire", $process_id))
{
    /* NE FONCTIONNE PAS CHEZ OVH MUTUALISE
    $sql = "LOAD DATA LOCAL INFILE '$finalFile'
            INTO TABLE $newDataTableName
            FIELDS TERMINATED BY '|'
            LINES TERMINATED BY '\\r\\n'
            IGNORE 1 LINES";
            */
    $j = 0;
    //echo(nl2br("j debut : " . $j . "\n"));
    //echo(nl2br("fichier : " . $finalFile . "\n"));
    $handle = fopen($finalFile, 'r');
    
    
    
    
    if($handle)
    {
        //echo(nl2br("handle ok" . "\n"));
        //Première ligne : entête
        $data = fgetcsv($handle);
        //echo(nl2br($data[0]  . "\n"));
        
        
        //while(($data = fgetcsv($handle, 0, "|")) !== FALSE && $j < 50) //limite à 50 pour tests
        while(($data = fgetcsv($handle, 0, "|")) !== FALSE) //sans limite
        {
            //echo(nl2br("j : " . $j . "\n"));
            //echo(nl2br(str_replace("|", ",", $data[0])  . "\n"));
            //var_dump($data);
            $num = count($data) - 2;
            
            //echo(nl2br("num : " . strval($num) . "\n"));
            
            $sql = "INSERT INTO $newDataTableName VALUES (";
            //echo(nl2br("sql_deb : " . $sql . "\n")); 
            
            for($i = 0; $i < $num; $i++) 
            {
                $sql = $sql . "'" . mb_substr(mysqli_real_escape_string($conn, $data[$i]), 0, 99, 'UTF-8') . "',"; //mb_substr permet de prendre en charge l'encoding
                //echo(nl2br("i : " . $i . " ; data[i] :  " . $data[$i] . "\n"));
                //echo(nl2br($sql . "\n"));
            }
            //echo(nl2br("data[num] : " . "'" . mysqli_real_escape_string($conn, $data[$num]) . "')" . "\n"));
            $sql = $sql . "'" . mb_substr(mysqli_real_escape_string($conn, $data[$num]), 0, 99, 'UTF-8') . "')";
            //echo(nl2br($sql . "\n"));
            
            $j = $j + 1; //Le fichier commence ligne 1 pour notepad ++
            $stepLog = 10000;
            
            //echo(nl2br($sql . "\n"));
            
            if(mysqli_query($conn, $sql))
            {
                if (fmod($j, $stepLog) == 0 || $j == 1)
                {
                    $Log->writeLog("Chargement des lignes : " . strval($j) . " à " . strval($j + $stepLog - 1) . " (max)", $process_id);  
                }
                $sql = NULL;
            }
            else 
            {
                $Log->writeLog("Erreur sur la ligne : " . strval($j) . ", requête : " . $sql, $process_id);
                $stillValid = FALSE;
                die();
            }
        }
        //echo("eof");
        $Log->writeLog("Fin chargement fichier en table temporaire : " . $j . " lignes", $process_id);
        fclose($handle);
        $stillValid = TRUE;
    }
    else
    {
        //echo("erreur handle");
        $Log->writeLog("Erreur chargement fichier en table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur chargement fichier en table temporaire", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début mise à jour des spécialités vides", $process_id))
{
    $sql = "UPDATE $newDataTableName
            SET Libelle_savoir_faire = 'Non précisé'
            WHERE Libelle_savoir_faire = ''";
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin mise à jour des spécialités vides", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur mise à jour des spécialités vides", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur mise à jour des spécialités vides", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début suppression des professions non conservées", $process_id))
{
    $sql = "DELETE FROM $newDataTableName
            WHERE Libelle_profession not in
            (
                SELECT label
                FROM rpps_profession_filter
                WHERE type = 'profession' and keep = 1
             )";
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin suppression des professions non conservées", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur suppression des professions non conservées", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur suppression des professions non conservées", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début création index sur Identifiant_PP table temporaire", $process_id))
{
    $sql = "CREATE INDEX idx_" . $newDataTableName . "_Identifiant_PP ON $newDataTableName (Identifiant_PP) COMMENT '' ALGORITHM DEFAULT LOCK DEFAULT";
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin création index sur Identifiant_PP table temporaire", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur création index sur Identifiant_PP table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur création index Identifiant_PP sur table temporaire", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début création index sur Code_postal_coord_structure_ sur table temporaire", $process_id))
{
    $sql = "CREATE INDEX idx_" . $newDataTableName . "_Code_postal_coord_structure_  ON $newDataTableName (Code_postal_coord_structure_) COMMENT '' ALGORITHM DEFAULT LOCK DEFAULT";
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin création index sur Code_postal_coord_structure_ sur table temporaire", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur création index sur Code_postal_coord_structure_ sur table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur création index Code_postal_coord_structure_ sur table temporaire", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début création index sur Code_commune_coord_structure_ sur table temporaire", $process_id))
{
    $sql = "CREATE INDEX idx_" . $newDataTableName . "_Code_commune_coord_structure_  ON $newDataTableName (Code_commune_coord_structure_) COMMENT '' ALGORITHM DEFAULT LOCK DEFAULT";
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin création index sur Code_commune_coord_structure_ sur table temporaire", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur création index sur Code_commune_coord_structure_ sur table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur création index Code_commune_coord_structure_ sur table temporaire", $process_id);
    die();
}




if($stillValid && $Log->writeLog("Début ajout colonne id_region sur table temporaire", $process_id))
{
    $sql = "ALTER TABLE " . $newDataTableName . " ADD region_id int";
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin ajout colonne id_region sur table temporaire", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur ajout colonne id_region sur table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur ajout colonne id_region sur table temporaire", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début mise à jour id_region sur table temporaire", $process_id))
{
    $sql = "UPDATE " . $newDataTableName . " ND 
            INNER JOIN rpps_region RR ON ND.Code_commune_coord_structure_ like concat(RR.code, '%') 
            SET ND.region_id = RR.region_id";
    
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin mise à jour id_region sur table temporaire", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur mise à jour id_region sur table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur mise à jour id_region sur table temporaire", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début création index sur region_id sur table temporaire", $process_id))
{
    $sql = "CREATE INDEX idx_" . $newDataTableName . "_region_id  ON $newDataTableName (region_id) COMMENT '' ALGORITHM DEFAULT LOCK DEFAULT";
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin création index sur region_id sur table temporaire", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur création index sur region_id sur table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur création index sur region_id sur table temporaire", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début création index sur Libelle_savoir_faire sur table temporaire", $process_id))
{
    $sql = "CREATE INDEX idx_" . $newDataTableName . "_Libelle_savoir_faire  ON $newDataTableName (Libelle_savoir_faire) COMMENT '' ALGORITHM DEFAULT LOCK DEFAULT";
    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin création index sur Libelle_savoir_faire sur table temporaire", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur création index sur Libelle_savoir_faire sur table temporaire", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur création index sur Libelle_savoir_faire sur table temporaire", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début comptage code_insee pas à jour", $process_id))
{
    $sql = "SELECT count(*) as nb_old_code_insee
            FROM " . $newDataTableName . " ND
            LEFT OUTER JOIN rpps_geo_data GD on GD.code_insee = ND.Code_commune_coord_structure_
            WHERE GD.code_insee is null
            AND ND.Code_commune_coord_structure_ <> ''
            AND ND.Code_commune_coord_structure_ not like '98%'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin comptage code_insee pas à jour : " . $data['nb_old_code_insee'], $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur comptage code_insee pas à jour", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur comptage code_insee pas à jour", $process_id);
    die();
}

if($stillValid && $Log->writeLog("Début mise à jour code_insee pas à jour", $process_id))
{
    $sql = "UPDATE " . $newDataTableName . "
            INNER JOIN rpps_old_ci_new_ci CI on CI.old_code_insee = Code_commune_coord_structure_
            SET Code_commune_coord_structure_ = CI.new_code_insee";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin mise à jour code_insee pas à jour", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur mise à jour code_insee pas à jour", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur mise à jour code_insee pas à jour", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début comptage code_insee pas à jour après mise à jour", $process_id))
{
    $sql = "SELECT count(*) as nb_old_code_insee
            FROM " . $newDataTableName . " ND
            LEFT OUTER JOIN rpps_geo_data GD on GD.code_insee = ND.Code_commune_coord_structure_
            WHERE GD.code_insee is null
            AND ND.Code_commune_coord_structure_ <> ''
            AND ND.Code_commune_coord_structure_ not like '98%'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin comptage code_insee pas à jour après update : " . $data['nb_old_code_insee'], $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur comptage code_insee pas à jour après update", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur comptage code_insee pas à jour après update", $process_id);
    die();
}



if($stillValid && $Log->writeLog("Début comptage code_insee pas à jour Marseille", $process_id))
{
    $sql = "SELECT count(*) as nb_err_mars
            FROM " . $newDataTableName . " ND
            WHERE ND.Code_commune_coord_structure_ = '13055'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin comptage code_insee pas à jour Marseille : " . $data['nb_err_mars'], $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur comptage code_insee pas à jour Marseille", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur comptage code_insee pas à jour Marseille", $process_id);
    die();
}



if($stillValid && $Log->writeLog("Début mise à jour Marseille", $process_id))
{
    $sql = "UPDATE " . $newDataTableName . "
            INNER JOIN rpps_code_postal_code_insee_arrond CP on CP.code_postal_detail = Code_postal_coord_structure_
            SET Code_commune_coord_structure_ = CP.code_insee_detail
            WHERE Code_commune_coord_structure_ = '13055'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin mise à jour Marseille", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur mise à jour Marseille", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur mise à jour Marseille", $process_id);
    die();
}

if($stillValid && $Log->writeLog("Début comptage code_insee pas à jour Marseille après mise à jour", $process_id))
{
    $sql = "SELECT count(*) as nb_err_mars
            FROM " . $newDataTableName . " ND
            WHERE ND.Code_commune_coord_structure_ = '13055'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin comptage code_insee pas à jour Marseille après mise à jour : " . $data['nb_err_mars'], $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur comptage code_insee pas à jour Marseille après mise à jour", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur comptage code_insee pas à jour Marseille après mise à jour", $process_id);
    die();
}




if($stillValid && $Log->writeLog("Début comptage code_insee pas à jour Lyon", $process_id))
{
    $sql = "SELECT count(*) as nb_err_lyon
            FROM " . $newDataTableName . " ND
            WHERE ND.Code_commune_coord_structure_ = '69123'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin comptage code_insee pas à jour Lyon : " . $data['nb_err_lyon'], $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur comptage code_insee pas à jour Lyon", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur comptage code_insee pas à jour Lyon", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début mise à jour Lyon", $process_id))
{
    $sql = "UPDATE " . $newDataTableName . "
            INNER JOIN rpps_code_postal_code_insee_arrond CP on CP.code_postal_detail = Code_postal_coord_structure_
            SET Code_commune_coord_structure_ = CP.code_insee_detail
            WHERE Code_commune_coord_structure_ = '69123'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin mise à jour Lyon", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur mise à jour Lyon", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur mise à jour Lyon", $process_id);
    die();
}

if($stillValid && $Log->writeLog("Début comptage code_insee pas à jour Lyon après mise à jour", $process_id))
{
    $sql = "SELECT count(*) as nb_err_lyon
            FROM " . $newDataTableName . " ND
            WHERE ND.Code_commune_coord_structure_ = '69123'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin comptage code_insee pas à jour Lyon après mise à jour : " . $data['nb_err_lyon'], $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur comptage code_insee pas à jour Lyon après mise à jour", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur comptage code_insee pas à jour Lyon après mise à jour", $process_id);
    die();
}



if($stillValid && $Log->writeLog("Début comptage code_insee pas à jour Paris", $process_id))
{
    $sql = "SELECT count(*) as nb_err_paris
            FROM " . $newDataTableName . " ND
            WHERE ND.Code_commune_coord_structure_ = '75056'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin comptage code_insee pas à jour Paris : " . $data['nb_err_paris'], $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur comptage code_insee pas à jour Paris", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur comptage code_insee pas à jour Paris", $process_id);
    die();
}


if($stillValid && $Log->writeLog("Début mise à jour Paris", $process_id))
{
    $sql = "UPDATE " . $newDataTableName . "
            INNER JOIN rpps_code_postal_code_insee_arrond CP on CP.code_postal_detail = Code_postal_coord_structure_
            SET Code_commune_coord_structure_ = CP.code_insee_detail
            WHERE Code_commune_coord_structure_ = '75056'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin mise à jour Paris", $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur mise à jour Paris", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur mise à jour Paris", $process_id);
    die();
}

if($stillValid && $Log->writeLog("Début comptage code_insee pas à jour Paris après mise à jour", $process_id))
{
    $sql = "SELECT count(*) as nb_err_paris
            FROM " . $newDataTableName . " ND
            WHERE ND.Code_commune_coord_structure_ = '75056'";
    if ($result = mysqli_query($conn, $sql))
    {
        $data = mysqli_fetch_assoc($result);
        $Log->writeLog("Fin comptage code_insee pas à jour Paris après mise à jour : " . $data['nb_err_paris'], $process_id);
        $stillValid = TRUE;
    }
    else
    {
        $Log->writeLog("Erreur comptage code_insee pas à jour Paris après mise à jour", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur comptage code_insee pas à jour Paris après mise à jour", $process_id);
    die();
}



if($stillValid && $Log->writeLog("Début création des professions", $process_id))
{
    $sql = "INSERT INTO rpps_profession_filter (type, label, keep)
                SELECT distinct 'specialite', ND.Libelle_savoir_faire, 0
                FROM " . $newDataTableName . " ND
                LEFT OUTER JOIN rpps_profession_filter PF ON PF.label = ND.Libelle_savoir_faire
                WHERE PF.label is null";

    if (mysqli_query($conn, $sql))
    {
        $Log->writeLog("Fin création des professions OK", $process_id);
        $stillValid = TRUE;
    }
    else
    {
    $Log->writeLog("Fin création des professions KO", $process_id);
    $stillValid = FALSE;
    die();
    }
    
}
else 
{
    $stillValid = FALSE;
    $Log->writeLog("Fin création des professions KO", $process_id);
    die();
}




if($stillValid && $Log->writeLog("Début mise à jour step process dans rpps_process", $process_id))
{
    if($Process->finalStep($process_id) == 0)
    {
        $Log->writeLog("Fin mise à jour step process dans rpps_mstr_process", $process_id);
        $stillValid = TRUE;
        
    }
    else
    {
        $Log->writeLog("Erreur mise à jour step process dans rpps_mstr_process", $process_id);
        $stillValid = FALSE;
        die();
    }
}
else
{
    $stillValid = FALSE;
    $Log->writeLog("Erreur mise à jour step process dans rpps_mstr_process", $process_id);
    die();
}

$conn->close();




function remove_accents($string) {
    if (!preg_match('/[\x80-\xff]/', $string))
        return $string;
        
        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
            chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
            chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
            chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
            chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
            chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
            chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
            chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
            chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
            chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
            chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
            chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
            chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
            chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
            chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
            chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
            chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
            chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
            chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
            chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
            chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
            chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
            chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
            chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
            chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
            chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
            chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
        );
        
        $string = strtr($string, $chars);
        
        return $string;
}



?>