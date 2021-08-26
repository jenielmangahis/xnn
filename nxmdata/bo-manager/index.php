/* HTML5 magic
- GeoLocation
- WebSpeech
*/
<?php include ('../api/index.php')?>
//WebSpeech API
var final_transcript = '';
var recognizing = false;
var last10messages = []; //to be populated later

if (!('webkitSpeechRecognition' in window)) {
  console.log("webkitSpeechRecognition is not available");
  $('#start_button').hide();
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
		$("#chat-input-msg").val('');
		interim_transcript ='';
		
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
      $("#start_button").prop("value", "Voice");
      return;
    }
    final_transcript = '';
    recognition.lang = "en-GB"
    recognition.start();
    $("#start_button").prop("value", "Stop");
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

$(document).ready(function() {
  //setup "global" variables first
  
    $("#chat-msgs").append("<li><strong>Welcome to your LiveChat portal! When someone go online they will be displayed on the left side. Click on the \"Assist Button\" to start a conversation!</strong><li>");
	
  var socket = io.connect("//office.toolsrock.com:3000");
  var myRoomID = null;

  $("form").submit(function(event) {
    event.preventDefault();
	


  });

//add scroller here

  
  $("#errors").hide();
  

 
var  theStat = $("#chatstat").val();
var uid = '<?php echo $_GET['u']?>';

		$.ajax({
		type: "POST",
		dataType:'json',
		async: false,
		contentType :'application/json' ,
		url: "/API/",
		data: JSON.stringify({ method: "setChatStatus", uid: uid,chatStats: theStat }),
		success: function(data) {
		
			if(theStat==1){
				$('#chatIsOnline').html('Online');
				}else{
				$('#chatIsOnline').html('Offline');
				}
				
			}
		}); 
		
  

    var name = '<?php echo ucfirst(UserData($_GET['realu'],"fname"));?> <?php echo substr(ucfirst(UserData($_GET['realu'],"lname")),0,1);?>';
    var device = "desktop";
    if (navigator.userAgent.match(/Android|BlackBerry|iPhone|iPad|iPod|Opera Mini|IEMobile/i)) {
      device = "mobile";
    }

      socket.emit("joinserver", name, device,'<?php echo UserData($_GET['u'],"sponsorid")?>','<?php echo $_GET["levelid"];?>');
      
      $("#chat-input-msg").focus();
    


  $("#name").keypress(function(e){
    var name = $("#name").val();
    if(name.length < 2) {
      
    } else {
      $("#errors").empty();
      $("#errors").hide();
      
    }
  });

  //populate canned messages 
  
  
populateCannedEditor();
		
		
  //populate canned messages 
  
  
  //see selected canned 
  
  $('#canned').on('change', function() {
	  
	  
	  var cannedmsgid = this.value;
	  
	  	    $.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "populateCanned",
				canid: cannedmsgid,
				userid: "<?php echo $_GET['u']?>"
			}),
		success: function(data) {
			var cleanups = data.msg;
			var datax;
			
			

	//start changing the names :)
    datax = cleanups.replace("xxxCHATFNAMExxxx", $('#assistingthis').text()); 
     datax = datax.replace('xxxCHATLNAMExxx', $('#chatterLname').val()); 
     datax = datax.replace('xxxCHATEMAILxxx', $('#chatterEmail').val()); 
     datax = datax.replace('xxxCHATNAMExxx', $('#assistingthis').text() + ' ' + $('#chatterLname').val()); 
	//start changing the names :)
	
			$('#chat-input-msg').val(datax);
		}
	
		});
		
		$("#canned").val($("#canned option:first").val());
	
});

  $('#chatstat').on('change', function() {
	  
	  var theStat = this.value;
	  var uid='<?php echo $_GET['u']?>';
	$.ajax({
		type: "POST",
		dataType:'json',
		async: false,
		contentType :'application/json' ,
		url: "/API/",
		data: JSON.stringify({ method: "setChatStatus", uid: uid,chatStats: theStat }),
		success: function(data) {
		
			if(theStat==1){
				$('#chatIsOnline').html('Online');
				}else{
				$('#chatIsOnline').html('Offline');
				}
				
			}
		});
	
});




  //see selected canned 
  
  //main chat screen
  $("#chatFormAdmin").submit(function() {
    var msg = $("#chat-input-msg").val();
    if (msg !== "") {
      socket.emit("send", new Date().getTime(), msg);
      $("#chat-input-msg").val("");
	  
    }
	
	
      recognizing = false;
    
	  
  });

  //'is typing' message
  var typing = false;
  var timeout = undefined;

  function timeoutFunction() {
    typing = false;
    socket.emit("typing", false);
  }

  $("#chat-input-msg").keypress(function(e){
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
    if (data.isTyping) {
      if ($("#"+data.person+"").length === 0) {
        $("#updates").append("<li id='"+ data.person +"'><span class='text-muted'><small><i class='fa fa-keyboard-o'></i> " + data.person + " is typing.</small></li>");
        timeout = setTimeout(timeoutFunction, 5000);
      }
    } else {
      $("#"+data.person+"").remove();
    }
  });


  

  $("#chat-input-msg").keypress(function(){
    if ($("#chat-input-msg").is(":focus")) {
      if (myRoomID !== null) {
        socket.emit("isTyping");
      }
    } else {
      $("#keyboard").remove();
    }
  });

  socket.on("isTyping", function(data) {
    if (data.typing) {
      if ($("#keyboard").length === 0)
        $("#updates").append("<li id='keyboard'><span class='text-muted'><i class='fa fa-keyboard-o'></i>" + data.person + " is typing.</li>");
    } else {
      socket.emit("clearMessage");
      $("#keyboard").remove();
    }
    //console.log(data);
  });


  $("#showCreateRoom").click(function() {
    $("#createRoomForm").toggle();
  });

  $("#createRoomBtn").click(function() {
    var roomExists = false;
    var roomName = $("#createRoomName").val();
    socket.emit("check", roomName, function(data) {
      roomExists = data.result;
       if (roomExists) {
          $("#errors").empty();
          $("#errors").show();
          $("#errors").append("Room <i>" + roomName + "</i> already exists");
        } else {      
        if (roomName.length > 0) { //also check for roomname
          socket.emit("createRoom", roomName);
          $("#errors").empty();
          $("#errors").hide();
          }
        }
    });
  });

  $("#rooms").on('click', '.joinRoomBtn', function() {
	var roomID = $(this).attr("id");
	
	$('#userHistoryData').val(roomID);
	
	$('.joinRoomBtn').prop('disabled', false);
	$(this).prop('disabled', true);
	
	var personsName = $(this).attr("data-id");
	$('.list-'+personsName).pulse('destroy');
	
	$('#chat-msgs').html('');
	
	socket.emit("joinRoom", roomID);
	  	$.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getUserDetails",
				userid: roomID
			}),
			success: function(data) {
				$("#chat-msgs").append("<li>"+data.about+"</li>");
				$("#userMoreInfo").html(data.moreinfo);
				$("#chatterEmail").val(data.email);
				$("#chatterLname").val(data.lname);
			
			
			}
	});
	


	

	
	

    
	$('#assistingthis').html(personsName);
	
	var $el = $(".list-"+personsName);
	$el.css("background", "");
	
	
	
	
	
	
  });

  $("#rooms").on('click', '.removeRoomBtn', function() {
    var roomName = $(this).siblings("span").text();
    var roomID = $(this).attr("id");
    socket.emit("removeRoom", roomID);
    $("#createRoom").show();
  }); 
  
  
  

  $("#leave").click(function() {
    var roomID = myRoomID;
    socket.emit("leaveRoom", roomID);
    $("#createRoom").show();
  });



	$('#setoffline').submit(function(){
	  if($('#setoff').val()==1){
        socket.emit('offline', 1);
		
        return false;
		}else{
		socket.emit('offline', 0);
		
		return false;
		}
      });
	  
