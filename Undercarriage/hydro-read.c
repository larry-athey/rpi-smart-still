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
#include "stdlib.h"
#include "unistd.h"
#include "errno.h"
#include "wiringPi.h"
#include "wiringSerial.h"

char serialDevice[32];
//------------------------------------------------------------------------------------------------
int getSerialDevice() {
  const char *filename = "/usr/share/rpi-smart-still/hydro-port";
  FILE *file;
  size_t len;

  // Check if the config file exists
  if (access(filename,F_OK) == -1) {
    printf("Config file %s does not exist.\n",filename);
    return 1;
  }

  // Open the config file
  file = fopen(filename,"r");
  if (file == NULL) {
    perror("Error opening config file");
    return 1;
  }

  // Read the config file content
  if (fgets(serialDevice,sizeof(serialDevice),file) != NULL) {
    len = strlen(serialDevice);
    if (len > 0 && serialDevice[len - 1] == '\n') {
      serialDevice[len - 1] = '\0';
    }
    return 0;
  } else {
    printf("Error reading config file or the file is empty.\n");
    fclose(file);
    return 1;
  }

  fclose(file);
}
//------------------------------------------------------------------------------------------------
int main() {
  int UART,Counter = 0;

  if (getSerialDevice() > 0) {
    printf("\n");
    return 1;
  }

  if ((UART = serialOpen(serialDevice,9600)) < 0) {
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
