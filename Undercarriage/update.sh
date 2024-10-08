#!/bin/bash


clear

echo "____________________.__    _________                      __      _________ __  .__.__  .__   "
echo "\______   \______   \__|  /   _____/ _____ _____ ________/  |_   /   _____//  |_|__|  | |  |  "
echo " |       _/|     ___/  |  \_____  \ /     \\\\__  \\\\_  __ \   __\  \_____  \\\\   __\  |  | |  |  "
echo " |    |   \|    |   |  |  /        \  Y Y  \/ __ \|  | \/|  |    /        \|  | |  |  |_|  |__"
echo " |____|_  /|____|   |__| /_______  /__|_|  (____  /__|   |__|   /_______  /|__| |__|____/____/"
echo "        \/                       \/      \/     \/                      \/                    "
echo

if [ -d /usr/share/rpi-smart-still ]; then
  echo "Updating RPi Smart Still software on this system..."
  echo
else
  echo "No existing RPi Smart Still installation found, run ./install.sh instead."
  echo
fi