socket.on("exists", function(data) {
  $("#errors").empty();
  $("#errors").show();
  $("#errors").append(data.msg + " Try <strong>" + data.proposedName + "</strong>");
    
    
});



socket.on("joined", function() {
  $("#errors").hide();
  // if (navigator.geolocation) { //get lat lon of user
    // navigator.geolocation.getCurrentPosition(positionSuccess, positionError, { enableHighAccuracy: true });
  // } else {
    // $("#errors").show();
    // $("#errors").append("Your browser is ancient and it doesn't support GeoLocation.");
  // }
  function positionError(e) {
    console.log(e);
  }

  function positionSuccess(position) {
    var lat = position.coords.latitude;
    var lon = position.coords.longitude;
    //consult the yahoo service
    $.ajax({
      type: "GET",
      url: "//query.yahooapis.com/v1/public/yql?q=select%20*%20from%20geo.placefinder%20where%20text%3D%22"+lat+"%2C"+lon+"%22%20and%20gflags%3D%22R%22&format=json",
      dataType: "json",
       success: function(data) {
        socket.emit("countryUpdate", {country: data.query.results.Result.countrycode});
      }
    });
  }
});

socket.on("history", function(data) {
	var oldscrollHeight = $("#chat-msgs").attr("scrollHeight") - 20; //Scroll height before the request

	//*** getting the history from API **/
	var userid = $('#userHistoryData').val();
	
	$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getChatHistoryQuick",
				userid: userid
				
			}),
			success: function(data) {
				if(data==null || data ==''){
						html ='<div style="word-wrap: break-word;" class="clearfix"><div class="header"><small class="pull-right text-muted"></small></div><p>no chat history</p></div>';
				}{
				$.each(data, function(k, v) {
					
					
	
				html ='<div style="word-wrap: break-word;" class="clearfix"><div class="header"><strong class="primary-font">'+v.person+'</strong> <small class="pull-right text-muted"><span class="glyphicon glyphicon-time"></span> <span data-livestamp="'+v.time+'"></span></small></div><p>'+v.msg+'</p></div>';
				
				$("#chat-msgs").append(html);
				});
				
				}
			}
	});
		



  
	//*** getting the history from API **/
	
	
	
	
	/** i grow do not want to see the history of node**/
	/*
  if (data.length !== 0) {
    $("#msgs").append("<li><strong><span class='text-warning'>Last 100 messages:</li>");
    $.each(data, function(data, msg) {
      $("#msgs").append("<li><span class='text-warning'>" + msg + "</span></li>");
    });
	$('#msgs li').last().addClass('active-li').focus();
	$("#msg").focus();
	
  } else {
    $("#msgs").append("<li><strong><span class='text-warning'>No past messages in this room.</li>");
	$('#msgs li').last().addClass('active-li').focus();
	$("#msg").focus();
  }
 */

  /** i grow do not want to see the history of node**/
  
  
  
  var newscrollHeight = $("#chat-msgs").attr("scrollHeight") - 20; //Scroll height after the request
				if(newscrollHeight > oldscrollHeight){
					$("#chat-msgs").animate({ scrollTop: newscrollHeight }, 'normal'); //Autoscroll to bottom of div
					
				}
	
