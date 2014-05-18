#!/bin/bash

# Udev rule priority from 00 to 99

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

cd $SCRIPT_DIR
if [ ! -f "source" ]; then
	echo "source file not found!";
	exit 1;
else
	. ./source
fi

if [ ! "$(id -u)" == "0" ]; then
	echo "This script must be run as root"
	exit 1
fi

echo "Installing dependencies .. this might take a while..."
apt-get -y update && apt-get -y upgrade
apt-get -y install apache2 php5 libapache2-php5 
echo "Dependencies installed"


# We should work in the script's directory
# Add the group
addgroup "gpio" 2>/dev/null
adduser "www-data" "gpio" 2>/dev/null

echo -n "Installing udev rules... "
cp udev.rules $RULES_TARGET && chmod a+r $RULES_TARGET && echo "[ OK ] " || echo " [ ERROR ]"

echo -n "Reloading udev rules... "
udevadm trigger && echo "[ OK ]" || echo "[ ERROR ]"




