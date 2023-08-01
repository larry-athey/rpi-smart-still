//------------------------------------------------------------------------------------------------
// Written by Larry Athey (Panhandle Ponics - https://3dgtower.com) v1.0.1 released July 12, 2023
//
// This project is based on an Arduino Uno/Nano/Mega or ESP32 and an ILI9341 320x240 color LCD/TFT
// display that uses SPI communications. Parallel shields are completely useless since they steal
// all of the GPIO pins of an Uno and this project requires I2C and one pin for a OneWire network.
//
// Unlike the E85 Sensor Hydrometer, this project uses a 1 KG load cell and a reference weight to
// calculate the density of your distillate. Ethanol is 6.53 pounds per gallon and water is 8.35
// pounds per gallon. The density of the distillate will change the buoyancy of a reference weight
// suspended in it from the load cell. In this case, we use a 64 gram stainless steel table cloth
// weight suspended in an acrylic 128 ml laboratory overflow cup to replace a parrot. While the
// distillate flows through the overflow cup, the buoyancy of the table cloth weight is tracked.
//
// While this design involves moving parts, the increased accuracy offsets that design downfall.
// Another advantage to this project's design is the use of a more accurate temperature sensor.
// Hot distillate will even render a glass hydrometer inaccurate, so keep that condenser cool!
// The cost of each unit is about the same, so it's really just up to you which one you'd rather
// use. I honestly don't have a problem with the moving parts and it's cheaper to replace a load
// cell than it is to replace the E85 sensor if it goes bad.
//
// Be sure to check out Load-Cell-Hydrometer.pdf file in the Diagrams directory, there are a few
// additional components needed besides the 4.7K pull up resistor needed in all OneWire projects.
// Load cells are an analog device and the circuit board that comes with them is an amplifier, so
// that means noise gets amplified too. I've added a couple capacitors and an inductor to filter
// out most of the noise. A grounded shield around its wires also wouldn't be a bad idea here.
//
// Beyond that, I'd recommend a decent 5 volt 2 amp power supply instead of running things off a
// USB port since DS18B20 temperature sensors and HX711 load cell amplifiers need a regulated 5
// volts or they'll fail to read accurately. For example, when powering an ESP32 by USB, the +5v
// pin will read +4.64 volts and this will cause a load cell to progressively read a lower value
// the longer it runs. They will also read higher over time if you feed them more than 5 volts.
//
// At this time, I still don't have any suggestion for speeding up the SPI protocol used with the
// Adafruit ILI9341 display library. I realize that the Bodmer TFT_eSPI library is faster, but it
// doesn't work with Arduinos. I've seen umpteen complaints and suggestions in the Adafruit user
// forums and none of the suggestions have improved anything in my tests, not even with an ESP32.
// The library is also the same dog slow speed in the WokWi simulator. I just considerer the slow
// offscreen buffer display with an ESP32 an artistic feature here since speed isn't a necessity.
//
// NOTE: Since this hydrometer mounts to the large evaporator column above the still, you should
// wait until the boiler is up to temp before you calibrate it. Since aluminum is an excellent
// heat conductor, you want your load cell in an area with a stable temperature and the top of
// the still is very consistent after the boiler is fully warmed up. Temperature also affects a
// glass hydrometer the same way, but you've likely never noticed it. Watch it on video sometime.
//------------------------------------------------------------------------------------------------
#include "Adafruit_ILI9341.h"  // TFT display library add-on to Adafruit GFX (sadly, it's slow)
#include "FreeSans10pt7b.h"    // https://github.com/moononournation/ArduinoFreeFontFile.git
#include "FreeDefaultFonts.h"  // Included in MCUFRIEND_kbv (used for the 7-segment LED style font)
#include "HX711.h"             // Load cell library from https://github.com/RobTillaart/HX711
#include "OneWire.h"           // OneWire Network communications library
#include "DallasTemperature.h" // Dallas Temperature DS18B20 temperature sensor library
#include "EthanolCalc.h"       // Long ass function to return ethanol % based on reference weight
//------------------------------------------------------------------------------------------------
// Arduino Uno GPIO pin mapping (See special note at the top of EthanolCalc.h)
//#define ONE_WIRE 4
//#define I2C_SCL A5
//#define I2C_SDA A4
//#define TFT_RST 8
//#define TFT_DC 9
//#define TFT_CS 10
//#define TFT_MOSI 11
//#define TFT_MISO 12
//#define TFT_CLK 13
//#define TFT_LED A0