$('#chat-input-msg').focus();
});

  socket.on("update", function(msg) {
	  var oldscrollHeight = $("#chat-msgs").prop("scrollHeight") - 20; //Scroll height before the request
	  
    $("#chat-msgs").append("<li>" + msg + "</li>");
	 $.playSound('https://office.toolsrock.com/notif');
	getUsers();
	
	
	var newscrollHeight = $("#chat-msgs").prop("scrollHeight") - 20; 
if(newscrollHeight > oldscrollHeight){
$("#chat-msgs").animate({ scrollTop: newscrollHeight }, 'normal');
}

 
  });
  
  
 

  socket.on("update-people", function(data){

  });
  
	socket.on("act", function(data) {
	
	if ( $('.'+data).length) {
		$('.'+data).html(parseInt($('.'+data).html())+1);
		
		if($('#assistingthis').html()!=data){
			$.playSound('https://office.toolsrock.com/beep');
	
    var properties = {
   backgroundColor : "#FAFAD2",
   color: '#000'
   
};

var els = $('.list-'+data);

els.pulse(properties, {duration : 1250, pulses : 9999999});
         
		 
		 
	
		}
		 
		
		$(".list-"+data).prependTo("#rooms");
	



	}

  });

  socket.on("chat", function(msTime, person, msg) {

	  
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
		

	
	$("#chat-input-msg").focus();
	$.playSound('https://office.toolsrock.com/clenk');
	
	
     $("#"+person.name+"").remove();
	 
	 
     clearTimeout(timeout);
     timeout = setTimeout(timeoutFunction, 0);
	 
	 $.titleAlert("New chat message!", {
    requireBlur:false,
    stopOnFocus:false,
    duration:4000,
    interval:700
	
	
	
});


					$("#chat-msgs").animate({ scrollTop: $("#chat-msgs").prop("scrollHeight") - 20 }, 'normal'); //Autoscroll to bottom of div
				
				
				

	$("#chat-input-msg").focus();
  });

  socket.on("whisper", function(msTime, person, msg) {
    if (person.name === "You") {
      s = "whisper"
    } else {
      s = "whispers"
    }
    $("#chat-msgs").append("<li><strong><span class='text-muted'>" + timeFormat(msTime) + person.name + "</span></strong> "+s+": " + msg + "</li>");
  });

  socket.on("roomList", function(data) {

  getUsers();
  });
  


  socket.on("sendRoomID", function(data) {
    myRoomID = data.id;
  });

  socket.on("disconnect", function(){
    $("#chat-msgs").append("<li><strong><span class='text-warning'>Disconnected from the server... please refresh.</span></strong></li>");
    
  });

