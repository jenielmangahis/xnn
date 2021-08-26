<?php
error_reporting(0);
session_start();
header('Content-Type: application/json');
### START BY GETTING  THE PASSED VARIABLES ###
$json = file_get_contents('php://input');
$obj = json_decode($json);
### END PASSED VARIABLES ####

$_SESSION['isadmin'] = 1;
############ START Constants  ###########


## INITIALIZE API ##
$_SESSION['db']='tsr';
### 


$hostname_cnMain = "dbserver";
$database_cnMain = $_SESSION['db'];
$username_cnMain = "solomon";
$password_cnMain = "xZn8GowU>4s6Pyf";
$cnMain = mysql_pconnect($hostname_cnMain, $username_cnMain, $password_cnMain) or trigger_error(mysql_error(),E_USER_ERROR); 



require_once 'swift/swift_required.php';
############ END Constants  ###########

if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) {
    $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
  }

  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}
}


#check if sponsor is online
if($obj->method =="isOnline"){
	
mysql_select_db($database_cnMain, $cnMain);

$q = "SELECT * FROM `users` WHERE id='".$obj->sponsorid."'";
$s = mysql_query($q, $cnMain) or die(mysql_error());

$c = mysql_fetch_assoc($s);
if($c['chatstatus']!=1){
$response['online']	 = 0;
}else{
$response['online']	 = 1;
}
echo json_encode($response);
}
#check if sponsor is online



#check for returningCbUser users
if($obj->method =="returningCbUser"){
	
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat` WHERE `roomid`='".$obj->roomid."'";
$s = mysql_query($q, $cnMain) or die(mysql_error());
while ($c = mysql_fetch_assoc($s)) {
$vals['id'] = $c['id'];
$vals['user'] = $c['user'];
$vals['active'] = $c['active'];
$vals['roomid'] = $c['roomid'];
}
echo json_encode($vals);

}
#check for returningCbUser users

#check for getUserDetails users
if($obj->method =="getUserDetails"){
	
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat` WHERE `roomid`='".$obj->userid."'";
$s = mysql_query($q, $cnMain) or die(mysql_error());
while ($c = mysql_fetch_assoc($s)) {
$vals['id'] = $c['id'];
$vals['user'] = $c['user'];
$vals['active'] = $c['active'];
$vals['roomid'] = $c['roomid'];
$vals['initialquestion'] = $c['initialquestion'];
$vals['device'] = $c['device'];
$vals['urlorigin'] = $c['urlorigin'];
$vals['userip'] = $c['userip'];
$vals['lname'] = $c['lname'];
$vals['email'] = $c['email'];



$ua_info = parse_user_agent($c['device']);


$vals['about'] = '<div class=" alert alert-info alert-dismissible fade in" role="alert"> <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button><h5>Question: '.$c['initialquestion'].'</div>';



$browse .= '<a target="_blank" href="'.$c['urlorigin'].'">'.$c['urlorigin'].' <i class="fa fa-mail-forward"></i></a>';

$lookup .= '<a target="_blank" href="http://www.ip-tracker.org/locator/ip-lookup.php?ip='.$c['userip'].'">'.$c['userip'].' <i class="fa fa-mail-forward"></i></a>';






$reff='<h3>User Information:</h3>';
$reff .='Name: '.$c['user'].' '.$c['lname'].'<br />';
$reff .='Email: '.$c['email'].'<br />';

$reff .='Initial Question: '.$c['initialquestion'].'<br />';

$reff .='Url Referrer: '.$browse. ' <br />';
$reff .='Browser: <i style="font-size: 20px;" class="fa fa-'.strtolower($ua_info['browser']).'"></i> v.'.$ua_info['version'].'<br />';

if(strtolower($ua_info['platform']) == "macintosh"){
	$apper = 'apple';
}else{
	$apper = strtolower($ua_info['platform']);
}

$reff .='OS:  <i style="font-size: 20px;" class="fa fa-'.$apper.'"></i> <br />';
$reff .='IP: '.$lookup;
$vals['moreinfo'] = $reff;

}
echo json_encode($vals);

}
#check for getUserDetails users


#check for see duponline users
if($obj->method =="isDupOnline"){
	
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat` WHERE `roomid`='".$obj->roomid."' and active=1";
$s = mysql_query($q, $cnMain) or die(mysql_error());
while ($c = mysql_fetch_assoc($s)) {
$vals['id'] = $c['id'];
$vals['user'] = $c['user'];
$vals['active'] = $c['active'];
$vals['roomid'] = $c['roomid'];
}
echo json_encode($vals);

}
#check for see duponline users


#check for see populatecanned users
if($obj->method =="populateCanned"){
	
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat-canned` WHERE `id`='".$obj->canid."'";
$s = mysql_query($q, $cnMain) or die(mysql_error());
while ($c = mysql_fetch_assoc($s)) {
$vals['msg'] = GetMergeCode($c['msg'],$obj->userid);
}
echo json_encode($vals);

}
#check for see populatecanned users



