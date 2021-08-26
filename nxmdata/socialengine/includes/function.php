<?php
function insert($data, $table_name){
    $data = array_map('mysql_real_escape_string', $data);
    $key = array_keys($data);
    $value = array_values($data);
    $key_string = implode(',', $key);
    $value_string = "'" . implode("','", $value) ."'";
    $query = mysql_query("insert into $table_name($key_string) values($value_string)");    
	return mysql_insert_id();
}

function update($data, $table_name){
    $data = array_map('mysql_real_escape_string', $data);
    $key = array_keys($data);
    $value = array_values($data);
    $key_string = implode(',', $key);
    $value_string = "'" . implode("','", $value) ."'";


    $query = mysql_query("UPDATE `sm_medias` SET `catid` = $key_string WHERE `id` = $value_string");  

}


function updateVideo($data, $table_name){
    $data = array_map('mysql_real_escape_string', $data);
    $key = array_keys($data);
    $value = array_values($data);
    $key_string = implode(',', $key);
    $value_string = "'" . implode("','", $value) ."'";


    $query = mysql_query("UPDATE `sm_medias` SET `catid` = $key_string WHERE `media` = $value_string");  

}



?>
