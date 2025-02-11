//------------------------------------------------------------------------------------------------
// RPi-Smart-Still | (CopyLeft) 2023-Present | Larry Athey (https://panhandleponics.com)
//
// Cooling Valve Driver v1.0.1, originally released July 14, 2023
//
// This program was written before the implementation of /usr/share/rpi-smart-sill/config.ini and
// uses WiringPi port numbers rather than Broadcom GPIO numbers. You'll need to run `gpio readall`
// to obtain the WiringPi port numbers that match the physical pins of the GPIO bus on your board.
// Edit the constants below as necessary, compile, and replace the python script with the compiled
// executable. This program does **not** read the port definitions in your config.ini file.
//
//------------------------------------------------------------------------------------------------
// Compile command:
// gcc -Wall -o valve valve.c -lwiringPi -lpthread
//------------------------------------------------------------------------------------------------
#include "stdio.h"
#include "stdlib.h"
#include "string.h"
#include "sys/time.h"
#include "wiringPi.h"
//------------------------------------------------------------------------------------------------
#define VALVE1_OPEN 6 // Valve 1 is the condenser valve
#define VALVE1_CLOSE 5
#define VALVE1_LIMIT_OPEN 4
#define VALVE1_LIMIT_CLOSE 3
#define VALVE2_OPEN 29 // Valve 2 is the dephleg valve
#define VALVE2_CLOSE 28
#define VALVE2_LIMIT_OPEN 24
#define VALVE2_LIMIT_CLOSE 1
//------------------------------------------------------------------------------------------------
void valveFullPosition(int WhichOne,int Direction) {
  //printf("valveFullPosition(WhichOne=%d,Direction=%d)\n",WhichOne,Direction);
  //return;

  if (WhichOne == 1) {
    if (Direction == 0) {
      digitalWrite(VALVE1_CLOSE,HIGH);
      while (digitalRead(VALVE1_LIMIT_CLOSE) == 1) delay(10);
      digitalWrite(VALVE1_CLOSE,LOW);
    } else {
      digitalWrite(VALVE1_OPEN,HIGH);
      while (digitalRead(VALVE1_LIMIT_OPEN) == 1) delay(10);
      digitalWrite(VALVE1_OPEN,LOW);
    }
  } else {
    if (Direction == 0) {
      digitalWrite(VALVE2_CLOSE,HIGH);
      while (digitalRead(VALVE2_LIMIT_CLOSE) == 1) delay(10);
      digitalWrite(VALVE2_CLOSE,LOW);
    } else {
      digitalWrite(VALVE2_OPEN,HIGH);
      while (digitalRead(VALVE2_LIMIT_OPEN) == 1) delay(10);
      digitalWrite(VALVE2_OPEN,LOW);
    }
  }
}
//------------------------------------------------------------------------------------------------
void valvePulse(int WhichOne,int Direction,int Duration) {
  //printf("valvePulse(WhichOne=%d,Direction=%d,Duration=%d)\n",WhichOne,Direction,Duration);
  //return;

  if (WhichOne == 1) {
    if (Direction == 0) {
      digitalWrite(VALVE1_CLOSE,HIGH);
      delay(Duration);
      digitalWrite(VALVE1_CLOSE,LOW);
    } else {
      digitalWrite(VALVE1_OPEN,HIGH);
      delay(Duration);
      digitalWrite(VALVE1_OPEN,LOW);
    }
  } else {
    if (Direction == 0) {
      digitalWrite(VALVE2_CLOSE,HIGH);
      delay(Duration);
      digitalWrite(VALVE2_CLOSE,LOW);
    } else {
      digitalWrite(VALVE2_OPEN,HIGH);
      delay(Duration);
      digitalWrite(VALVE2_OPEN,LOW);
    }
  }
}
//------------------------------------------------------------------------------------------------
void valveCalibrate(int WhichOne,int Direction) {
  //printf("valveCalibrate(WhichOne=%d,Direction=%d)\n",WhichOne,Direction);
  //return;

  struct timeval startTime,stopTime;
  if (Direction == 0) {
    valveFullPosition(WhichOne,1);
    gettimeofday(&startTime,NULL);
    valveFullPosition(WhichOne,0);
    gettimeofday(&stopTime,NULL);
    printf("%d\n",((stopTime.tv_sec - startTime.tv_sec) * 1000000 + stopTime.tv_usec - startTime.tv_usec) / 1000);
  } else {
    valveFullPosition(WhichOne,0);
    gettimeofday(&startTime,NULL);
    valveFullPosition(WhichOne,1);
    gettimeofday(&stopTime,NULL);
    printf("%d\n",((stopTime.tv_sec - startTime.tv_sec) * 1000000 + stopTime.tv_usec - startTime.tv_usec) / 1000);
  }
}
//------------------------------------------------------------------------------------------------
void valveStatus(int WhichOne) {
  //printf("valveStatus(WhichOne=%d)\n",WhichOne);
  //return;

  if (WhichOne == 1) {
    if (digitalRead(VALVE1_LIMIT_OPEN) == 0) {
      printf("1\n");
    } else if (digitalRead(VALVE1_LIMIT_CLOSE) == 0) {
      printf("0\n");
    } else {
      printf("10\n");
    }
  } else {
    if (digitalRead(VALVE2_LIMIT_OPEN) == 0) {
      printf("1\n");
    } else if (digitalRead(VALVE2_LIMIT_CLOSE) == 0) {
      printf("0\n");
    } else {
      printf("10\n");
    }
  }
}
//------------------------------------------------------------------------------------------------
int main(int argc,char **argv) {
  char *p;

  //for (int i = 0; i < argc; ++i) {
  //  printf("argv[%d]: %s\n", i, argv[i]);
  //}

  if ((argc == 1) || (argc > 4)) {
    printf("\nRPi-Smart-Still Cooling Valve Driver v1.0.1 released July 14, 2023\n\n");
    printf("Usage:\n");
    printf("  valve [1 or 2] [open/close/status] [time in ms, or calibrate, or nothing to fully open/close]\n");
    printf("  valve 1 open 2500 {Run valve #1 forward for 2.5 seconds}\n");
    printf("  valve 1 status {Read valve #1 limit switches. 0=full closed, 1=full open, 10=in between}\n");
    printf("  valve 2 open calibrate {Run valve #2 from full closed to open and echo the total time in ms}\n\n");
    return 1;
  }

  if ((strcmp(argv[1],"1") != 0) && (strcmp(argv[1],"2") != 0)) {
    printf("\nInvalid valve number specified!\n\n");
    return 1;
  }

  if ((strcmp(argv[2],"open") != 0) && (strcmp(argv[2],"close") != 0) && (strcmp(argv[2],"status") != 0)) {
    printf("\nInvalid valve direction specified!\n\n");
    return 1;
  }

  wiringPiSetup();
  pinMode(VALVE1_OPEN,OUTPUT);
  pinMode(VALVE1_CLOSE,OUTPUT);
  pinMode(VALVE1_LIMIT_OPEN,INPUT);
  pinMode(VALVE1_LIMIT_CLOSE,INPUT);
  pinMode(VALVE2_OPEN,OUTPUT);
  pinMode(VALVE2_CLOSE,OUTPUT);
  pinMode(VALVE2_LIMIT_OPEN,INPUT);
  pinMode(VALVE2_LIMIT_CLOSE,INPUT);

  if (argc == 3) {
    // Valve full open or full close or status requested
    if (strcmp(argv[2],"open") == 0) {
      valveFullPosition(strtol(argv[1],&p,10),1);
    } else if (strcmp(argv[2],"close") == 0) {
      valveFullPosition(strtol(argv[1],&p,10),0);
    } else {
      valveStatus(strtol(argv[1],&p,10));
    }
  } else {
    if (strcmp(argv[3],"calibrate") == 0) {
      // Valve calibrate requested
      if (strcmp(argv[2],"open") == 0) {
        valveCalibrate(strtol(argv[1],&p,10),1);
      } else {
        valveCalibrate(strtol(argv[1],&p,10),0);
      }
    } else {
      // Valve pulse requested
      if (strtol(argv[3],&p,10) > 0) {
        if (strcmp(argv[2],"open") == 0) {
          valvePulse(strtol(argv[1],&p,10),1,strtol(argv[3],&p,10));
        } else {
          valvePulse(strtol(argv[1],&p,10),0,strtol(argv[3],&p,10));
        }
      } else {
        printf("\nInvalid valve duration time specified!\n\n");
        return 1;
      }
    }
  }
}
//------------------------------------------------------------------------------------------------
