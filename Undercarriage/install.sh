#!/bin/bash

clear

echo "____________________.__    _________                      __      _________ __  .__.__  .__   "
echo "\______   \______   \__|  /   _____/ _____ _____ ________/  |_   /   _____//  |_|__|  | |  |  "
echo " |       _/|     ___/  |  \_____  \ /     \\\\__  \\\\_  __ \   __\  \_____  \\\\   __\  |  | |  |  "
echo " |    |   \|    |   |  |  /        \  Y Y  \/ __ \|  | \/|  |    /        \|  | |  |  |_|  |__"
echo " |____|_  /|____|   |__| /_______  /__|_|  (____  /__|   |__|   /_______  /|__| |__|____/____/"
echo "        \/                       \/      \/     \/                      \/                    "
echo
echo "THIS INSTALLER EXPECTS YOU TO BE USING A RAW UNMODIFIED OS INSTALLATION!!!!"
echo
echo "This installer script assumes that you are running it as the username 'pi'."
echo "If you are using another user account, press CTRL-C to terminate the script"
echo "create a 'pi' user with sudo access and run this script again."
echo
read -p "Press ENTER to continue the installation or CTRL+C to cancel..." nothing

if [ "$USER" != "pi" ]; then
  echo
  echo "This script needs to be ran as the user 'pi', terminating script."
  exit 1
fi

apt=$(which apt)
if [ "$apt" != "/usr/bin/apt" ]; then
  echo "The RPi Smart Still software requires a Debian derivative operating system."
  exit 1
fi

