//------------------------------------------------------------------------------------------------
// RPi Smart Still Controller | (CopyLeft) 2024-Present | Larry Athey (https://panhandleponics.com)
// Bird Brain v1.2.2 - LIDAR Hydrometer Reader and Parrot Flow Monitor - Released April 30, 2026
//
// You must be using a v2.x ESP32 library to compile this code. It appears that v3.x libraries do
// not contain compatible headers for certain legacy libraries that I rely on. You should also use
// the following URL in your preferences under Additional Boards Manager URLs.
//
// https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
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
// the top of the overflow tube aiming upwards. A reflector equal to the diameter of the overflow
// cup is attached to the top of the hydrometer, about 20mm above the 100% line. Calibrate it with
// the RPis Smart Still Controller and you're off to the races.
//
// The reflector can be 3D printed or made out of construction paper and have hardly any effect on
// the hydrometer. Having the sensor that close to distillate isn't dangerous at all. It only runs
// on 3.3 volts and distillate isn't conductive, nor is distilled water. I've submerged one in 90%
// ethanol and distilled water while running and nothing happened, it never even stopped working.
//
// Calibration is as simple as putting the hydrometer in the parrot, add water until the 100% mark
// is even with the top of the center tube, click the button in the RPi Smart Still Controller for
// the 100% calibration. Then fill it up until the 0% mark is even with the top of the center tube
// and click the button for the 0% calibration. Calibrating the flow sensor works the same way but
// you fill it with vodka rather than water due to the dielectric properties of ethanol.
//
// The new flow sensor is a custom device designed by James (The Doc) from Ireland and determines
// the flow rate by the height of ethanol in a vessel and reading the electrical capacitance from
// two copper plates in the ethanol. The higher the flow rate, the higher the ethanol level, the
// higher the sensor reading. A perforated overflow tube controls the ethanol level in the vessel.
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
//------------------------------------------------------------------------------------------------
char Uptime[10];               // Global placeholder for the formatted uptime reading
float TempC = 0;               // Global placeholder for ethanol temperature reading
float dist0 = 132.0;           // Reflector distance for 0% ABV
float dist100 = 20.0;          // Reflector distance for 100% ABV
float Distance = 0.0;          // Current LIDAR distance measurement
float emptyValue = 58.0;       // Flow sensor reading when the vessel is empty
float fullValue = 28.0;        // Flow sensor reading when the vessel is full
long SerialCounter;            // Timekeeper for serial data output updates
bool NewChip = true;           // True if the flash memory hasn't been initialized
byte EthanolBuf[10];           // Buffer for smoothing out ethanol readings
//------------------------------------------------------------------------------------------------
Adafruit_VL53L0X Lidar = Adafruit_VL53L0X();
OneWire oneWire(ONE_WIRE);
DallasTemperature DT(&oneWire);
Preferences preferences;
//------------------------------------------------------------------------------------------------
#include "flow-sensor.h"       // Inline code library for the custom capacitive flow sensor
//------------------------------------------------------------------------------------------------
void setup() {
  Serial.begin(9600);
  while (! Serial) delay(10);
  Serial.println("");

  DT.begin();
  if (! Lidar.begin(VL53L0X_I2C_ADDR,false,&Wire,Lidar.VL53L0X_SENSE_HIGH_ACCURACY)) {
    Serial.println("Failed to initialize VL53L0X");
    while(true);
  }

  // Check the flash memory to see if this is a new ESP32 and stuff it with default values if so
  GetMemory();
  if (NewChip) {
    ResetMemory();
    GetMemory();
  }

  // Set up the custom flow sensor library
  pinMode(SENSE_PIN,INPUT);
  pinMode(CHARGE_PIN,OUTPUT);
  digitalWrite(CHARGE_PIN,LOW);
  delay(100);

  pinMode(USER_LED,OUTPUT);
  SerialCounter = millis();

  for (byte x = 0; x <= 9; x ++) EthanolBuf[x] = 0;
}
//------------------------------------------------------------------------------------------------
void TempUpdate() { // Update the distillate temperature value
  DT.requestTemperatures();
  TempC = DT.getTempCByIndex(0);
}
//------------------------------------------------------------------------------------------------
float CalcEthanol() { // Convert the Distance millimeters to an ethanol ABV value (temperature compensated)
  const float tolerance = 3.0f;  // mm — increase to compensate for a noisy VL53L0X sensor

  if (Distance > (dist0 + tolerance) || Distance < (dist100 - tolerance)) {
    return 0.0f;  // Invalid reading → treat as 0% ABV or "no valid measurement"
  }

  // Standard densities at 20 °C (kg/m³)
  const float d0_20   = 998.20f;   // water
  const float d100_20 = 789.24f;   // pure ethanol

  // Fit constants so that: dist = k / density + m
  float delta_h   = dist0 - dist100;
  float delta_inv = (1.0f / d0_20) - (1.0f / d100_20);
  if (fabs(delta_inv) < 1e-8f) return 0.0f;

  float k = delta_h / delta_inv;   // will be negative — that's physically correct
  float m = dist0 - k / d0_20;

  // Observed density at current temperature
  float denom = Distance - m;
  if (fabs(denom) < 0.01f) return 0.0f;
  float density_obs = k / denom;

  // Clamp to realistic range
  if (density_obs > 1100.0f) density_obs = 1100.0f;
  if (density_obs < 780.0f)  density_obs = 780.0f;

  // === TEMPERATURE CORRECTION (fixed & improved) ===
  // density_T = density_20 / (1 + α·ΔT)   →   density_20 = density_T · (1 + α·ΔT)
  // α = blended cubical expansion coefficient for 0–100 % ethanol-water mixtures
  const float alpha = 0.00085f;   // excellent average for this application
  float density_20 = density_obs * (1.0f + alpha * (TempC - 20.0f));

  // Official OIML ethanol density table at 20 °C (1% steps)
  static const float density_table[101] = {
    998.20f, 996.70f, 995.73f, 993.81f, 992.41f, 991.06f, 989.73f, 988.43f, 987.16f, 985.92f,
    984.71f, 983.52f, 982.35f, 981.21f, 980.08f, 978.97f, 977.87f, 976.79f, 975.71f, 974.63f,
    973.56f, 972.48f, 971.40f, 970.31f, 969.21f, 968.10f, 966.97f, 965.81f, 964.64f, 963.44f,
    962.21f, 960.95f, 959.66f, 958.34f, 956.98f, 955.59f, 954.15f, 952.69f, 951.18f, 949.63f,
    948.05f, 946.42f, 944.76f, 943.06f, 941.32f, 939.54f, 937.73f, 935.88f, 934.00f, 932.09f,
    930.14f, 928.16f, 926.16f, 924.12f, 922.06f, 919.96f, 917.84f, 915.70f, 913.53f, 911.33f,
    909.11f, 906.87f, 904.60f, 902.31f, 899.99f, 897.65f, 895.28f, 892.89f, 890.48f, 888.03f,
    885.56f, 883.06f, 880.54f, 877.99f, 875.40f, 872.79f, 870.15f, 867.48f, 864.78f, 862.04f,
    859.27f, 856.46f, 853.62f, 850.74f, 847.82f, 844.85f, 841.84f, 838.77f, 835.64f, 832.45f,
    829.18f, 825.83f, 822.39f, 818.85f, 815.18f, 811.38f, 807.42f, 803.27f, 798.90f, 794.25f,
    789.24f
  };

  // Linear interpolation in the table
  for (int i = 0; i < 100; ++i) {
    float d_high = density_table[i];  // higher density = lower ABV
    float d_low  = density_table[i + 1];
    if (density_20 >= d_low && density_20 <= d_high) {
      float fraction = (d_high - density_20) / (d_high - d_low);
      return roundf(((float)i + fraction) * 10.0f) / 10.0f;  // 0.1 % resolution
    }
  }

  // Fallback
  return (density_20 <= d100_20) ? 100.0f : 0.0f;
}
//------------------------------------------------------------------------------------------------
void GetMemory() { // Get all of the configuration settings from flash memory
  preferences.begin("prefs",true);
  dist0      = preferences.getFloat("dist0",132.0);
  dist100    = preferences.getFloat("dist100",20.0);
  emptyValue = preferences.getFloat("emptyvalue",58.0);
  fullValue  = preferences.getFloat("fullvalue",28.0);
  NewChip    = preferences.getBool("newchip",true);
  preferences.end();
  Serial.print("dist0: "); Serial.println(dist0);
  Serial.print("dist100: "); Serial.println(dist100);
  Serial.print("emptyValue: "); Serial.println(emptyValue);
  Serial.print("fullValue: "); Serial.println(fullValue);
  Serial.println("#!");
}
//------------------------------------------------------------------------------------------------
void ResetMemory() { // Restore all of the configuration settings to their defaults
  // The distances below are for this hydrometer -> https://www.amazon.com/dp/B013S1VAM4
  preferences.begin("prefs",false);
  preferences.putFloat("dist0",132.0);  // 0%
  preferences.putFloat("dist100",20.0); // 100%
  preferences.putFloat("emptyvalue",58.0); // Empty capacitance
  preferences.putFloat("fullvalue",28.0);  // Full capaitance
  preferences.putBool("newchip",false); // New chip Y/N
  preferences.end();
  for (byte x = 0; x <= 9; x ++) {
    digitalWrite(USER_LED,HIGH);
    delay(100);
    digitalWrite(USER_LED,LOW);
    delay(100);
  }
}
//------------------------------------------------------------------------------------------------
void UpdateMemory(byte Slot) { // Update a flash memory slot for a specific configuration item
  preferences.begin("prefs",false);
  if (Slot == 48) { // ASCII code for 0
    preferences.putFloat("dist0",dist0);
  } else if (Slot == 49) { // ASCII code for 1
    preferences.putFloat("dist100",dist100);
  } else if (Slot == 50) { // ASCII code for 2
    preferences.putFloat("emptyvalue",emptyValue);
  } else if (Slot == 51 { // ASCII code for 3
    preferences.putFloat("fullvalue",fullValue);
  }
  preferences.end();
  GetMemory();
  for (byte x = 0; x <= 9; x ++) {
    digitalWrite(USER_LED,HIGH);
    delay(100);
    digitalWrite(USER_LED,LOW);
    delay(100);
  }
}
//------------------------------------------------------------------------------------------------
void RebootUnit() { // Reboot the device, write to flash memory here before restarting if needed
  ESP.restart();
}
//------------------------------------------------------------------------------------------------
void loop() {
  VL53L0X_RangingMeasurementData_t measure;
  byte Data = 0;
  uint FlowRate = 0;
  uint EthanolAvg = 0;
  unsigned long CurrentTime = millis();
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
    } else if (Data == 35) { // Update a specific flash memory slot if a "#" is received
      if (Serial.available()) { // Formatted as #0 = dist0, #1 = dist100, #2 = emptyValue, #3 = fullValue
        Data = Serial.read();
        UpdateMemory(Data);
      }
    } else if (Data == 42) { // Restore default Divisions[x] values if a "*" is received
      ResetMemory();
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
      Distance = float(measure.RangeMilliMeter);
      for (byte x = 0; x <= 8; x ++) EthanolBuf[x] = EthanolBuf[x + 1];
      EthanolBuf[9] = round(CalcEthanol());
      for (byte x = 0; x <= 9; x ++) EthanolAvg += EthanolBuf[x];
      EthanolAvg *= 0.1;
    }

    // Get the current distillate flow rate (capacitance level across the flow sensor plates)
    FlowRate = getFlowSensor();
  
    digitalWrite(USER_LED,HIGH);
    digitalWrite(CHARGE_PIN,LOW);
    Serial.print("Uptime: "); Serial.println(Uptime);
    Serial.print("Distance: "); Serial.print(Distance); Serial.printf(", Capacitance: %.2fpf\r\n",capacitance_pf);
    Serial.print("Flow: "); Serial.println(FlowRate);
    Serial.print("Ethanol: "); Serial.println(EthanolAvg);
    Serial.print("TempC: "); Serial.println(TempC,1);
    Serial.println("#"); // Pound sign marks the end of a data block to the Raspberry PI
    Serial.flush();
    delay(200);
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
