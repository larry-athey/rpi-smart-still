//---------------------------------------------------------------------------------------------
// DS18B20 Temperature Reader - Reads raw data from a GPIO pin without the need for /sys/bus/w1
// devices to be present. This is because the RPi clone market is so fractured that Armbian has
// pretty much given up on creating a dependable w1-gpio kernel overlay for any RPi clone. What
// people are calling an RPi clone is merely a clone at the physical appearance level. The GPIO
// functionality at the CPU level is a totally different ballgame, even though they're all ARM.
//
// This code was derived from https://github.com/andriadze/DS18B20WiringPi and cleaned up.
//
// So much for eliminating the WiringPi dependency in order to support RPi clones. [FaCePaLm]
//---------------------------------------------------------------------------------------------
// Compile command:
// gcc -Wall -o ds18b20 ds18b20.c -lwiringPi -lpthread
//---------------------------------------------------------------------------------------------
#define USE_GPIOMEM
//---------------------------------------------------------------------------------------------
#include <wiringPi.h>
#include <stddef.h>
#include <stdio.h>
#include <stdlib.h>
#include <fcntl.h>
#include <sys/mman.h>
#include <unistd.h>
#include <time.h>
#include <string.h>
#include <sched.h>
//---------------------------------------------------------------------------------------------
// This constant points to the WiringPi (not GPIO) pin connected to physical pin #7. This will
// vary from one RPi clone to another because WiringPi pins vary from one CPU maker to another.
#define DS18B20_PIN_NUMBER 7
//---------------------------------------------------------------------------------------------
#define DS18B20_SKIP_ROM         0xCC
#define DS18B20_CONVERT_T        0x44
#define DS18B20_MATCH_ROM        0x55
#define DS18B20_SEARCH_ROM       0XF0
#define DS18B20_READ_SCRATCHPAD  0xBE
#define DS18B20_WRITE_SCRATCHPAD 0x4E
#define DS18B20_COPY_SCRATCHPAD  0x48
#define DELAY1US DelayMicrosecondsNoSleep(1);

unsigned char ScratchPad[9];
double temperature;
int resolution;
unsigned short ArgResolution = 0;
unsigned short ArgScan = 0;
unsigned short ArgWaitTime = 750;