if($obj->method =="getTicketDetails"){ 


mysql_select_db($database_cnMain, $cnMain);

$query_rsQueryRecord = "SELECT * FROM `tblTickets` where `ticketid`='".$obj->ticketid."'";


$rsQueryRecord = mysql_query($query_rsQueryRecord, $cnMain) or die(mysql_error());
$row_rsQueryRecord = mysql_fetch_assoc($rsQueryRecord);
$totalRows_rsQueryRecord = mysql_num_rows($rsQueryRecord);
$arx = array();
if($totalRows_rsQueryRecord!=0){
$x=0;
do {

$arx[$x]['ticketid'] = $row_rsQueryRecord['ticketid'];
$arx[$x]['system'] = $row_rsQueryRecord['system'];
$arx[$x]['status'] = $row_rsQueryRecord['status'];
$arx[$x]['subject'] = $row_rsQueryRecord['subject'];
$arx[$x]['body'] = $row_rsQueryRecord['body'];
$arx[$x]['duedate'] = $row_rsQueryRecord['duedate'];
$arx[$x]['lastupdatedate'] = $row_rsQueryRecord['lastupdatedate'];
$arx[$x]['userid'] = UserData($row_rsQueryRecord['userid'],"fname");
$arx[$x]['ticket'] = strtoupper($row_rsQueryRecord['system']).'-'.$row_rsQueryRecord['ticketid'];


$x++;

} while ($row_rsQueryRecord = mysql_fetch_assoc($rsQueryRecord)); 


}


## see if cancellation-
if($obj->cancellation==1){
mysql_select_db($database_cnMain, $cnMain);


$theint = preg_replace("/[^0-9]/","",$obj->ticketid) ;
$query_rsQueryRecord = "SELECT * FROM `cancel_listing` where `id`='".$theint."'";


$rsQueryRecord = mysql_query($query_rsQueryRecord, $cnMain) or die(mysql_error());
$row_rsQueryRecord = mysql_fetch_assoc($rsQueryRecord);
$totalRows_rsQueryRecord = mysql_num_rows($rsQueryRecord);
$arx = array();
if($totalRows_rsQueryRecord!=0){
$x=0;
do {

	$arx[$x]['ticketid'] = 'cancellation-'.$row_rsQueryRecord['id'];
	$arx[$x]['system'] = $_SESSION['db'];
	$arx[$x]['status'] = 5;
	$arx[$x]['subject'] = 'CANCELLATION: '.UserData($row_rsQueryRecord['userid'],"fname")  ;
	$arx[$x]['body'] = $row_rsQueryRecord['cancel_reason'].$row_rsQueryRecord['survey_answers'];
	$arx[$x]['duedate'] = $row_rsQueryRecord['cancel_effectivity'];
	$arx[$x]['lastupdatedate'] = $row_rsQueryRecord['cancel_effectivity'];
	$arx[$x]['userid'] = UserData($row_rsQueryRecord['userid'],"fname");
	$arx[$x]['ticket'] = 'CANC-'.strtoupper($_SESSION['db']).'-'.$row_rsQueryRecord['id'];
	$x++;

} while ($row_rsQueryRecord = mysql_fetch_assoc($rsQueryRecord)); 
}
}
## see if cancellation-


echo json_encode($arx);


}

## END GET TICKET INDIVIDUAL


if($obj->method =="getTickets"){ 

mysql_select_db($database_cnMain, $cnMain);
$ticketTypeAdmin ='';
$ticketType='';
if($obj->ticketType!=0){
$ticketTypeAdmin =' where status='.$obj->ticketType;
$ticketType=' and status='.$obj->ticketType;
}

$query_rsQueryRecord = "SELECT * FROM `tblTickets` WHERE `userid`='".$_SESSION['sponsorID']."' ".$ticketType;


if($_SESSION['isadmin']==1){
$query_rsQueryRecord = "SELECT * FROM `tblTickets`".$ticketTypeAdmin;
}

$rsQueryRecord = mysql_query($query_rsQueryRecord, $cnMain) or die(mysql_error());
$row_rsQueryRecord = mysql_fetch_assoc($rsQueryRecord);
$totalRows_rsQueryRecord = mysql_num_rows($rsQueryRecord);
$arx = array();
if($totalRows_rsQueryRecord!=0){
$x=0;
do {
	$arx[$x]['ticketid'] = $row_rsQueryRecord['ticketid'];
	$arx[$x]['system'] = $row_rsQueryRecord['system'];
	$arx[$x]['status'] = $row_rsQueryRecord['status'];
	$arx[$x]['subject'] = $row_rsQueryRecord['subject'];
	$arx[$x]['body'] = $row_rsQueryRecord['body'];
	$arx[$x]['duedate'] = $row_rsQueryRecord['duedate'];
	$arx[$x]['lastupdatedate'] = $row_rsQueryRecord['lastupdatedate'];
	$arx[$x]['userid'] = UserData($row_rsQueryRecord['userid'],"fname");
	$arx[$x]['ticket'] = strtoupper($row_rsQueryRecord['system']).'-'.$row_rsQueryRecord['ticketid'];
	$x++;
} while ($row_rsQueryRecord = mysql_fetch_assoc($rsQueryRecord)); 
}



######## END GET ALL CANCELLATIONS ########

if($_SESSION['isadmin']==1){
$query_rsQueryRecord = "SELECT * FROM `cancel_listing`";


$rsQueryRecord = mysql_query($query_rsQueryRecord, $cnMain) or die(mysql_error());
$row_rsQueryRecord = mysql_fetch_assoc($rsQueryRecord);
$totalRows_rsQueryRecord = mysql_num_rows($rsQueryRecord);

if($totalRows_rsQueryRecord!=0){
$x=0;
do {
	$arx[$x]['ticketid'] = 'cancellation-'.$row_rsQueryRecord['id'];
	$arx[$x]['system'] = $_SESSION['db'];
	$arx[$x]['status'] = 5;
	$arx[$x]['subject'] = 'CANCELLATION: '.UserData($row_rsQueryRecord['userid'],"fname")  ;
	$arx[$x]['body'] = $row_rsQueryRecord['cancel_reason'];
	$arx[$x]['duedate'] = $row_rsQueryRecord['cancel_effectivity'];
	$arx[$x]['lastupdatedate'] = $row_rsQueryRecord['cancel_effectivity'];
	$arx[$x]['userid'] = UserData($row_rsQueryRecord['userid'],"fname");
	$arx[$x]['ticket'] = 'CANC-'.strtoupper($_SESSION['db']).'-'.$row_rsQueryRecord['id'];
	$x++;
} while ($row_rsQueryRecord = mysql_fetch_assoc($rsQueryRecord)); 
}
}

######## END GET ALL CANCELLATIONS ########

//$arx['sql']=$query_rsQueryRecord;
echo json_encode($arx);


}



