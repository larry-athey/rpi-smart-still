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
  # Get rid of any leftover C code from the pre-Python version
  rm -f /usr/share/rpi-smart-still/*.c

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
    cat /usr/share/rpi-smart-still/RPi/GPIO.py | grep "pyGPIO2"
    if [ $? -eq 0 ]; then
      sudo cp -rfv ./RPi-GPIO-BPiZero/RPi /usr/share/rpi-smart-still
    else
      sudo cp -rfv ./RPi-GPIO-OPi/RPi /usr/share/rpi-smart-still
    fi
  fi

  echo
  echo "Compiling and installing the latest DS18B20 temperature sensor reader"
  BusPin=$(gpio readall | head -n7 | tail -n1 | tr -d '|' | awk '{print $2}')
  sed -i "s/#define DS18B20_PIN_NUMBER 7/#define DS18B20_PIN_NUMBER $BusPin/g" ./ds18b20.c > /dev/null 2>&1
  sudo gcc -Wall -o /usr/share/rpi-smart-still/ds18b20 ./ds18b20.c -lwiringPi -lpthread

  sudo chmod +x /usr/share/rpi-smart-still/*
  sudo chmod -x /usr/share/rpi-smart-still/config.ini
  sudo chmod -x /usr/share/rpi-smart-still/rss.py
  sudo chown -R www-data:www-data /usr/share/rpi-smart-still
  sudo chmod g+w -R /usr/share/rpi-smart-still

  # Force all undercarriage daemons to restart
  sudo pkill -f "/usr/share/rpi-smart-still/rss"

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