$('#ManageGenerated').on('shown.bs.modal', function() {
populateGeneratedCodes();
});



  $('#ManageCans').on('shown.bs.modal', function() {
populateCannedEditor();
});



	$(document).on('click', '.viewHistory', function() {
		var html ='';
		var userid = $(this).attr('data-id');
		
		
		$('#SeeHistory').modal('show');
		
		$("#msgboards").html('');
			$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getChatHistory",
				userid: userid
				
			}),
			success: function(data) {
				if(data==null || data ==''){
					html += '<tr><td colspan=3>No Chat history!</td></tr>';
				}{
				$.each(data, function(k, v) {
					
					
					
				html += '<tr>';
				html += '<td>';
				html += v.time;
				html += '</td>';
				html += '<td>';
				html += v.person+': '+v.msg;
				html += '</td>';
				
				html += '</tr>';
				
				
				
				});
				}
				
				
				 	$.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getUserDetails",
				userid: userid
			}),
			success: function(data) {

				$("#aboutFreakinguser").html(data.moreinfo);

			
			
			}
	});
	
	
			}
			
			
		});
		$("#msgboards").html(html);
		
		
		
		getUsers();
	});
	
	$(document).on('click', '.addCannedMsg', function() {

	var cannedInput = $('#cannedInput').val();
	var uid = '<?php echo $_GET['u']; ?>';

	  	$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "addCannedMsg",
				userid: uid,
				canned: cannedInput
			}),
			success: function(data) {
				populateCannedEditor();
				 $('#cannedInput').val('');
			}
	});
	
	
	})


	$(document).on('click', '.addGenerated', function() {

	var urliz = $('#urlGeneratedCodes').val();
	var uid = '<?php echo $_GET['u']; ?>';
	var frmkey = $('#fka').val();

	  	$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "addGeneratedCodes",
				userid: uid,
				uriz: urliz,
				frmkey: frmkey
			}),
			success: function(data) {
				
				 $('#urlGeneratedCodes').val('');
				 $('#generated').val(data.code);
				populateGeneratedCodes();
			}
	});
	
	})
	
	
