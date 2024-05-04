//------------------------------------------------------------------------------------------------
// Written by Larry Athey (https://panhandleponics.com) v1.0.1 released July 1, 2024
//
//------------------------------------------------------------------------------------------------
#include "Adafruit_VL53L0X.h"  // VL53L0X LIDAR sensor library by Adafruit
#include "OneWire.h"           // OneWire Network communications library
#include "DallasTemperature.h" // Dallas Temperature DS18B20 temperature sensor library
//------------------------------------------------------------------------------------------------
#define ONE_WIRE 15
#define FLOW_SENSOR 39
#define I2C_SCL 22
#define I2C_SDA 21
#define USER_LED 2
//------------------------------------------------------------------------------------------------
char Uptime[10];    // Global placeholder for the formatted uptime reading
byte Ethanol = 0;   // Global placeholder for ethanol percentage reading
float TempC = 0;    // Global placeholder for ethanol temperature reading
long LoopCounter;   // Timekeeper for distance measurement updates
long SerialCounter; // Timekeeper for serial data output updates
uint Divisions[11]; // Measurements for the hydrometer's 10% divisions
byte FlowBuf[100];  // Buffer for calculating the flow rate percentage
//------------------------------------------------------------------------------------------------
OneWire oneWire(ONE_WIRE);
DallasTemperature DT(&oneWire);
//------------------------------------------------------------------------------------------------
void setup() {
  Serial.begin(9600);
  Serial.println("");
  DT.begin();
  for (byte x = 0; x <= 99; x ++) FlowBuf[x] = 0;
  LoopCounter = millis();
  SerialCounter = LoopCounter;

  pinMode(FLOW_SENSOR,INPUT_PULLDOWN);
  pinMode(USER_LED,OUTPUT);
  digitalWrite(USER_LED,HIGH);
}
//------------------------------------------------------------------------------------------------
void TempUpdate() { // Update the distillate temperature
  DT.requestTemperatures();
  TempC = DT.getTempCByIndex(0);
}
//------------------------------------------------------------------------------------------------
byte CalcEthanol(uint Distance) { // Convert the distance to ethanol ABV

}
//------------------------------------------------------------------------------------------------
void RebootUnit() {
  ESP.restart(); // For Arduinos, see https://arduinogetstarted.com/faq/how-to-reset-arduino-by-programming
}
//------------------------------------------------------------------------------------------------
void loop() {
  long CurrentTime = millis();
  if (CurrentTime > 4200000000) RebootUnit();
  unsigned long allSeconds = CurrentTime / 1000;
  int runHours = allSeconds / 3600;
  int secsRemaining = allSeconds % 3600;
  int runMinutes = secsRemaining / 60;
  int runSeconds = secsRemaining % 60;
  sprintf(Uptime,"%02d:%02d:%02d",runHours,runMinutes,runSeconds);
  for (byte x = 0; x <= 98; x ++) FlowBuf[x] = FlowBuf[x + 1];
  FlowBuf[99] = digitalRead(FLOW_SENSOR);
}
//------------------------------------------------------------------------------------------------
