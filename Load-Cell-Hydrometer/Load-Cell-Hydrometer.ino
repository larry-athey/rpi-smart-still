//------------------------------------------------------------------------------------------------
// Written by Larry Athey (https://panhandleponics.com) v1.0.1 released July 12, 2023
//
// This project is based on an Arduino Uno/Nano/Mega or ESP32 and an ILI9341 320x240 color LCD/TFT
// display that uses SPI communications. Parallel shields are completely useless since they steal
// all of the GPIO pins of an Uno and this project requires I2C and one pin for a OneWire network.
//
// Unlike the E85 Sensor Hydrometer, this project uses a 1 KG load cell and a reference weight to
// calculate the density of your distillate. Ethanol is 6.53 pounds per gallon and water is 8.35
// pounds per gallon. The density of the distillate will change the buoyancy of a reference weight
// suspended in it from the load cell. In this case, we use a 64 gram stainless steel table cloth
// weight suspended half way into an overflow cup to replace a parrot. While the distillate flows
// through the overflow cup, the buoyancy of the table cloth weight is tracked.
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
// USB port since an ESP32 and DS18B20 temperature sensors need a regulated 5 volts or they will
// become very random in their operation. For example, when powering an ESP32 by a USB port, the
// +5v pin will read +4.64 volts and this will cause the load cell to progressively read a lower
// value the longer that it runs.
//
// At this time, I still don't have any suggestion for speeding up the SPI protocol used with the
// Adafruit ILI9341 display library. I realize that the Bodmer TFT_eSPI library is faster, but it
// doesn't work with Arduinos. I've seen umpteen complaints and suggestions in the Adafruit user
// forums and none of the suggestions have improved anything in my tests, not even with an ESP32.
// The library is also the same dog slow speed in the WokWi simulator. I just considerer the slow
// offscreen buffer display with an ESP32 an artistic feature here since speed isn't a necessity.
//------------------------------------------------------------------------------------------------
#include "Adafruit_ILI9341.h"  // TFT display library add-on to Adafruit GFX (sadly, it's slow)
#include "FreeSans10pt7b.h"    // https://github.com/moononournation/ArduinoFreeFontFile.git
#include "FreeDefaultFonts.h"  // Included in MCUFRIEND_kbv (used for the 7-segment LED style font)
#include "HX711.h"             // Load cell library from https://github.com/RobTillaart/HX711
#include "OneWire.h"           // OneWire Network communications library
#include "DallasTemperature.h" // Dallas Temperature DS18B20 temperature sensor library
//------------------------------------------------------------------------------------------------
// Arduino Uno GPIO pin mapping
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

