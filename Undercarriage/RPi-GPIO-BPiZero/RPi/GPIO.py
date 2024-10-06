#----------------------------------------------------------------------------------------------
# RPi-Smart-Still | (CopyLeft) 2023-Present | Larry Athey (https://panhandleponics.com)
#
# RPi.GPIO substitute for Banana Pi M2 Zero (and other older RPi clones with oddball CPUs)
# Requires pyGPIO2 to be installed from https://github.com/rlatn1234/pyGPIO2
#
# Don't rag on my weird ass Python code, it's not my primary language. All this was originally
# in C, but the WiringPi library only supports Raspberry Pi (Broadcom) boards, not clones. I've
# had to rewrite everything in Python with the assistance of the Grok AI (x.com) to coach me on
# porting from one language to the other. I honestly detest Python and have no idea how it ever
# became a standard. Whitespace aware unencapsulated shit language, reminds me of 1980's BASIC.
#----------------------------------------------------------------------------------------------
import os,sys
from pyGPIO2.gpio import gpio,port

HIGH = 1
LOW = 0
IN = 0
OUT = 1
BOARD = 0
BCM = 0
PUD_UP = 1
PUD_DOWN = 2
#----------------------------------------------------------------------------------------------
def getPort(channel):
  if channel == 2:
    return port.GPIO2
  elif channel == 3:
    return port.GPIO3
  elif channel == 4:
    return port.GPIO4
  elif channel == 5:
    return port.GPIO5
  elif channel == 6:
    return port.GPIO6
  elif channel == 7:
    return port.GPIO7
  elif channel == 8:
    return port.GPIO8
  elif channel == 9:
    return port.GPIO9
  elif channel == 10:
    return port.GPIO10
  elif channel == 11:
    return port.GPIO11
  elif channel == 12:
    return port.GPIO12
  elif channel == 13:
    return port.GPIO13
  elif channel == 14:
    return port.GPIO14
  elif channel == 15:
    return port.GPIO15
  elif channel == 16:
    return port.GPIO16
  elif channel == 17:
    return port.GPIO17
  elif channel == 18:
    return port.GPIO18
  elif channel == 19:
    return port.GPIO19
  elif channel == 20:
    return port.GPIO20
  elif channel == 21:
    return port.GPIO21
  elif channel == 22:
    return port.GPIO22
  elif channel == 23:
    return port.GPIO23
  elif channel == 24:
    return port.GPIO24
  elif channel == 25:
    return port.GPIO25
  elif channel == 26:
    return port.GPIO26
  elif channel == 27:
    return port.GPIO27
  else:
    return 0
#----------------------------------------------------------------------------------------------
def input(channel):
  channel = getPort(channel)
  return gpio.input(channel)
#----------------------------------------------------------------------------------------------
def output(channel,state):
  channel = getPort(channel)
  gpio.output(channel,state)
  return True
#----------------------------------------------------------------------------------------------
def pullup(channel,direction):
  channel = getPort(channel)
  gpio.pullup(channel,direction)
  return True
#----------------------------------------------------------------------------------------------
def setmode(Ignored):
  if not os.getegid() == 0:
    sys.exit("GPIO access scripts must be run as root on this device, terminating script.")
  gpio.init()
  return True
#----------------------------------------------------------------------------------------------
def setup(channel,direction):
  channel = getPort(channel)
  gpio.setcfg(channel,direction)
  return True
#----------------------------------------------------------------------------------------------