###############################
###############################


if($obj->method =="getTicketsNumber"){ 
$arx = array();
mysql_select_db($database_cnMain, $cnMain);


$ticketTypeAdmin ='';
$ticketType='';
if($obj->ticketType!=0){
$ticketTypeAdmin =' where status='.$obj->ticketType;
$ticketType=' and status='.$obj->ticketType;
}


##All tickets
$q1 = "SELECT * FROM `tblTickets` WHERE `userid`='".$_SESSION['sponsorID']."' ".$ticketType;
if($_SESSION['isadmin']==1){
$q1 = "SELECT * FROM `tblTickets`".$ticketTypeAdmin;
}
$rs1 = mysql_query($q1, $cnMain) or die(mysql_error());
$r1 = mysql_fetch_assoc($rs1);
$totals = mysql_num_rows($rs1);
##end all TICKETS


##All urgent
$q2 = "SELECT * FROM `tblTickets` WHERE `userid`='".$_SESSION['sponsorID']."' and status=1";
if($_SESSION['isadmin']==1){
$q2 = "SELECT * FROM `tblTickets` where `status`='1'";
}
$rs2 = mysql_query($q2, $cnMain) or die(mysql_error());
$r2 = mysql_fetch_assoc($rs2);
$urgent = mysql_num_rows($rs2);
##end all urgent


##All requests
$q3 = "SELECT * FROM `tblTickets` WHERE `userid`='".$_SESSION['sponsorID']."' and status=2";
if($_SESSION['isadmin']==1){
$q3 = "SELECT * FROM `tblTickets` where `status`='2'";
}
$rs3 = mysql_query($q3, $cnMain) or die(mysql_error());
$r3 = mysql_fetch_assoc($rs3);
$requests = mysql_num_rows($rs3);
##end all requests



##All questions
$q4 = "SELECT * FROM `tblTickets` WHERE `userid`='".$_SESSION['sponsorID']."' and status=3";
if($_SESSION['isadmin']==1){
$q4 = "SELECT * FROM `tblTickets` where `status`='3'";
}
$rs4 = mysql_query($q4, $cnMain) or die(mysql_error());
$r4 = mysql_fetch_assoc($rs4);
$questions = mysql_num_rows($rs4);
##end all questions


##All done
$q5 = "SELECT * FROM `tblTickets` WHERE `userid`='".$_SESSION['sponsorID']."' and status=4";
if($_SESSION['isadmin']==1){
$q5 = "SELECT * FROM `tblTickets` where `status`='4'";
}
$rs5 = mysql_query($q5, $cnMain) or die(mysql_error());
$r5 = mysql_fetch_assoc($rs5);
$done = mysql_num_rows($rs5);
##end all done


##All cancels

if($_SESSION['isadmin']==1){
$q5 = "SELECT * FROM `cancel_listing`";
}
$rs5 = mysql_query($q5, $cnMain) or die(mysql_error());
$r5 = mysql_fetch_assoc($rs5);
$cancels = mysql_num_rows($rs5);
##end all cancels

$arx['totaltickets'] = $totals;
$arx['urgent'] = $urgent;
$arx['requests'] = $requests;
$arx['questions'] = $questions;
$arx['done'] = $done;
$arx['cancels'] = $cancels;







//$arx['sql']=$query_rsQueryRecord;
echo json_encode($arx);


}

###############################
###############################

