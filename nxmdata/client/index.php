//PULSATE:

(function(a){a.titleAlert=function(e,c){if(a.titleAlert._running){a.titleAlert.stop()}a.titleAlert._settings=c=a.extend({},a.titleAlert.defaults,c);if(c.requireBlur&&a.titleAlert.hasFocus){return}c.originalTitleInterval=c.originalTitleInterval||c.interval;a.titleAlert._running=true;a.titleAlert._initialText=document.title;document.title=e;var b=true;var d=function(){if(!a.titleAlert._running){return}b=!b;document.title=(b?e:a.titleAlert._initialText);a.titleAlert._intervalToken=setTimeout(d,(b?c.interval:c.originalTitleInterval))};a.titleAlert._intervalToken=setTimeout(d,c.interval);if(c.stopOnMouseMove){a(document).mousemove(function(f){a(this).unbind(f);a.titleAlert.stop()})}if(c.duration>0){a.titleAlert._timeoutToken=setTimeout(function(){a.titleAlert.stop()},c.duration)}};a.titleAlert.defaults={interval:500,originalTitleInterval:null,duration:0,stopOnFocus:true,requireBlur:false,stopOnMouseMove:false};a.titleAlert.stop=function(){clearTimeout(a.titleAlert._intervalToken);clearTimeout(a.titleAlert._timeoutToken);document.title=a.titleAlert._initialText;a.titleAlert._timeoutToken=null;a.titleAlert._intervalToken=null;a.titleAlert._initialText=null;a.titleAlert._running=false;a.titleAlert._settings=null};a.titleAlert.hasFocus=true;a.titleAlert._running=false;a.titleAlert._intervalToken=null;a.titleAlert._timeoutToken=null;a.titleAlert._initialText=null;a.titleAlert._settings=null;a.titleAlert._focus=function(){a.titleAlert.hasFocus=true;if(a.titleAlert._running&&a.titleAlert._settings.stopOnFocus){var b=a.titleAlert._initialText;a.titleAlert.stop();setTimeout(function(){if(a.titleAlert._running){return}document.title=".";document.title=b},1000)}};a.titleAlert._blur=function(){a.titleAlert.hasFocus=false};a(window).bind("focus",a.titleAlert._focus);a(window).bind("blur",a.titleAlert._blur)})(jQuery);




<?php
//see if the user have good catid
include('../api/index.php');
mysql_select_db($database_cnMain, $cnMain);
$q = "SELECT
categorymap.catid,
categorymap.userid,
users.id,
users.sponsorid,
users.leaderid,
users.site
FROM
categorymap
INNER JOIN users ON categorymap.userid = users.id
where id= '".$_GET['s']."'";
$s = mysql_query($q, $cnMain) or die(mysql_error());
while ($c = mysql_fetch_assoc($s)) {

$catid = $c['catid'];

}




//see if the user have good catid














// we start here for sessioning and creating the cookie for the room
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}
function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}
?>
// CREATED BY SOLOMON COPYRIGHT TO NAXUM 2015

<?php if($catid!=13){?>
var final_transcript = '';
var recognizing = false;
var last10messages = []; //to be populated later



if (!('webkitSpeechRecognition' in window)) {
  
	$('#start_button').addClass('hidden');
} else {
  var recognition = new webkitSpeechRecognition();
  recognition.continuous = true;
  recognition.interimResults = true;

  recognition.onstart = function() {
    recognizing = true;
  };

  recognition.onresult = function(event) {
    var interim_transcript = '';
    for (var i = event.resultIndex; i < event.results.length; ++i) {
      if (event.results[i].isFinal) {
        final_transcript += event.results[i][0].transcript;
        $('#chat-input-msg').addClass("final");
        $('#chat-input-msg').removeClass("interim");
      } else {
        interim_transcript += event.results[i][0].transcript;
        $("#chat-input-msg").val(interim_transcript);
        $('#chat-input-msg').addClass("interim");
        $('#chat-input-msg').removeClass("final");
      }
    }
    $("#chat-input-msg").val(final_transcript);
    };
  }

  function startButton(event) {
    if (recognizing) {
      recognition.stop();
      recognizing = false;
      $("#start_button").prop("value", "Record");
      return;
    }
    final_transcript = '';
    recognition.lang = "en-GB"
    recognition.start();
    $("#start_button").prop("value", "Recording ... Click to stop.");
    $("#chat-input-msg").val();
  }
//end of WebSpeech

/*
Functions
*/
function toggleNameForm() {
   $("#login-screen").toggle();
}

function toggleChatWindow() {
  $("#main-chat-screen").toggle();
}

// Pad n to specified size by prepending a zeros
function zeroPad(num, size) {
  var s = num + "";
  while (s.length < size)
    s = "0" + s;
  return s;
}

// Format the time specified in ms from 1970 into local HH:MM:SS
function timeFormat(msTime) {
  var d = new Date(msTime);
  return zeroPad(d.getHours(), 2) + ":" +
    zeroPad(d.getMinutes(), 2) + ":" +
    zeroPad(d.getSeconds(), 2) + " ";
}

//START Functions

function createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name,"",-1);
}









