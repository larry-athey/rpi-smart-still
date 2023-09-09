//------------------------------------------------------------------------------------------------
// RPI-Smart-Still Hydrometer Serial Data Reader v1.0.1 released July 14, 2023
// Written by Larry Athey (https://panhandleponics.com)
//
// Simple program to wait up to 10 seconds for data to arrive from the hydrometer and echo it to
// stdout for the undercarriage to parse and record in the database. This isn't feasible to do in
// PHP alone, even though it's technically possible to do with a whole lot of spaghetti code.
//------------------------------------------------------------------------------------------------
#include "stdio.h"
#include "string.h"
#include "errno.h"
#include "wiringPi.h"
#include "wiringSerial.h"
//------------------------------------------------------------------------------------------------
int main() {
  int UART,Counter = 0;

  if ((UART = serialOpen("/dev/ttyAMA0",9600)) < 0) {
    fprintf(stderr,"Unable to open serial device: %s\n",strerror(errno));
    return 1;
  }

  if (wiringPiSetup () == -1) {
    fprintf(stdout,"Unable to start wiringPi: %s\n",strerror(errno));
    return 1;
  }

  while (Counter < 10) {
    if (serialDataAvail(UART)) {
      delay(500);
      while (serialDataAvail(UART)) {
        fprintf(stdout,"%c",serialGetchar(UART));
      }
      break;
    } else {
      delay(1000);
      Counter ++;
    }
  }

}
//------------------------------------------------------------------------------------------------
