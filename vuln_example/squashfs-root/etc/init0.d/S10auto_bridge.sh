#!/bin/sh
echo [$0]: $1 ... > /dev/console
case "$1" in
start)
	xmldbc -P /etc/scripts/DETECT_AUTO_BRIDGE.php -V START=1 > /var/run/detect_auto_bridge_start.sh
	chmod 777 /var/run/detect_auto_bridge_start.sh
	./var/run/detect_auto_bridge_start.sh
	;;
*)
	echo [$0]: Invalid argument - $1 > /dev/console
	;;
esac
