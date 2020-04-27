<?php
namespace Classes;


include_once dirname(__FILE__) . '/db_connect.php';
include_once dirname(__FILE__) . '/User.php';

class WebInterface
{
    private $menu = [];
    
    
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
    
    
    public function getMenu($user_token) //Récupérer les données pour construire le menu
    {
        $instance = \ConnectDB::getInstance();
        $conn = $instance->getConnection();
        
        $user_token = mysqli_real_escape_string($conn, $user_token);
        
        $User = new User();
        
        $result = $User->getUserData($user_token);
        
        switch($result) {
            case 0:
                $role_id = $User->__get('role_id');
                break;
            case -1 : 
                return -1; //utilisateur inconnu
                break;
            case -2 : 
                return -2; //requête en erreur
                break;
        }
        
        //echo($role_id);
        
        $sql = "SELECT DOM.label as dom_label, PA.label as page_label, PA.route as page_route 
                FROM rpps_page PA
                INNER JOIN rpps_domain DOM on DOM.domain_id = PA.domain_id
                WHERE PA.role_id = $role_id
                ORDER BY DOM.display_order, PA.display_order";
        
        //echo $sql;
        
        if ($result = mysqli_query($conn, $sql))
        {
            $data = [];
            while ($line = mysqli_fetch_assoc($result))
            {
                $data[] = $line;
            }
            if (count($data) > 0)
            {
                $this->menu = $data;
                return 0;
                //response(200, 'leaving_doctors_number_by_sales_pro', $data);
            }
            else
            {
                return -3; //pas de menu associé à ce rôle
                //response(200, 'no_more_leaving_doctors', NULL);
            }
        }
        else
        {
            return -4; //erreur de requête
        }
        
    }
}