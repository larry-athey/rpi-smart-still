//------------------------------------------------------------------------------------------------
// Written by Larry Athey (https://panhandleponics.com) v1.0.1 released July 1, 2024
//
//------------------------------------------------------------------------------------------------
#include "Adafruit_VL53L0X.h"  // VL53L0X LIDAR sensor library by Adafruit
#include "OneWire.h"           // OneWire Network communications library
#include "DallasTemperature.h" // Dallas Temperature DS18B20 temperature sensor library
#include "Preferences.h"       // ESP32 Flash memory read/write library
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
uint Distance = 0;  // Global placeholder for the LIDAR distance measurement
uint Divisions[11]; // Measurements for the hydrometer's 10% divisions
long SerialCounter; // Timekeeper for serial data output updates
byte FlowBuf[100];  // Buffer for calculating the flow rate percentage
//------------------------------------------------------------------------------------------------
OneWire oneWire(ONE_WIRE);
DallasTemperature DT(&oneWire);
Adafruit_VL53L0X lox = Adafruit_VL53L0X();
//------------------------------------------------------------------------------------------------
void setup() {
  Serial.begin(9600);
  Serial.println("");
  DT.begin();
  for (byte x = 0; x <= 99; x ++) FlowBuf[x] = 0;
  SerialCounter = millis();

  pinMode(FLOW_SENSOR,INPUT_PULLDOWN);
  pinMode(USER_LED,OUTPUT);
}
//------------------------------------------------------------------------------------------------
void TempUpdate() { // Update the distillate temperature
  DT.requestTemperatures();
  TempC = DT.getTempCByIndex(0);
}
//------------------------------------------------------------------------------------------------
byte CalcEthanol() { // Convert the Distance millimeters to ethanol ABV

}
//------------------------------------------------------------------------------------------------
void RebootUnit() { // Reboot the device, write to flash memory here before restarting
  ESP.restart();
}
//------------------------------------------------------------------------------------------------
void loop() {
  byte Data = 0;
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

  // Communications to my Raspberry PI based still monitor/controller uses 9600 baud serial data
  if (CurrentTime - SerialCounter >= 1000) {
    digitalWrite(USER_LED,HIGH);
    for (byte x = 0; x <= 99; x ++) {
      if (FlowBuf[x] > 0) Data ++;
    }
    Serial.print("Uptime: ");
    Serial.println(Uptime);
    Serial.print("Distance: ");
    Serial.println(Distance);
    Serial.print("Flow: ");
    Serial.println(Data);
    Serial.print("Ethanol: ");
    Serial.println(Ethanol);
    Serial.print("TempC: ");
    Serial.println(TempC,1);
    Serial.println("#"); // Pound signs mark the start and end of data blocks to the Raspberry PI
    delay(100);
    digitalWrite(USER_LED,LOW);
    SerialCounter = CurrentTime;
  } else {
    delay(100);
  }
}
//------------------------------------------------------------------------------------------------
