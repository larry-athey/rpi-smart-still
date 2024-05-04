//------------------------------------------------------------------------------------------------
// Written by Larry Athey (https://panhandleponics.com) v1.0.1 released July 1, 2024
//
// If you have seen any of George Duncan's videos on YouTube from around 2020, you might remember
// an Arduino project of his called the "Talking Parrot Head". It involved a black PVC tube on top
// of a parrot with a VL53L0X sensor at the top that bounced the beam downward to a disc on top of
// a glass hydrometer. Depending on the distance, it would play WAV files on an SD card and speak
// the current ethanol ABV. Kind of bulky and made the parrot top heavy, but it certainly worked.
//
// While there was a window in the side near the float point, it still made the hydrometer hard to
// read visually. Yeah, I know that it was made for a blind guy. Even though I'm 50% blind, I want
// want to be able to see the hydrometer itself without the obstacle. You can just as easily shoot
// the beam upward to the bottom side of the disc on the hydrometer and turn that ABV reading into
// a digital signal that the RPi Smart Still Controller can use.
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
Adafruit_VL53L0X Lidar = Adafruit_VL53L0X();
//------------------------------------------------------------------------------------------------
void setup() {
  Serial.begin(9600);
  Serial.println("");
  DT.begin();
  //Lidar.begin();
  for (byte x = 0; x <= 10; x ++) Divisions[x] = 0;
  for (byte x = 0; x <= 99; x ++) FlowBuf[x] = 0;
  SerialCounter = millis();

  pinMode(FLOW_SENSOR,INPUT_PULLDOWN);
  pinMode(USER_LED,OUTPUT);
}
//------------------------------------------------------------------------------------------------
void TempUpdate() { // Update the distillate temperature value
  DT.requestTemperatures();
  TempC = DT.getTempCByIndex(0);
}
//------------------------------------------------------------------------------------------------
byte CalcEthanol() { // Convert the Distance millimeters to an ethanol ABV value
  float Tenth,Subtotal = 0,Total = 0;
  for (byte x = 0; x <= 10; x ++) {
    if (Divisions[x] > 0) {
      if (Divisions[x] == Distance) {
        return x * 10;
      } else {
        if ((x < 10) && (Distance > Divisions[x]) && (Distance < Divisions[x + 1])) {
          Tenth = (Divisions[x + 1] - Divisions[x]) / 10;
          for (byte y = 1; y <= 9; y ++) {
            Subtotal += Tenth;
            if (Divisions[x] + Subtotal >= Distance) {
              Total = (Divisions[x] * 10) + y;
              return Total;
            }
          }
        }
      }
    }
  }
  return 0;
}
//------------------------------------------------------------------------------------------------
void RebootUnit() { // Reboot the device, write to flash memory here before restarting if needed
  ESP.restart();
}
//------------------------------------------------------------------------------------------------
void loop() {
  //VL53L0X_RangingMeasurementData_t measure;
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

  // Check for serial data commands from the RPi Smart Still controller
  while (Serial.available()) {
    Data = Serial.read();
    if (Data == 33) { // Reboot the device if a "!" is received
      RebootUnit();
    } else if (Data == 35) { // Update a specific Divisions[x] slot if a "#" is received
      delay(500);
    }  
    Data = 0;
  }

  // Get the current reflector distance and convert it to an ethanol ABV value
  //Lidar.rangingTest(&measure,false);
  //if (measure.RangeStatus != 4) {
  //  Distance = measure.RangeMilliMeter;
  //  Ethanol  = CalcEthanol();
  //}

  // Build the data block to be sent to the RPi Smart Still Controller once every second
  if (CurrentTime - SerialCounter >= 1000) {
    digitalWrite(USER_LED,HIGH);
    TempUpdate();
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
    digitalWrite(USER_LED,LOW);
    SerialCounter = CurrentTime;
  }
  delay(100);
}
//------------------------------------------------------------------------------------------------