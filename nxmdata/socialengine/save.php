<?php
header("Access-Control-Allow-Origin: *");

include('includes/function.php');
include('includes/connection.php');


foreach(array('video', 'audio') as $type) {
    if (isset($_FILES["${type}-blob"])) {
    
        ///echo '/var/rep/cb/gtt/social/';
        
		$fileName = $_POST["${type}-filename"];
        $uploadDirectory = '/var/rep/files/globaltraffictakeover.com/www/social/'.$fileName;
        
        if (!move_uploaded_file($_FILES["${type}-blob"]["tmp_name"], $uploadDirectory)) {
            echo(" problem moving uploaded file");
        }
		
	//	echo($fileName);
		
		 connection_open();
     $data_array = array(
							'media' => $fileName,
							'type'=>1,
						);
   $table_name = "sm_medias";
   
	
    echo json_encode(insert($data_array, $table_name));
    

connection_close();

    }
}
?>