function initChatScript(name,ret){
var html ='';

  var now = moment().unix();
html +='<ul class="lc-chat" id="chat-msgs" style="min-height: 200px;max-height: 300px;overflow-x: hidden;overflow-y: auto;">';
html +='<li class="left clearfix">';
html +='<strong class="primary-font">Welcome to Live Chat '+name+'!</strong>';
html +='</li>';
//html += getHistoryNow(readCookie('roomid2'));

		$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getChatHistoryQuickClient",
				userid: ret
				
			}),
			success: function(data) {
				
				if (data != undefined){
					$.each(data, function(k, v) {
				html +='<li class="left clearfix"><span class="chat-img pull-left"></span><div class="clearfix" style="word-wrap: break-word;"><div class="header"><strong class="primary-font">'+v.person+'</strong> <small class="pull-right text-muted"><span class="glyphicon glyphicon-time"></span><span data-livestamp="'+v.time+'"></span></small></div><p>'+v.msg+'</p></div></li>';
				});	
				}
			
				
			
				
				
				
			}
	});


html +='<li class="left clearfix"><span class="chat-img pull-left"></span><div class="clearfix" style="word-wrap: break-word;"><div class="header"><strong class="primary-font"></strong> <small class="pull-right text-muted"><span class="glyphicon glyphicon-time"></span><span data-livestamp="'+now+'"></span></small></div><p>Our team will be in touch with you in a moment... please wait.</p></div></li>';


html +='</ul>';
html +='</div>';
html +='<div class="inline-form">';
html +='<div class="lc-panel-footer">';

html +='<div class="lc-input-group">';
html +='<input type="text" autocomplete="off" placeholder="Type your message here..." class="lc-form-control lc-input-sm" id="chat-input-msg">';
html +='<button id="btn-chat" class="lcbtn lcbtn-warning lcbtn-sm">Send</button>';
html +='<span id="updates"></span>';
html +='</div>';

html +='</div>';
html +='</div>';

return html;
}

function initChatScriptOffline(){
var html ='';

html +='<div class="headadj">';
html +='	        <div class="main-container">';
html +=' <div class="lc-md-3" style="float: right; bottom: 0px; position: fixed; right: 0px; padding: 0px; margin-bottom: 0px;z-index:99999999;">';
html +='<div class="lc-panel lc-panel-primary" style="margin-bottom:0px;">';
html +='<div class="lc-panel-heading" id="lc-accordion" data-toggle="lccollapse" data-parent="#lc-accordion" href="#cboffline" style="cursor:pointer;">';
html +='<span class="lc-glyphicon lc-glyphicon-comment"></span> Live Chat - offline <i class="arrower fa fa-chevron-circle-up pull-right" style="font-size: 25px;"></i>';
html +='</div>';
html +='<div class="lc-panel-collapse collapse" id="cboffline">';
html +='<div class="cb panel-body" id="msg-body" style="min-height: 200px;max-height: 300px;overflow-x: hidden;overflow-y: auto;">';
html +='<div id="offflinemsgr">';
html +='<form id="chatSubmissionScript" name="signupform" method="post" action="/cgi/signup.cgi"  class="lc-form-horizontal">';
html +='<p style="margin-top: 0px;margin-bottom: 3px">We are not available at this time. Please leave a message. Thank you.</p>';
html +='<input type="hidden" value="<?php echo $_GET["s"]?>" name="sponsorid">';
html +='<input type="hidden" value="Prospect" name="level">';
html +='<input type="hidden" value="8182" name="catid">';
html +='<input type="hidden" value="10" name="retcatid">';

html +='<input type="hidden" value="Submit" name="baction">';
html +='<input type="hidden" value="1" name="use_mf_only"><input type="hidden" name="required_mf" value="fname,lname,email" /><input type="hidden" name="required_desc" value="Your First Name,Your Last Name,Your Valid Email" />';
html +='<input type="text" class="lc-form-control" name="fname" placeholder="first name" />';
html +='<input type="text" class="lc-form-control" name="lname" placeholder="last name" />';
html +='<input type="text" class="lc-form-control" name="email" placeholder="email address" />';
html +='<textarea class="lc-form-control" name="notes"  placeholder="type your message here.."></textarea>';
html +='<button class="lcbtn lcbtn-warning lcbtn-sm" id="lc-btn-submit-offline-msg">Send</button>';

html +='</form>';
html +='</div>';
html +='</div>';
html +='</div>';
html +='</div>';
html +='</div>';
html +='</div>';
html +='</div>';

return html;
}



