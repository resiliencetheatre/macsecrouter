<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>OTP Router</title>
<meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />
<script src="js/chart.js"></script>
<script src="js/feather.js" ></script>
<link href="css/communit.css" rel="stylesheet" />
<link href="css/w3.css" rel="stylesheet" />
</head>

<body>
	
	<div class="grid-container">

	<div class="grid-item">
		<h4><span id="wifiControlSocketStatus"></span> Wifi connect</h4>
		<p>Scan and connect Wifi. Wifi can be used to Internet connectivity.</p>
				
			<div class="w3-container">
				<input type="button" class="w3-btn w3-green" value="Scan networks" onclick="scanNetworks()">
			</div>
			<p>
			Select network and give network password:
			<div class="w3-container">
					
				<select name="ssid" id="ssid"></select>
				
				<input type="text" id="password" name="password" value="">
				<input type="button" class="w3-btn w3-green" value="Connect" onclick="connectWifiNetwork()">		
				<input type="button" class="w3-btn w3-red" value="Forget" onclick="forgetWifiNetwork()">
				
				<p></p>
				<span id="wifiControlSocketData"></span>
				
			</div>
	
	</div>

	<div class="grid-item">
		<h4>Interface addresses</h4>
		<p>Device connectivity addresses:</p>
				
			<div class="w3-container">
				<center>
				<table width=90% border=0 >
				<tr>
				<td width=33% valign="top"><b>Wifi:</b><span id="wifiIp"></span><p><span id="wifiDetails"></span></p></td><td valign="top" width=33%><b><center>enp1s0u3</center></b></td><td valign="top" width=33%><b>eth0</b><span id="ethIp"></span></td>
				</tr>
				</table>
				
				<img src="img/interfaces-ui.png" width=200px;>
				</center>
			
			</div>
			
	</div>


</div> 


<script>	

var idCountdown;
feather.replace();

function getTime() {
	var today = new Date();
	var hours =  today.getHours() ;
	var minutes =  today.getMinutes() ;
	var seconds =  today.getSeconds() ;
	if ( today.getHours() < 10 ) { hours = "0"+today.getHours();}
	if ( today.getMinutes() < 10 ) { minutes = "0"+today.getMinutes();}
	if ( today.getSeconds() < 10 ) { seconds = "0"+today.getSeconds();}
	var time = hours+ ":" + minutes + ":" + seconds;
	return time;
}

function logMessage(message) {
	console.log("Logging: ", message);
}

function removeOptions(selectElement) {
   var i, L = selectElement.options.length - 1;
   for(i = L; i >= 0; i--) {
      selectElement.remove(i);
   }
}

function scanNetworks() {
	removeOptions(document.getElementById('ssid'));
	wifiControlSocket.send("wifiscan\n");
	logMessage("Scanning networks...");
	$('#wifiControlSocketData').innerHTML = "Scanning networks..."
}

function connectWifiNetwork() {
	// password & ssid	
	var ssid_select = document.getElementById('ssid');
	var network_ssid = ssid_select.options[ssid_select.selectedIndex].text;
	var network_password = document.getElementById('password').value ;
	document.getElementById('password').value = "";
	$('#wifiControlSocketData').innerHTML = "Connecting..."
	logMessage("Connecting to " +  network_ssid);
	wifiControlSocket.send("wificonnect," + network_ssid + "," + network_password + "\n");
}

function forgetWifiNetwork() {
	var ssid_select = document.getElementById('ssid');
	var network_ssid = ssid_select.options[ssid_select.selectedIndex].text;
	document.getElementById('password').value = "";
	$('#wifiControlSocketData').innerHTML = "Forgetting..."
	logMessage("Forgetting " +  network_ssid);
	wifiControlSocket.send("wififorget," + network_ssid + "\n");
}

function getipaddresses() {
	wifiControlSocket.send("getips\n");
}

function getwlanstatus() {
	wifiControlSocket.send("wlanstatus\n");
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
	// var wsHost = "192.168.5.57";
	// console.log("wsProtocol: " + wsProtocol + " wsHost: " + wsHost );

	var otptunnel_latency;
	var otptunnel_packetloss;
	
	
	//
	// Wifi control socket (9050)
	//
	wifiControlSocket = new WebSocket(wsProtocol+wsHost+':9050');
	wifiControlSocket.onopen = function(event) {
		$('#wifiControlSocketStatus').innerHTML = '<span style="color: green">█</span>';
		$('#wifiControlSocketData').innerHTML = '[waiting data]';
	};
	wifiControlSocket.onmessage = function(event) {
		var incomingMessage = event.data;
		var trimmedString = incomingMessage.substring(0, 200);		
		let prefixArray = trimmedString.split(';');
		
		// Update network dropdown when prefix is 'visiblenetworks'
		if (  prefixArray[0] === "visiblenetworks" ) {
			$('#wifiControlSocketData').innerHTML = "Scan completed!"
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
			// DEBUG
			// getipaddresses();
		}
		if (  prefixArray[0] === "connectstatus" ) {
			logMessage("Connect completed" );
			$('#wifiControlSocketData').innerHTML = "Connect completed " + prefixArray[1]
			getipaddresses();
		}
		if (  prefixArray[0] === "forgotnetwork" ) {
			logMessage("Network removed" );
			$('#wifiControlSocketData').innerHTML = "Network removed."
		}
		if (  prefixArray[0] === "ipaddresses" ) {
			logMessage("Updating IP addresses" );
			let ipaddresslist = prefixArray[1];
			let addressArray = ipaddresslist.split(',');
			$('#wifiControlSocketData').innerHTML = addressArray
			$('#wifiIp').innerHTML = " " + addressArray[1];
			$('#ethIp').innerHTML = " " + addressArray[3];
			// 
		}
		if (  prefixArray[0] === "wlanstatus" ) { 
			let wlanstatus = prefixArray[1];
			logMessage("Updating WIFi status" );
			$('#wifiDetails').innerHTML = wlanstatus;
		}

	};
	wifiControlSocket.onclose = function(event) {
		$('#wifiControlSocketStatus').innerHTML = '<span style="color: red">█</span> ' + event.reason;	
	};
	
	logMessage("loaded");	
	setTimeout(function(){
		getipaddresses();
		getwlanstatus();
	}, 3000);
</script>

<center><p style="color:white"> 	&#169; Resilience Theatre 2023 </p> </center>
</body>
</html>
