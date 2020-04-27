<?php
namespace Classes;
use Exception;

include_once dirname(__FILE__) . '/db_connect.php';
include_once dirname(__FILE__) . '/Region.php';


use \Classes\Region;



class SalesPro 
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
    private $leaving_doctors_array = [];

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
    
    public function getSalesProIdByToken($sales_pro_token, $region_id)
    {
        
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $sales_pro_token = mysqli_real_escape_string($conn, $sales_pro_token);
        $region_id = mysqli_real_escape_string($conn, $region_id);
        
        $Region = new Region();
        
        $result = $Region->getRegionCode($region_id);
        switch($result) {
            case 0:
                $region_code = $Region->__get('code');
                break;
            case -1:
                return -1;
                break;
            case -2:
                return -2;
                break;
        }
        
        
        $sql = "SELECT SP.sales_pro_id as sales_pro_id
                FROM rpps_" . $region_code ."_sales_pro SP
                WHERE SP.sales_pro_token = '$sales_pro_token'";
        
        //echo($sql);
        try
        {
            if ($result = mysqli_query($conn, $sql))
            {
                $data = mysqli_fetch_assoc($result);
                //echo(count($data));
                if($data['sales_pro_id'] === NULL)
                {
                    return -3; //erreur grave, le code js a été changé
                }
                else
                {
                    $this->sales_pro_id = $data['sales_pro_id'];
                    return 0;
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


}








?>