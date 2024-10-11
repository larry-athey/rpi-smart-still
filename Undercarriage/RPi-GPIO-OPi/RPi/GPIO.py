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
def getPort(channel):
  # Oddly enough, this works like WiringPi in reverse (converts RPi GPIO to WiringPi numbers)
  if channel == 2:
    return 8
  elif channel == 3:
    return 9
  elif channel == 4:
    return 7
  elif channel == 5:
    return 21
  elif channel == 6:
    return 22
  elif channel == 7:
    return 11
  elif channel == 8:
    return 10
  elif channel == 9:
    return 13
  elif channel == 10:
    return 12
  elif channel == 11:
    return 14
  elif channel == 12:
    return 26
  elif channel == 13:
    return 23
  elif channel == 14:
    return 15
  elif channel == 15:
    return 16
  elif channel == 16:
    return 27
  elif channel == 17:
    return 0
  elif channel == 18:
    return 1
  elif channel == 19:
    return 24
  elif channel == 20:
    return 28
  elif channel == 21:
    return 29
  elif channel == 22:
    return 3
  elif channel == 23:
    return 4
  elif channel == 24:
    return 5
  elif channel == 25:
    return 6
  elif channel == 26:
    return 26
  elif channel == 27:
    return 2
  else:
    return -1
#----------------------------------------------------------------------------------------------
def cleanup(**kwargs):
  # This function does nothing, it's just here to mask an RPi.GPIO function
  return True
#----------------------------------------------------------------------------------------------
def input(channel):
  channel = getPort(channel)
  wiringpi.digitalRead(channel)
  return 0
#----------------------------------------------------------------------------------------------
def output(channel,state):
  channel = getPort(channel)
  wiringpi.digitalWrite(channel,state)
  return True
#----------------------------------------------------------------------------------------------
def pullup(channel,direction):
  channel = getPort(channel)
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
  channel = getPort(channel)
  wiringpi.pinMode(channel,direction)
  return True
#----------------------------------------------------------------------------------------------
def setwarnings(Ignored):
  # This function does nothing, it's just here to mask an RPi.GPIO function
  return True
#----------------------------------------------------------------------------------------------