if($obj->method =="SendTicketBackoffice"){ 

## ADD SYSTEM
mysql_select_db($database_cnMain, $cnMain);
$todayz = date("m-d-y");  

$updateSQL = "INSERT INTO  `tblTickets`(`subject`,`body`,`duedate`,`system`,`status`,`userid`,`email`,`fullname`,`lastupdatedate`) VALUES (". GetSQLValueString($obj->subject,"text").",". GetSQLValueString($obj->body,"text").",". GetSQLValueString($obj->due,"text").",". GetSQLValueString($_SESSION['db'],"text").",". GetSQLValueString($obj->status,"text").",". GetSQLValueString($obj->userID,"text").",". GetSQLValueString($obj->email,"text").",". GetSQLValueString($obj->fullname,"text").",". GetSQLValueString($todayz,"text").");";
mysql_query($updateSQL, $cnMain) or die(mysql_error());


if(filter_var($obj->email, FILTER_VALIDATE_EMAIL)) {

 $subject = "We have received your request!";
 $body = 'Subject:'.$obj->subject .'<br />Body:<br />' .$obj->body;

 
 $transport = Swift_SmtpTransport::newInstance('mail.webyoungmasters.com', 25)
  ->setUsername('mailer@webyoungmasters.com')
    ->setPassword('test14344');

$mailer = Swift_Mailer::newInstance($transport);



$message = Swift_Message::newInstance($subject)
  ->setFrom(array('support@toolsrock.com' => 'Toolsrock Support'))
  ->setTo(array($obj->email => $obj->fullname))
  ->setBody($body,'text/html');

$result = $mailer->send($message);

}

echo "{'response':'submitted'}";
}


if($obj->method =="SendTicket"){ 

## ADD SYSTEM
mysql_select_db($database_cnMain, $cnMain);
$todayz = date("m-d-y");  

$updateSQL = "INSERT INTO  `tblTickets`(`subject`,`body`,`duedate`,`system`,`status`,`userid`,`email`,`fullname`,`lastupdatedate`) VALUES (". GetSQLValueString($obj->subject,"text").",". GetSQLValueString($obj->body,"text").",". GetSQLValueString($obj->due,"text").",". GetSQLValueString($_SESSION['db'],"text").",". GetSQLValueString($obj->status,"text").",". GetSQLValueString($obj->userID,"text").",". GetSQLValueString($obj->email,"text").",". GetSQLValueString($obj->fullname,"text").",". GetSQLValueString($todayz,"text").");";
mysql_query($updateSQL, $cnMain) or die(mysql_error());


if(filter_var($obj->email, FILTER_VALIDATE_EMAIL)) {

 $subject = "We have received your request!";
 $body = 'Subject:'.$obj->subject .'<br />Body:<br />' .$obj->body;

 
 $transport = Swift_SmtpTransport::newInstance('mail.webyoungmasters.com', 25)
  ->setUsername('mailer@webyoungmasters.com')
    ->setPassword('test14344');

$mailer = Swift_Mailer::newInstance($transport);



$message = Swift_Message::newInstance($subject)
  ->setFrom(array('support@toolsrock.com' => 'Toolsrock Support'))
  ->setTo(array($obj->email => $obj->fullname))
  ->setBody($body,'text/html');

$result = $mailer->send($message);

}

echo "{'response':'submitted'}";
}


#check for online users
if($obj->method =="getOnlineUsers"){
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat` WHERE `sponsor`='".$obj->sponsorid."' and `display`=1";

if($obj->adm == 1){
	$q = "SELECT * FROM `chat` WHERE `levelid`='3' and `display`='1'";
}

$s = mysql_query($q, $cnMain) or die(mysql_error());
$x=0;
while ($c = mysql_fetch_assoc($s)) {
$vals[$x]['id'] = $c['id'];
$vals[$x]['user'] = $c['user'];
$vals[$x]['active'] = $c['active'];
$vals[$x]['roomid'] = $c['roomid'];
$x++;
}
echo json_encode($vals);
}
#check for online users


#check for getChatHistory
if($obj->method =="getChatHistory"){
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat-logs` WHERE `roomid`='".$obj->userid."' order by `time` desc";
$s = mysql_query($q, $cnMain) or die(mysql_error());
$x=0;
while ($c = mysql_fetch_assoc($s)) {
  
$logs[$x]['time'] =  date("F j, Y, g:i a",round($c['time']/1000));     
$logs[$x]['stamp'] =  $c['time'];


$logs[$x]['msg'] = $c['msg'];
$logs[$x]['person'] = $c['person'];

$x++;
}
echo json_encode($logs);
}
#check for getChatHistory


#check for getChatHistory
if($obj->method =="getChatHistoryQuick"){
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat-logs` WHERE `roomid`='".$obj->userid."' order by `time` asc";
$s = mysql_query($q, $cnMain) or die(mysql_error());
$x=0;
while ($c = mysql_fetch_assoc($s)) {
  
$logs[$x]['time'] =  date("F j, Y, g:i a",round($c['time']/1000));     
$logs[$x]['stamp'] =  $c['time'];


$logs[$x]['msg'] = $c['msg'];
$logs[$x]['person'] = $c['person'];

$x++;
}
echo json_encode($logs);
}
#check for getChatHistory
#check for getChatHistory

if($obj->method =="getChatHistoryQuickClient"){
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat-logs` WHERE `roomid`='".$obj->userid."' order by `time` asc";
$s = mysql_query($q, $cnMain) or die(mysql_error());
$x=0;
while ($c = mysql_fetch_assoc($s)) {
  
$logs[$x]['time'] =  date("F j, Y, g:i a",round($c['time']/1000));     
$logs[$x]['stamp'] =  $c['time'];


$logs[$x]['msg'] = $c['msg'];
$logs[$x]['person'] = $c['person'];

$x++;
}
echo json_encode($logs);
}
#check for getChatHistory