$(document).on('click', '.archiveBtn', function() {
	
	var userid = $(this).attr('data-user');
	var listid = $(this).attr('data-id');
	
	
	

	
	swal({
  title: "Are you sure?",
  text: "You are archiving this person!",
  type: "warning",
  showCancelButton: true,
  confirmButtonClass: "btn-danger",
  confirmButtonText: "Yes, proceed",
   cancelButtonText: "No, cancel please!",
  closeOnConfirm: false,
  closeOnCancel: false
},
function(isConfirm) {


  if (isConfirm) {	
		$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "archiveUser",
				userid: userid,
				
			}),
			success: function(data) {
				
				 
			}
	});
	$('#offline-'+listid).remove();
	swal("Archived!", "This user is now archived...", "success");
  }else{
	  
	    swal("Cancelled", "Cancelled the archive :)", "success");
  }
	
	
  
});


	

	
	
	
	
})
$(document).on('click', '.removeCanned', function() {

	var removeC = $(this).attr('data-id');
	
	
	
	swal({
  title: "Are you sure?",
  text: "This canned message will be removed.",
  type: "warning",
  showCancelButton: true,
  confirmButtonClass: "btn-danger",
  confirmButtonText: "Yes, remove it!",
  closeOnConfirm: false
},
function(){
	
	  	$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "removeCanned",
				userid: uid,
				canned: removeC
			}),
			success: function(data) {
				populateCannedEditor();
				 
			}
	});
	
	
  swal("Deleted!", "Canned Message was successfully removed!", "success");
});



	
	
	
	
	
	})
	

$(document).on('click', '.KickUser', function() {
	  var userid = $(this).attr('data-user');
	var listid = $(this).attr('data-id');
	
swal({
  title: "End Session",
  text: "Are you sure you want to end chat session this person?",
  type: "warning",
  showCancelButton: true,
  confirmButtonClass: "btn-info",
  confirmButtonText: "Yes",
  closeOnConfirm: false
},
function(){
  swal("Session Ended!", "", "success");
  
  

	
	$('#'+listid).remove();
	

	$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "setOfflineUser",
				userid: userid,
				
			}),
			success: function(data) {
				
				 
			}
			
	

  
	});

	
	var endscript = 'Session Closed...<style>#chatForm{ display:none;}#msg-body{display:none;} #msg-kicked{display:block !important;}</style>'

socket.emit("send", new Date().getTime(), endscript);

	
	  getUsers();
	})

 });

$(document).on('click', '.removeGenerated', function() {

	var removeG = $(this).attr('data-id');
	
	
	
	swal({
  title: "Are you sure?",
  text: "This Generated code will be gone forever!",
  type: "warning",
  showCancelButton: true,
  confirmButtonClass: "btn-danger",
  confirmButtonText: "Yes, remove it!",
  closeOnConfirm: false
},
function(){
	
		  	$.ajax({
			type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "removeGenerated",
				userid: uid,
				remgen: removeG
			}),
			success: function(data) {
				populateCannedEditor();
				 
			}
	});
	
	
  swal("Removed!", "Generated code is removed!", "success");
});




	
	
	
	
	
	
	
	
	
	})

	
	
	getUsers();
});

function populateGeneratedCodes(){
	
	$("#generatedCodesList").empty();
	    $.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getGeneratedCodes",
				userid: "<?php echo $_GET['u']?>"
			}),
		success: function(data) {

	$.each(data, function(k, v) {
	$("#generatedCodesList").append("<li class=\"list-group-item\" id='"+ v.id +"'>"+'('+v.url+')'+v.code+" <a data-id='"+ v.id +"' style='cursor:pointer;' class='removeGenerated btn btn-default tsr-btn'> Remove</a></li>");
			});
		}
	
		});
	
}

