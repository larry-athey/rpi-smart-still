#!/bin/bash

if [ "$USER" != "pi" ]; then
  echo "This script needs to be ran as the user 'pi', terminating script."
  exit 1
fi

clear

echo "THIS INSTALLER EXPECTS YOU TO BE USING A RAW UNMODIFIED OS INSTALLATION!!!!"
echo
echo "If you are using anything prior to a Raspberry Pi 4, you need to be running"
echo "a 32 bit version of Raspbian, even though there are 64 bit versions for it."
echo "Unless you have 4+ GB of RAM, a 64 bit OS is completely useless. You surely"
echo "will have problems compiling the servo valve driver under a 64 bit Raspbian"
echo "on anything older than a Pi 4."
echo
echo "This installer script assumes that you are running it as the username 'pi'."
echo "If you are using another user account, press CTRL-C to terminate the script"
echo "create a 'pi' user with sudo access and run this script again."
echo
read -p "Press ENTER to continue the installation or CTRL+C to cancel..." nothing

sudo dpkg-reconfigure locales

sudo apt update
sudo apt upgrade -y
sudo apt autoremove -y
sudo apt install -y lshw alsa-utils espeak ffmpeg mpg123 lighttpd php php-common php-fpm php-mysql mariadb-server mariadb-client
sudo apt --fix-broken install -y
sudo apt clean

OS=$(cat /etc/issue)
Bullseye=0

echo $OS | grep "Raspbian" > /dev/null
if [ $? -eq 0 ]; then
  Raspbian=1
  echo $OS | grep "11" > /dev/null
  if [ $? -eq 0 ]; then
    Bullseye=1
  fi
else
  Raspbian=0
  echo $OS | grep "bullseye" > /dev/null
  if [ $? -eq 0 ]; then
    Bullseye=1
  fi
fi

sudo lighttpd-enable-mod fastcgi
sudo lighttpd-enable-mod fastcgi-php
if [ $Raspbian -eq 1 ] && [ $Bullseye -eq 0 ]; then
  sed -i "s/7.4:/8.2:/g" ./15-fastcgi-php.conf 
fi
sudo cp -f ./15-fastcgi-php.conf /etc/lighttpd/conf-available/15-fastcgi-php.conf
sudo service lighttpd force-reload

sudo rm -f /var/www/html/index.lighttpd.html
sudo cp -f ./web-ui/* /var/www/html
sudo mkdir -p /var/www/html/voice_prompts
sudo chown -R www-data:www-data /var/www/html
sudo chmod g+w -R /var/www/html
sudo usermod -a -G www-data pi
ln -s /var/www/html /home/pi/webroot

if [ $Raspbian -eq 1 ]; then
  wget https://project-downloads.drogon.net/wiringpi-latest.deb
  sudo dpkg -i wiringpi-latest.deb
else
  echo "Installing Debian for ARM 1-Wire support."
fi

if [ $Raspbian -eq 1 ] && [ $Bullseye -eq 1 ]; then
  # This is strictly for Raspbian 11 "Legacy" systems, this file has moved to /boot/firmare/config.txt
  # on Raspbian 12 which breaks serial communications on GPIO pins 14/15 with anything before a model 5.
  # This file also doesn't exist on Armbian and standard Debian for ARM systems.
  sudo systemctl mask serial-getty@ttyAMA0.service
  cat /boot/config.txt | grep "dtoverlay=uart0"
  if [ ! $? -eq 0 ]; then
    sudo cp /boot/config.txt /tmp/config.txt
    sudo chown pi:pi /tmp/config.txt
    sudo echo "dtoverlay=uart0" >> /tmp/config.txt
    sudo rm -f /boot/config.txt
    sudo mv /tmp/config.txt /boot/config.txt
    sudo chown root:root /boot/config.txt
  fi
fi

sudo mkdir -p /usr/share/rpi-smart-still
sudo cp -f cronjob /usr/share/rpi-smart-still
sudo cp -f hydro-port /usr/share/rpi-smart-still
sudo cp -f rss* /usr/share/rpi-smart-still
sudo cp -f *.c /usr/share/rpi-smart-still
sudo cp -f *.php /usr/share/rpi-smart-still

if [ $Raspbian -eq 1 ]; then
  sudo gcc -o /usr/share/rpi-smart-still/heating /usr/share/rpi-smart-still/heating.c -l wiringPi
  sudo gcc -o /usr/share/rpi-smart-still/hydro-read /usr/share/rpi-smart-still/hydro-read.c -l wiringPi
  sudo gcc -o /usr/share/rpi-smart-still/hydro-write /usr/share/rpi-smart-still/hydro-write.c -l wiringPi
  sudo gcc -o /usr/share/rpi-smart-still/valve /usr/share/rpi-smart-still/valve.c -l wiringPi
fi

sudo chmod +x /usr/share/rpi-smart-still/*
sudo chmod -x /usr/share/rpi-smart-still/*.c

sudo chown -R www-data:www-data /usr/share/rpi-smart-still
sudo chmod g+w -R /usr/share/rpi-smart-still
ln -s /usr/share/rpi-smart-still /home/pi/undercarriage

sudo systemctl enable mariadb > /dev/null 2>&1
sudo systemctl start mariadb > /dev/null 2>&1

sudo cp -f rc.local /etc/rc.local
sudo chmod +x /etc/rc.local

clear

echo "Time to secure the MySQL server, you will want to answer Yes to all questions"
echo "EXCEPT for the one about using a Unix socket for authentication. Just be sure"
echo "to set the root password to one that you can remember, simple is fine. Keep in"
echo "mind that this system isn't designed to for inbound internet access, you don't"
echo "have to worry about anything too complicated. THIS IS NOT A PUBLIC WEB SERVER!"
echo

sudo mysql_secure_installation
sudo mysql < db-setup.sql

clear

echo "Now installing phpMyAdmin, be sure to select the lighttpd configuration!"
echo
read -p "Press ENTER to continue..." nothing

sudo apt install -y phpmyadmin
sudo apt purge -y apache2
sudo service lighttpd force-reload
sudo rm -f /usr/share/rpi-smart-still/rpi-smart-still
sudo rm -f /var/www/html/html

clear

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
    echo "Since you aren't running Raspbian 11, the serial communications bus on GPIO"
    echo "pins 14/15 will not work. This will prevent you from using the LIDAR hydrometer"
    echo "reader or the Load Cell hydrometer. All other features will still work normally."
  fi
else
  echo "Debian for ARM detected, things are a little different with this OS. You'll"
  echo "need to manually install WiringPI for your OS and compile the binaries that"
  echo "I have written in C. Not a big deal, just copy and paste the commands below"
  echo "after WiringPI is installed and tested with 'gpio readall'."
  echo
  echo "If you are using a Banana PI board, go to the following URL for WiringPI."
  echo "https://github.com/BPI-SINOVOIP/BPI-WiringPi"
  echo
  echo "Compile Commands:"
  echo "sudo gcc -o /usr/share/rpi-smart-still/heating /usr/share/rpi-smart-still/heating.c -l wiringPi"
  echo "sudo gcc -o /usr/share/rpi-smart-still/hydro-read /usr/share/rpi-smart-still/hydro-read.c -l wiringPi"
  echo "sudo gcc -o /usr/share/rpi-smart-still/hydro-write /usr/share/rpi-smart-still/hydro-write.c -l wiringPi"
  echo "sudo gcc -o /usr/share/rpi-smart-still/valve /usr/share/rpi-smart-still/valve.c -l wiringPi"
fi

echo
echo
echo "You can delete this git clone after you are done, it is no longer needed."
echo
