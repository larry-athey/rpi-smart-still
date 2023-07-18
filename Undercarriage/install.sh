#!/bin/bash

sudo dpkg-reconfigure locales

sudo apt update
sudo apt upgrade -y
sudo apt autoremove -y
sudo apt install -y alsa-utils espeak git-core lighttpd php php-common php-fpm 
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

sudo gcc -o /usr/share/rpi-smart-still/valve /usr/share/rpi-smart-still/valve.c -l wiringPi

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
