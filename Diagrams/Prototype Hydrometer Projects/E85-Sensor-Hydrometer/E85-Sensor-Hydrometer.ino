//------------------------------------------------------------------------------------------------
// Written by Larry Athey (https://panhandleponics.com) v1.0.1 released July 12, 2023
//
// NOTE: This project is no longer supported by the RPi Smart Still system. This code is merely
// provided for example purposes to show another method for creating a digital hydrometer.
//
// This project is based on an Arduino Uno/Nano/Mega/Micro and any 320x240 color LCD/TFT display
// that uses SPI communications. Parallel shields are completely useless since they steal GPIO D8
// and D4 which are required to use Arduino timer1 as an externally triggered frequency counter. 
//
// This should work with any automotive Flex-Fuel E85 sensor since they all output a 50 to 150 Hz
// square wave to represent a 0% to 100% ethanol concentration in gasoline. They also pulse this
// square wave on a duty cycle to represent the fuel temperature (1ms = -40C up to 5ms = +80C).
// That's right, it's all analog, these sensors don't use a digital protocol. Their output is a
// low frequency so they can be used over long unshielded wires and still be immune to noise and
// RF interference.
//
// Now comes the curve ball. When using these sensors with distillate from a still, the readings
// are completely different from gasoline. If it was possible to have 100% ethanol, they would
// output 150 Hz. But as you go lower in proof (meaning, more water in it) the frequency goes up.
// The reason for this is that they really are not an ethanol sensor, it's actually a capacitor
// that changes value depending on the density of the dielectric. E-85 Ethanol has 15% water in
// it, the more water in the sensor, the higher the output frequency. The oscillator is tuned so
// that 100% gasoline will result in <= 50 Hz and will increase based on the water content.
//
// Liquid Densities: Gasoline = 6.25#/gallon, Ethanol = 6.53#/gallon, Water = 8.35#/gallon
//
// ---> This is not a scientifically precise method for reading ethanol content. Those handheld
// digital alcometers used in distilleries use something called an Oscillating U-Tube device for
// precision liquid density measurements. That is why those cost a couple thousand dollars each.
// See https://en.wikipedia.org/wiki/Oscillating_U-tube for more information. The next best way
// to read ethanol percentage would require moving parts, such as monitoring a reference weight
// in distillate using a load cell, or reading the height of a hydrometer floating in a parrot.
//
// The sensor reading routines are adapted from https://github.com/dalathegreat/Arduino-Flex-Fuel
// and stripped down to reduce code slack and eliminate the PWM output to a car's computer. This
// project is a component of my Raspberry PI (or clone) based still monitor/controller for those
// home distillers that want a DIY smart still or just a way to record progress of distillation
// runs. However, this can be used as a stand-alone digital hydrometer which is way cheaper than
// those obscenely over-priced handheld digital alcometers that distilleries use.
//
// The only external component needed is a 4.7K pull up resistor from the 5 volt pin to the GPIO
// in pin. Since this project needs to run on 12 volts due to the ethanol sensor, I'd recommend
// sticking a Raspberry PI 3 CPU heatsink to the voltage regulator on an Uno or Mega. I really
// don't understand why those things use a linear voltage regulator instead of a buck regulator.
//
// As to the question of whether or not this code is compatible with an ESP32, no it is not, and
// I can't help you with that. I have tried reading the output of the sensor with the frequency
// counter library for the ESP32 and the PWM output of the sensor interferes with the reliability
// of the library's readings. Strange that my cheap little pocket oscilloscope can read it just
// fine, but not an ESP32. If somebody else can do it, my hat is off to you, I gave up on it.
//------------------------------------------------------------------------------------------------
#include "Adafruit_ILI9341.h" // TFT display library add-on to Adafruit GFX (sadly, it's slow)
#include "FreeSans10pt7b.h"   // https://github.com/moononournation/ArduinoFreeFontFile.git
#include "FreeDefaultFonts.h" // Included in MCUFRIEND_kbv (used for the 7-segment LED style font)
//------------------------------------------------------------------------------------------------
#define IN_PIN 4 // Arduino timer1 is the only one that supports input triggering
                 // ATmega328 listens on pin D8, ATmega32U4 and Atmega2560 listen on pin D4

// Arduino Uno pin mapping for SPI
//#define TFT_RST 7
//#define TFT_DC 9
//#define TFT_CS 10
//#define TFT_MOSI 11
//#define TFT_MISO 12
//#define TFT_CLK 13
//#define TFT_LED A0

// Arduino Nano pin mapping for SPI
//#define TFT_RST 7
//#define TFT_DC 9
//#define TFT_CS 13
//#define TFT_MISO 15
//#define TFT_MOSI 14
//#define TFT_CLK 16
//#define TFT_LED A0

