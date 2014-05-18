#!/bin/bash

# Clean up the rule file
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

echo -n "Cleaning udev rules... "
rm "$RULES_TARGET" && echo "[ OK ]" || echo "[ ERROR ]"

echo -n "Reloading udev rules... "
udevadm trigger && echo "[ OK ]" || echo "[ ERROR ]"


echo "Cleaning up complete!"
echo "You can now remove the gpio-web folder:"
echo "cd ../..; rm -rf gpio-web"
echo ""
echo "Thanks for using!"



