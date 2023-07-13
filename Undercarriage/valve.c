//------------------------------------------------------------------------------------------------
// RPI-Smart-Still Cooling Valve Driver
//
// While the rest of the undercarriage runs on shell scripts the need for millisecond timing in
// valve motor control requires that a separate compiled binary be used instead. The valves can
// fully open or close in 6 to 8 seconds, so the "gpio" and "sleep" commands can't be used here
// due to the amount of timing and positioning error that method would introduce.
//
// The valves used in this project are not stepper motor controlled, they are merely motorized
// ball valves with upper and lower limit switches. Calibration is a matter of timing them from
// full closed to full open and counting the elapsed milliseconds. It's best that you also time
// the valve in the other direction as well and then use an average of the two times. This test
// should also be performed under load from the input water pressure.
//
// NOTE: In the case of the dephleg valve, you need to remove the temperature sensor and plug
// the hole during this test. Otherwise, you will likely end up with water all over the place
// because this will likely blow the temperature sensor out of its receiver. You can eliminate
// the need for this by permanently plugging the receiver and running the water the opposite
// direction and using a 1/4" barbed Tee at the top to hold the sensor. Then lock your sensor
// into the Tee with heat shrink tubing so that it can't blow out.
//------------------------------------------------------------------------------------------------
#include "stdio.h"
#include "stdlib.h"
#include "string.h"
#include "sys/time.h"
#include "wiringPi.h"
//------------------------------------------------------------------------------------------------
#define VALVE1_OPEN 25 // Valve 1 is the condenser valve
#define VALVE1_CLOSE 24
#define VALVE1_LIMIT_OPEN 23
#define VALVE1_LIMIT_CLOSE 22
#define VALVE2_OPEN 21 // Valve 2 is the dephleg valve
#define VALVE2_CLOSE 20
#define VALVE2_LIMIT_OPEN 19
#define VALVE2_LIMIT_CLOSE 18
//------------------------------------------------------------------------------------------------
void valveFullPosition(int WhichOne,int Direction) {
  //printf("valveFullPosition(WhichOne=%d,Direction=%d)\n",WhichOne,Direction);
  //return;

  if (WhichOne == 1) {
    if (Direction == 0) {
      digitalWrite(VALVE1_CLOSE,HIGH);
      while (digitalRead(VALVE1_LIMIT_CLOSE) == 1) delay(100);
      digitalWrite(VALVE1_CLOSE,LOW);
    } else {
      digitalWrite(VALVE1_OPEN,HIGH);
      while (digitalRead(VALVE1_LIMIT_OPEN) == 1) delay(100);
      digitalWrite(VALVE1_OPEN,LOW);
    }
  } else {
    if (Direction == 0) {
      digitalWrite(VALVE2_CLOSE,HIGH);
      while (digitalRead(VALVE2_LIMIT_CLOSE) == 1) delay(100);
      digitalWrite(VALVE2_CLOSE,LOW);
    } else {
      digitalWrite(VALVE2_OPEN,HIGH);
      while (digitalRead(VALVE2_LIMIT_OPEN) == 1) delay(100);
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
    if (digitalRead(VALVE1_LIMIT_OPEN) == 1) {
      printf("1\n");
    } else if (digitalRead(VALVE1_LIMIT_CLOSE) == 1) {
      printf("0\n");
    } else {
      printf("10\n");
    }
  } else {
    if (digitalRead(VALVE2_LIMIT_OPEN) == 1) {
      printf("1\n");
    } else if (digitalRead(VALVE2_LIMIT_CLOSE) == 1) {
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
    printf("\nRPI-Smart-Still Cooling Valve Driver\n\n");
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

  wiringPiSetupGpio();
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