// Arduino Mega pin mapping for SPI
//#define TFT_RST 7
//#define TFT_DC 9
//#define TFT_MISO 50
//#define TFT_MOSI 51
//#define TFT_CLK 52
//#define TFT_CS 53
//#define TFT_LED A0

// Arduino Pro Micro pin mapping for SPI
#define TFT_RST 7
#define TFT_DC 9
#define TFT_CS 10
#define TFT_MISO 14
#define TFT_CLK 15
#define TFT_MOSI 16
#define TFT_LED A0
//------------------------------------------------------------------------------------------------
Adafruit_ILI9341 tft = Adafruit_ILI9341(TFT_CS,TFT_DC,TFT_MOSI,TFT_CLK,TFT_RST,TFT_MISO);

// The following four variables are parts of the original Arduino Flex-Fuel project on GitHub
volatile uint16_t revTick; // Tick counter used in the Arduino timer1 based frequency counter
static long highTime = 0;  // The following three variables are used in reading the ethanol temperature
static long lowTime = 0;   // Not sure why the author of this routine made these global variables
static long tempPulse;     // I'm leaving these as-is since they're not hurting anything this way

int HZ = 0;                // Global placeholder for ethanol sensor frequency
int Ethanol = 0;           // Global placeholder for ethanol percentage
float TempC = 0;           // Global placeholder for ethanol temperature reading
bool eToggle = true;       // Ethanol display toggle byte (false=%ABV or true=Proof)
long EthanolCounter;       // Timekeeper for ethanol display update
long TempCounter;          // Timekeeper for temperature display update
long FreqCounter;          // Timekeeper for frequency display update
long SerialCounter;        // Timekeeper for the serial output to the RPI still monitor/controller
byte eTest = 50;           // This is only used by the code block in loop() for display testing
//------------------------------------------------------------------------------------------------
void setupTimer() {        // Set up Arduino timer1 (we need a timer that supports external triggering)
  TCCR1A = 0;              // Select normal mode
  TCCR1B = 132;            // (10000100) Falling edge trigger, Timer = CPU Clock/256, noise cancellation on
  TCCR1C = 0;              // Normal mode
  TIMSK1 = 33;             // (00100001) Input capture and overflow interupts enabled
  TCNT1  = 0;              // Start from 0
}
//------------------------------------------------------------------------------------------------
ISR(TIMER1_CAPT_vect) {    // Ethanol sensor square wave cycle end detected
  revTick = ICR1;          // Record the duration of the last square wave cycle for frequency calculation
  TCNT1   = 0;             // Restart timer1 to read the next square wave cycle time
}
//------------------------------------------------------------------------------------------------
ISR(TIMER1_OVF_vect) {     // Timer1 overflow/timeout detected
  revTick = 0;             // Reset the recorded square wave cycle time value
}
//------------------------------------------------------------------------------------------------
void setup() {
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
  tft.print("Ethanol Sensor Frequency:");
  FreqUpdate();
  TempUpdate();
  EthanolUpdate();

  Serial.begin(9600);
  while (! Serial) delay(1);
  Serial.println("");
  pinMode(IN_PIN,INPUT);
  setupTimer();
  EthanolCounter = millis();
  TempCounter = EthanolCounter;
  FreqCounter = EthanolCounter;
  SerialCounter = EthanolCounter;
}
//------------------------------------------------------------------------------------------------
void getTemperature() { // Update the distillate TempC value (from the Arduino Flex-Fuel project)
  float rawTemp = 0;    // NOTE: ACDelco 13507128 does not have temperature reporting capabilities
  int Duty;

  tempPulse = pulseIn(IN_PIN,HIGH);
  if (tempPulse > highTime) highTime = tempPulse;

  tempPulse = pulseIn(IN_PIN,LOW);
  if (tempPulse > lowTime) lowTime = tempPulse;

  Duty = ((100 * (highTime / (double(lowTime + highTime))))); // Calculate Duty cycle (integer extra decimal)
  float T = (float(1.0 / float(HZ)));                         // Calculate total Period time
  float Period = float(100 - Duty) * T;                       // Calculate the active Period time (100-Duty)*T
  float temp2 = float(10) * float(Period);                    // Convert ms to whole number
  rawTemp = ((40.25 * temp2) - 81.25);                        // Calculate rawTemp (1ms = -40C, 5ms = +80C)
  TempC = int(rawTemp);
  TempC = TempC * 0.1;
}
//------------------------------------------------------------------------------------------------
void getEthanol() { // Calculate the ethanol percentage from the sensor output frequency
  // This code block is how you would calculate ethanol in gasoline, this doesn't work for distillation
  //if (HZ > 50) {
  //  Ethanol = HZ - 50;
  //  if (Ethanol > 100) Ethanol = 100;
  //} else {
  //  Ethanol = 0;
  //}
  // Distillate percentage is different because water is more dense than ethanol and raises the frequency
  if (HZ > 150) {
    Ethanol = 250 - HZ; // Sensor outputs 250 Hz with pure distilled water, density reduces with ethanol
    if (Ethanol > 100) Ethanol = 100; // Two safety nets to keep the ethanol reading between 0% and 100%
    if (Ethanol < 0) Ethanol = 0;
  } else {
    Ethanol = 0;
  }
}
//------------------------------------------------------------------------------------------------
void EthanolUpdate() { // Update the ethanol value on the display
  byte Reading;
  eToggle = ! eToggle;
  if (eToggle) {
    Reading = Ethanol * 2;
  } else {
    Reading = Ethanol;
  }
  if (Ethanol <= 19) {
    tft.setTextColor(ILI9341_BLUE);
  } else if ((Ethanol > 19) && (Ethanol <= 29)) {
    tft.setTextColor(ILI9341_CYAN);
  } else if ((Ethanol > 29) && (Ethanol <= 49)) {
    tft.setTextColor(ILI9341_GREEN);
  } else if ((Ethanol > 49) && (Ethanol <= 64)) {
    tft.setTextColor(ILI9341_YELLOW);
  } else if ((Ethanol > 64) && (Ethanol <= 84)) {
    tft.setTextColor(ILI9341_MAGENTA);
  } else {
    tft.setTextColor(ILI9341_RED);
  }
  if (Reading > 99) {
    tft.setCursor(40,171);
  } else {
    tft.setCursor(90,171);
  }
  tft.setFont(&FreeSevenSegNumFont);
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
  EthanolCounter = millis();
}
//------------------------------------------------------------------------------------------------
void TempUpdate() { // Update the distillate temperature on the display
  String cStr = String(TempC,1);
  String fStr = String(TempC * 9 / 5 + 32,1);
  tft.setFont(&FreeSans10pt7b);
  tft.setTextSize(1);
  tft.setTextColor(ILI9341_DARKGREY);
  tft.fillRect(140,1,155,24,ILI9341_BLACK);
  tft.setCursor(145,19);
  tft.print(cStr + "C / " + fStr + "F");
  TempCounter = millis();
}
//------------------------------------------------------------------------------------------------
void FreqUpdate() { // Update the sensor frequency value on the display
  tft.setFont(&FreeSans10pt7b);
  tft.setTextSize(1);
  tft.setTextColor(ILI9341_DARKGREEN);
  tft.fillRect(245,215,75,24,ILI9341_BLACK);
  tft.setCursor(247,233);
  tft.print(String(HZ) + " Hz");
  FreqCounter = millis();
}
//------------------------------------------------------------------------------------------------
void loop() {
  long CurrentTime = millis();
  char Uptime[10];
  unsigned long allSeconds = CurrentTime / 1000;
  int runHours = allSeconds / 3600;
  int secsRemaining = allSeconds % 3600;
  int runMinutes = secsRemaining / 60;
  int runSeconds = secsRemaining % 60;
  sprintf(Uptime,"%02u:%02u:%02u",runHours,runMinutes,runSeconds);

  if (revTick > 0) {      // Prevent division by zero, a zero is due to a timer1 overflow/timeout
    HZ = 63000 / revTick; // 3456000 ticks per minute, 57600 per second
  } else {                // 63000 ticks seems to be more accurate for my ACDelco 13577429
    HZ = 0;               // Adjust as necessary by comparison to an actual glass hydrometer
  }

  getTemperature();
  getEthanol();

/*
  // Uncomment the following code block for display testing
  TempC = random(18,25);
  eTest ++;
  if (eTest == 150) eTest = 50;
  HZ = eTest;
*/

  // I have to use stupid timing in screen updates to distract from the screen flickering because
  // my TFT screen is too large for off-screen 16 bit buffers due to the Arduino's limited memory
  if (CurrentTime - EthanolCounter >= 5000) EthanolUpdate();
  if (CurrentTime - TempCounter >= 6000) TempUpdate();
  if (CurrentTime - FreqCounter >= 1000) FreqUpdate();

  // Communications to my Raspberry PI based still monitor/controller uses 9600 baud serial data
  if (CurrentTime - SerialCounter >= 1000) {
    Serial.print("Uptime: ");
    Serial.println(Uptime);
    Serial.print("revTick: ");
    Serial.println(revTick);
    Serial.print("Frequency: ");
    Serial.println(HZ);
    Serial.print("Ethanol: ");
    Serial.println(Ethanol);
    Serial.print("TempC: ");
    Serial.println(TempC,1);
    Serial.println("#"); // Pound signs mark the start and end of data blocks to the Raspberry PI
    SerialCounter = CurrentTime;
  }
}
//------------------------------------------------------------------------------------------------
