<?php
namespace Classes;

use Exception;
include_once dirname(__FILE__) . '/db_connect.php';

class Log 
{
    public function writeLog($message, $process_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $message = mysqli_real_escape_string($conn, $message);
        $process_id = mysqli_real_escape_string($conn, $process_id);

        echo(nl2br("Etape (" . date('Y-m-d H:i:s') . ") : " . $message . ", Process : " . $process_id . "\n")); 
        $sql = "INSERT INTO rpps_log (message, process_id) values ('$message', $process_id)";
        
        try {
            if (mysqli_query($conn, $sql)) 
            {
                return TRUE;
            } else 
            {
                return FALSE;
            }
        } catch (Exception $e) {
            echo ("Erreur : " . $e);
        }
    }
    
    
    public function writeLogNoEcho($region_id, $message, $process_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $message = mysqli_real_escape_string($conn, $message);
        $process_id = mysqli_real_escape_string($conn, $process_id);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        //commenter le echo pour l'utilisation en WS
        //echo(nl2br("Etape (" . date('Y-m-d H:i:s') . ") : " . $message . ", Process : " . $process_id . "\n")); //à supprimer
        $sql = "INSERT INTO rpps_log (message, process_id, region_id) values ('$message', $process_id, $region_id)";
        //echo($sql);
        
        try {
            if (mysqli_query($conn, $sql))
            {
                return TRUE;
            } else
            {
                return FALSE;
            }
        } catch (Exception $e) {
            echo ("Erreur : " . $e);
        }
    }
    
}

