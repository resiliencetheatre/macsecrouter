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
		<h3>OTP Router</h3>
		<p>Connection latency:</p>
		<center>
			<table border=0 width=100%>
				<tr>
					<td></td><td></td><td width=45%><span id="dnsSocketStatusInGraph"></span></td>
				</tr>
			</table>			
			<img src="img/conn.png" width=300px;>
			<table border=0 width=100%>
				<tr>
					<td></td><td><center><span id="socketStatusInGraph"></span></center></td><td></td>
				</tr>
			</table>	
		</center>	
	</div>

	<div class="grid-item">
		<p><h4><span id="systemMessagesStatus"></span> System log</h4></p>
		<div id="systemMessages" class="systemMsgView" ></div>
	</div>
	
	<div class="grid-item">
		<p><h4><span id="otpsocketStatus"></span><span id="socketStatus"></span> Network status for OTP endpoint:</h4></p>
		<span id="socketData"></span><span id="otpsocketData"></span>
		<canvas id="otpChart" style="border: 0px solid black; width:60%; height:150px;"></canvas>	
	</div>
	
	<div class="grid-item">
		<p><h4><span id="dnsSocketStatus"></span> Network status for Internet:</h4></p>
		<span id="dnsSocketData"></span>
		<canvas id="dnsChart" style="border: 0px solid black; width:60%; height:150px;"></canvas>	
	</div>

	<div class="grid-item">
		<p><h4><span id="txKeyUsagesocketStatus"></span> OTP Key consumption (transmit)</h4></p>
		<span id="txKeyUsagesocketData"></span>
		<center>
		<canvas id="txKeyUsage" style="border: 0px solid black; width:100%;max-height:200px"></canvas>			
		</center>
	</div>
	
	<div class="grid-item">
		<p><h4><span id="rxKeyUsagesocketStatus"></span> OTP Key consumption (receive)</h4></p>
		<span id="rxKeyUsagesocketData"></span>
		<center>
		<canvas id="rxKeyUsage" style="border: 0px solid black; width:100%;max-height:200px"></canvas>			
		</center>
	</div>

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


<script type='text/javascript'>
	// graph.js variables for packetgraph.js
	var xIndex=0;
	var xValues = [0,1,2,3,4,5,6,7,8,9,10];
	var latencyValues = [];
	var packetLossValues = [];
	var otplatencyValues = [];
	var otppacketLossValues = [];
	var dnsXindex=0;
	var dnsXvalues = [0,1,2,3,4,5,6,7,8,9,10];
	var dnsLatencyValues = [];
	var dnsPacketLossValues = [];
	var key_xIndex=0;
	var key_xValues = [0,1,2,3,4,5,6,7,8,9,10];
	var key_persentage = [];
	var rx_key_xIndex=0;
	var rx_key_xValues = [0,1,2,3,4,5,6,7,8,9,10];
	var rx_key_persentage = [];
	var audioControlSocket;
</script>
<script src="js/packetgraph.js" ></script>