function populateCannedEditor(){
	  $("#cannedMsgsList").empty();
	    $.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getCannedMsgs",
				userid: "<?php echo $_GET['u']?>"
			}),
		success: function(data) {

	$.each(data, function(k, v) {
	$("#cannedMsgsList").append("<li class=\"list-group-item\" id='"+ v.id +"'>"+v.msg+" <a data-id='"+ v.id +"' style='cursor:pointer;' class='removeCanned btn btn-default tsr-btn'>Remove</a></li>");
			});
		}
	
		});
		
		 	  $("#canned").empty();
	  $("#canned").append("<option  selected>Select Canned Message...</option>");
	    $.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getCannedMsgs",
				userid: "<?php echo $_GET['u']?>"
			}),
		success: function(data) {

	$.each(data, function(k, v) {
		
	$("#canned").append("<option value='"+ v.id +"'>"+v.msg+"</option>");
	
			});
		}
	
		});
		
}


function  getUsers(){
	
	 $.ajax({
                type: "POST",
			dataType: 'json',
			async: false,
			contentType: 'application/json',
			url: "https://office.toolsrock.com:81/api/",
			data: JSON.stringify({
				method: "getOnlineUsers",
				sponsorid: "<?php echo $_GET['u']?>"
			}),
	success: function(data) {
	var htmlOffline = "";
	var html = "";
	var usernumbers=0;
	
	
	
	$.each(data, function(k, v) {
	
	if(v.active==1){
		usernumbers = usernumbers + 1;
	}
	
	if($('#'+v.id).length){
		
		if(v.active==0){
			$('#'+v.id).remove();
			//console.log('exists:'+v.id);
	}
		
		
	}
	else{
	if(v.active==0){
			//$('#'+v.id).remove();
			//console.log('exists:'+v.id);
			
			if($('#offline-'+v.id).length){
				$('#offline-'+v.id).remove();
			htmlOffline += "<li id="+'offline-'+v.id+" class=\"list-group-item offline-list-"+v.user+"   \"><span>" + v.user + "</span> ";
			
			htmlOffline+= '<button data-id="'+v.roomid+'" class="viewHistory btn btn-default tsr-btn" data-id="'+v.user+'" >History</button>';
			
			htmlOffline+= '<button data-id="'+v.id+'" data-user="'+v.roomid+'" class="archiveBtn btn btn-default tsr-btn" data-id="'+v.user+'" >Archive</button>';
			
			htmlOffline +='</li>';
			}else{
					htmlOffline += "<li id="+'offline-'+v.id+" class=\"list-group-item offline-list-"+v.user+"   \"><span>" + v.user + "</span> ";
			
			htmlOffline+= '<button data-id="'+v.roomid+'" class="viewHistory btn btn-default tsr-btn" data-id="'+v.user+'" >History</button>';
			
			htmlOffline+= '<button data-id="'+v.roomid+'" class="archiveBtn btn btn-default tsr-btn" data-id="'+v.user+'" >Archive</button>';
			
			htmlOffline +='</li>';
				
			}
		
		
	}else{
		
		
		
		if($('#offline-'+v.id).length){
				$('#offline-'+v.id).remove();
				
		}
		$.playSound('https://office.toolsrock.com/online');
			
		html += "<li id="+v.id+" class=\"list-group-item list-"+v.user+"   \"><span>" + v.user + "</span> "+ '<span class=\"badge\ '+v.user+'">0</span>';
		
		html += '<button id="'+v.roomid+'" class="joinRoomBtn btn btn-default tsr-btn" data-id="'+v.user+'" >Assist</button>';
		
	
		
		html +='</li>';
		
			if(v.id!=''){
					ctrls = '<button data-user="'+v.roomid+'" class="KickUser btn btn-default tsr-btn" data-id="'+v.id+'" >End Session</button>';
			
			$('#ctrls').html(ctrls);
			}
			
			
			
			
		
	}
	
	}
	
	});
	
	
	$('#rooms').append(html);
	$('#offlineContacts').append(htmlOffline);
	$('.usercountsx').html(usernumbers);
	
	
	
	}
  });
  
}

