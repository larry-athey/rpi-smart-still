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
  if [ ! -f /usr/share/rpi-smart-still/config.ini ]; then
    echo "Your software installation is too outdated, you will need to run ./install.sh instead."
    echo
    exit 1
  fi

  echo "Updating RPi Smart Still software on this system..."
  echo

  echo "Updating undercarriage source code"
  echo
  sudo cp -fv cronjob /usr/share/rpi-smart-still
  sudo cp -fv heating /usr/share/rpi-smart-still
  sudo cp -fv hydro-read /usr/share/rpi-smart-still
  sudo cp -fv relay /usr/share/rpi-smart-still
  sudo cp -fv valve /usr/share/rpi-smart-still
  sudo cp -fv rss* /usr/share/rpi-smart-still
  sudo cp -fv *.php /usr/share/rpi-smart-still

  if [ -d /usr/share/rpi-smart-still/RPi ]; then
    sudo cp -rfv ./RPi-GPIO-BPiZero/RPi /usr/share/rpi-smart-still
  fi

  sudo chmod +x /usr/share/rpi-smart-still/*
  sudo chmod -x /usr/share/rpi-smart-still/config.ini
  sudo chmod -x /usr/share/rpi-smart-still/rss.py
  sudo chown -R www-data:www-data /usr/share/rpi-smart-still
  sudo chmod g+w -R /usr/share/rpi-smart-still

  echo
  echo "Updating web root source code"
  echo
  sudo cp -fv ./web-ui/* /var/www/html
  sudo mkdir -p /var/www/html/voice_prompts
  sudo chown -R www-data:www-data /var/www/html
  sudo chmod g+w -R /var/www/html

  echo
  echo "All Done"
  echo
else
  echo "No existing RPi Smart Still installation found, run ./install.sh instead."
  echo
fi
