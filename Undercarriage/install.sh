#!/bin/bash

clear

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

sudo cp -f ./web-ui/*.php /var/www/html
sudo chown -R www-data:www-data /var/www/html
sudo chmod g+w -R /var/www/html

wget https://project-downloads.drogon.net/wiringpi-latest.deb
sudo dpkg -i wiringpi-latest.deb

sudo mkdir -p /usr/share/rpi-smart-still
sudo cp -f cronjob /usr/share/rpi-smart-still
sudo cp -f rss* /usr/share/rpi-smart-still
sudo cp -f valve.c /usr/share/rpi-smart-still
sudo cp -f PhpSerial.php /usr/share/rpi-smart-still

sudo gcc -o /usr/share/rpi-smart-still/valve /usr/share/rpi-smart-still/valve.c -l wiringPi

sudo systemctl enable mariadb > /dev/null 2>&1
sudo systemctl start mariadb > /dev/null 2>&1

clear

echo "Time to secure the MySQL server, you will want to answer Yes to all questions"
echo "and be sure to set the root password to one that you can remember. Since this"
echo "system isn't meant to be given inbound public internet access, you don't have"
echo "to worry about anything too complicated. THIS IS NOT A PUBLIC WEB SERVER!"
echo

sudo mysql_secure_installation

echo
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
echo "You will also need to run 'sudo raspi-config' and go to Interface Options to"
echo "enable 1-wire support. This will require your Raspberry PI to be rebooted."
echo
echo
echo "You can delete this git clone after you are done, it is no longer needed."
echo
