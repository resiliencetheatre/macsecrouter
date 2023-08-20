<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Vault PIN</title>
<meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />
<script src="js/chart.js"></script>
<script src="js/feather.js" ></script>
<link href="css/communit.css" rel="stylesheet" />
<link href="css/w3.css" rel="stylesheet" />
</head>

<body>	
	<div class="header-container"> 
		<div class="header-item">
			<center>
			<table width=95% cellpadding="30" border=0>
				<tr valign="top">
					<td width=40%>
						<center>
							<p> </p>
							<img id="headerimage" src="img/usb.svg" width=80%>
							<p>
								<span id="status"></span>
							</p>
							
							<div class="w3-light-grey"> 
								<div id="myBar" class="w3-container w3-green" style="height:20px;width:0%"><span id="timeoutCount"></span></div>
							</div>
							
						</center>
					</td>
					<td>
						<div id="pinEntrySection">
							<center>
							<h3>Vault PIN</h3>
							<p>Key & data storage is locked.</p><p>Please enter code for opening the vault:</p>
							<p>
								<input type="password" id="code" name="code" required minlength="4" maxlength="8" size="5">
							</p>
							<input type="button" class="w3-btn w3-green" value="Submit" onclick="submitpin()">
							<input type="button" class="w3-btn w3-red" value="Poweroff" onclick="powerOff()">
							</center>
						</div>
					</td>
				</tr>
			</table>
			</center>
		</div>
	</div>

<script>	

	
feather.replace();
var idCountdown;
var countDownInProgress=0;
myBar.className = "w3-container w3-red";
var pinDisplay = document.getElementById("pinEntrySection");
pinDisplay.style.display = "none"; 

var pinSubmitInProgress=false;

function submitpin() {
	
	// TODO: should we check submit status from backend? + check input lenght ? 
	// TODO: wifi socket status?
	
	pinSubmitInProgress=true;
	
	// Check password
	var vault_password = document.getElementById('code').value ;
	
	if ( vault_password.length < 4 && vault_password.length > 10 ) {
		$('#status').innerHTML = "<font color=red>No valid code!</font>"
		return;
	}
	
	if ( countDownInProgress == 0 ) {
		
			$('#status').innerHTML = "Checking, please wait."
			openVault();
			countDownInProgress = 1;
			var elem = document.getElementById("myBar");
			var existingWidth = elem.style.width;
			var existingWidthValue = existingWidth.slice(0, -1) 
			var width = existingWidthValue;
			var id = setInterval(frame, 5);
			clearInterval(idCountdown);  
			myBar.className = "w3-container w3-green";
			// Visually count up
			function frame() {
				if (width >= 100) {
					clearInterval(id);
				} else {
					width++;					
					elem.style.width = width + '%';
					// $('#timeoutCount').innerHTML = Math.round( width / 3,1) + ' s';
				}
			}
	
			idCountdown = setInterval(countDownframe, 330);  
			function countDownframe() {
				if (width == 0) {
					clearInterval(idCountdown);
					elem.style.width = '0%';
					// audioControlSocket.send("audio-off\n");
					myBar.className = "w3-container w3-green";
					$('#status').innerHTML = "Check completed!"
					countDownInProgress = 0;					
				} else {
					width--;
					elem.style.width = width + '%';
					// console.log("countDownframe() 1", elem.style.width);
					// $('#timeoutCount').innerHTML =    Math.round(width / 3,1) + ' s';
				}
			}
			
		}
		
}

function checkUsbDrivePresense() {
	
		setTimeout(function(){
			wifiControlSocket.send("driveinserted\n");
			if ( pinSubmitInProgress == false ) {
				checkUsbDrivePresense();
			}
		  }, 5000);	

}

// TODO: IP definition
function openRemoteFilemanager() {
	window.open('http://10.10.0.1', '_blank');
}

function openVault() {
	var vault_password = document.getElementById('code').value ;
	document.getElementById('code').value = "";
	wifiControlSocket.send("vaultopen,nossid," + vault_password + "\n");
}

function powerOff() {
	$('#status').innerHTML = "Poweroff!"
	wifiControlSocket.send("poweroff\n");
}

function proceedToCommunit() 
{
    window.location.replace("/communit/index.php");
	
    // window.open('/communit/index.php');
}