function initChatScriptOnline(){
	  var now = moment().unix();
var html ='';
var kicked ='';

kicked +='<div class="cb panel-body" id="msg-kicked" style="min-height: 200px;display:none;">';
kicked +='<ul class="lc-chat" id="msgs-kik" style="min-height: 200px;max-height: 300px;overflow-x: hidden;overflow-y: auto;">';
kicked +='<li class="left clearfix"><span class="chat-img pull-left"></span>';
kicked +='<div class="clearfix" style="word-wrap: break-word;">';
kicked +='<div class="header"><strong class="primary-font"></strong> <small class="pull-right text-muted"><span class="glyphicon glyphicon-time"></span><span data-livestamp="'+now+'"></span></small></div>';
kicked +='<p>Your Chat Session has ended.</p>';
kicked +='</div>';
kicked +='</li>';
kicked +='</ul>';
kicked +='</div>';


html +='<div class="headadj">';
html +='	        <div class="main-container">';
html +=' <div class="lc-md-3" style="float: right; bottom: 0px; position: fixed; right: 0px; padding: 0px; margin-bottom: 0px;z-index:99999999;">';
html +='<div class="lc-panel lc-panel-primary" style="margin-bottom:0px;">';
html +='<div class="lc-panel-heading" id="lc-accordion" data-toggle="lccollapse" data-parent="#lc-accordion" href="#cb6" style="cursor:pointer;">';
html +='<span class="lc-glyphicon lc-glyphicon-comment"></span> Live Chat - online <i class="arrower fa fa-chevron-circle-up pull-right" style="font-size: 25px;"></i>';
html +='</div>';
html +='<div class="lc-panel-collapse collapse" id="cb6">';

html +=kicked;

html +='<div class="cb panel-body" id="msg-body" style="min-height: 200px;">';
html +='<div id="offflinemsgr">';
html +='<form id="chatSubmissionScriptOnline" name="chatSubmissionScript" method="post" action="/cgi/signup.cgi" class="lc-form-horizontal">';
html +='<p>Hi! Welcome to Live Chat! please type in your information!</p>';
html +='<input type="hidden" value="<?php echo $_GET["s"]?>" name="sponsorid">';
html +='<input type="hidden" value="Prospect" name="level">';
html +='<input type="hidden" value="8182" name="catid">';
html +='<input type="hidden" value="10" name="retcatid">';
html +='<input type="hidden" value="Submit" name="baction">';
html +='<input type="hidden" value="1" name="use_mf_only"><input type="hidden" name="required_mf" value="fname,lname,email" /><input type="hidden" name="required_desc" value="Your First Name,Your Last Name,Your Valid Email" />';
html +='<input type="text" class="lc-form-control" id="chatScriptName" name="fname" placeholder="first name" />';
html +='<input type="text" class="lc-form-control" id="chatLname" name="lname" placeholder="last name" />';
html +='<input type="text" class="lc-form-control" id="chatEmail" name="email" placeholder="email address" />';
html +='<textarea class="lc-form-control" name="notes" id="theInitialQuestion" placeholder="type your message here.."></textarea>';
html +='<input type="hidden" value="Send"/>';

html +='</form>';
html +='<button class="lcbtn lcbtn-warning lcbtn-sm" id="lc-btn-submit-online-msg">Send</button>';
html +='</div>';
html +='</div>';
html +='</div>';
html +='</div>';
html +='</div>';
html +='</div>';
html +='</div>';

return html;
}

