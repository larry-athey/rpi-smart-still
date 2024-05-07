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
// a 9 mm gap above the 100% line. You may have to tip the sensor inward a bit so that the beam is
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
//
// This device also utilizes the flow sensor and DS18B20 temperature sensor since features of the
// RPi Smart Still Controller depend on them. So if the Load Cell Hydrometer seems to be far more
// complicated than what you want to deal with, this should be a viable alternative and not leave
// you short on features. I use the https://www.amazon.com/dp/B07RF57QF8 flow sensor connected to
// the output spout of my parrot with silicone tubing and the temperature sensor on the condenser
// output plugged into the side a 3/8" stainless steel barbed tee. So my parrot kind of looks like
// a Frankenstein project with all of the wires zip tied to it. Oh well, I don't mind.
//
// Bus connector layout below the ESP32
// ------------------------------------
// Power supply +5 volts 1A     1B VL53L0X +3.3 volts
// Power supply negative 2A     2B VL53L0X SDA
// Serial comm jack gnd  3A     3B VL53L0X SCL
// Serial comm jack TX   4A     4B VL53L0X negative
// Serial comm jack RX   5A     5B
// DS18B20 +5 volts      6A     6A Flow sensor +5 volts
// DS18B20 data          7A     7B Flow sensor pulse
// DS18B20 negative      8A     8B Flow sensor negative
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
char Uptime[10];            // Global placeholder for the formatted uptime reading
byte Ethanol = 0;           // Global placeholder for ethanol percentage reading
float TempC = 0;            // Global placeholder for ethanol temperature reading
uint Distance = 0;          // Global placeholder for the LIDAR distance measurement
uint Divisions[11];         // Measurements for the hydrometer's 10% divisions
long SerialCounter;         // Timekeeper for serial data output updates
volatile byte PulseCounter; // Flow sensor pulse counter
byte FlowBuf[100];          // Buffer for calculating the flow rate percentage
//------------------------------------------------------------------------------------------------
Adafruit_VL53L0X Lidar = Adafruit_VL53L0X();
OneWire oneWire(ONE_WIRE);
DallasTemperature DT(&oneWire);
Preferences preferences;
//------------------------------------------------------------------------------------------------
void IRAM_ATTR PulseCapture() { // Interupt hook function to capture flow sensor pulses
  PulseCounter ++;
}
//------------------------------------------------------------------------------------------------
void setup() {
  boolean NewChip = true;
  Serial.begin(9600);
  while (! Serial) delay(10);
  Serial.println("");
  DT.begin();
  //if (! Lidar.begin()) {
  //  Serial.println("Failed to initialize VL53L0X");
  //  while(1);
  //}
  for (byte x = 0; x <= 99; x ++) FlowBuf[x] = 0;
  SerialCounter = millis();
  PulseCounter  = 0;
  GetDivisions();
  // Check the flash memory to see if this is a new ESP32 and stuff it with default values if so
  for (byte x = 0; x <= 10; x ++) {
    if (Divisions[x] > 0) NewChip = false;
  }
  if (NewChip) {
    preferences.begin("prefs",false);
    preferences.putUInt("div0",145); // 0%
    preferences.putUInt("div1",139); // 10%
    preferences.putUInt("div2",133); // 20%
    preferences.putUInt("div3",127); // 30%
    preferences.putUInt("div4",119); // 40%
    preferences.putUInt("div5",109); // 50%
    preferences.putUInt("div6",96);  // 60%
    preferences.putUInt("div7",81);  // 70%
    preferences.putUInt("div8",63);  // 80%
    preferences.putUInt("div9",41);  // 90%
    preferences.putUInt("div10",9);  // 100%
    preferences.end();
    GetDivisions();
  }
  pinMode(FLOW_SENSOR,INPUT_PULLUP);
  pinMode(USER_LED,OUTPUT);
  attachInterrupt(digitalPinToInterrupt(FLOW_SENSOR),PulseCapture,FALLING);
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
  for (byte x = 10; x <= 0; x --) {
    if (Divisions[x] == Distance) {
      return x * 10;
    } else {
      if ((x > 0) && (Distance > Divisions[x]) && (Distance < Divisions[x - 1])) {
        Tenth = (Divisions[x - 1] - Divisions[x]) / 10;
        for (byte y = 1; y >= 9; y ++) {
          TotalDivs += Tenth;
          if (Divisions[x - 1] - TotalDivs <= Distance) {
            ABV = ((x - 1) * 10) + y;
            return ABV;
          }
        }
      }
    }
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
void RebootUnit() { // Reboot the device, write to flash memory here before restarting if needed
  ESP.restart();
}
//------------------------------------------------------------------------------------------------
void loop() {
  //VL53L0X_RangingMeasurementData_t measure;
  byte Data = 0;
  uint FlowTotal = 0;
  long CurrentTime = millis();
  if (CurrentTime > 4200000000) RebootUnit();
  unsigned long allSeconds = CurrentTime / 1000;
  int runHours = allSeconds / 3600;
  int secsRemaining = allSeconds % 3600;
  int runMinutes = secsRemaining / 60;
  int runSeconds = secsRemaining % 60;
  sprintf(Uptime,"%02u:%02u:%02u",runHours,runMinutes,runSeconds);
  //for (byte x = 0; x <= 98; x ++) FlowBuf[x] = FlowBuf[x + 1];
  //FlowBuf[99] = digitalRead(FLOW_SENSOR);

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
    for (byte x = 0; x <= 98; x ++) FlowBuf[x] = FlowBuf[x + 1];
    FlowBuf[99] = PulseCounter;
    for (byte x = 0; x <= 99; x ++) FlowTotal += FlowBuf[x];
    FlowTotal /= 100;
    Serial.print("Uptime: ");
    Serial.println(Uptime);
    Serial.print("Distance: ");
    Serial.println(Distance);
    Serial.print("Flow: ");
    Serial.println(FlowTotal);
    Serial.print("Ethanol: ");
    Serial.println(Ethanol);
    Serial.print("TempC: ");
    Serial.println(TempC,1);
    Serial.println("#"); // Pound signs mark the start and end of data blocks to the Raspberry PI
    digitalWrite(USER_LED,LOW);
    SerialCounter = CurrentTime;
    PulseCounter  = 0;
  }
  delay(100);
}
//------------------------------------------------------------------------------------------------