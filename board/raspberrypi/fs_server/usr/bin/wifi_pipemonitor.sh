#!/bin/sh

fifo_name="/tmp/wificontrolout"                      

# trap "rm -f $fifo_name" EXIT                  

exec 3< $fifo_name                              # redirect fifo_name to fd 3
                                                # (not required, but makes read clearer)
while :; do
    if read -r -u 3 line; then                  # read line from fifo_name

	# Scan (old)
	# if [ "$line" = 'wifiscan' ]; then
	# 	printf "%s: 'wifiscan' command received\n" "$fifo_name"
	# 	# NOTE: ssid's with space do not work with this, feel free to fix this.
	# 	state=$(iwctl station wlan0 show | grep "^\s*State" | awk '{print $2}')
	# 	iwctl station wlan0 scan  > /dev/null 2>&1
	# 	sleep 2
	# 	networks=$(iwctl station wlan0 get-networks | tail -n +5 | head -n -1 |  cut -d" " -f7)
	# 	echo $networks > /tmp/wificontrolin
	# fi

	printf "=> %s: %s\n" "$fifo_name" "$line"  

	# TODO: Unify command parsing

	# Capture connect parameters & do connect
	elements=$(echo $line | tr "," "\n")
	COUNTER=0
	for element in $elements
	do		
		if [ $COUNTER == 0 ]; then
			COMMAND_CODE=$element
		fi
		if [ $COUNTER == 1 ]; then
			SSID_VALUE=$element
		fi
		if [ $COUNTER == 2 ]; then
			PASSWORD_VALUE=$element
		fi
		let COUNTER++
	done
	
	# wificonnect
	if [ "$COMMAND_CODE" = 'wificonnect' ]; then
		echo "Wifi connect with: $SSID_VALUE & $PASSWORD_VALUE"
		/bin/iwctl --passphrase ${PASSWORD_VALUE} station wlan0 connect ${SSID_VALUE}
		echo "connectstatus;OK" > /tmp/wificontrolin
		# execution output: $? to UI ack message ?
		sleep 5
		# Send IP's
		wlan_ip=$(ip a show wlan0 | grep 'inet ' | head -n 4 | awk '{print $2}' | cut -d'/' -f1)
		eth0_ip=$(ip a show eth0 | grep 'inet ' | head -n 4 | awk '{print $2}' | cut -d'/' -f1)		
		echo "ipaddresses;" wlan0,$wlan_ip,eth0,$eth0_ip > /tmp/wificontrolin
		sleep 1
		# Send also details
		wlan_status=$(iwctl station wlan0 show | tail -n +5 | head -n -1|cut -d" " -f 13-40)
		echo "wlanstatus;" $wlan_status > /tmp/wificontrolin
	fi
	
	# wifi forget
	if [ "$COMMAND_CODE" = 'wififorget' ]; then
		echo "Wifi forget of: $SSID_VALUE"
		/bin/iwctl known-networks ${SSID_VALUE} forget
		# TODO: ack message or output capture to UI ?
		echo "forgotnetwork;" > /tmp/wificontrolin
	fi
	
	# Wifi scan (new)
	if [ "$COMMAND_CODE" = 'wifiscan' ]; then
		printf "%s: 'wifiscan' command received\n" "$fifo_name"
		# NOTE: ssid's with space do not work with this, feel free to fix this.
		state=$(iwctl station wlan0 show | grep "^\s*State" | awk '{print $2}')
		iwctl station wlan0 scan  > /dev/null 2>&1
		sleep 2
		knownnetworks=$(iwctl known-networks list | tail -n +5 | head -n -1 |  cut -d" " -f3)
		networks=$(iwctl station wlan0 get-networks | tail -n +5 | head -n -1 |  cut -d" " -f7)
		echo "visiblenetworks;" $networks $knownnetworks > /tmp/wificontrolin
	fi
	
	# interface ip's
	if [ "$COMMAND_CODE" = 'getips' ]; then
	
		wlan_ip=$(ip a show wlan0 | grep 'inet ' | head -n 4 | awk '{print $2}' | cut -d'/' -f1)
		eth0_ip=$(ip a show eth0 | grep 'inet ' | head -n 4 | awk '{print $2}' | cut -d'/' -f1)
		echo wlan0,$wlan_ip,eth0,$eth0_ip
		echo "ipaddresses;" wlan0,$wlan_ip,eth0,$eth0_ip > /tmp/wificontrolin
	fi
	
	# wlan status
	# iwctl station wlan0 show | tail -n +5 | head -n -1|cut -d" " -f 13-40
	if [ "$COMMAND_CODE" = 'wlanstatus' ]; then
		wlan_status=$(iwctl station wlan0 show | tail -n +5 | head -n -1|cut -d" " -f 13-40)
		echo "wlanstatus;" $wlan_status > /tmp/wificontrolin
	fi
	

	#
	# TODO: CHeck if vault is already open
	#
	if [ "$COMMAND_CODE" = 'isvaultopen' ]; then

		if [ -f "/tmp/vault/created" ]; then
                        echo "wlanstatus; Vault is already open!" > /tmp/wificontrolin
		else
			echo "wlanstatus; Vault is closed" > /tmp/wificontrolin
                fi


	fi


	#
	# vaultopen + nossid + pin
	#
	if [ "$COMMAND_CODE" = 'vaultopen' ]; then
		echo "vaultopen: $PASSWORD_VALUE"
		echo "wlanstatus; Please wait..." > /tmp/wificontrolin 
		
		echo $PASSWORD_VALUE | sha256sum | cut -d" " -f1 > /tmp/vaultkey
		/sbin/cryptsetup luksOpen --key-file /tmp/vaultkey /mnt/usba/vault_0 volume1 # && echo -n "Valid pin!" > /tmp/wificontrolin
		
		# 
		# don't change these indications
		#
		if [ $? -eq 0 ]
		then
			mkdir /tmp/vault
			mount /dev/mapper/volume1 /tmp/vault
			systemctl start otp-tunnel-server.service
			systemctl start dpinger-wg-endpoint-socket.service
			systemctl start dpinger-otp-socket.service
			systemctl start dpinger-dns-socket.service
			systemctl start keymonitor-rx-socket.service
			systemctl start keymonitor-tx-socket.service
			systemctl start systemlog-socket.service
			systemctl stop apache-init
			systemctl start apache-full
			echo "wlanstatus; Valid pin!" > /tmp/wificontrolin
		else
			echo "wlanstatus; Invalid pin!" > /tmp/wificontrolin
		fi

		# echo "wlanstatus; mounting vault partition..." > /tmp/wificontrolin		
		# mount /dev/mapper/volume1 /tmp/vault
		
		# echo "wlanstatus; vault partition ready" > /tmp/wificontrolin
		# umount  /tmp/vault
		# cryptsetup luksClose volume1
	fi	

	#
	# poweroff
	#
        if [ "$COMMAND_CODE" = 'poweroff' ]; then
                echo "poweroff stopping services"         
		# Stop services, unmount and luksClose
		systemctl stop keymonitor-rx-socket.service
		systemctl stop keymonitor-tx-socket.service
		systemctl stop otp-tunnel-client.service
		systemctl stop wifi-pipemonitor-socket.service
		systemctl stop dpinger-otp-socket.service
		systemctl stop dpinger-wg-endpoint-socket.service
		systemctl stop dpinger-dns-socket.service
		systemctl stop systemlog-socket.service
		systemctl stop dpinger-dns
		systemctl stop dpinger-otp
		systemctl stop dpinger-wg-endpoint
		echo "poweroff unmounting vault"
		umount  /tmp/vault
		echo "Closing vault"
		cryptsetup luksClose volume1
		# TODO: poweroff with force
		/sbin/poweroff -f
        fi

	#
	# USB drive status check
	#

	if [ "$COMMAND_CODE" = 'driveinserted' ]; then

		if [ -f "/mnt/usba/vault_0" ]; then
			echo "wlanstatus; USB drive found" > /tmp/wificontrolin		
		else
			echo "wlanstatus; USB drive not found" > /tmp/wificontrolin
		fi

	fi


    fi
done

exec 3<&-                                       # reset fd 3 redirection

exit 0