(function($){

  $.extend({
    playSound: function(){
      return $("<embed src='"+arguments[0]+".mp3' hidden='true' autostart='true' loop='false' class='playSound'>" + "<audio autoplay='autoplay' style='display:none;' controls='controls'><source src='"+arguments[0]+".mp3' /><source src='"+arguments[0]+".ogg' /></audio>").appendTo('body');
    }
  });

  
  
})(jQuery);

(function(a){a.titleAlert=function(e,c){if(a.titleAlert._running){a.titleAlert.stop()}a.titleAlert._settings=c=a.extend({},a.titleAlert.defaults,c);if(c.requireBlur&&a.titleAlert.hasFocus){return}c.originalTitleInterval=c.originalTitleInterval||c.interval;a.titleAlert._running=true;a.titleAlert._initialText=document.title;document.title=e;var b=true;var d=function(){if(!a.titleAlert._running){return}b=!b;document.title=(b?e:a.titleAlert._initialText);a.titleAlert._intervalToken=setTimeout(d,(b?c.interval:c.originalTitleInterval))};a.titleAlert._intervalToken=setTimeout(d,c.interval);if(c.stopOnMouseMove){a(document).mousemove(function(f){a(this).unbind(f);a.titleAlert.stop()})}if(c.duration>0){a.titleAlert._timeoutToken=setTimeout(function(){a.titleAlert.stop()},c.duration)}};a.titleAlert.defaults={interval:500,originalTitleInterval:null,duration:0,stopOnFocus:true,requireBlur:false,stopOnMouseMove:false};a.titleAlert.stop=function(){clearTimeout(a.titleAlert._intervalToken);clearTimeout(a.titleAlert._timeoutToken);document.title=a.titleAlert._initialText;a.titleAlert._timeoutToken=null;a.titleAlert._intervalToken=null;a.titleAlert._initialText=null;a.titleAlert._running=false;a.titleAlert._settings=null};a.titleAlert.hasFocus=true;a.titleAlert._running=false;a.titleAlert._intervalToken=null;a.titleAlert._timeoutToken=null;a.titleAlert._initialText=null;a.titleAlert._settings=null;a.titleAlert._focus=function(){a.titleAlert.hasFocus=true;if(a.titleAlert._running&&a.titleAlert._settings.stopOnFocus){var b=a.titleAlert._initialText;a.titleAlert.stop();setTimeout(function(){if(a.titleAlert._running){return}document.title=".";document.title=b},1000)}};a.titleAlert._blur=function(){a.titleAlert.hasFocus=false};a(window).bind("focus",a.titleAlert._focus);a(window).bind("blur",a.titleAlert._blur)})(jQuery);


//PULSATE:

(function(t,e){"use strict";var n={pulses:1,interval:0,returnDelay:0,duration:500};t.fn.pulse=function(u,a,r){var i="destroy"===u;return"function"==typeof a&&(r=a,a={}),a=t.extend({},n,a),a.interval>=0||(a.interval=0),a.returnDelay>=0||(a.returnDelay=0),a.duration>=0||(a.duration=500),a.pulses>=-1||(a.pulses=1),"function"!=typeof r&&(r=function(){}),this.each(function(){function n(){return void 0===s.data("pulse")||s.data("pulse").stop?void 0:a.pulses>-1&&++p>a.pulses?r.apply(s):(s.animate(u,f),void 0)}var o,s=t(this),l={},d=s.data("pulse")||{};d.stop=i,s.data("pulse",d);for(o in u)u.hasOwnProperty(o)&&(l[o]=s.css(o));var p=0,c=t.extend({},a);c.duration=a.duration/2,c.complete=function(){e.setTimeout(n,a.interval)};var f=t.extend({},a);f.duration=a.duration/2,f.complete=function(){e.setTimeout(function(){s.animate(l,c)},a.returnDelay)},n()})}})(jQuery,window,document);