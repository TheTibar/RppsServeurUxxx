<?php 
/**/
function LoadPNG($imgname, $color)
{
    $color_r = substr($color, 1, strlen($color) - 1);
    echo($color);
    
    $split_hex_color = str_split( $color_r, 2 ); 
    $r = hexdec( $split_hex_color[0] ); 
    $g = hexdec( $split_hex_color[1] ); 
    $b = hexdec( $split_hex_color[2] );
    
    
    $im = imagecreatefrompng ($imgname);
    
    /*imagealphablending( $im, false );*/
    /*imagesavealpha( $im, true );*/
    
    
    imagetruecolortopalette($im, false, 255);
    $index = imagecolorclosest ($im, 0, 0, 0); // GET BLACK COLOR
    imagecolorset($im, $index, $r, $g, $b); // SET COLOR TO $color
    
    $index = imagecolorclosest ($im, 255, 255, 255); // GET WHITE COLOR
    imagecolorset($im, $index, 255, 255, 255, 127); // SET COLOR WHITE transparent
    
    
    
    $name = basename($imgname);
    var_dump($im);
    
    imagepng($im, getcwd()."/Img/" . $color . ".png"); // save image as png
    
    //echo(getcwd()."/Img" . "/new_color" . $name);
    //imagedestroy($im);
}
$dir = getcwd()."/Img/";
$image = $dir . "marker-icon_tst2.png";

//echo($image);
$color = '#d47f8f';
LoadPNG($image, $color);

 /*
function createAvatarImage($string)
{
    
    $imageFilePath = getcwd()."/Img/Avatar/" . $string . ".png";
    
    //base avatar image that we use to center our text string on top of it.
    $avatar = imagecreatetruecolor(60,60);
    $bg_color = imagecolorallocate($avatar, 211, 211, 211);
    imagefill($avatar,0,0,$bg_color);
    $avatar_text_color = imagecolorallocate($avatar, 0, 0, 0);
    // Load the gd font and write
    $font = imageloadfont(getcwd()."/Font/X_B_7x13_LE.gdf");
    imagestring($avatar, $font, 10, 10, $string, $avatar_text_color);
    imagepng($avatar, $imageFilePath);
    imagedestroy($avatar);
    
    return $imageFilePath;
}

$string = 'Xavier';

createAvatarImage($string);
*/
?>





