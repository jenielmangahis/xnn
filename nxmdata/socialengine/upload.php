<?php
header('Access-Control-Allow-Origin: *');
include('includes/function.php');
include('includes/connection.php');
//If directory doesnot exists create it.
$output_dir = "/var/rep/files/globaltraffictakeover.com/www/social/";

if (isset($_FILES["image_file"])) {
    $ret = array();

    $error = $_FILES["image_file"]["error"]; 

 

            $fileName = rand(1,99887)."_".$_FILES["image_file"]["name"];


            move_uploaded_file($_FILES["image_file"]["tmp_name"], $output_dir . $fileName);
			
           $target= $ret[$fileName] = $output_dir . $fileName;
		   
		   
		   
    if(strpos(mime_content_type($target),'image')!== false) {
$xtype=0;
    }else{
$xtype=1;
    }
    
    echo connection_open();
     $data_array = array(
							'media' => $fileName,
							'type'=>$xtype
						);

   $table_name = "sm_medias";
   
	
    echo json_encode(insert($data_array, $table_name));
    
}
echo connection_close();
?>