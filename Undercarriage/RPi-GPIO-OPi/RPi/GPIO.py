#----------------------------------------------------------------------------------------------
# RPi-Smart-Still | (CopyLeft) 2023-Present | Larry Athey (https://panhandleponics.com)
#
# RPi.GPIO substitute for later model Orange Pi boards
# Requires wiringOP-Python to be installed from https://github.com/orangepi-xunlong/wiringOP-Python
#
# Don't rag on my weird ass Python code, it's not my primary language. All this was originally
# in C, but the WiringPi library only supports Raspberry Pi (Broadcom) boards, not clones. I've
# had to rewrite everything in Python with the assistance of the Grok AI (x.com) to coach me on
# porting from one language to the other. I honestly detest Python and have no idea how it ever
# became a standard. Whitespace aware unencapsulated shit language, reminds me of 1980's BASIC.
#----------------------------------------------------------------------------------------------
import os,sys
import wiringpi
from wiringpi import GPIO as WPI

HIGH = WPI.HIGH
LOW = WPI.LOW
IN = WPI.INPUT
OUT = WPI.OUTPUT
BOARD = 0
BCM = 0
PUD_UP = WPI.PUD_UP
PUD_DOWN = WPI.PUD_DOWN
RPI_INFO = "Generic RPi Clone"
RPI_REVISION = RPI_INFO
VERSION = RPI_INFO
#----------------------------------------------------------------------------------------------
# This RPi.GPIO masking library doesn't use a getPort() function to translate Raspberry Pi GPIO
# port numbers. You need to edit /usr/share/rpi-smart-still/config.ini and point the variables
# to WiringPi port numbers. Execute 'gpio readall' to show the WiringPi values for your device.
#----------------------------------------------------------------------------------------------
def cleanup(**kwargs):
  # This function does nothing, it's just here to mask an RPi.GPIO function
  return True
#----------------------------------------------------------------------------------------------
def input(channel):
  wiringpi.digitalRead(channel)
  return 0
#----------------------------------------------------------------------------------------------
def output(channel,state):
  wiringpi.digitalWrite(channel,state)
  return True
#----------------------------------------------------------------------------------------------
def pullup(channel,direction):
  wiringpi.pullUpDnControl(channel,direction)
  return True
#----------------------------------------------------------------------------------------------
def setmode(Ignored):
  if not os.getegid() == 0:
    print("")
    print("GPIO access scripts must be run as root on this device, terminating script.")
    print("")
    sys.exit(1)
  wiringpi.wiringPiSetup()
  return True
#----------------------------------------------------------------------------------------------
def setup(channel,direction):
  wiringpi.pinMode(channel,direction)
  return True
#----------------------------------------------------------------------------------------------
def setwarnings(Ignored):
  # This function does nothing, it's just here to mask an RPi.GPIO function
  return True
#----------------------------------------------------------------------------------------------
