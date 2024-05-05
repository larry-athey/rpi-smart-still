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
//
// The VL53L0X sensor is mounted to the side of the overflow cup of the parrot roughly even with
// the top of the overflow tube aiming upwards. A paper disc equal to the diameter of the overflow
// cup is attached to the top of the hydrometer, roughly even with the top of the label with about
// a 10mm gap above the 100% line. You may have to tip the sensor inward a bit so that the beam is
// always trained on the disc. Calibrate it with the RPis Smart Still Controller and you're ready.
//
// The disc can be made from a business card and have hardly any effect on the hydrometer, if even
// half a percent. Having the sensor that close to distillate isn't dangerous at all. It only runs
// on 3.3 volts and distillate isn't conductive, nor is distilled water. I've submerged one in 90%
// ethanol and distilled water while running and nothing happened, it never stopped working.
//
// Calibration is as simple as putting the hydrometer in the parrot, add water until the 100% mark
// is even with the top of the center tube, click the button in the RPi Smart Still Controller for
// the 100% calibration, and repeat for every 10% mark including the 0% mark. The settings will be
// stored in the flash memory and you won't have to do it again unless you replace your hydrometer.
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
Adafruit_VL53L0X Lidar = Adafruit_VL53L0X();
OneWire oneWire(ONE_WIRE);
DallasTemperature DT(&oneWire);
Preferences preferences;
//------------------------------------------------------------------------------------------------
void setup() {
  Serial.begin(9600);
  while (! Serial) delay(10);
  Serial.println("");
  DT.begin();
  preferences.begin("my-app",false);
  //if (! Lidar.begin()) {
  //  Serial.println("Failed to initialize VL53L0X");
  //  while(1);
  //}
  for (byte x = 0; x <= 99; x ++) FlowBuf[x] = 0;
  SerialCounter = millis();
  GetDivisions();
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
void GetDivisions() { // Stuff the Divisions array with saved values stored in flash memory
  Divisions[0] = preferences.getUInt("Div0",0);
  Divisions[1] = preferences.getUInt("Div1",0);
  Divisions[2] = preferences.getUInt("Div2",0);
  Divisions[3] = preferences.getUInt("Div3",0);
  Divisions[4] = preferences.getUInt("Div4",0);
  Divisions[5] = preferences.getUInt("Div5",0);
  Divisions[6] = preferences.getUInt("Div6",0);
  Divisions[7] = preferences.getUInt("Div7",0);
  Divisions[8] = preferences.getUInt("Div8",0);
  Divisions[9] = preferences.getUInt("Div9",0);
  Divisions[10] = preferences.getUInt("Div10",0);
}
//------------------------------------------------------------------------------------------------
void UpdateDivision(byte Slot) { // Update a flash memory slot for a specific Divisions array item
  char SlotName[5];
  if (Slot == 97) {
    Slot = 10;
  } else {
    Slot -= 48;
  }
  if ((Slot >= 0) && (Slot <= 10)) {
    sprintf(SlotName,"Div%i",Slot);
    preferences.putUInt(SlotName,Distance);
    for (byte x = 0; x <= 9; x ++) {
      digitalWrite(USER_LED,HIGH);
      delay(100);
      digitalWrite(USER_LED,LOW);
      delay(100);
    }
  }
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
      if (Serial.available()) { // Formatted as #0 = 0%, #1 = 10% .. #9 = 90%, #a = 100%
        Data = Serial.read();
        UpdateDivision(Data);
      }
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