Legacy=0
if [ $# -gt 0 ] && [ $1 == "legacy" ]; then
  Legacy=1
fi

sudo dpkg-reconfigure locales

sudo systemctl mask sleep.target suspend.target hibernate.target hybrid-sleep.target
sudo apt update
sudo apt install -y alsa-utils curl espeak ffmpeg mpg123 lighttpd python3 python3-pip python3-dev python3-serial php php-common php-fpm php-mysql mariadb-server mariadb-client
sudo apt --fix-broken install -y
sudo apt autoremove -y

OS=$(cat /etc/issue)
Bullseye=0
OrangePi=0

echo $OS | grep "Raspbian" > /dev/null 2>&1
if [ $? -eq 0 ]; then
  Raspbian=1
  echo $OS | grep "11" > /dev/null 2>&1
  if [ $? -eq 0 ]; then
    Bullseye=1
  fi
else
  Raspbian=0
  echo $OS | grep "[B|b]ullseye" > /dev/null 2>&1
  if [ $? -eq 0 ]; then
    Bullseye=1
  fi
  uname -a | grep "[O|o]range" > /dev/null 2>&1
  if [ $? -eq 0 ]; then
    OrangePi=1
  fi
fi

# Get rid of any leftover C code from the pre-Python version
rm -f /usr/share/rpi-smart-still/*.c

sudo systemctl enable lighttpd.service
sudo systemctl start lighttpd.service
sudo lighttpd-enable-mod fastcgi
sudo lighttpd-enable-mod fastcgi-php
if [ $Bullseye -eq 0 ]; then
  PHPversion=$(php --version | sed -n 's/^PHP \([0-9]\+\.[0-9]\+\).*/\1/p')
  sed -i "s/7.4/$PHPversion/g" ./15-fastcgi-php.conf
fi
sudo cp -f 15-fastcgi-php.conf /etc/lighttpd/conf-available/15-fastcgi-php.conf
sudo chown -R www-data:www-data /var/log/lighttpd
sudo systemctl restart lighttpd.service

sudo rm -f /var/www/html/index.lighttpd.html
sudo cp -f ./web-ui/* /var/www/html
cd /var/www/html
sudo tar -xzvf bootstrap.tar.gz
sudo rm -f bootstrap.tar.gz
sudo tar -xzvf js.tar.gz
sudo rm -f js.tar.gz
cd -
sudo mkdir -p /var/www/html/voice_prompts
sudo chown -R www-data:www-data /var/www/html
sudo chmod g+w -R /var/www/html
sudo usermod -a -G www-data pi
ln -s /var/www/html /home/pi/webroot

if [ $Raspbian -eq 0 ]; then
  sed -i "s/ttyAMA0/ttyS0/g" ./config.ini
else
  if [ $Bullseye -eq 0 ]; then
    sed -i "s/ttyAMA0/serial0/g" ./config.ini
  fi
fi

sudo mkdir -p /usr/share/rpi-smart-still
sudo cp -f config.ini /usr/share/rpi-smart-still
sudo cp -f cronjob /usr/share/rpi-smart-still
sudo cp -f heating /usr/share/rpi-smart-still
sudo cp -f hydro-read /usr/share/rpi-smart-still
sudo cp -f relay /usr/share/rpi-smart-still
sudo cp -f valve /usr/share/rpi-smart-still
sudo cp -f rss* /usr/share/rpi-smart-still
sudo cp -f *.php /usr/share/rpi-smart-still

sudo chmod +x /usr/share/rpi-smart-still/*
sudo chmod -x /usr/share/rpi-smart-still/rss.py
sudo chmod -x /usr/share/rpi-smart-still/config.ini

sudo chown -R www-data:www-data /usr/share/rpi-smart-still
sudo chmod g+w -R /usr/share/rpi-smart-still
ln -s /usr/share/rpi-smart-still /home/pi/undercarriage

if [ $Raspbian -eq 0 ]; then
  # Debian for ARM (Armbian) configuration procedures.
  if [ $OrangePi -eq 1 ] && [ $Legacy -eq 0 ]; then
    # Orange Pi configuration procedures.
    git clone https://github.com/orangepi-xunlong/wiringOP
    cd wiringOP
    sudo ./build
    cd ..
    sudo apt-get -y install swig python3-setuptools
    git clone --recursive https://github.com/orangepi-xunlong/wiringOP-Python -b next
    cd wiringOP-Python
    git submodule update --init --remote
    python3 generate-bindings.py > bindings.i
    sudo python3 setup.py install
    cd ..
    sudo cp -rf ./RPi-GPIO-OPi/RPi /usr/share/rpi-smart-still
    sudo cp -f ./config.opi /usr/share/rpi-smart-still/config.ini
  else
    # Banana Pi M5/M2pro/M2S/CM4/M4B/M4Z/F3 and Legacy Models (including old Orange Pi units)
    if [ $Legacy -eq 1 ]; then
      echo "Legacy device installation requested"
      sudo cp -rf ./RPi-GPIO-BPiZero/RPi /usr/share/rpi-smart-still
      git clone https://github.com/rlatn1234/pyGPIO2
      cd pyGPIO2
      sudo python3 setup.py build install
      cd ..
    else
      git clone https://github.com/Dangku/RPi.GPIO
      cd RPi.GPIO
      sudo python3 setup.py clean --all
      sudo python3 setup.py build install
      cd ..
      git clone https://github.com/Dangku/WiringPi
      cd WiringPi
      sudo ./build
      cd ..
    fi
  fi
  sudo cp -f rc.local.armbian /etc/rc.local
  sudo chmod +x /etc/rc.local
  sudo apt purge brltty -y
  sudo systemctl stop serial-getty@ttyS0.service > /dev/null 2>&1
  sudo systemctl disable serial-getty@ttyS0.service > /dev/null 2>&1
  cat /etc/modules | grep "w1-gpio"
  if [ ! $? -eq 0 ]; then
    echo "w1-gpio" | sudo tee -a /etc/modules
  fi
  cat /etc/modules | grep "w1-therm"
  if [ ! $? -eq 0 ]; then
    echo "w1-therm" | sudo tee -a /etc/modules
  fi
  Config="/boot/armbianEnv.txt"
  if [ -f $Config ]; then
    # The following items are 100% correct kernel configuration parameters to enable Armbian DS18B20 support
    # But without a functional w1-gpio kernel overlay, no DS18B20 sensors will appear in /sys/bus/w1/devices
    cat $Config | grep "overlays=w1-gpio"
    if [ ! $? -eq 0 ]; then
      echo "overlays=w1-gpio" | sudo tee -a $Config
    fi
    cat $Config | grep "param_w1_pin=PA6"
    if [ ! $? -eq 0 ]; then
      echo "param_w1_pin=PA6" | sudo tee -a $Config
    fi
    cat $Config | grep "param_w1_pin_int_pullup=1"
    if [ ! $? -eq 0 ]; then
      echo "param_w1_pin_int_pullup=1" | sudo tee -a $Config
    fi
  fi
else
  # Raspbian specific configuration procedures.
  sudo cp -f rc.local /etc/rc.local
  sudo chmod +x /etc/rc.local
  git clone https://github.com/WiringPi/WiringPi
  cd WiringPi
  sudo ./build
  #wget https://project-downloads.drogon.net/wiringpi-latest.deb
  #sudo dpkg -i wiringpi-latest.deb
  if [ $Bullseye -eq 1 ]; then
    sudo systemctl stop serial-getty@ttyAMA0.service > /dev/null 2>&1
    sudo systemctl disable serial-getty@ttyAMA0.service > /dev/null 2>&1
    Config="/boot/config.txt"
  else
    sudo systemctl stop serial-getty@ttyS0.service > /dev/null 2>&1
    sudo systemctl disable serial-getty@ttyS0.service > /dev/null 2>&1
    Config="/boot/firmware/config.txt"
  fi
  cat $Config | grep "dtoverlay=uart0"
  if [ ! $? -eq 0 ]; then
    echo "dtoverlay=uart0" | sudo tee -a $Config
  fi
fi

if [ $Legacy -eq 0 ]; then
  # 3 out 3 devices I have running Armbian, 1 has a nonfunctional w1-gpio kernel overlay, the other 2 have none at all
  # Since this method is 3x faster than reading vales from /sys/bus/w1/devices it's also used with Raspberry Pi boards 
  BusPin=$(gpio readall | head -n7 | tail -n1 | tr -d '|' | awk '{print $2}')
  sed -i "s/#define DS18B20_PIN_NUMBER 7/#define DS18B20_PIN_NUMBER $BusPin/g" ./ds18b20.c
  sudo gcc -Wall -o /usr/share/rpi-smart-still/ds18b20 ./ds18b20.c -lwiringPi -lpthread
fi

sudo systemctl enable mariadb > /dev/null 2>&1
sudo systemctl start mariadb > /dev/null 2>&1

clear

echo "____________________.__    _________                      __      _________ __  .__.__  .__   "
echo "\______   \______   \__|  /   _____/ _____ _____ ________/  |_   /   _____//  |_|__|  | |  |  "
echo " |       _/|     ___/  |  \_____  \ /     \\\\__  \\\\_  __ \   __\  \_____  \\\\   __\  |  | |  |  "
echo " |    |   \|    |   |  |  /        \  Y Y  \/ __ \|  | \/|  |    /        \|  | |  |  |_|  |__"
echo " |____|_  /|____|   |__| /_______  /__|_|  (____  /__|   |__|   /_______  /|__| |__|____/____/"
echo "        \/                       \/      \/     \/                      \/                    "
echo
echo "Time to secure the MySQL server, you will want to answer Yes to all questions"
echo "EXCEPT for the one about using a Unix socket for authentication. Just be sure"
echo "to set the root password to one that you can remember, simple is fine. Keep in"
echo "mind that this system isn't designed to for inbound internet access, you don't"
echo "have to worry about anything too complicated. THIS IS NOT A PUBLIC WEB SERVER!"
echo

sudo mysql_secure_installation
sudo mysql < db-setup.sql

clear

echo "____________________.__    _________                      __      _________ __  .__.__  .__   "
echo "\______   \______   \__|  /   _____/ _____ _____ ________/  |_   /   _____//  |_|__|  | |  |  "
echo " |       _/|     ___/  |  \_____  \ /     \\\\__  \\\\_  __ \   __\  \_____  \\\\   __\  |  | |  |  "
echo " |    |   \|    |   |  |  /        \  Y Y  \/ __ \|  | \/|  |    /        \|  | |  |  |_|  |__"
echo " |____|_  /|____|   |__| /_______  /__|_|  (____  /__|   |__|   /_______  /|__| |__|____/____/"
echo "        \/                       \/      \/     \/                      \/                    "
echo
echo "Now installing phpMyAdmin, be sure to select the lighttpd configuration!"
echo
read -p "Press ENTER to continue..." nothing

sudo apt install -y phpmyadmin
sudo apt purge -y apache2
sudo service lighttpd force-reload
sudo apt clean

clear

echo "____________________.__    _________                      __      _________ __  .__.__  .__   "
echo "\______   \______   \__|  /   _____/ _____ _____ ________/  |_   /   _____//  |_|__|  | |  |  "
echo " |       _/|     ___/  |  \_____  \ /     \\\\__  \\\\_  __ \   __\  \_____  \\\\   __\  |  | |  |  "
echo " |    |   \|    |   |  |  /        \  Y Y  \/ __ \|  | \/|  |    /        \|  | |  |  |_|  |__"
echo " |____|_  /|____|   |__| /_______  /__|_|  (____  /__|   |__|   /_______  /|__| |__|____/____/"
echo "        \/                       \/      \/     \/                      \/                    "
echo
echo "Installation is now complete, but you still need to create the CRON job that"
echo "runs the undercarriage of the system."
echo
echo "Run 'sudo crontab -e' and paste the line of text below into the editor & save."
echo
echo "* * * * * /usr/share/rpi-smart-still/cronjob"
echo
echo "The CRON job shown in the above fires off every minute to verify that all 3"
echo "of the process scripts are running for input, output, and logic control."
echo
echo

if [ $Raspbian -eq 1 ]; then
  echo "You will also need to run 'sudo raspi-config' and go to Interface Options to"
  echo "enable 1-Wire support. Then go to Serial Port and turn off the login shell"
  echo "over serial and leave the serial port enabled. Then exit raspi-config, this"
  echo "will cause your Raspberry PI to be rebooted."

  if [ $Bullseye -eq 0 ]; then
    echo
    echo "NOTE: Since you are not running Raspbian 11 you will need to rely on a USB"
    echo "serial interface if you intend to use LIDAR Hydrometer Reader or Load Cell"
    echo "Hydrometer. Simply edit the file /usr/share/rpi-smart-still/config.ini and"
    echo "update the HYDRO_PORT variable to point to the correct device for your USB"
    echo "serial interface."
  fi
else
  if [ $Legacy -eq 0 ]; then
    echo "Debian for ARM detected, things are a little different with this OS than it"
    echo "is with Raspbian. Just run 'sudo reboot' and you're done. Isn't that better?"
  else
    echo "Since this is a legacy installation, there are some manual steps that need to"
    echo "be taken before the system will be fully functional. Please refer to the Wiki"
    echo "page below for the necessary completion steps."
    echo
    echo "https://github.com/larry-athey/rpi-smart-still/wiki/10.-Legacy-Installation-Help"
  fi
fi

echo
echo
echo "You can delete this git clone after you are done, it is no longer needed."
echo