#define false 0
#define true 1
typedef int bool;
bool DEBUG = false;
//---------------------------------------------------------------------------------------------
void DelayMicrosecondsNoSleep(int delay_us) {
  long int start_time;
  long int time_difference;
  struct timespec gettime_now;

  clock_gettime(CLOCK_REALTIME, &gettime_now);
  start_time = gettime_now.tv_nsec; //Get nS value
  while (1) {
    clock_gettime(CLOCK_REALTIME,&gettime_now);
    time_difference = gettime_now.tv_nsec - start_time;
    if (time_difference < 0) time_difference += 1000000000; //(Rolls over every 1 second)
    if (time_difference > (delay_us * 1000)) //Delay for # nS
    break;
  }
}
//---------------------------------------------------------------------------------------------
int DoReset(void) {
  int loop;

  pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
  DelayMicrosecondsNoSleep(10);
  pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
  pinMode(DS18B20_PIN_NUMBER,OUTPUT); //OUT_GPIO(DS_PIN);
  digitalWrite(DS18B20_PIN_NUMBER,LOW); //GPIO_CLR=1<<DS_PIN;
  usleep(480);
  pinMode(DS18B20_PIN_NUMBER, INPUT); //INP_GPIO(DS_PIN);
  DelayMicrosecondsNoSleep(60);
  if (digitalRead(DS18B20_PIN_NUMBER)==0) {
    DelayMicrosecondsNoSleep(420);
    return 1;
  }
  return 0;
}
//---------------------------------------------------------------------------------------------
void WriteByte(unsigned char value) {
  unsigned char Mask=1;
  int loop;

  for (loop = 0; loop < 8; loop ++) {
    pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
    pinMode(DS18B20_PIN_NUMBER,OUTPUT); //OUT_GPIO(DS_PIN);
    digitalWrite(DS18B20_PIN_NUMBER,LOW); //GPIO_CLR= 1 <<DS_PIN;
    if ((value & Mask) != 0) {
      DELAY1US
      pinMode(DS18B20_PIN_NUMBER,INPUT); // INP_GPIO(DS_PIN);
      DelayMicrosecondsNoSleep(60);
    } else {
      DelayMicrosecondsNoSleep(60);
      pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
      DelayMicrosecondsNoSleep(1);
    }
    Mask *= 2;
    DelayMicrosecondsNoSleep(60);
  }
  usleep(100);
}
//---------------------------------------------------------------------------------------------
void WriteBit(unsigned char value) {
  pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
  pinMode(DS18B20_PIN_NUMBER,OUTPUT); //OUT_GPIO(DS_PIN);
  digitalWrite(DS18B20_PIN_NUMBER,LOW); //GPIO_CLR=1 <<DS_PIN;
  if (value) {
    DELAY1US
    pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
    DelayMicrosecondsNoSleep(60);
  } else {
    DelayMicrosecondsNoSleep(60);
    pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
    DelayMicrosecondsNoSleep(1);
  }
  DelayMicrosecondsNoSleep(60);
}
//---------------------------------------------------------------------------------------------
unsigned char ReadBit(void) {
  unsigned char rvalue = 0;

  pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
  pinMode(DS18B20_PIN_NUMBER,OUTPUT); //OUT_GPIO(DS_PIN);
  digitalWrite(DS18B20_PIN_NUMBER,LOW); //GPIO_CLR= 1 << DS_PIN;
  DELAY1US
  pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
  DelayMicrosecondsNoSleep(2);
  if (digitalRead(DS18B20_PIN_NUMBER) !=0 ) rvalue = 1;
  DelayMicrosecondsNoSleep(60);
  return rvalue;
}
//---------------------------------------------------------------------------------------------
unsigned char ReadByte(void) {
  unsigned char Mask = 1;
  int loop;
  unsigned  char data = 0;
  int loop2;

  for (loop = 0; loop < 8; loop ++) {
    pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
    pinMode(DS18B20_PIN_NUMBER,OUTPUT); //OUT_GPIO(DS_PIN);
    digitalWrite(DS18B20_PIN_NUMBER,LOW); //GPIO_CLR= 1<<DS_PIN;
    DELAY1US
    pinMode(DS18B20_PIN_NUMBER,INPUT); //INP_GPIO(DS_PIN);
    DelayMicrosecondsNoSleep(2);
    if (digitalRead(DS18B20_PIN_NUMBER) != 0) data |= Mask;
    Mask *= 2;
    DelayMicrosecondsNoSleep(60);
  }
  return data;
}
//---------------------------------------------------------------------------------------------
int ReadScratchPad(void) {
  int loop;

  if (DEBUG) printf("%s\n", "Reading scratch");
  WriteByte(DS18B20_READ_SCRATCHPAD);
  for (loop = 0; loop < 9; loop ++) {
    ScratchPad[loop]=ReadByte();
  }
}
//---------------------------------------------------------------------------------------------
unsigned char CalcCRC(unsigned char * data, unsigned char byteSize) {
  unsigned char shift_register = 0;
  unsigned char loop,loop2;
  char DataByte;

  for (loop = 0; loop < byteSize; loop ++) {
    DataByte = *(data + loop);
    for (loop2 = 0; loop2 < 8; loop2 ++) {
      if ((shift_register ^ DataByte)& 1) {
        shift_register = shift_register >> 1;
        shift_register ^=  0x8C;
      } else shift_register = shift_register >> 1;
      DataByte = DataByte >> 1;
    }
  }
  return shift_register;
}
//---------------------------------------------------------------------------------------------
char IDGetBit(unsigned long long *llvalue, char bit) {
  unsigned long long Mask = 1ULL << bit;
  return ((*llvalue & Mask) ? 1 : 0);
}
//---------------------------------------------------------------------------------------------
unsigned long long IDSetBit(unsigned long long *llvalue, char bit, unsigned char newValue) {
  unsigned long long Mask = 1ULL << bit;

  if ((bit >= 0) && (bit < 64)) {
    if (newValue==0) *llvalue &= ~Mask;
    else *llvalue |= Mask;
  }
  return *llvalue;
}
//---------------------------------------------------------------------------------------------
void SelectSensor(unsigned long long ID) {
  int BitIndex;
  char Bit;

  WriteByte(DS18B20_MATCH_ROM);
  for (BitIndex = 0; BitIndex < 64; BitIndex ++) WriteBit(IDGetBit(&ID,BitIndex));
}
//---------------------------------------------------------------------------------------------
int SearchSensor(unsigned long long * ID, int * LastBitChange) {
  int BitIndex;
  char Bit,NoBit;

  if (*LastBitChange <0) return 0;
  if (DEBUG) printf("%s\n","Searching sensor(s)");
  if (*LastBitChange < 64) {
    IDSetBit(ID,*LastBitChange,1);
    for (BitIndex = *LastBitChange + 1; BitIndex < 64; BitIndex ++) IDSetBit(ID,BitIndex,0);
  }
  *LastBitChange = -1;
  if (! DoReset()) return -1;
  WriteByte(DS18B20_SEARCH_ROM);
  for (BitIndex = 0; BitIndex < 64; BitIndex ++) {
    NoBit = ReadBit();
    Bit = ReadBit();
    if (Bit && NoBit) return -2;
    if (! Bit && ! NoBit) {
      if (IDGetBit(ID,BitIndex)) {
        WriteBit(1);
      } else {
        *LastBitChange = BitIndex;
        WriteBit(0);
      }
    } else if (! Bit) {
      WriteBit(1);
      IDSetBit(ID,BitIndex,1);
    } else {
      WriteBit(0);
      IDSetBit(ID,BitIndex,0);
    }
  }
  return 1;
}
//---------------------------------------------------------------------------------------------
int ReadSensor(unsigned long long ID) {
  int RetryCount;
  int loop;
  unsigned char CRCByte;
  union {
    short SHORT;
    unsigned char CHAR[2];
  } IntTemp;
  time_t t = time(NULL);
  struct tm tm = *localtime(&t);

  temperature=-9999.9;
  if (DEBUG) printf("%s\n", "Reading sensor");
  for (RetryCount = 0; RetryCount < 10; RetryCount ++) {
    if (!DoReset()) continue;
    SelectSensor(ID);
    if (! ReadScratchPad()) continue;
    CRCByte= CalcCRC(ScratchPad,8);
    if (CRCByte != ScratchPad[8]) continue;
    resolution = 0;
    switch (ScratchPad[4]) {
      case 0x1f: resolution = 9; break;
      case 0x3f: resolution = 10; break;
      case 0x5f: resolution = 11; break;
      case 0x7f: resolution = 12; break;
    }
    if (resolution == 0) continue;
    IntTemp.CHAR[0] = ScratchPad[0];
    IntTemp.CHAR[1] = ScratchPad[1];
    temperature = 0.0625 * (double) IntTemp.SHORT;
    ID &= 0x00FFFFFFFFFFFFFFULL;
    if (DEBUG) {
      printf("%02llX-%012llX : ",ID & 0xFFULL, ID >>8);
      printf("%02d bits  Temperature: %6.2f +/- %4.2f Celsius\n", resolution ,temperature, 0.0625 * (double)  (1<<(12 - resolution)));
    } else {
      printf("%02llX-%012llX:",ID & 0xFFULL,ID >> 8);
      printf("%4.2f\n",temperature);
    }
    return 1;
  }
  return 0;
}
//---------------------------------------------------------------------------------------------
int GlobalStartConversion(void) {
  int retry = 0;
  int maxloop;

  while (retry < 10) {
    if (! DoReset()) {
      usleep(10000);
    } else {
      WriteByte(DS18B20_SKIP_ROM);
      WriteByte(DS18B20_CONVERT_T);
      maxloop = 0;
#define USE_CONSTANT_DELAY
#ifdef USE_CONSTANT_DELAY
      usleep(ArgWaitTime * 1000);
      return 1;
#else
      while (! ReadBit()) {
        maxloop ++;
        if (maxloop > 100000) break;
      }
      if (maxloop <= 100000) return 1;
#endif
    }
    retry ++;
  }
  return 0;
}
//---------------------------------------------------------------------------------------------
void WriteScratchPad(unsigned char TH, unsigned char TL, unsigned char config) {
  DoReset();
  usleep(10);
  WriteByte(DS18B20_SKIP_ROM);
  WriteByte(DS18B20_WRITE_SCRATCHPAD);
  WriteByte(TH);
  WriteByte(TL);
  WriteByte(config);
}
//---------------------------------------------------------------------------------------------
void CopyScratchPad(void) {
  DoReset();
  usleep(1000);
  WriteByte(DS18B20_SKIP_ROM);
  WriteByte(DS18B20_COPY_SCRATCHPAD);
  usleep(100000);
}
//---------------------------------------------------------------------------------------------
void ChangeSensorsResolution(int resolution) {
  int config = 0;

  switch (resolution) {
    case 9:  config = 0x1f; break;
    case 10: config = 0x3f; break;
    case 11: config = 0x5f; break;
    default: config = 0x7f; break;
  }
  WriteScratchPad(0xff,0xff,config);
  usleep(1000);
  CopyScratchPad();
}
//---------------------------------------------------------------------------------------------
void ScanForSensor(void) {
  unsigned long long ID=0ULL;
  int NextBit=64;
  int _NextBit;
  int rcode;
  int retry=0;
  unsigned long long _ID;
  unsigned char _ID_CRC;
  unsigned char _ID_Calc_CRC;
  unsigned char _ID_Family;

  if (DEBUG) printf("%s\n","Scan for sensors");
  while (retry < 10) {
    _ID = ID;
    _NextBit = NextBit;
    rcode = SearchSensor(&_ID,&_NextBit);
    if (rcode == 1) {
      _ID_CRC = (unsigned char) (_ID >> 56);
      _ID_Calc_CRC = CalcCRC((unsigned char *) &_ID,7);
      if (_ID_CRC == _ID_Calc_CRC) {
        if (ReadSensor(_ID)) {
          ID = _ID;
          NextBit = _NextBit;
          retry = 0;
        } else retry = 0;
      } else retry ++;
    } else if (rcode == 0) {
      break;
    } else retry ++;
  }
}
//---------------------------------------------------------------------------------------------
void set_max_priority(void) {
  struct sched_param sched;

  memset(&sched,0,sizeof(sched));
  sched.sched_priority = sched_get_priority_max(SCHED_FIFO);
  sched_setscheduler(0,SCHED_FIFO,&sched);
}
//---------------------------------------------------------------------------------------------
void set_default_priority(void) {
  struct sched_param sched;

  memset(&sched,0,sizeof(sched));
  sched.sched_priority = 0;
  sched_setscheduler(0,SCHED_OTHER,&sched);
}
//---------------------------------------------------------------------------------------------
int main(int argc, char **argv) {
  int loop;
  int Flag = 0;

  wiringPiSetup();
  pinMode(DS18B20_PIN_NUMBER,INPUT);
  Flag = 0;

  for (loop = 0; loop < 100; loop ++) {
    usleep(1000);
    if (digitalRead(DS18B20_PIN_NUMBER) != 0) {
      Flag = 1;
      break;
    }
  }

  if (Flag == 0) {
    printf("*** Error - Unable to detect Logic level 1. No pull-up? : 0\n");
    exit(-1);
  }

  if (GlobalStartConversion() == 0) {
    printf("*** Error - Unable to detect any DS18B20 sensors! : 0\n");
    exit(-2);
  }

  set_max_priority();
  ScanForSensor();
  set_default_priority();
  return 0;
}
//---------------------------------------------------------------------------------------------
