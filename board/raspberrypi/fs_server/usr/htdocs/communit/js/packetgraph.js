	//
	// graph.js variables
	//
	const myLineChart = new Chart("otpChart", {
	  type: "line",
	  data: {
		labels: xValues,
		datasets: [{
				label: 'Latency',
				backgroundColor: "rgba(200,0,0,1.0)",
				borderColor: "rgba(200,0,0,0.3)",
				data: latencyValues
			},
			{
				label: 'Packet loss',
				backgroundColor: "rgba(0,50,200,1.0)",
				borderColor: "rgba(0,50,200,0.3)",
				data: packetLossValues
			},
			{
				label: 'OTP latency',
				backgroundColor: "rgba(0,200,0,1.0)",
				borderColor: "rgba(0,200,0,0.3)",
				data: otplatencyValues
			},
			{
				label: 'OTP packet loss',
				backgroundColor: "rgba(0,100,50,1.0)",
				borderColor: "rgba(0,100,50,0.3)",
				data: otppacketLossValues
			}
				
			
			]
	  },
	  options: {
		legend: {display: false},
		scales: {
			y: {
				suggestedMin: 0,
				suggestedMax: 200
			},
			x: {
				suggestedMin: 0,
				suggestedMax: 100
			}
		}
	  }
	});
	
	const dnsLineChart = new Chart("dnsChart", {
	  type: "line",
	  data: {
		labels: dnsXvalues,
		datasets: [{
				label: 'Latency',
				backgroundColor: "rgba(20,180,0,1.0)",
				borderColor: "rgba(20,180,0,0.3)",
				data: dnsLatencyValues
			},
			{
				label: 'Packet loss',
				backgroundColor: "rgba(220,220,0,1.0)",
				borderColor: "rgba(240,240,0,0.3)",
				data: dnsPacketLossValues
			}]
	  },
	  options: {
		legend: {display: false},
		scales: {
			y: {
				suggestedMin: 0,
				suggestedMax: 200
			},
			x: {
				suggestedMin: 0,
				suggestedMax: 100
			}
		}
	  }
	});
	
	const keyUsageChart = new Chart("txKeyUsage", {
	  type: 'doughnut',
		data: {
		  labels: ["available","used"],
		  datasets: [{
			label: "Presentage",
			backgroundColor: ["#3cba9f","#c45850"],
			data: key_persentage
		  }]
		},
		options: {
		  title: {
			display: true,
			text: 'TX key usage'
		  }
		}
	});

const rxKeyUsageChart = new Chart("rxKeyUsage", {
		type: 'doughnut',
		data: {
		  labels: ["available","used"],
		  datasets: [{
			label: "Presentage",
			backgroundColor: ["#3cba9f","#c45850"],
			data: rx_key_persentage
		  }]
		},
		options: {
		  title: {
			display: true,
			text: 'RX key usage'
		  }
		}
	});