// Arduino Nano GPIO pin mapping (See special note at the top of EthanolCalc.h)
//#define ONE_WIRE 4
//#define I2C_SCL A5
//#define I2C_SDA A4
//#define TFT_RST 7
//#define TFT_DC 9
//#define TFT_CS 10
//#define TFT_MOSI 11
//#define TFT_MISO 12
//#define TFT_CLK 13
//#define TFT_LED A0

// Arduino Mega GPIO pin mapping
//#define ONE_WIRE 4
//#define I2C_SCL A5
//#define I2C_SDA A4
//#define TFT_RST 7
//#define TFT_DC 9
//#define TFT_MISO 50
//#define TFT_MOSI 51
//#define TFT_CLK 52
//#define TFT_CS 53
//#define TFT_LED A0

// 38-pin ESP32 WROOM GPIO mapping
#define ONE_WIRE 36
#define FLOW_SENSOR 39
#define I2C_SCL 22
#define I2C_SDA 21
#define TFT_RST 0
#define TFT_DC 2
#define TFT_CS 5
#define TFT_MISO 19
#define TFT_CLK 18
#define TFT_MOSI 23
#define TFT_LED 4
//------------------------------------------------------------------------------------------------
char Uptime[10];      // Global placeholder for uptime reading
byte Ethanol = 0;     // Global placeholder for ethanol percentage
float TempC = 0;      // Global placeholder for ethanol temperature reading
float WeightBuf[50];  // Buffer for storing the last 50 load cell readings
bool eToggle = false; // Ethanol display toggle byte (false=%ABV or true=Proof)
long ScreenCounter;   // Timekeeper for display updates
long SerialCounter;   // Timekeeper for serial data output updates
byte FlowSense = 1;   // Storage byte for the optical distillate flow sensor status
byte eTest = 0;       // This is only used by the code block in loop() for display testing
//------------------------------------------------------------------------------------------------
Adafruit_ILI9341 tft = Adafruit_ILI9341(TFT_CS,TFT_DC,TFT_MOSI,TFT_CLK,TFT_RST,TFT_MISO);
HX711 Scale;
OneWire oneWire(ONE_WIRE);
DallasTemperature DT(&oneWire);
//------------------------------------------------------------------------------------------------
void setup() {
  byte Counter;
  float Tare;
  ScreenCounter = millis();
  SerialCounter = ScreenCounter;
  Serial.begin(9600);
  Serial.println("");

  pinMode(TFT_LED,OUTPUT);
  digitalWrite(TFT_LED,HIGH);
  tft.begin();
  tft.setRotation(1);
  tft.fillScreen(ILI9341_BLACK);
  tft.setFont(&FreeSans10pt7b);
  tft.setTextSize(1);
  tft.setTextColor(ILI9341_DARKGREY);
  tft.setCursor(2,19);
  tft.print("Distillate Temp:");
  tft.setTextColor(ILI9341_DARKGREEN);
  tft.setCursor(2,233);
  tft.print("Hydrometer Uptime:");
 
  tft.setTextColor(ILI9341_RED);
  tft.setCursor(25,110);
  tft.print("Remove the reference weight,");
  tft.setCursor(25,135);
  tft.print("Pausing 15 Seconds");
  Counter = 0;
  while (Counter < 15) {
    tft.print(".");
    delay(1000);
    Counter ++;
  }
  tft.fillRect(0,74,320,95,ILI9341_BLACK);

  Scale.begin(I2C_SDA,I2C_SCL);
  Scale.set_gain(64,true);
  while (! Scale.is_ready()) delay(250);
  Tare = Scale.get_units(20);
  Serial.println("#");
  Serial.print("Tare1: ");
  Serial.println(Tare);

  // If you forget to remove the reference weight, it will halt the system start up until you do
  if (Tare > -32000) {
    tft.setCursor(45,110);
    tft.print("Load cell detecting weight,");
    tft.setCursor(45,135);
    tft.print("Correct this and reboot...");
    while (true) {
      while (Serial.available()) {
        Counter = Serial.read();
        if (Counter == 33) { // Reboot the hydrometer if a "!" is received
          ESP.restart(); // For Arduinos, see https://arduinogetstarted.com/faq/how-to-reset-arduino-by-programming
        }
      }
    }
  }

  Scale.tare();
  Scale.set_average_mode();
  Tare = Scale.get_units(20);
  Serial.print("Tare2: ");
  Serial.println(Tare);
  Serial.println("#");

  tft.setCursor(35,110);
  tft.print("Attach the reference weight,");
  tft.setCursor(35,135);
  tft.print("Pausing 15 Seconds");
  Counter = 0;
  while (Counter < 15) {
    tft.print(".");
    delay(1000);
    Counter ++;
  }
  tft.fillRect(0,74,320,95,ILI9341_BLACK);

  // NOTE: Barometric pressure can affect the stability of a load cell, especially during a rain
  // storm. If Tare2 is outside of +/- 1..5 from zero, reset the unit and start over.
  Scale.calibrate_scale(64,20);
  for (byte x = 0; x <= 49; x ++) WeightBuf[x] = 64;
  tft.setTextColor(ILI9341_YELLOW);
  tft.setCursor(90,95);
  tft.print("Load Cell Calibrated");
  tft.setCursor(90,120);
  tft.print("Install Parrot Cup");
  tft.setCursor(90,145);
  tft.print("Pausing 30 Seconds");
  tft.setCursor(90,155);
  Counter = 0;
  while (Counter < 30) {
    tft.print(".");
    delay(1000);
    Counter ++;
  }
}
//------------------------------------------------------------------------------------------------
void EthanolUpdate() { // Update the ethanol value on the display
  byte Reading,canvasX;
  uint16_t FG;
  eToggle = ! eToggle;
  if (eToggle) {
    Reading = Ethanol * 2;
  } else {
    Reading = Ethanol;
  }
  if (Ethanol <= 19) {
    FG = ILI9341_BLUE;
  } else if ((Ethanol > 19) && (Ethanol <= 29)) {
    FG = ILI9341_CYAN;
  } else if ((Ethanol > 29) && (Ethanol <= 49)) {
    FG = ILI9341_GREEN;
  } else if ((Ethanol > 49) && (Ethanol <= 64)) {
    FG = ILI9341_YELLOW;
  } else if ((Ethanol > 64) && (Ethanol <= 84)) {
    FG = ILI9341_MAGENTA;
  } else {
    FG = ILI9341_RED;
  }
  // Off-screen buffers only work with Arduino Mega and ESP32 due to the memory requirements
  GFXcanvas1 canvas(220,95);
  canvas.setTextWrap(false);
  if (Reading > 99) {
    canvas.setCursor(1,91);
    canvasX = 37;
  } else {
    canvas.setCursor(1,91);
    canvasX = 80;
  }
  canvas.setFont(&FreeSevenSegNumFont);
  canvas.setTextSize(2);
  canvas.print(String(Reading));
  canvas.setFont(&FreeSans10pt7b);
  if (Reading > 99) {
    canvas.setCursor(193,57);
  } else {
    canvas.setCursor(133,57); 
  }
  if (eToggle) {
    canvas.print("P");
  } else {
    canvas.print("%");
  }
  tft.drawBitmap(canvasX,75,canvas.getBuffer(),canvas.width(),canvas.height(),FG,ILI9341_BLACK);
  /*
  // Old school screen updates for Arduino Uno/Nano (blank the area and redraw)
  if (Reading > 99) {
    tft.setCursor(40,171);
  } else {
    tft.setCursor(90,171);
  }
  tft.setFont(&FreeSevenSegNumFont);
  tft.setTextColor(FG);
  tft.setTextSize(2);
  tft.fillRect(0,74,320,95,ILI9341_BLACK);
  tft.print(String(Reading));
  tft.setFont(&FreeSans10pt7b);
  if (Reading > 99) {
    tft.setCursor(235,131);
  } else {
    tft.setCursor(222,131); 
  }
  if (eToggle) {
    tft.print("P");
  } else {
    tft.print("%");
  }
  */
}
//------------------------------------------------------------------------------------------------
void TempUpdate() { // Update the distillate temperature on the display
  if (DT.getDeviceCount() > 0) {
    DT.requestTemperatures();
    TempC = DT.getTempCByIndex(0);
  }
  String cStr = String(TempC,1);
  String fStr = String(TempC * 9 / 5 + 32,1);
  // Off-screen buffers only work with Arduino Mega and ESP32 due to the memory requirements
  GFXcanvas1 canvas(155,24);
  canvas.setTextWrap(false);
  canvas.setFont(&FreeSans10pt7b);
  canvas.setTextSize(1);
  canvas.setCursor(1,19);
  canvas.print(cStr + "C / " + fStr + "F");
  tft.drawBitmap(142,0,canvas.getBuffer(),canvas.width(),canvas.height(),ILI9341_DARKGREY,ILI9341_BLACK);
  /*
  // Old school screen updates for Arduino Uno/Nano (blank the area and redraw)
  tft.setFont(&FreeSans10pt7b);
  tft.setTextSize(1);
  tft.setTextColor(ILI9341_DARKGREY);
  tft.fillRect(140,1,155,24,ILI9341_BLACK);
  tft.setCursor(145,19);
  tft.print(cStr + "C / " + fStr + "F");
  */
}
//------------------------------------------------------------------------------------------------
void TimeUpdate() {
  // Off-screen buffers only work with Arduino Mega and ESP32 due to the memory requirements
  GFXcanvas1 canvas(105,24);
  canvas.setTextWrap(false);
  canvas.setFont(&FreeSans10pt7b);
  canvas.setTextSize(1);
  canvas.setCursor(1,19);
  canvas.print(Uptime);
  tft.drawBitmap(184,215,canvas.getBuffer(),canvas.width(),canvas.height(),ILI9341_DARKGREEN,ILI9341_BLACK);
  /*
  // Old school screen updates for Arduino Uno/Nano (blank the area and redraw)
  tft.setFont(&FreeSans10pt7b);
  tft.setTextSize(1);
  tft.setTextColor(ILI9341_DARKGREEN);
  tft.fillRect(182,215,105,24,ILI9341_BLACK);
  tft.setCursor(184,233);
  tft.print(Uptime);
  */
}
//------------------------------------------------------------------------------------------------
void loop() {
  long CurrentTime = millis();
  float Weight,WeightAvg = 0;
  byte Data;
  unsigned long allSeconds = CurrentTime / 1000;
  int runHours = allSeconds / 3600;
  int secsRemaining = allSeconds % 3600;
  int runMinutes = secsRemaining / 60;
  int runSeconds = secsRemaining % 60;
  sprintf(Uptime,"%02d:%02d:%02d",runHours,runMinutes,runSeconds);
  if (digitalRead(FLOW_SENSOR) == 1) FlowSense = 0;

  // Get the current weight of the steel ball and calculate the ethanol percentage from
  // the buoyancy offset of the reference weight. Higher ethanol makes the ball heavier.
  if (CurrentTime - SerialCounter >= 1000) {
    // Check for serial data commands from the RPi Smart Still controller
    while (Serial.available()) {
      Data = Serial.read();
      if (Data == 33) { // Reboot the hydrometer if a "!" is received
        ESP.restart(); // For Arduinos, see https://arduinogetstarted.com/faq/how-to-reset-arduino-by-programming
      } else if (Data == 35) { // Recalibrate the load cell if a "#" is received
        digitalWrite(TFT_LED,LOW);
        Scale.calibrate_scale(64,20);
        digitalWrite(TFT_LED,HIGH);
      }
    }
    Weight = Scale.get_units(20);
    for (byte x = 0; x <= 48; x ++) WeightBuf[x] = WeightBuf[x + 1];
    WeightBuf[49] = Weight;
    WeightAvg = 0;
    for (byte x = 0; x <= 49; x ++) WeightAvg += WeightBuf[x];
    WeightAvg /= 50;
    Ethanol = CalcEthanol(WeightAvg);
  }

/*
  // Uncomment the following code block for display testing
  TempC = random(18,25);
  eTest ++;
  if (eTest == 100) eTest = 0;
  Ethanol = eTest;
*/

  // Complete screen redraw takes about 2.5 seconds with the Adafruit_ILI9341 library
  // This keeps all of the latest stats on the screen for 5 seconds before refreshing
  if (CurrentTime - ScreenCounter >= 7500) {
    EthanolUpdate();
    TempUpdate();
    TimeUpdate();
    ScreenCounter = CurrentTime;
  }
  // Communications to my Raspberry PI based still monitor/controller uses 9600 baud serial data
  if (CurrentTime - SerialCounter >= 1000) {
    char WeightLog[25];
    sprintf(WeightLog,"%.2f %.2f",Weight,WeightAvg);
    Serial.print("Uptime: ");
    Serial.println(Uptime);
    Serial.print("Weight: ");
    Serial.println(WeightLog);
    Serial.print("Flow: ");
    Serial.println(FlowSense);
    Serial.print("Ethanol: ");
    Serial.println(Ethanol);
    Serial.print("TempC: ");
    Serial.println(TempC,1);
    Serial.println("#"); // Pound signs mark the start and end of data blocks to the Raspberry PI
    FlowSense = 1;
    SerialCounter = CurrentTime;
  }
}
//------------------------------------------------------------------------------------------------