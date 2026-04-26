//------------------------------------------------------------------------------------------------
// RPi Smart Still Controller | (CopyLeft) 2024-Present | Larry Athey (https://panhandleponics.com)
//
// This may or may not be needed/wanted, depending on your build. All of the PWM capable pins of
// the Pi GPIO bus are used by the hat. If you want a variable speed cooling fan that follows the
// CPU temperature, this will need to be don via USB.
//
// Some people are fine with the CPU fan running full speed all the time and can safely connect
// the fan to the 5 volt or 12 volt supply lines. I prefer a ball bearing 5 volt fan that adjusts
// its speed as needed depending on the CPU temperature.
//
//------------------------------------------------------------------------------------------------
// USB interfaced PWM fan controller
// MCU target: Seeed Studio XIAO SAMD21
// Receives CPU temperature (as plain text number + newline, e.g. "42" or "55.3")
// Drives a 5V fan via 2N3904 NPN transistor (base through 1K resistor)
//
// Wiring abstract:
// - XIAO 5V or USB powered
// - Fan +5V to your 5V rail
// - Fan GND to collector of 2N3904
// - Emitter of 2N3904 to GND
// - MCU pin -> 1K resistor -> base of 2N3904
//
// Upload this sketch to the XIAO (select "Seeed XIAO" board in Arduino IDE)
//
// Below is a bash script that you can launch with /etc/rc.local as a detached process.
//
// #!/bin/bash
// # Simple loop that checks CPU temp every 5 seconds and sends it to the fan controller
// while true; do
//   TEMP=$(cat /sys/class/thermal/thermal_zone0/temp | awk '{print $1/1000}')
//   echo "$TEMP" > /dev/ttyACM0
//   sleep 5
// done
//------------------------------------------------------------------------------------------------
const int FAN_PWM_PIN = 5;        // Change if necessary. PWM-capable pins on XIAO SAMD21: 0,1,3,4,5,6,8,9,10,11,12,13
const float MIN_TEMP = 40.0;      // Below this temp = fan off (0%)
const float MAX_TEMP = 55.0;      // At or above this temp = fan 100%
const unsigned long SERIAL_TIMEOUT = 100; // ms to wait for full line (safety)

float currentTemp = 0.0;
unsigned long lastUpdate = 0;
//------------------------------------------------------------------------------------------------
void setup() {
  pinMode(FAN_PWM_PIN,OUTPUT);
  analogWrite(FAN_PWM_PIN,0);     // Start with fan off

  Serial.begin(115200);           // USB serial connection to Pi (baud rate doesn't really matter for USB)
  while (! Serial);               // Wait for USB serial to connect

  Serial.println("USB PWM Fan Controller ready");
  Serial.println("Send temperature as a number followed by newline, e.g. echo \"42\" > /dev/ttyACM0");
}
//------------------------------------------------------------------------------------------------
void updateFan() {
  int pwmValue = 0;

  if (currentTemp >= MAX_TEMP) {
    pwmValue = 255;
  } else if (currentTemp <= MIN_TEMP) {
    pwmValue = 0;
  } else {
    float range = MAX_TEMP - MIN_TEMP;
    float normalized = (currentTemp - MIN_TEMP) / range;
    pwmValue = (int)(normalized * 255.0);
  }

  analogWrite(FAN_PWM_PIN, pwmValue);

  Serial.print("Temp: ");
  Serial.print(currentTemp);
  Serial.print("°C → PWM: ");
  Serial.println(pwmValue);
}
//------------------------------------------------------------------------------------------------
void loop() {
  // Read complete lines from serial (non-blocking)
  if (Serial.available()) {
    String input = Serial.readStringUntil('\n');
    input.trim();

    if (input.length() > 0) {
      float temp = input.toFloat();

      if (temp > 0.0 && temp < 120.0) { // reasonable CPU temp range
        currentTemp = temp;
        updateFan();
      } else {
        Serial.println("Invalid temp");
      }
    }
  }

  // Force an update every 10 seconds even if no new data is received (keeps the fan running smoothly)
  if (millis() - lastUpdate > 10000) {
    updateFan();
    lastUpdate = millis();
  }
}
//------------------------------------------------------------------------------------------------
