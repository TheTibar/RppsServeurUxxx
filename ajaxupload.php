<?php

include_once dirname(__FILE__) .  '/Classes/db_connect.php';

$instance = \ConnectDB::getInstance();
$conn = $instance->getConnection();

$sql = "select param_value from param where param_name = 'tmp_rpps_path'";
$sql_result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($sql_result);

//var_dump($data);
$valid_extensions = array('csv'); // valid extensions

$_SESSION["tmp_rpps_path"] = $data["param_value"];
$path = $_SESSION["tmp_rpps_path"]; // upload directory



/*if(!empty($_FILES['fileToUpload']))*/
if($_FILES['fileToUpload']['tmp_name'] != '')
{
    $img = $_FILES['fileToUpload']['name'];
    $tmp = $_FILES['fileToUpload']['tmp_name'];
    // get uploaded file's extension
    $ext = strtolower(pathinfo($img, PATHINFO_EXTENSION));
    
    // check's valid format
    if(in_array($ext, $valid_extensions)) 
    {
        $path = $path.strtolower($img); 
        if(move_uploaded_file($tmp,$path)) 
        {
            $_SESSION["fichier_import"] = $path;
            $fichier = $_SESSION["fichier_import"];
            
            //Stockage du nom du fichier et de sa date d'import
            $sql = "INSERT INTO file_history (file_name) VALUES ('$fichier')";

            if (mysqli_query($conn, $sql))
            {
                //Suppression des données qui pourraient rester dans new_data
                $sql = "DROP TABLE IF EXISTS new_data";

                if (mysqli_query($conn, $sql))
                {
                    //Création de la table new data
                    $table = 'new_data';
                    // Récupération structure CSV et création table
                    ini_set('auto_detect_line_endings',TRUE);
                    $handle = fopen($fichier,'r');
                    //on lit la première ligne pour récupérer la structure
                    $data = fgetcsv($handle);
                    if (!$data) 
                    {
                        echo "cannot_read_csv_file";
                    }
                    else 
                    {
                        $str_lst_fields = $data[0];
                        $str_lst_fields = str_replace(";", ",", $str_lst_fields);
                        
                        
                        //suppression accents et remplacement par caractères non accentués
                        $str_lst_fields = remove_accents($str_lst_fields);
                        
                        //suppression caractères spéciaux
                        $str_lst_fields = preg_replace ('/[^0-9a-zA-Z,]/', '_', $str_lst_fields);
                        
                        $str_lst_fields = str_replace("__", "_", $str_lst_fields);
                        
                        $str_lst_fields = str_replace(",", " varchar(100), ", $str_lst_fields);
                        
                        $str_lst_fields = substr($str_lst_fields, 0, -2); //supprime les 2 derniers caractères qui sont ", "
                        
                        $sql = "CREATE TABLE $table (" . $str_lst_fields . ")";
                        
                        if (mysqli_query($conn, $sql))
                        {
                            //Chargement du nouveau fichier dans la table new_data
                            $sql = "LOAD DATA INFILE '$fichier'
                                INTO TABLE new_data
                                FIELDS TERMINATED BY ';'
                                LINES TERMINATED BY '\\r\\n'
                                IGNORE 1 LINES";
                            
                            if (mysqli_query($conn, $sql))
                            {
                                echo 'fulfill_new_data_ok';
                            }
                            else
                            {
                                echo 'fulfill_new_data_error';
                            }
                        }
                        else
                        {
                            echo 'create_new_data_structure_error';
                        }
                    }
                }
                else
                {
                    echo 'drop_new_data_error';
                }
            }
            else
            {
                echo 'save_filename_db_error';
            }
            
        }
    } 
    else 
    {
        echo 'invalid_extension';
    }
}
else 
{
    echo 'no_file';
}


//$conn->close();



function response($status, $status_message, $data)
{
    header("HTTP/1.1 ".$status);
    //header("Content-Type:application/json;charset=utf-8", false);
    $response['status']=$status;
    $response['status_message']=$status_message;
    $response['data']=$data;
    $json_response = json_encode($response, JSON_UNESCAPED_UNICODE);
    echo $json_response;
}


function remove_accents($string) {
    if ( !preg_match('/[\x80-\xff]/', $string) )
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