// Arduino Nano GPIO pin mapping
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
#define ONE_WIRE 15
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
char Uptime[10];            // Global placeholder for the formatted uptime reading
byte Ethanol = 0;           // Global placeholder for ethanol percentage reading
float TempC = 0;            // Global placeholder for ethanol temperature reading
float WeightBuf[10];        // Buffer for storing the last 10 load cell readings
byte FlowBuf[100];          // Buffer for calculating the flow rate percentage
bool eToggle = false;       // Ethanol display toggle byte (false=%ABV or true=Proof)
long ScreenCounter;         // Timekeeper for display updates
long SerialCounter;         // Timekeeper for serial data output updates
volatile byte PulseCounter; // Flow sensor pulse counter
byte eTest = 0;             // This is only used by the code block in loop() for display testing
//------------------------------------------------------------------------------------------------
Adafruit_ILI9341 tft = Adafruit_ILI9341(TFT_CS,TFT_DC,TFT_MOSI,TFT_CLK,TFT_RST,TFT_MISO);
HX711 Scale;
OneWire oneWire(ONE_WIRE);
DallasTemperature DT(&oneWire);
//------------------------------------------------------------------------------------------------
void IRAM_ATTR PulseCapture() { // Interupt hook function to capture flow sensor pulses
  if (PulseCounter < 255) PulseCounter ++;
}
//------------------------------------------------------------------------------------------------
void setup() {
  byte Counter;
  float Tare;
  for (byte x = 0; x <= 99; x ++) FlowBuf[x] = 0;
  for (byte x = 0; x <= 9; x ++) WeightBuf[x] = 64;
  ScreenCounter = millis();
  SerialCounter = ScreenCounter;
  PulseCounter  = 0;
  DT.begin();
  Serial.begin(9600);
  while (! Serial) delay(10);
  Serial.println("");
  pinMode(FLOW_SENSOR,INPUT_PULLUP);
  attachInterrupt(digitalPinToInterrupt(FLOW_SENSOR),PulseCapture,FALLING);

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
  Tare = Scale.get_units();
  Serial.println("#");
  Serial.print("Tare1: ");
  Serial.println(Tare);

  /*
  // Testing code block to display the default load cell tare value on the screen
  tft.setCursor(45,110);
  tft.print("Tare1: ");
  tft.print(Tare);
  delay(5000);
  tft.fillRect(0,74,320,95,ILI9341_BLACK);
  */

  // If you forget to remove the reference weight, it will halt the system start up until you do
  if (Tare > 0) {
    tft.setCursor(45,110);
    tft.print("Load cell detecting weight,");
    tft.setCursor(45,135);
    tft.print("Correct this and reboot...");
    while (true) {
      while (Serial.available()) {
        Counter = Serial.read();
        if (Counter == 33) { // Reboot the hydrometer if a "!" is received
          RebootUnit();
        }
      }
    }
  }

  // Wait for the HX711 amplifier to settle down, an upper limit of 5 is tolerable, reduce this if you like
  // Remember, load cells and the HX711 are analog devices and are affected by temperature and barometric pressure
  // Through all of my testing and debugging, I have found that they are the most reliable at a stable 75F or higher
  // This is why my smart still controller sends a recalibrate command when the column/dephleg reach temperature
  tft.setCursor(55,110);
  tft.print("Stabilizing the load cell");
  Tare = -1;
  while ((Tare < 0) || (Tare > 5)) {
    Scale.tare();
    Scale.set_runavg_mode();
    Tare = Scale.get_units();
  }
  tft.fillRect(0,74,320,95,ILI9341_BLACK);
  Serial.print("Tare2: ");
  Serial.println(Tare);
  Serial.println("#");

  /*
  // Testing code block to display the mode-adjusted load cell tare value on the screen
  tft.setCursor(45,110);
  tft.print("Tare2: ");
  tft.print(Tare);
  delay(5000);
  tft.fillRect(0,74,320,95,ILI9341_BLACK);
  */

  tft.setTextColor(ILI9341_YELLOW);
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

  Scale.calibrate_scale(64);
  tft.setTextColor(ILI9341_GREEN);
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
void RebootUnit() {
  ESP.restart(); // For Arduinos, see https://arduinogetstarted.com/faq/how-to-reset-arduino-by-programming
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
  uint16_t FG;
  DT.requestTemperatures();
  TempC = DT.getTempCByIndex(0);
  String cStr = String(TempC,1);
  String fStr = String(TempC * 9 / 5 + 32,1);
  if (TempC < 20) {
    FG = ILI9341_BLUE;
  } else if (TempC > 21) {
    FG = ILI9341_RED;
  } else {
    FG = ILI9341_DARKGREY;
  }
  // Off-screen buffers only work with Arduino Mega and ESP32 due to the memory requirements
  GFXcanvas1 canvas(155,24);
  canvas.setTextWrap(false);
  canvas.setFont(&FreeSans10pt7b);
  canvas.setTextSize(1);
  canvas.setCursor(1,19);
  canvas.print(cStr + "C / " + fStr + "F");
  tft.drawBitmap(142,0,canvas.getBuffer(),canvas.width(),canvas.height(),FG,ILI9341_BLACK);
  /*
  // Old school screen updates for Arduino Uno/Nano (blank the area and redraw)
  tft.setFont(&FreeSans10pt7b);
  tft.setTextSize(1);
  tft.setTextColor(FG);
  tft.fillRect(140,1,155,24,ILI9341_BLACK);
  tft.setCursor(145,19);
  tft.print(cStr + "C / " + fStr + "F");
  */
}
//------------------------------------------------------------------------------------------------
void TimeUpdate(String Debug) { // Update the uptime on the display or show debugging information
  // Off-screen buffers only work with Arduino Mega and ESP32 due to the memory requirements
  GFXcanvas1 canvas(105,24);
  canvas.setTextWrap(false);
  canvas.setFont(&FreeSans10pt7b);
  canvas.setTextSize(1);
  canvas.setCursor(1,19);
  if (Debug == "") {
    canvas.print(Uptime);
  } else {
    canvas.print(Debug);
  }
  tft.drawBitmap(184,215,canvas.getBuffer(),canvas.width(),canvas.height(),ILI9341_DARKGREEN,ILI9341_BLACK);
  /*
  // Old school screen updates for Arduino Uno/Nano (blank the area and redraw)
  tft.setFont(&FreeSans10pt7b);
  tft.setTextSize(1);
  tft.setTextColor(ILI9341_DARKGREEN);
  tft.fillRect(182,215,105,24,ILI9341_BLACK);
  tft.setCursor(184,233);
  if (Debug == "") {
    tft.print(Uptime);
  } else {
    tft.print(Debug);
  }
  */
}
//------------------------------------------------------------------------------------------------
byte CalcEthanol(float WeightAvg) { // Convert the weight average to an ethanol ABV value
  float Divisions[11],Tenth,TotalDivs = 0;
  byte ABV;
  Divisions[0] = 56.00; // These are the weights for each of the ABV divisions that you would see
  Divisions[1] = 56.06; // on a glass hydrometer. You may need to adjust these based on your load
  Divisions[2] = 56.12; // cell. These were taken with 20C/68F to 21C/70F distillate in a room of
  Divisions[3] = 56.20; // the same temperature. Remember, with load cells being aluminum, they're
  Divisions[4] = 56.35; // more affected by temperature and will expand/contract, which will cause
  Divisions[5] = 56.50; // their reading to drift. The temperature shown in the display will tell
  Divisions[6] = 56.65; // you if you're outside of the calibrated range. If it's red or blue, you
  Divisions[7] = 56.80; // will not be getting accurate readings.
  Divisions[8] = 57.00;
  Divisions[9] = 57.20;
  Divisions[10] = 57.42;
  for (byte x = 10; x >= 0; x --) {
    if (Divisions[x] == WeightAvg) {
      return x * 10;
    } else {
      if ((x > 0) && (WeightAvg > Divisions[x]) && (WeightAvg < Divisions[x - 1])) {
        Tenth = (Divisions[x - 1] - Divisions[x]) * 0.1;
        for (byte y = 1; y <= 9; y ++) {
          TotalDivs += Tenth;
          if (Divisions[x - 1] - TotalDivs <= WeightAvg) {
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
void loop() {
  long CurrentTime = millis();
  if (CurrentTime > 4200000000) RebootUnit();
  float WeightAvg = 0;
  float FlowTotal = 0;
  byte Data = 0;
  unsigned long allSeconds = CurrentTime / 1000;
  int runHours = allSeconds / 3600;
  int secsRemaining = allSeconds % 3600;
  int runMinutes = secsRemaining / 60;
  int runSeconds = secsRemaining % 60;
  sprintf(Uptime,"%02u:%02u:%02u",runHours,runMinutes,runSeconds);
  for (byte x = 0; x <= 98; x ++) FlowBuf[x] = FlowBuf[x + 1];
  FlowBuf[99] = digitalRead(FLOW_SENSOR);

  // Get the current weight of the steel ball and calculate the ethanol percentage from
  // the buoyancy offset of the reference weight. Higher ethanol makes the ball heavier.
  if (CurrentTime - SerialCounter >= 1000) {
    // Check for serial data commands from the RPi Smart Still controller
    while (Serial.available()) {
      Data = Serial.read();
      if (Data == 33) { // Reboot the hydrometer if a "!" is received
        RebootUnit();
      } else if (Data == 35) { // Recalibrate the load cell if a "#" is received
        digitalWrite(TFT_LED,LOW);
        Scale.calibrate_scale(64);
        for (byte x = 0; x <= 9; x ++) WeightBuf[x] = 64;
        digitalWrite(TFT_LED,HIGH);
      }
    }
    for (byte x = 0; x <= 8; x ++) WeightBuf[x] = WeightBuf[x + 1];
    WeightBuf[9] = Scale.get_units(15);
    if (WeightBuf[9] > 64) { // Train the runavg mode before ethanol starts running
      Scale.calibrate_scale(64);
      WeightBuf[9] = Scale.get_units(15);
    }
    for (byte x = 0; x <= 9; x ++) WeightAvg += WeightBuf[x];
    WeightAvg *= 0.1;
    Ethanol = CalcEthanol(WeightAvg);
    /*
    // Uncomment the following code block for display testing with fake ethanol values
    eTest ++;
    if (eTest == 100) eTest = 0;
    Ethanol = eTest;
    */
  }

  // Complete screen redraw takes about 2.5 seconds with the Adafruit_ILI9341 library
  // This keeps all of the latest stats on the screen for 5 seconds before refreshing
  if (CurrentTime - ScreenCounter >= 7500) {
    EthanolUpdate();
    TempUpdate();
    TimeUpdate("");
    ScreenCounter = CurrentTime;
  }

  // Build the data block to be sent to the RPi Smart Still Controller once every second
  if (CurrentTime - SerialCounter >= 1000) {
    char WeightLog[25];
    sprintf(WeightLog,"%.2f %.2f",WeightBuf[9],WeightAvg);
    for (byte x = 0; x <= 98; x ++) FlowBuf[x] = FlowBuf[x + 1];
    FlowBuf[99] = PulseCounter;
    for (byte x = 0; x <= 99; x ++) FlowTotal += FlowBuf[x];
    FlowTotal *= 0.01;
    if (eToggle) TimeUpdate(WeightLog);
    Serial.print("Uptime: ");
    Serial.println(Uptime);
    Serial.print("Weight: ");
    Serial.println(WeightLog);
    Serial.print("Flow: ");
    Serial.println(FlowTotal);
    Serial.print("Ethanol: ");
    Serial.println(Ethanol);
    Serial.print("TempC: ");
    Serial.println(TempC,1);
    Serial.println("#"); // Pound signs mark the start and end of data blocks to the Raspberry PI
    SerialCounter = CurrentTime;
    PulseCounter  = 0;
  }
}
//------------------------------------------------------------------------------------------------
