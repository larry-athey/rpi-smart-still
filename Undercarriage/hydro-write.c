//------------------------------------------------------------------------------------------------
// RPI-Smart-Still Hydrometer Serial Data Writer v1.0.1 released July 14, 2023
// Written by Larry Athey (https://panhandleponics.com)
//
// Simple program to send commands to the hydrometer to reboot or recalibrate it.
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
int main(int argc,char **argv) {
  int UART;

  if (getSerialDevice() > 0) {
    printf("\n");
    return 1;
  }

  if (argc != 2) {
    fprintf(stderr,"Invalid usage, requires a 1 or 2 character command to be passed");
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

  serialPuts(UART,argv[1]);
  delay(500);
  serialFlush(UART);

}
//------------------------------------------------------------------------------------------------