#check for online users
if($obj->method =="getCannedMsgs"){
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat-canned` WHERE `userid`='".$obj->userid."'";
$s = mysql_query($q, $cnMain) or die(mysql_error());
$x=0;
while ($c = mysql_fetch_assoc($s)) {
$vals[$x]['id'] = $c['id'];
$vals[$x]['msg'] = $c['msg'];
$vals[$x]['issystem'] = $c['issystem'];

$x++;
}
echo json_encode($vals);
}
#check for online users


#check for getGeneratedCodes users
if($obj->method =="getGeneratedCodes"){
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT * FROM `chat-generated` WHERE `userid`='".$obj->userid."'";
$s = mysql_query($q, $cnMain) or die(mysql_error());
$x=0;
while ($c = mysql_fetch_assoc($s)) {
$vals[$x]['id'] = $c['id'];
$vals[$x]['url'] = $c['url'];
$vals[$x]['code'] = htmlentities($c['code']);

$x++;
}
echo json_encode($vals);
}
#check for getGeneratedCodes users



#check for addCannedMsg users
if($obj->method =="addCannedMsg"){
mysql_select_db($database_cnMain, $cnMain);
$q = "INSERT INTO `chat-canned` ( `msg`, `userid`) VALUES ( '".$obj->canned."', '".$obj->userid."')";
$s = mysql_query($q, $cnMain) or die(mysql_error());

echo '{"response":"ok"}';
}
#check for addCannedMsg users


#check for addGeneratedCodes users
if($obj->method =="addGeneratedCodes"){
	
	//{"method":"addGeneratedCodes","userid":"24","uriz":"webyoungmasters.com"}
mysql_select_db($database_cnMain, $cnMain);

$hashedz  = md5($obj->uriz);
$frmkey  = $obj->frmkey;

$gencodes ='
<!-- START CHAT SCRIPT |'.$obj->urlz.'| -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js?'.$hashedz.'"></script>
<link href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.4.0/css/font-awesome.css" rel="stylesheet">
<div id="chatboxContent"></div>
<script src="https://office.toolsrock.com:3000/socket.io/socket.io.js?'.$hashedz.'"></script>
<script src="https://office.toolsrock.com/js/moment.min.js?'.$hashedz.'"></script>
<script src="https://office.toolsrock.com/js/livestamp.min.js?'.$hashedz.'"></script>
<link href="https://office.toolsrock.com:81/remote/chat.css?'.$hashedz.'" rel="stylesheet">
<script src="https://office.toolsrock.com:81/remote?s='.$obj->userid.'&levelid=4&sys=&k='.$hashedz.'&rkz='.$frmkey.'"></script>
<script src="https://office.toolsrock.com:81/remote/pop.js?'.$hashedz.'"></script>
<!-- END  CHAT SCRIPT -->';





$q = "INSERT INTO `chat-generated` ( `url`, `userid`,`code`,`hash`) VALUES ( '".$obj->uriz."', '".$obj->userid."',".GetSQLValueString($gencodes,"text").",'".$hashedz."')";
$s = mysql_query($q, $cnMain) or die(mysql_error());

$rets['code']=$gencodes;


echo json_encode($rets);

}
#check for addGeneratedCodes users





#check for removeCanned users
if($obj->method =="removeCanned"){
mysql_select_db($database_cnMain, $cnMain);
$q = "DELETE FROM `chat-canned` WHERE (`id`='".$obj->canned."' and `userid`='".$obj->userid."')";
$s = mysql_query($q, $cnMain) or die(mysql_error());

echo '{"response":"ok"}';
}
#check for removeCanned users



#check for setOfflineUser users
if($obj->method =="setOfflineUser"){
mysql_select_db($database_cnMain, $cnMain);
#$q = "DELETE FROM `chat` WHERE (`id`='".$obj->canned."' and `userid`='".$obj->userid."')";

$q = "UPDATE `chat` SET `active`='0' WHERE (`roomid`='".$obj->userid."')";

$s = mysql_query($q, $cnMain) or die(mysql_error());

echo '{"response":"ok"}';
}
#check for setOfflineUser users

#check for archiveUser users
if($obj->method =="archiveUser"){
mysql_select_db($database_cnMain, $cnMain);
#$q = "DELETE FROM `chat` WHERE (`id`='".$obj->canned."' and `userid`='".$obj->userid."')";

$q = "UPDATE `chat` SET `display`='0' WHERE (`roomid`='".$obj->userid."')";

$s = mysql_query($q, $cnMain) or die(mysql_error());

echo '{"response":"ok"}';
}
#check for archiveUser users


#check for removeGenerated users
if($obj->method =="removeGenerated"){
mysql_select_db($database_cnMain, $cnMain);
$q = "DELETE FROM `chat-generated` WHERE (`id`='".$obj->remgen."' and `userid`='".$obj->userid."')";
$s = mysql_query($q, $cnMain) or die(mysql_error());

echo '{"response":"ok"}';
}
#check for removeGenerated users



##### FUNCTION FOR MERGECODES ############

function GetMergeCode($raw,$reciever,$owner){

global $database_cnMain;
global $cnMain;

mysql_select_db($database_cnMain, $cnMain);

$q = "SELECT * FROM `users` WHERE id='$reciever'";
$rsMergeCodes = mysql_query($q, $cnMain) or die(mysql_error());
$dataReciever = mysql_fetch_assoc($rsMergeCodes);

$qSystem = "SELECT * FROM `systeminfo`";
$rsSystem = mysql_query($qSystem, $cnMain) or die(mysql_error());
$dataSystem = mysql_fetch_assoc($rsSystem);



$query_rsContacts2 = "SELECT * FROM `users` WHERE id='$owner'";
$rsMergeCodes2 = mysql_query($query_rsContacts2, $cnMain) or die(mysql_error());
$dataOwner = mysql_fetch_assoc($rsMergeCodes2);



$raw = str_replace('#NAME#',$dataReciever['fname'] . " ". $dataReciever['lname'],$raw);
$raw = str_replace('#FNAME#',$dataReciever['fname'],$raw);
$raw = str_replace('#LNAME#',$dataReciever['lname'],$raw);
$raw = str_replace('#EMAIL#',$dataReciever['email'],$raw);
$raw = str_replace('#ADDRESS#',$dataReciever['address'],$raw);
$raw = str_replace('#ADDRESS2#',$dataReciever['address2'],$raw);
$raw = str_replace('#CITY#',$dataReciever['city'],$raw);
$raw = str_replace('#STATE#',$dataReciever['state'],$raw);
$raw = str_replace('#ZIP#',$dataReciever['zip'],$raw);
$raw = str_replace('#COUNTRY#',$dataReciever['country'],$raw);
$raw = str_replace('#TIMEZONE#',$dataReciever['timezone'],$raw);
$raw = str_replace('#BESTTIME#',$dataReciever['besttime'],$raw);
$raw = str_replace('#BUSINESS#',$dataReciever['business'],$raw);
$raw = str_replace('#DAYPHONE#',$dataReciever['dayphone'],$raw);
$raw = str_replace('#DAYPHONE_DIGITS#',$dataReciever['dayphone'],$raw);
$raw = str_replace('#EVEPHONE#',$dataReciever['evephone'],$raw);
$raw = str_replace('#EVEPHONE_DIGITS#',$dataReciever['evephone'],$raw);
$raw = str_replace('#CELLPHONE#',$dataReciever['cellphone'],$raw);
$raw = str_replace('#CELLPHONE_DIGITS#',$dataReciever['cellphone'],$raw);
$raw = str_replace('#FAX#',$dataReciever['fax'],$raw);
$raw = str_replace('#FAX_DIGITS#',$dataReciever['fax'],$raw);
$raw = str_replace('#OPTINDATETIME#',$dataReciever['optindatetime'],$raw);
$raw = str_replace('#SITE#',$dataReciever['site'],$raw);
$raw = str_replace('//naxumimages.com','http://naxumimages.com',$raw);

##chat specifics
$raw = str_replace('#CHATFNAME#','xxxCHATFNAMExxxx',$raw);
$raw = str_replace('#CHATLNAME#','xxxCHATLNAMExxx',$raw);
$raw = str_replace('#CHATEMAIL#','xxxCHATEMAILxxx',$raw);
$raw = str_replace('#CHATNAME#','xxxCHATNAMExxx',$raw);

##chat specifics


$raw = str_replace('#MY_NAME#',$dataOwner['fname'] . " ". $dataOwner['lname'],$raw);
$raw = str_replace('#MY_FNAME#',$dataOwner['fname'],$raw);
$raw = str_replace('#MY_LNAME#',$dataOwner['lname'],$raw);
$raw = str_replace('#MY_EMAIL#',$dataOwner['email'],$raw);
$raw = str_replace('#MY_ADDRESS#',$dataOwner['address'],$raw);
$raw = str_replace('#MY_ADDRESS2#',$dataOwner['address2'],$raw);
$raw = str_replace('#MY_CITY#',$dataOwner['city'],$raw);
$raw = str_replace('#MY_STATE#',$dataOwner['state'],$raw);
$raw = str_replace('#MY_ZIP#',$dataOwner['zip'],$raw);
$raw = str_replace('#MY_COUNTRY#',$dataOwner['country'],$raw);
$raw = str_replace('#MY_TIMEZONE#',$dataOwner['timezone'],$raw);
$raw = str_replace('#MY_BESTTIME#',$dataOwner['besttime'],$raw);
$raw = str_replace('#MY_BUSINESS#',$dataOwner['business'],$raw);
$raw = str_replace('#MY_DAYPHONE#',$dataOwner['dayphone'],$raw);
$raw = str_replace('#MY_DAYPHONE_DIGITS#',$dataOwner['dayphone'],$raw);
$raw = str_replace('#MY_EVEPHONE#',$dataOwner['evephone'],$raw);
$raw = str_replace('#MY_EVEPHONE_DIGITS#',$dataOwner['evephone'],$raw);
$raw = str_replace('#MY_CELLPHONE#',$dataOwner['cellphone'],$raw);
$raw = str_replace('#MY_CELLPHONE_DIGITS#',$dataOwner['cellphone'],$raw);
$raw = str_replace('#MY_FAX#',$dataOwner['fax'],$raw);
$raw = str_replace('#MY_FAX_DIGITS#',$dataOwner['fax'],$raw);
$raw = str_replace('#MY_OPTINDATETIME#',$dataOwner['optindatetime'],$raw);

$raw = str_replace('#MY_SITE#',$dataOwner['site'],$raw);






$personalInfo = '<br />Name: '.$dataReciever['fname'].' '. $dataReciever['lname']. '<br />'.'Email: '.$dataReciever['email'] . '<br />'.'Address: '.$dataReciever['address'] .'<br />' .$dataReciever['address2'] . '<br />'.$dataReciever['city'] .', '. $dataReciever['state'] .' ' .  $dataReciever['zip'] . '<br />'.$dataReciever['country'] . '<br />'.'Day Phone: '. $dataReciever['dayphone'] . '<br />'.'Eve Phone: '. $dataReciever['evephone'] .'<br />'.'Cell Phone:  '. $dataReciever['cellphone'] . '<br />'.'Time Zone: '. $dataReciever['timezone'] . '<br />'.'Best Time to Contact: '. $dataReciever['besttime'] . '<br />';
$personalInfoOwner = 'Name: '.$dataOwner['fname'].' '. $dataOwner['lname']. '<br />'.'Email: '.$dataOwner['email'] . '<br />'.'Address: '.$dataOwner['address'] .'<br />' .$dataOwner['address2'] . '<br />'.$dataOwner['city'] .', '. $dataOwner['state'] .' ' .  $dataOwner['zip'] . '<br />'.$dataOwner['country'] . '<br />'.'Day Phone: '. $dataOwner['dayphone'] . '<br />'.'Eve Phone: '. $dataOwner['evephone'] .'<br />'.'Cell Phone:  '. $dataOwner['cellphone'] . '<br />'.'Time Zone: '. $dataOwner['timezone'] . '<br />'.'Best Time to Contact: '. $dataOwner['besttime'] . '<br />';




$raw = str_replace('#PERSONALINFO#',$personalInfo,$raw);

$raw = str_replace('#MY_PERSONALINFO#',$personalInfoOwner,$raw);
## START SYSTEMS 

$raw = str_replace('#SYSTEM#',$dataSystem['company'],$raw);


## END SYSTEMS 


/*
#ABOUT_YOURSELF#

#CURRENT_OCCUPATION#
#DESIRED_INCOME#
#DOB#
#GENDER#
#HOURS_A_WEEK#
#INTEREST_LEVEL#
#MLM_BEFORE#
#MONEY_TO_INVEST#
#PROSPECT_COMMENTS#

#START_WHEN#
#MY_SITE#
#MY_MEMBERID#
#MY_FACEBOOK#
#MY_TWITTER#
#MY_WORDPRESS#
#MY_YOUTUBE#
#MY_PICTURE#
#MY_PICTURE_WH#
#DOMAIN#
#DATE#
#DATE+3#
#DATE-3#
#DATE+365#
#DATE-1M#
#DATE+10M#
#DATE+1Y#
#DATE+0Y+10M+0D#
#DATE+1Y+2M+4D#
#DATE+1Y-1M+4D#

*/




return $raw;
}


function UserData($id,$data){
$hostname_cnMain = "dbserver";
$database_cnMain = $_SESSION['db'];
$username_cnMain = "solomon";
$password_cnMain = "xZn8GowU>4s6Pyf";
$cnMain = mysql_pconnect($hostname_cnMain, $username_cnMain, $password_cnMain) or trigger_error(mysql_error(),E_USER_ERROR); 
mysql_select_db($database_cnMain, $cnMain);

$q = "SELECT * FROM `users` WHERE id='$id'";
$rs = mysql_query($q, $cnMain) or die(mysql_error());
$userData = mysql_fetch_assoc($rs);

return $userData[$data];


}



function getCategoryName($catID){
global $database_cnMain;
global $cnMain;

mysql_select_db($database_cnMain, $cnMain);

$q = "SELECT * FROM `categories` WHERE id='$catID'";
$rs = mysql_query($q, $cnMain) or die(mysql_error());
$categName = mysql_fetch_assoc($rs);

return $categName['category'];


}



function GetUserID($un){
global $database_cnMain;
global $cnMain;

mysql_select_db($database_cnMain, $cnMain);

$q = "SELECT * FROM `users` WHERE `site`='".$un."'";
$rs = mysql_query($q, $cnMain) or die(mysql_error());
$userData = mysql_fetch_assoc($rs);

return $userData['id'];


}

function GetNotes($xid){
global $database_cnMain;
global $cnMain;

mysql_select_db($database_cnMain, $cnMain);

$q = "SELECT * FROM `usernotes` WHERE `userid`='".$xid."'";
$rs = mysql_query($q, $cnMain) or die(mysql_error());
$UserNotes = mysql_fetch_assoc($rs);

return $UserNotes['notes'];


}




##### END FUNCTION FOR MERGECODES ############


##functions for useragents:

/**
 * Parses a user agent string into its important parts
 *
 * @author Jesse G. Donat <donatj@gmail.com>
 * @link https://github.com/donatj/PhpUserAgent
 * @link http://donatstudios.com/PHP-Parser-HTTP_USER_AGENT
 * @param string|null $u_agent User agent string to parse or null. Uses $_SERVER['HTTP_USER_AGENT'] on NULL
 * @throws InvalidArgumentException on not having a proper user agent to parse.
 * @return string[] an array with browser, version and platform keys
 */
function parse_user_agent( $u_agent = null ) {
	if( is_null($u_agent) ) {
		if( isset($_SERVER['HTTP_USER_AGENT']) ) {
			$u_agent = $_SERVER['HTTP_USER_AGENT'];
		} else {
			throw new \InvalidArgumentException('parse_user_agent requires a user agent');
		}
	}
	$platform = null;
	$browser  = null;
	$version  = null;
	$empty = array( 'platform' => $platform, 'browser' => $browser, 'version' => $version );
	if( !$u_agent ) return $empty;
	if( preg_match('/\((.*?)\)/im', $u_agent, $parent_matches) ) {
		preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iPhone|iPad|iPod|Linux|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|(New\ )?Nintendo\ (WiiU?|3?DS)|Xbox(\ One)?)
				(?:\ [^;]*)?
				(?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);
		$priority           = array( 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android' );
		$result['platform'] = array_unique($result['platform']);
		if( count($result['platform']) > 1 ) {
			if( $keys = array_intersect($priority, $result['platform']) ) {
				$platform = reset($keys);
			} else {
				$platform = $result['platform'][0];
			}
		} elseif( isset($result['platform'][0]) ) {
			$platform = $result['platform'][0];
		}
	}
	if( $platform == 'linux-gnu' ) {
		$platform = 'Linux';
	} elseif( $platform == 'CrOS' ) {
		$platform = 'Chrome OS';
	}
	preg_match_all('%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|Safari|MSIE|Trident|AppleWebKit|TizenBrowser|Chrome|
			Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edge|CriOS|
			Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
			NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
			(?:\)?;?)
			(?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
		$u_agent, $result, PREG_PATTERN_ORDER);
	// If nothing matched, return null (to avoid undefined index errors)
	if( !isset($result['browser'][0]) || !isset($result['version'][0]) ) {
		if( preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result) ) {
			return array( 'platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null : null );
		}
		return $empty;
	}
	if( preg_match('/rv:(?P<version>[0-9A-Z.]+)/si', $u_agent, $rv_result) ) {
		$rv_result = $rv_result['version'];
	}
	$browser = $result['browser'][0];
	$version = $result['version'][0];
	$lowerBrowser = array_map('strtolower', $result['browser']);
	$find = function ( $search, &$key ) use ( $lowerBrowser ) {
		$xkey = array_search(strtolower($search), $lowerBrowser);
		if( $xkey !== false ) {
			$key = $xkey;
			return true;
		}
		return false;
	};
	$key  = 0;
	$ekey = 0;
	if( $browser == 'Iceweasel' ) {
		$browser = 'Firefox';
	} elseif( $find('Playstation Vita', $key) ) {
		$platform = 'PlayStation Vita';
		$browser  = 'Browser';
	} elseif( $find('Kindle Fire', $key) || $find('Silk', $key) ) {
		$browser  = $result['browser'][$key] == 'Silk' ? 'Silk' : 'Kindle';
		$platform = 'Kindle Fire';
		if( !($version = $result['version'][$key]) || !is_numeric($version[0]) ) {
			$version = $result['version'][array_search('Version', $result['browser'])];
		}
	} elseif( $find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS' ) {
		$browser = 'NintendoBrowser';
		$version = $result['version'][$key];
	} elseif( $find('Kindle', $key) ) {
		$browser  = $result['browser'][$key];
		$platform = 'Kindle';
		$version  = $result['version'][$key];
	} elseif( $find('OPR', $key) ) {
		$browser = 'Opera Next';
		$version = $result['version'][$key];
	} elseif( $find('Opera', $key) ) {
		$browser = 'Opera';
		$find('Version', $key);
		$version = $result['version'][$key];
	} elseif( $find('Midori', $key) ) {
		$browser = 'Midori';
		$version = $result['version'][$key];
	} elseif( $browser == 'MSIE' || ($rv_result && $find('Trident', $key)) || $find('Edge', $ekey) ) {
		$browser = 'MSIE';
		if( $find('IEMobile', $key) ) {
			$browser = 'IEMobile';
			$version = $result['version'][$key];
		} elseif( $ekey ) {
			$version = $result['version'][$ekey];
		} else {
			$version = $rv_result ?: $result['version'][$key];
		}
		if( version_compare($version, '12', '>=') ) {
			$browser = 'Edge';
		}
	} elseif( $find('Vivaldi', $key) ) {
		$browser = 'Vivaldi';
		$version = $result['version'][$key];
	} elseif( $find('Chrome', $key) || $find('CriOS', $key) ) {
		$browser = 'Chrome';
		$version = $result['version'][$key];
	} elseif( $browser == 'AppleWebKit' ) {
		if( ($platform == 'Android' && !($key = 0)) ) {
			$browser = 'Android Browser';
		} elseif( strpos($platform, 'BB') === 0 ) {
			$browser  = 'BlackBerry Browser';
			$platform = 'BlackBerry';
		} elseif( $platform == 'BlackBerry' || $platform == 'PlayBook' ) {
			$browser = 'BlackBerry Browser';
		} elseif( $find('Safari', $key) ) {
			$browser = 'Safari';
		} elseif( $find('TizenBrowser', $key) ) {
			$browser = 'TizenBrowser';
		}
		$find('Version', $key);
		$version = $result['version'][$key];
	} elseif( $key = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser'])) ) {
		$key = reset($key);
		$platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $key);
		$browser  = 'NetFront';
	}
	return array( 'platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null );
}

##functions for useragents:
?>

