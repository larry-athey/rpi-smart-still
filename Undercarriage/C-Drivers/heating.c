//------------------------------------------------------------------------------------------------
// RPi-Smart-Still | (CopyLeft) 2023-Present | Larry Athey (https://panhandleponics.com)
//
// Heating Stepper Motor Driver v1.0.1, originally released July 14, 2023
//
// This program was written before the implementation of /usr/share/rpi-smart-sill/config.ini and
// uses WiringPi port numbers rather than Broadcom GPIO numbers. You'll need to run `gpio readall`
// to obtain the WiringPi port numbers that match the physical pins of the GPIO bus on your board.
// Edit the constants below as necessary, compile, and replace the python script with the compiled
// executable. This program does **not** read the port definitions in your config.ini file.
//
//------------------------------------------------------------------------------------------------
// Compile command:
// gcc -Wall -o heating heating.c -lwiringPi -lpthread
//------------------------------------------------------------------------------------------------
#include "stdio.h"
#include "stdlib.h"
#include "string.h"
#include "sys/time.h"
#include "wiringPi.h"
//------------------------------------------------------------------------------------------------
#define STEPPER_ENABLE 23
#define STEPPER_PULSE 26
#define STEPPER_DIR 14
#define STEPPER_MS 5 // Nema 17 motor is 200 steps per revolution, 1000ms divided by 200 is 5ms
//------------------------------------------------------------------------------------------------
void StepperEnable(int Status) {
  //printf("StepperEnable(Status=%d)\n",Status);
  //return;

  if (Status == 1) {
    digitalWrite(STEPPER_ENABLE,HIGH);
  } else {
    digitalWrite(STEPPER_ENABLE,LOW);
  }
}
//------------------------------------------------------------------------------------------------
void StepperPulse(int Direction,int Steps) {
  //printf("StepperPulse(Direction=%d,Steps=%d)\n",Direction,Steps);
  //return;

  if (Direction == 1) {
    digitalWrite(STEPPER_DIR,HIGH);
  } else {
    digitalWrite(STEPPER_DIR,LOW);
  }
  for (int x = 1; x <= Steps; x ++) {
    digitalWrite(STEPPER_PULSE,HIGH);
    delay(STEPPER_MS);
    digitalWrite(STEPPER_PULSE,LOW);
    delay(STEPPER_MS);
  }
}
//------------------------------------------------------------------------------------------------
int main(int argc,char **argv) {
  char *p;

  //for (int i = 0; i < argc; ++i) {
  //  printf("argv[%d]: %s\n", i, argv[i]);
  //}

  if ((argc == 1) || (argc > 3)) {
    printf("\nRPi-Smart-Still Heating Stepper Motor Driver v1.0.1 released July 14, 2023\n\n");
    printf("Usage:\n");
    printf("  heating enable {Enables the stepper motor controller and locks the motor}\n");
    printf("  heating disable {Disables the stepper motor controller and unlocks the motor}\n");
    printf("  heating [cw or ccw] [steps] {Rotates the stepper up or down X number of steps}\n");
    printf("  heating cw 100 {Turns your heating dial/valve clockwise 100 steps}\n\n");
    return 1;
  }

  if ((strcmp(argv[1],"cw") != 0) && (strcmp(argv[1],"ccw") != 0) && (strcmp(argv[1],"enable") != 0) && (strcmp(argv[1],"disable") != 0)) {
    printf("\nInvalid stepper motor command!\n\n");
    return 1;
  }

  if ((argc == 3) && (strtol(argv[2],&p,10) <= 0)) {
    printf("\nInvalid stepper motor step value specified!\n\n");
    return 1;
  }

  wiringPiSetup();
  pinMode(STEPPER_ENABLE,OUTPUT);
  pinMode(STEPPER_PULSE,OUTPUT);
  pinMode(STEPPER_DIR,OUTPUT);

  if (argc == 2) {
    // Heating stepper motor enable or disable requested
    if (strcmp(argv[1],"enable") == 0) {
      StepperEnable(1);
    } else if (strcmp(argv[1],"disable") == 0) {
      StepperEnable(0);
    }
  } else if (argc == 3) {
    if (strcmp(argv[1],"cw") == 0) {
      // Heating stepper motor pulse up requested
      StepperEnable(1);
      StepperPulse(1,strtol(argv[2],&p,10));
    } else if (strcmp(argv[1],"ccw") == 0) {
      // Heating stepper motor pulse down requested
      StepperEnable(1);
      StepperPulse(0,strtol(argv[2],&p,10));
    }
  }
}
//------------------------------------------------------------------------------------------------