function MakeRoomID(x){
    var s = "";
    while(s.length<x&&x>0){
        var r = Math.random();
        s+= (r<0.1?Math.floor(r*100):String.fromCharCode(Math.floor(r*26) + (r>0.5?97:65)));
    }
    return s;
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function generateChatScript(returningVisitor){
var name,roomid,initialquestion,returningz,chatScriptName,chatLname,chatEmail;

var socket = io.connect("https://office.toolsrock.com:3000");		

	
	if(returningVisitor){
		$.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "returningCbUser",
				roomid: returningVisitor
			}),
			success: function(data) {
				name = data.user;
				
			}
	});

	roomid = returningVisitor;
	returningz = 'dead';
	}else{
		
		chatLname = capitalizeFirstLetter($('#chatLname').val());
		name = capitalizeFirstLetter($('#chatScriptName').val()) + " " +  chatLname.slice(0,1);
		roomid = MakeRoomID(20);
		initialquestion = $('#theInitialQuestion').val();
		
		chatEmail = $('#chatEmail').val();
		createCookie('roomid2', roomid, 360);
	}
	var sponsorid = "<?php echo $_GET['s']?>";
	var userip = "<?php echo $ip?>";
	var levelid = "<?php echo $_GET['levelid']?>";
	var device = navigator.userAgent;
	var urlorigin = window.location.href;

	socket.emit("joinserver", name, device);
	socket.emit("check", name, function(data) {
      roomExists = data.result;
       if (!roomExists) {
        if (name.length > 0) { //also check for roomname
          socket.emit("createRoom", name,sponsorid,levelid,roomid,device,initialquestion,userip,returningz,urlorigin,chatLname,chatEmail);
          }
        }
    });

$('#msg-body').html('');
$('#msg-body').html(initChatScript(name,returningVisitor));



  //main chat screen
  
  $('#chat-input-msg').keypress(function (e) {
 var key = e.which;
 if(key == 13)  // the enter key code
  {
    
	  var msg = $("#chat-input-msg").val();
    if (msg !== "") {
      socket.emit("send", new Date().getTime(), msg);
      $("#chat-input-msg").val("");
	  $("#chat-input-msg").focus();
	  return false;
    }
	
	
    return false;  
  }
});  


   
  $("#btn-chat").click(function(event) {
	  
	  event.stopImmediatePropagation();
	event.preventDefault();
	
    var msg = $("#chat-input-msg").val();
    if (msg !== "") {
      socket.emit("send", new Date().getTime(), msg);
      $("#chat-input-msg").val("");
	  $("#chat-input-msg").focus();
	  return false;
    }
	
	
  });





  $("#chat-input-msg").keypress(function(e){
	    //'is typing' message
  var typing = false;
  var timeout = undefined;
    socket.on("sendRoomID", function(data) {
    var myRoomID = data.id;
  });
  
    if (e.which !== 13) {
      if (typing === false && myRoomID !== null && $("#chat-input-msg").is(":focus")) {
        typing = true;
        socket.emit("typing", true);
      } else {
        clearTimeout(timeout);
        timeout = setTimeout(timeoutFunction, 5000);
      }
    }
  });
  
    socket.on("isTyping", function(data) {
		  //'is typing' message
  var typing = false;
  var timeout = undefined;
  
    if (data.isTyping) {
      if ($("#"+data.person+"").length === 0) {
		  $("#updates").html('');
        $("#updates").append("<small><i class='fa fa-keyboard-o'></i> " + data.person + " is typing.</small>");
		clearTimeout(timeout);
        timeout = setTimeout(timeoutFunction, 5000);
      }
    } else {
      $("#updates").html('');
    }
  });
  
  
  socket.on("sendRoomID", function(data) {
    myRoomID = data.id;
  });
  
    function timeoutFunction() {
    typing = false;
    socket.emit("typing", false);
  }
  
}


$(document).ready(function() {
var oldscrollHeight = $("#chat-msgs").prop("scrollHeight") - 20; //Scroll height before the request
	
$('#chatboxContent').html('');
$.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "isOnline",
				sponsorid: "<?php echo $_GET['s']?>"
			}),
			success: function(data) {
				var oldscrollHeight = $("#chat-msgs").prop("scrollHeight") - 20; //Scroll height before the request
				if(data.online==1){
					//if the user is online, let's build the chat script view!
					if(readCookie('roomid2')){
						$('#chatboxContent').html(initChatScriptOnline());
						
						$.ajax({
							type: "POST",
							dataType: 'json',
							async: false,
							contentType: 'application/json',
							url: "https://office.toolsrock.com:81/api/",
							data: JSON.stringify({
								method: "isDupOnline",
								roomid: readCookie('roomid2')
							}),
							success: function(data) {
								if(data){
										$('#offflinemsgr').html('<h1>You can only have one chat session at a time.</h1>');
								}
								else{
									
									
									generateChatScript(readCookie('roomid2'));
									
									
								}
							}
							});
					
						
						
					}else{
						$('#chatboxContent').html(initChatScriptOnline());
					}
				}else{
					//if not break the script :D
					$('#chatboxContent').html(initChatScriptOffline());
					
				}
			}
			
});

