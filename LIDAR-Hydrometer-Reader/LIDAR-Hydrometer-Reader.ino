//------------------------------------------------------------------------------------------------
// RPi Smart Still Controller | (CopyLeft) 2024-Present | Larry Athey (https://panhandleponics.com)
// Bird Brain v1.2.1 - LIDAR Hydrometer Reader and Parrot Flow Monitor - Released November 3, 2024
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
// cup is attached to the top of the hydrometer, about 20mm above the 100% line. The sensor mount
// that I've provided a 3D model for, places your sensor at the perfect angle so that it's sloped
// inward but doesn't hit the glass and cause erroneous readings. Calibrate it with the RPis Smart
// Still Controller and you're off to the races.
//
// The disc can be made out of construction paper and have hardly any effect on the hydrometer, if
// even .5 percent. Having the sensor that close to distillate isn't dangerous at all. It only runs
// on 3.3 volts and distillate isn't conductive, nor is distilled water. I've submerged one in 90%
// ethanol and distilled water while running and nothing happened, it never even stopped working.
//
// Calibration is as simple as putting the hydrometer in the parrot, add water until the 100% mark
// is even with the top of the center tube, click the button in the RPi Smart Still Controller for
// the 100% calibration, and repeat for every 10% mark including the 0% mark. The settings will be
// stored in the flash memory and you won't have to do it again unless you change your hydrometer.
//
// This device also utilizes a DS18B20 temperature sensor in order to tell the mart still system to
// turn up the condenser flow if the distillate temperature is too hot for the hydrometer to read
// correctly and prevent a fire hazard. Hot distillate means that you're not condensing all of the
// vapor back to a liquid, so you're actually losing product if it's too hot.
//
// The new flow sensor is a custom device designed by James (The Doc) from Ireland and determines
// the flow rate by the height of ethanol in a vessel and reading the electrical capacitance from
// two copper plates in the ethanol. The higher the flow rate, the higher the ethanol level, the
// higher the capacitance. A perforated overflow tube controls the ethanol level in the vessel.
//------------------------------------------------------------------------------------------------
#include "Adafruit_VL53L0X.h"  // VL53L0X LIDAR sensor library by Adafruit
#include "OneWire.h"           // OneWire Network communications library
#include "DallasTemperature.h" // Dallas Temperature DS18B20 temperature sensor library
#include "Preferences.h"       // ESP32 Flash memory read/write library
//------------------------------------------------------------------------------------------------
#define ONE_WIRE 15            // 1-Wire network pin for the DS18B20 temperature sensor
#define I2C_SCL 22             // I2C clock pin
#define I2C_SDA 21             // I2C data pin
#define USER_LED 2             // Blue or Neopixel LED on the ESP32 board
#define SENSE_PIN 39           // Capacitive flow sensor analog input pin
#define CHARGE_PIN 36          // Capacitive flow sensor charging output pin
//------------------------------------------------------------------------------------------------
char Uptime[10];               // Global placeholder for the formatted uptime reading
byte Ethanol = 0;              // Global placeholder for ethanol percentage reading
float TempC = 0;               // Global placeholder for ethanol temperature reading
uint Distance = 0;             // Global placeholder for the LIDAR distance measurement
uint Divisions[11];            // Measurements for the hydrometer's 10% divisions
long SerialCounter;            // Timekeeper for serial data output updates
byte EthanolBuf[10];           // Buffer for smoothing out ethanol readings
byte FlowBuf[10];              // Buffer for smoothing out flow sensor readings
//------------------------------------------------------------------------------------------------
// Constants and variables here are for the capacitance reading functions
const byte chargeTime_us = 84;
const byte dischargeTime_ms = 40;
const byte numMeasurements = 40;
const uint16_t MAX_ADC_VALUE = 4095;
const float resistor_mohm = 2.035;
const float Vref = 3.3;

float capacitance_pf = 0;
float capVoltage = 0;

