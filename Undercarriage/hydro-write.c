//------------------------------------------------------------------------------------------------
// RPI-Smart-Still Hydrometer Serial Data Writer v1.0.1 released July 14, 2023
// Written by Larry Athey (https://panhandleponics.com)
//
// Simple program to send commands to the hydrometer to reboot or recalibrate it.
//------------------------------------------------------------------------------------------------
#include "stdio.h"
#include "string.h"
#include "errno.h"
#include "wiringPi.h"
#include "wiringSerial.h"
//------------------------------------------------------------------------------------------------
int main(int argc,char **argv) {
  int UART;

  if (argc != 2) {
    fprintf(stderr,"Invalid usage, requires a 1 or 2 character command to be passed");
    return 1;
  }

  if ((UART = serialOpen("/dev/ttyAMA0",9600)) < 0) {
    fprintf(stderr,"Unable to open serial device: %s\n",strerror(errno));
    return 1;
  }

  if (wiringPiSetup () == -1) {
    fprintf(stdout,"Unable to start wiringPi: %s\n",strerror(errno));
    return 1;
  }

  serialPuts(UART,argv[1]);
  delay(500);
  serialFlush(UART);

}
//------------------------------------------------------------------------------------------------