//

$(document).on('click', '#lc-btn-submit-offline-msg', function(e) {
	 e.preventDefault();
		$.ajax({
			url: "/cgi/signup.cgi",
			type: "post",
			data: $('#chatSubmissionScript').serialize(),
			success: function(d) {
				
			}
			
		})
		$('#offflinemsgr').html('<h1>We have received your message. We\'ll contact you shortly!</h1>');
});


$(document).on('click', '#lc-btn-submit-online-msg', function(e) {
	 e.preventDefault();
		$.ajax({
			url: "/cgi/signup.cgi",
			type: "post",
			data: $('#chatSubmissionScriptOnline').serialize(),
			success: function(d) {
				e.preventDefault();
			}
		})
		
	generateChatScript(readCookie('roomid2'));
	e.preventDefault();
	$("#chat-input-msg").focus();
});
//
function timeoutFunction() {
    typing = false;
    socket.emit("typing", false);
}

var socket = io.connect("https://office.toolsrock.com:3000");

  //setup "global" variables first
   
  var myRoomID = null;


//put here scroller
  

  $("#errors").hide();





  
  socket.on("kickUser", function(data) {
		
  });
  
socket.on("exists", function(data) {
	
   $("#errors").empty();
   $("#errors").show();
   $("#errors").append("<strong>You already have an active support window! please close that one and refresh this one to activate the chat.</strong>");
    // toggleNameForm();
     toggleChatWindow();
});

socket.on("joined", function() {

});

