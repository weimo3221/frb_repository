#!/bin/sh
echo "[$0] ..."
fwupdater -i /var/firmware.seama
echo 1 > /proc/driver/system_reset
echo 1 > /proc/system_reset
