#!/bin/sh
echo [$0]  $1 ... > /dev/console
[ "$1" != "bound" ] && exit 1
# We only need ip & subnet
phpsh /etc/scripts/autobridge.php IPADDR=$ip SUBNET=$subnet

exit 0