<script>	

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
	$('#systemMessages').innerHTML += message + "<br>";
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
	var otptunnel_latency;
	var otptunnel_packetloss;
	
	//
	// Socket for packet loss and latency for OTP end point &
	// OTP tunnel. 
	//
	var socket = new WebSocket(wsProtocol+wsHost+':9000');
	socket.onopen = function(event) {
		$('#socketStatus').innerHTML = '<span style="color: green">█</span>';
		$('#socketData').innerHTML = '[waiting data]';
		logMessage("OTP end point monitoring connected");
	};
	socket.onmessage = function(event) {
		var incomingMessage = event.data;
		var trimmedString = incomingMessage.substring(0, 80);
		const dataArray = trimmedString.split(" ");
		// console.log( dataArray[0], dataArray[1], dataArray[2], dataArray[3] ); 
		var pingTarget = dataArray[0];
		var latency = dataArray[1]/1000;
		var packetLoss = dataArray[3];
		var packetLossString = packetLoss.toString();
		var truncatedPacketLoss = packetLossString.substring(0,3);
		var latencyString = latency.toString();
		var truncatedLatency = latencyString.substring(0,5);
		$('#socketData').innerHTML = "<b>OTP endpoint packet loss:</b> " + truncatedPacketLoss + " % <b>Latency:</b> " + truncatedLatency + " ms";
		if ( packetLoss == 100 ) {
			$('#socketStatusInGraph').innerHTML = "<font color='red'>OTP device unreachable</font>";
		} else {
			if ( packetLoss > 5 && packetLoss < 100 ) {
				$('#socketStatusInGraph').innerHTML = "<b>Packet loss:</b><font color='red'> " + truncatedPacketLoss + " % </font><b>Latency:</b> " + truncatedLatency + " ms";
			} else {
				$('#socketStatusInGraph').innerHTML = "<b>Packet loss:</b> " + truncatedPacketLoss + " % <b>Latency:</b> " + truncatedLatency + " ms";			
			}
		}
		myLineChart.data.labels[xIndex] = getTime(); 
		if ( otptunnel_latency == null ){
			otptunnel_latency = 0; 
			otptunnel_packetloss = 0;
		}
		if ( latency == null ){
			latency = 0; 
			packetLoss = 0;
		}
		myLineChart.data.datasets[0].data[xIndex] = latency;
		myLineChart.data.datasets[1].data[xIndex] = packetLoss;
		myLineChart.data.datasets[2].data[xIndex] = otptunnel_latency;
		myLineChart.data.datasets[3].data[xIndex] = otptunnel_packetloss;		
		xIndex++;
		myLineChart.update();
	};
	
	socket.onclose = function(event) {
		$('#socketStatus').innerHTML = '<span style="color: red">█</span>' + event.reason;	
	};
	
	// 
	// Socket for inside OTP tunnel packetloss and latency
	// 
	var otpsocket = new WebSocket(wsProtocol+wsHost+':9060');
	otpsocket.onopen = function(event) {
		$('#otpsocketStatus').innerHTML = '<span style="color: green">█</span>';
		logMessage("OTP tunnel monitoring connected");
	};
	
	otpsocket.onmessage = function(event) {
		var incomingMessage = event.data;
		var trimmedString = incomingMessage.substring(0, 80);
		const dataArray = trimmedString.split(" ");
		var pingTarget = dataArray[0];
		var latency = dataArray[1]/1000;
		var packetLoss = dataArray[3];
		var packetLossString = packetLoss.toString();
		var truncatedPacketLoss = packetLossString.substring(0,3);
		var latencyString = latency.toString();
		var truncatedLatency = latencyString.substring(0,5);
		$('#otpsocketData').innerHTML = " <b>OTP Tunnel packet loss:</b> " + truncatedPacketLoss + " % <b>Latency:</b> " + truncatedLatency + " ms";
		// Global variables
		otptunnel_latency = latency;
	    otptunnel_packetloss = packetLoss;
	};
	
	otpsocket.onclose = function(event) {
		$('#otpsocketStatus').innerHTML = '<span style="color: red">█</span>' + event.reason;	
	};
	
	//
	// Socket for packet loss and latency for DNS end point
	//
	var dnsSocket = new WebSocket(wsProtocol+wsHost+':9005');
	dnsSocket.onopen = function(event) {
		$('#dnsSocketStatus').innerHTML = '<span style="color: green">█</span>';
		$('#dnsSocketData').innerHTML = '[waiting data]';
		logMessage("DNS end point monitoring connected");
	};
	dnsSocket.onmessage = function(event) {
		var dnsIncomingMessage = event.data;
		var dnsTrimmedString = dnsIncomingMessage.substring(0, 80);
		const dnsDataArray = dnsTrimmedString.split(" ");
		var dnsPingTarget = dnsDataArray[0];
		var dnsLatency = dnsDataArray[1]/1000;
		var dnsPacketLoss = dnsDataArray[3];
		var dnsPacketLossString = dnsPacketLoss.toString();
		var dnsTruncatedPacketLoss = dnsPacketLossString.substring(0,3);
		var dnsLatencyString = dnsLatency.toString();
		var dnsTruncatedLatency = dnsLatencyString.substring(0,5);
		$('#dnsSocketData').innerHTML = "<b>Packet loss:</b> " + dnsTruncatedPacketLoss + " % <b>Latency:</b> " + dnsTruncatedLatency + " ms";
		if ( dnsPacketLoss == 100 ) {
			$('#dnsSocketStatusInGraph').innerHTML = "<font color='red'>No Internet</font>";
		} else {
			if ( dnsPacketLoss > 5 && dnsPacketLoss < 100 ) {
				$('#dnsSocketStatusInGraph').innerHTML = "<b>Packet loss:</b><font color='red'>  " + dnsTruncatedPacketLoss + " % </font><br><b>Latency:</b> " + dnsTruncatedLatency + " ms";
			} else {
				$('#dnsSocketStatusInGraph').innerHTML = "<b>Packet loss:</b> " + dnsTruncatedPacketLoss + " % <br><b>Latency:</b> " + dnsTruncatedLatency + " ms";
			}
		}	
		dnsLineChart.data.labels[dnsXindex] = getTime(); 		
		if ( dnsLatency == null ){
			dnsLatency = 0; 
			dnsPacketLoss = 0;
		}
		dnsLineChart.data.datasets[0].data[dnsXindex] = dnsLatency;
		dnsLineChart.data.datasets[1].data[dnsXindex] = dnsPacketLoss;
		dnsXindex++;
		dnsLineChart.update();		
	};
	
	dnsSocket.onclose = function(event) {
		$('#dnsSocketStatus').innerHTML = '<span style="color: red">█</span>' + event.reason;	
	};
	
	//
	// Socket for TX key usage
	//
	var txKeyUsagesocket = new WebSocket(wsProtocol+wsHost+':9010');
	txKeyUsagesocket.onopen = function(event) {
		$('#txKeyUsagesocketStatus').innerHTML = '<span style="color: green">█</span>';
		$('#txKeyUsagesocketData').innerHTML = '[waiting data]';
	};
	txKeyUsagesocket.onmessage = function(event) {
		var incomingMessage = event.data;
		var trimmedString = incomingMessage.substring(0, 80);
		const presentageValue = trimmedString.substring(0, trimmedString.length - 2);
		var remainingKey=String(100-presentageValue);
		var truncatedRemainingKey = remainingKey.substring(0,6);
		$('#txKeyUsagesocketData').innerHTML = "TX key remaining: " + truncatedRemainingKey + " %";
		logMessage("TX key remaining: " + truncatedRemainingKey + " %");
		keyUsageChart.data.datasets[0].data[0] = remainingKey;
		keyUsageChart.data.datasets[0].data[1] = presentageValue;
		keyUsageChart.update();
		key_xIndex++;
	};
	
	txKeyUsagesocket.onclose = function(event) {
		$('#txKeyUsagesocketStatus').innerHTML = '<span style="color: red">█</span> ' + event.reason;	
	};
	
	//
	// Socket for RX key usage (pie)
	//
	var rxKeyUsagesocket = new WebSocket(wsProtocol+wsHost+':9015');
	rxKeyUsagesocket.onopen = function(event) {
		$('#rxKeyUsagesocketStatus').innerHTML = '<span style="color: green">█</span>';
		$('#rxKeyUsagesocketData').innerHTML = '[waiting data]';
	};
	rxKeyUsagesocket.onmessage = function(event) {
		var incomingMessage = event.data;
		var trimmedString = incomingMessage.substring(0, 80);
		const presentageValue = trimmedString.substring(0, trimmedString.length - 2);
		var remainingKey = String(100-presentageValue);
		var truncatedRemainingKey = remainingKey.substring(0,6);
		$('#rxKeyUsagesocketData').innerHTML = "RX key remaining: " + truncatedRemainingKey + " %";
		logMessage("RX key remaining: " + truncatedRemainingKey + " %");
		rxKeyUsageChart.data.datasets[0].data[0] = remainingKey;
		rxKeyUsageChart.data.datasets[0].data[1] = presentageValue;
		rxKeyUsageChart.update();
		rx_key_xIndex++;
	};
	
	rxKeyUsagesocket.onclose = function(event) {
		$('#rxKeyUsagesocketStatus').innerHTML = '<span style="color: red">█</span> ' + event.reason;	
	};
	
	//
	// Socket for message log (9020) /tmp/logresponses
	//
	var logMsgSocket = new WebSocket(wsProtocol+wsHost+':9020');
	logMsgSocket.onopen = function(event) {
		$('#systemMessagesStatus').innerHTML = '<span style="color: green">█</span>';
		// $('#systemMessages').innerHTML = '[waiting data]';
	};
	logMsgSocket.onmessage = function(event) {
		var incomingMessage = event.data;
		var trimmedString = incomingMessage.substring(0, 80);
		$('#systemMessages').innerHTML = trimmedString;
		logMsgSocket.send("ack");
	};
	logMsgSocket.onclose = function(event) {
		$('#systemMessagesStatus').innerHTML = '<span style="color: red">█</span> ' + event.reason;	
	};
	
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
