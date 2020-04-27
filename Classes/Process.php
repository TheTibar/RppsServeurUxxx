<?php
namespace Classes;

use Exception;
include_once dirname(__FILE__) . '/db_connect.php';

class Process 
{
    
    private $process_id;
    
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
    
    public function newProcess($region_id, $name, $step)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();

        $sql = "INSERT INTO rpps_process (region_id, name, step)
                VALUES ($region_id, '$name', $step)";
        try {
            if (mysqli_query($conn, $sql))
            {
                $this->process_id = mysqli_insert_id($conn);
                return 0;
            } else
            {
                return -1;
            }
        } catch (Exception $e) {
            echo ("Erreur : " . $e);
        }
    }
    
    public function getCurrentProcess()
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        //en batch, le process en cours est à l'étape 1 jusqu'à la fin ou il passe à 2, voir si cela suffit
        $sql = "SELECT max(process_id) as process_en_cours
                FROM rpps_process
                WHERE step = 1"; 
        try {
            if ($sql_result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($sql_result);
                $currentProcess = $data['process_en_cours'];
                return $currentProcess;
            } else
            {
                return FALSE;
            }
        } catch (Exception $e) {
            echo ("Erreur : " . $e);
        }
    }
    
    
    public function nextStep($process_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);

        $sql = "UPDATE rpps_process 
                SET step = step + 1 
                WHERE process_id = $process_id";
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
    
    
    public function finalStep($process_id)
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $process_id = mysqli_real_escape_string($conn, $process_id);
        
        $sql = "UPDATE rpps_process
                SET step = 99,
                    is_ok = 1
                WHERE process_id = $process_id";
        
        //echo($sql);
        
        try {
            if (mysqli_query($conn, $sql))
            {
                return 0;
            } else
            {
                return -1;
            }
        } catch (Exception $e) {
            echo ("Erreur : " . $e);
        }
    }
    
    
}