function $(selector) {
	return document.querySelector(selector);
}
	//
	// Websocket protocol and host for sockets bellow
	//
	var wsProtocol = null;
	if(window.location.protocol === 'http:')
			wsProtocol = "ws://";
	else
			wsProtocol = "wss://";
	
	var wsHost = location.host;
	
	// document.getElementById('remotefilesButton').setAttribute('class', 'w3-btn w3-orange');
	// document.getElementById('remotefilesButton').setAttribute('title', 'Remote is offline');
		
	//
	// Wifi control socket (9050)
	//
	wifiControlSocket = new WebSocket(wsProtocol+wsHost+':9050');
	wifiControlSocket.onopen = function(event) {
		// $('#wifiControlSocketStatus').innerHTML = '<span style="color: green">█</span>';
		// $('#wifiControlSocketData').innerHTML = '[waiting data]';
	};
	wifiControlSocket.onmessage = function(event) {
		var incomingMessage = event.data;
		var trimmedString = incomingMessage.substring(0, 200);		
		let prefixArray = trimmedString.split(';');
		// Update network dropdown when prefix is 'visiblenetworks'
		if (  prefixArray[0] === "visiblenetworks" ) {
			// $('#wifiControlSocketData').innerHTML = "Scan completed!"
			logMessage("Scan completed" );
			let networksString = prefixArray[1];
			console.log("networksString: " + networksString);
			let networksArray = networksString.split(' ');
			let selectTag = document.getElementById('ssid');
			networksArray.forEach((element, index) => {
				let option_elem = document.createElement('option');
				option_elem.value = index;
				option_elem.textContent = element;
				if ( index > 0 ) {
					selectTag.appendChild(option_elem);
				}
			});	
		}
		if (  prefixArray[0] === "connectstatus" ) {
			logMessage("Connect completed" );
			// $('#wifiControlSocketData').innerHTML = "Connect completed " + prefixArray[1]
			getipaddresses();
		}
		if (  prefixArray[0] === "forgotnetwork" ) {
			logMessage("Network removed" );
			// $('#wifiControlSocketData').innerHTML = "Network removed."
		}
		if (  prefixArray[0] === "ipaddresses" ) {
			logMessage("Updating IP addresses" );
			let ipaddresslist = prefixArray[1];
			let addressArray = ipaddresslist.split(',');
			// $('#wifiControlSocketData').innerHTML = addressArray
			// $('#wifiIp').innerHTML = " " + addressArray[1];
			// $('#ethIp').innerHTML = " " + addressArray[3];
		}
		if (  prefixArray[0] === "wlanstatus" ) { 
			let wlanstatus = prefixArray[1];
			
			console.log("wlanstatus: " + wlanstatus);
			
			$('#status').innerHTML = wlanstatus;			
			
			if ( wlanstatus.includes("Valid") ) {
				console.log("redirect or whatever");
				clearInterval(idCountdown);
				var elem = document.getElementById("myBar");
				elem.style.width = '0%';
				// , '_blank' AKU
				// window.open('/communit/index.php'); 
				proceedToCommunit();
			}
			
			if ( wlanstatus.includes("Invalid") ) {
				console.log("shutdown or terminate");
				clearInterval(idCountdown);
				var elem = document.getElementById("myBar");
				elem.style.width = '0%';
				myBar.className = "w3-container w3-red";
				document.getElementById("headerimage").src = headerimage.src.replace("img/vault.svg", "img/skull.svg");
				pinSubmitInProgress=false;
				
			}
			
			if ( wlanstatus.includes("USB drive found") ) {
				pinDisplay.style.display = "block";
				clearInterval(idCountdown);
				var elem = document.getElementById("myBar");
				elem.style.width = '0%';
				myBar.className = "w3-container w3-red";
				document.getElementById("headerimage").src = headerimage.src.replace("img/usb.svg", "img/vault.svg");
				
				console.log("Let's make sure drive is still there...");
				// wifiControlSocket.send("driveinserted\n");
				
			}
			
			if ( wlanstatus.includes("USB drive not found") ) {
				pinDisplay.style.display = "none";
				clearInterval(idCountdown);
				var elem = document.getElementById("myBar");
				elem.style.width = '0%';
				myBar.className = "w3-container w3-red";
				document.getElementById("headerimage").src = headerimage.src.replace("img/vault.svg", "img/usb.svg");
				document.getElementById("headerimage").src = headerimage.src.replace("img/skull.svg", "img/usb.svg");
				console.log("No drive, checking again");
				// wifiControlSocket.send("driveinserted\n");
			}
			
			if ( wlanstatus.includes("Vault is already open") ) {
				proceedToCommunit();
			}
			
			
			// TODO: On success, redirect and continue
			// TODO: On error: do something else
			// logMessage("Updating WIFi status" );
			// $('#wifiDetails').innerHTML = wlanstatus;
		}

	}; // onMessage end
	
	wifiControlSocket.onclose = function(event) {
		// $('#wifiControlSocketStatus').innerHTML = '<span style="color: red">█</span> ' + event.reason;	
		console.log("onclose");
	};

	// wifiControlSocket.send("driveinserted\n");

	
	setTimeout(function(){
	 	console.log("Checking if vault is already open");
	  	wifiControlSocket.send("isvaultopen\n");
	  }, 2000);
	  
	$('#status').innerHTML = "Checking USB drive..."
	setTimeout(function(){
	 	console.log("Check USB on page load");
	  	checkUsbDrivePresense();
	  }, 4000);	
	
	
	
	
</script>
<center><p style="color:white"> 	&#169; Resilience Theatre 2023 </p> </center>

</body>
</html>
