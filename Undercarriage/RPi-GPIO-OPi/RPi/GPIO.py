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

HIGH = 1
LOW = 0
IN = 0
OUT = 1
BOARD = 0
BCM = 0
PUD_UP = 1
PUD_DOWN = 2
RPI_INFO = "Generic RPi Clone"
RPI_REVISION = RPI_INFO
VERSION = RPI_INFO
#----------------------------------------------------------------------------------------------
def getPort(channel):
  # Oddly enough, this works like WiringPi in reverse (converts RPi GPIO to WiringPi numbers)
  return 0
#----------------------------------------------------------------------------------------------
def cleanup(**kwargs):
  # This function does nothing, it's just here to mask an RPi.GPIO function
  return True
#----------------------------------------------------------------------------------------------
def input(channel):
  channel = getPort(channel)
  #return gpio.input(channel)
  return 0
#----------------------------------------------------------------------------------------------
def output(channel,state):
  channel = getPort(channel)
  #gpio.output(channel,state)
  return True
#----------------------------------------------------------------------------------------------
def pullup(channel,direction):
  channel = getPort(channel)
  #gpio.pullup(channel,direction)
  return True
#----------------------------------------------------------------------------------------------
def setmode(Ignored):
  #if not os.getegid() == 0:
  #  print("")
  #  print("GPIO access scripts must be run as root on this device, terminating script.")
  #  print("")
  #  sys.exit(1)
  #gpio.init()
  return True
#----------------------------------------------------------------------------------------------
def setup(channel,direction):
  channel = getPort(channel)
  #gpio.setcfg(channel,direction)
  return True
#----------------------------------------------------------------------------------------------
def setwarnings(Ignored):
  # This function does nothing, it's just here to mask an RPi.GPIO function
  return True
#----------------------------------------------------------------------------------------------