struct doubleLong {
  long charged_value;
  long discharged_value;
};
//------------------------------------------------------------------------------------------------
Adafruit_VL53L0X Lidar = Adafruit_VL53L0X();
OneWire oneWire(ONE_WIRE);
DallasTemperature DT(&oneWire);
Preferences preferences;
//------------------------------------------------------------------------------------------------
void setup() {
  Serial.begin(9600);
  Serial2.begin(115200);
  Serial2.setDebugOutput(true); // Send ESP32 debug data to an ignored serial port
  Serial.setDebugOutput(false); // Turn off ESP32 debug data on the RPi output port
  while ((! Serial) or (! Serial2)) delay(10);
  Serial.println("");

  DT.begin();
  if (! Lidar.begin(VL53L0X_I2C_ADDR,false,&Wire,Lidar.VL53L0X_SENSE_HIGH_ACCURACY)) {
    Serial.println("Failed to initialize VL53L0X");
    while(true);
  }

  // Check the flash memory to see if this is a new ESP32 and stuff it with default values if so
  boolean NewChip = true;
  GetDivisions();
  for (byte x = 0; x <= 10; x ++) {
    if (Divisions[x] > 0) NewChip = false;
  }
  if (NewChip) {
    ResetDivisions();
    GetDivisions();
  }

  pinMode(SENSE_PIN,INPUT);
  pinMode(CHARGE_PIN,OUTPUT);
  digitalWrite(CHARGE_PIN,LOW);
  delay(dischargeTime_ms);

  pinMode(USER_LED,OUTPUT);
  SerialCounter = millis();

  for (byte x = 0; x <= 9; x ++) FlowBuf[x] = 0;
  for (byte x = 0; x <= 9; x ++) EthanolBuf[x] = 0;
}
//------------------------------------------------------------------------------------------------
void TempUpdate() { // Update the distillate temperature value
  DT.requestTemperatures();
  TempC = DT.getTempCByIndex(0);
}
//------------------------------------------------------------------------------------------------
byte CalcEthanol() { // Convert the Distance millimeters to an ethanol ABV value
  float Tenth,TotalDivs = 0;
  byte ABV;
  for (byte x = 10; x >= 0; x --) {
    if (Divisions[x] == Distance) {
      return x * 10;
    } else {
      if ((x > 0) && (Distance > Divisions[x]) && (Distance < Divisions[x - 1])) {
        Tenth = (Divisions[x - 1] - Divisions[x]) * 0.1;
        for (byte y = 1; y <= 9; y ++) {
          TotalDivs += Tenth;
          if (Divisions[x - 1] - round(TotalDivs) <= Distance) {
            ABV = ((x - 1) * 10) + y;
            return ABV;
          }
        }
      }
    }
    if (x == 0) return 0; // Goofy nature of for|next loop ending values
  }
  return 0;
}
//------------------------------------------------------------------------------------------------
void GetDivisions() { // Stuff the Divisions array with saved values stored in flash memory
  preferences.begin("prefs",true);
  Divisions[0] = preferences.getUInt("div0",0);
  Divisions[1] = preferences.getUInt("div1",0);
  Divisions[2] = preferences.getUInt("div2",0);
  Divisions[3] = preferences.getUInt("div3",0);
  Divisions[4] = preferences.getUInt("div4",0);
  Divisions[5] = preferences.getUInt("div5",0);
  Divisions[6] = preferences.getUInt("div6",0);
  Divisions[7] = preferences.getUInt("div7",0);
  Divisions[8] = preferences.getUInt("div8",0);
  Divisions[9] = preferences.getUInt("div9",0);
  Divisions[10] = preferences.getUInt("div10",0);
  preferences.end();
  for (byte x = 0; x <= 10; x ++) {
    Serial.print("div");
    Serial.print(x);
    Serial.print(": ");
    Serial.println(Divisions[x]);
  }
  Serial.println("#");
}
//------------------------------------------------------------------------------------------------
void UpdateDivision(byte Slot) { // Update a flash memory slot for a specific Divisions array item
  char SlotName[6];
  if (Slot == 97) {
    Slot = 10;
  } else {
    Slot -= 48;
  }
  if ((Slot >= 0) && (Slot <= 10)) {
    preferences.begin("prefs",false);
    sprintf(SlotName,"div%u",Slot);
    preferences.putUInt(SlotName,Distance);
    preferences.end();
    GetDivisions();
    for (byte x = 0; x <= 9; x ++) {
      digitalWrite(USER_LED,HIGH);
      delay(100);
      digitalWrite(USER_LED,LOW);
      delay(100);
    }
  }
}
//------------------------------------------------------------------------------------------------
void ResetDivisions() { // Restore all of the default reflector distance values
  // The measurements below are for this hydrometer -> https://www.amazon.com/dp/B013S1VAM4
  // If you have a different hydrometer, you should measure the scale and add 20mm to each
  // division and mark 20mm above the 100% line so you know where the reflector needs to be
  preferences.begin("prefs",false);
  preferences.putUInt("div0",132); // 0%
  preferences.putUInt("div1",126); // 10%
  preferences.putUInt("div2",122); // 20%
  preferences.putUInt("div3",117); // 30%
  preferences.putUInt("div4",110); // 40%
  preferences.putUInt("div5",101); // 50%
  preferences.putUInt("div6",90);  // 60%
  preferences.putUInt("div7",78);  // 70%
  preferences.putUInt("div8",63);  // 80%
  preferences.putUInt("div9",45);  // 90%
  preferences.putUInt("div10",20); // 100%
  preferences.end();
  for (byte x = 0; x <= 9; x ++) {
    digitalWrite(USER_LED,HIGH);
    delay(100);
    digitalWrite(USER_LED,LOW);
    delay(100);
  }
}
//------------------------------------------------------------------------------------------------
doubleLong measureADC(int num_measurements,byte charge_pin,byte sense_pin,byte charge_time_us,byte discharge_time_ms) { // Read the ADC
  long charged_val = 0;
  long discharged_val = 0;
 
  for (int i = 0; i < num_measurements; i ++) {
    digitalWrite(charge_pin,HIGH);
    delayMicroseconds(charge_time_us - 54);
    charged_val += analogRead(sense_pin);
    digitalWrite(charge_pin,LOW);
    delay(discharge_time_ms);
    discharged_val += analogRead(sense_pin);
  }
  Serial.flush();
  Serial.println("\n!"); // Tell the Raspberry Pi to purge any serial data prior to this
  return {charged_val /= num_measurements,discharged_val /= num_measurements};
}
//------------------------------------------------------------------------------------------------
float getCapacitance() { // Read the flow sensor, this can take up to 4 seconds to complete
  doubleLong ADCvalues = measureADC(numMeasurements,CHARGE_PIN,SENSE_PIN,chargeTime_us,dischargeTime_ms);
  capVoltage = (Vref * ADCvalues.charged_value) / (float)MAX_ADC_VALUE;
  capacitance_pf = -1.0 * ((chargeTime_us) / resistor_mohm ) / log(1 - (capVoltage / Vref));
  if (capacitance_pf > 0.0) capacitance_pf -= 9.0;
  return capacitance_pf;
}
//------------------------------------------------------------------------------------------------
void RebootUnit() { // Reboot the device, write to flash memory here before restarting if needed
  ESP.restart();
}
//------------------------------------------------------------------------------------------------
void loop() {
  VL53L0X_RangingMeasurementData_t measure;
  byte Data = 0;
  float Flow = 0,FlowTotal = 0;
  uint EthanolAvg = 0;
  long CurrentTime = millis();
  if (CurrentTime > 4200000000) {
    RebootUnit();
  } else if (CurrentTime < 1000) {
     // Purge VL53L0X of any garbage readings after a reboot or power cycle
     for (byte x = 0; x <= 9; x ++) {
       Lidar.rangingTest(&measure,false);
       delay(100);
     }
  }
  unsigned long allSeconds = CurrentTime / 1000;
  int runHours = allSeconds / 3600;
  int secsRemaining = allSeconds % 3600;
  int runMinutes = secsRemaining / 60;
  int runSeconds = secsRemaining % 60;
  sprintf(Uptime,"%02u:%02u:%02u",runHours,runMinutes,runSeconds);

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
    } else if (Data == 42) { // Restore default Divisions[x] values if a "*" is received
      ResetDivisions();
      RebootUnit();
    }  
  }

  // Build the data block to be sent to the RPi Smart Still Controller once every second
  if (CurrentTime - SerialCounter >= 1000) {
    // Get the current distillate temperature
    TempUpdate();

    // Get the current reflector distance and convert it to an ethanol ABV value
    Lidar.rangingTest(&measure,false);
    if (measure.RangeStatus != 4) {
      Distance = measure.RangeMilliMeter;
      for (byte x = 0; x <= 8; x ++) EthanolBuf[x] = EthanolBuf[x + 1];
      EthanolBuf[9] = CalcEthanol();
      for (byte x = 0; x <= 9; x ++) EthanolAvg += EthanolBuf[x];
      Ethanol = EthanolAvg * 0.1;
    }

    // Get the current distillate flow rate (capacitance of the flow sensor plates)
    // RPi Smart Still Controller ignores this until Ethanol value is greater than zero
    for (byte x = 0; x <= 8; x ++) FlowBuf[x] = FlowBuf[x + 1];
    Flow = getCapacitance();
    Flow -= 300;
    if (Flow < 0) Flow = 0;
    FlowBuf[9] = round((Flow / 700) * 100);
    if (FlowBuf[9] > 100 FlowBuf[9] = 100;
    for (byte x = 0; x <= 9; x ++) FlowTotal += FlowBuf[x];
    FlowTotal *= 0.01;

    digitalWrite(USER_LED,HIGH);
    Serial.print("Uptime: "); Serial.println(Uptime);
    Serial.print("Distance: "); Serial.println(Distance);
    Serial.print("Flow: "); Serial.println(FlowTotal);
    Serial.print("Ethanol: "); Serial.println(Ethanol);
    Serial.print("TempC: "); Serial.println(TempC,1);
    Serial.println("#"); // Pound sign marks the end of a data block to the Raspberry PI
    Serial.flush();
    delay(250);
    digitalWrite(USER_LED,LOW);
    SerialCounter = CurrentTime;
  }
}
//------------------------------------------------------------------------------------------------
/*
// Create & run a new sketch with the following code to fully erase the flash memory of an ESP32

#include <nvs_flash.h>

void setup() {
  nvs_flash_erase(); // erase the NVS partition and...
  nvs_flash_init();  // initialize the NVS partition.
  while(true);
}

void loop() {

}
*/
//------------------------------------------------------------------------------------------------