socket.on("history", function(data) {
  if (data.length !== 0) {
    $("#chat-msgs").append("<li><strong><span class='text-warning'>Last 10 messages:</li>");
    $.each(data, function(data, msg) {
      $("#chat-msgs").append("<li><span class='text-warning'>" + msg + "</span></li>");
    });
  } else {
    $("#chat-msgs").append("<li><strong><span class='text-warning'>No past messages in this room.</li>");
  }
});

  socket.on("update", function(msg) {
	  
	  
	  



	


    $("#chat-msgs").append("<li>" + msg + "</li>");
	

	  
	  
	  
	  
	  
  });




  socket.on("chat", function(msTime, person, msg) {
	  var oldscrollHeight = $("#chat-msgs").prop("scrollHeight") - 20; //Scroll height before the request
	    var typing = false;
  var timeout = undefined;



	  
	    $.extend({
    playSound: function(){
      return $("<embed src='"+arguments[0]+".mp3' hidden='true' autostart='true' loop='false' class='playSound'>" + "<audio autoplay='autoplay' style='display:none;' controls='controls'><source src='"+arguments[0]+".mp3' /><source src='"+arguments[0]+".ogg' /></audio>").appendTo('body');
    }
  });
  
	   var now = moment().unix();
		var html ='';
		
		   html+='<li class="left clearfix"><span class="chat-img pull-left">';
		   html+='</span>';
		   html+='<div class="clearfix" style="word-wrap: break-word;">';
		   html+='<div class="header">';
		   html+='<strong class="primary-font">'+person.name+'</strong> <small class="pull-right text-muted">';
		   html+='<span class="glyphicon glyphicon-time"></span><span data-livestamp="'+now+'"></span></small>';
		   html+='</div>';
		   html+='<p>'+msg+'</p></div>';
		   html+='<li tabindex="1" style="border-bottom:none;margin-bottom:0px;padding-bottom:0px;"></li>';
		
		html+='<li tabindex="1" style="border-bottom:none;margin-bottom:0px;padding-bottom:0px;"></li>';
		 $('#chat-msgs').append(html);
		



    //$("#msgs").append("<li><strong><span class='text-success'>" + timeFormat(msTime) + person.name + "</span></strong>: " + msg + "</li>");
    //clear typing field
     $("#"+person.name+"").remove();
     clearTimeout(timeout);
     timeout = setTimeout(timeoutFunction, 0);
	 
	$.playSound('https://office.toolsrock.com/clenk');
	


	
if(!$("#cb6").hasClass('in')){
	$("#cb6").addClass('in');
}


(function(a){a.titleAlert=function(e,c){if(a.titleAlert._running){a.titleAlert.stop()}a.titleAlert._settings=c=a.extend({},a.titleAlert.defaults,c);if(c.requireBlur&&a.titleAlert.hasFocus){return}c.originalTitleInterval=c.originalTitleInterval||c.interval;a.titleAlert._running=true;a.titleAlert._initialText=document.title;document.title=e;var b=true;var d=function(){if(!a.titleAlert._running){return}b=!b;document.title=(b?e:a.titleAlert._initialText);a.titleAlert._intervalToken=setTimeout(d,(b?c.interval:c.originalTitleInterval))};a.titleAlert._intervalToken=setTimeout(d,c.interval);if(c.stopOnMouseMove){a(document).mousemove(function(f){a(this).unbind(f);a.titleAlert.stop()})}if(c.duration>0){a.titleAlert._timeoutToken=setTimeout(function(){a.titleAlert.stop()},c.duration)}};a.titleAlert.defaults={interval:500,originalTitleInterval:null,duration:0,stopOnFocus:true,requireBlur:false,stopOnMouseMove:false};a.titleAlert.stop=function(){clearTimeout(a.titleAlert._intervalToken);clearTimeout(a.titleAlert._timeoutToken);document.title=a.titleAlert._initialText;a.titleAlert._timeoutToken=null;a.titleAlert._intervalToken=null;a.titleAlert._initialText=null;a.titleAlert._running=false;a.titleAlert._settings=null};a.titleAlert.hasFocus=true;a.titleAlert._running=false;a.titleAlert._intervalToken=null;a.titleAlert._timeoutToken=null;a.titleAlert._initialText=null;a.titleAlert._settings=null;a.titleAlert._focus=function(){a.titleAlert.hasFocus=true;if(a.titleAlert._running&&a.titleAlert._settings.stopOnFocus){var b=a.titleAlert._initialText;a.titleAlert.stop();setTimeout(function(){if(a.titleAlert._running){return}document.title=".";document.title=b},1000)}};a.titleAlert._blur=function(){a.titleAlert.hasFocus=false};a(window).bind("focus",a.titleAlert._focus);a(window).bind("blur",a.titleAlert._blur)})(jQuery);

 
 $.titleAlert("New chat message!", {
    requireBlur:false,
    stopOnFocus:false,
    duration:4000,
    interval:700
});




var newscrollHeight = $("#chat-msgs").prop("scrollHeight") - 20; 
if(newscrollHeight > oldscrollHeight){
$("#chat-msgs").animate({ scrollTop: newscrollHeight }, 'normal');
}
$('#chat-input-msg').focus();



  
  });

  socket.on("whisper", function(msTime, person, msg) {
    if (person.name === "You") {
      s = "whisper"
    } else {
      s = "whispers"
    }
    $("#chat-msgs").append("<li><strong><span class='text-muted'>" + timeFormat(msTime) + person.name + "</span></strong> "+s+": " + msg + "</li>");
  });

  
  



  socket.on("disconnect", function(){
    $("#chat-msgs").append("<li><strong><span class='text-warning'>You have been disconnected from the server please refresh.</span></strong></li>");

  });


  
  
  var newscrollHeight = $("#chat-msgs").prop("scrollHeight") - 20; 
if(newscrollHeight > oldscrollHeight){
$("#chat-msgs").animate({ scrollTop: newscrollHeight }, 'normal');
}

});


$(document).on('click', '#lc-accordion', function(e) {
	
	
	if($('.arrower').hasClass('fa-chevron-circle-up')){
		$('.arrower').removeClass('fa-chevron-circle-up');
		$('.arrower').addClass('fa-chevron-circle-down');
		
	}else{
		$('.arrower').addClass('fa-chevron-circle-up');
		$('.arrower').removeClass('fa-chevron-circle-down');
	}
	if(!$(this).hasClass('in')){
		$(this).addClass('in');
	}
	
	
	$('#chat-input-msg').focus(function () {
		
		$("#chat-msgs").animate({ scrollTop: $("#chat-msgs").prop("scrollHeight") - 20 }, 'normal');
		
	});
	
	
});


(function($){

  $.extend({
    playSound: function(){
      return $("<embed src='"+arguments[0]+".mp3' hidden='true' autostart='true' loop='false' class='playSound'>" + "<audio autoplay='autoplay' style='display:none;' controls='controls'><source src='"+arguments[0]+".mp3' /><source src='"+arguments[0]+".ogg' /></audio>").appendTo('body');
    }
  });

  
  
  
})(jQuery);


<?php } ?>


