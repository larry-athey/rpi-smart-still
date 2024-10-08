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

  echo "Updating undercarriage source code"
  echo
  sudo cp -f cronjob /usr/share/rpi-smart-still
  sudo cp -f heating /usr/share/rpi-smart-still
  sudo cp -f hydro-read /usr/share/rpi-smart-still
  sudo cp -f relay /usr/share/rpi-smart-still
  sudo cp -f valve /usr/share/rpi-smart-still
  sudo cp -f rss* /usr/share/rpi-smart-still
  sudo cp -f *.php /usr/share/rpi-smart-still

  sudo chmod +x /usr/share/rpi-smart-still/*
  sudo chmod -x /usr/share/rpi-smart-still/rss.py

  echo "Updating web root source code"
  echo

  echo
else
  echo "No existing RPi Smart Still installation found, run ./install.sh instead."
  echo
fi
