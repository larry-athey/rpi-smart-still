#!/bin/bash

clear

echo "THIS INSTALLER EXPECTS YOU TO BE USING A RAW UNMODIFIED OS INSTALLATION!!!!"
echo
echo "If you are using anything prior to a Raspberry Pi 4, you need to be running"
echo "a 32 bit version of Raspbian, even though there are 64 bit versions for it."
echo "Unless you have 4+ GB of RAM, a 64 bit OS is completely useless. You surely"
echo "will have problems compiling the servo valve driver under a 64 bit Raspbian"
echo "on anything older than a Pi 4."
echo
read -p "Press ENTER to continue the installation or CTRL+C to cancel..." nothing

sudo dpkg-reconfigure locales

sudo apt update
sudo apt upgrade -y
sudo apt autoremove -y
sudo apt install -y alsa-utils espeak lighttpd php php-common php-fpm php-mysql mariadb-server mariadb-client
sudo apt --fix-broken install -y
sudo apt clean

sudo lighttpd-enable-mod fastcgi
sudo lighttpd-enable-mod fastcgi-php
sudo cp -f ./15-fastcgi-php.conf /etc/lighttpd/conf-available/15-fastcgi-php.conf
sudo service lighttpd force-reload

sudo rm -f /var/www/html/index.lighttpd.html
sudo cp -f ./web-ui/*.php /var/www/html
sudo chown -R www-data:www-data /var/www/html
sudo chmod g+w -R /var/www/html

wget https://project-downloads.drogon.net/wiringpi-latest.deb
sudo dpkg -i wiringpi-latest.deb
sudo systemctl mask serial-getty@ttyAMA0.service
sudo echo "dtoverlay=uart0" >> /boot/config.txt

sudo mkdir -p /usr/share/rpi-smart-still
sudo cp -f cronjob /usr/share/rpi-smart-still
sudo cp -f rss* /usr/share/rpi-smart-still
sudo cp -f valve.c /usr/share/rpi-smart-still
sudo cp -f PhpSerial.php /usr/share/rpi-smart-still

sudo gcc -o /usr/share/rpi-smart-still/hydro-read /usr/share/rpi-smart-still/hydro-read.c -l wiringPi
sudo gcc -o /usr/share/rpi-smart-still/hydro-write /usr/share/rpi-smart-still/hydro-write.c -l wiringPi
sudo gcc -o /usr/share/rpi-smart-still/valve /usr/share/rpi-smart-still/valve.c -l wiringPi

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
sudo mysql < dbsetup.sql

clear

echo "Now installing phpMyAdmin, be sure to select the lighttpd configuration!"
echo
read -p "Press ENTER to continue..." nothing

sudo apt install -y phpmyadmin
sudo service lighttpd force-reload

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
echo "You will also need to run 'sudo raspi-config' and go to Interface Options to"
echo "enable 1-Wire support. Then go to Serial Port and turn off the login shell"
echo "over serial and leave the serial port enabled. Then exit raspi-config, this"
echo "will cause your Raspberry PI to be rebooted."
echo
echo
echo "You can delete this git clone after you are done, it is no longer needed."
echo
