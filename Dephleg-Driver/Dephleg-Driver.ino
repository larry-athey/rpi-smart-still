//------------------------------------------------------------------------------------------------
// RPi Smart Still Controller | (CopyLeft) 2024-Present | Larry Athey (https://panhandleponics.com)
//------------------------------------------------------------------------------------------------
// XIAO SAMD21 Powered Dephlegmator Cooling Valve Emulator for Peristaltic Pump
// Seeed Studio XIAO SAMD21 (Arduino IDE board name “Seeeduino XIAO”)
//
// Emulates a US Solid motorized ball valve (10 second full travel, with limit switches)
// while driving a TB6612FNG H-Bridge feeding a 400 ml/min peristaltic pump via PWM.
//
// - Watches RPi Smart Still dephleg valve OPEN / CLOSE GPIO lines
// - Accumulates actual command time so 10 ms micro-pulses are tracked
// - Advances/retracts duty by 1% for every 100 ms of command time
// - Reports limit-switch states back to the RPi Smart Still
// - Outputs PWM duty cycle 0-100% to a TB6612FNG H-Bridge
//
// This optional device may be required for those who are running a pump for their cooling water
// rather than running on well or city water pressure. This is because impeller pumps don't deliver
// the necessary pressure to force water through the ball valves if they are choked down to 30% or
// lower. This prevents the water flow through the dephlegmator from running low enough for reflux.
//
// The problem is that you can't achieve any balance between no output and switching right back to
// pot still mode. The way to solve the problem is to replace the ball valve with a peristaltic
// pump that by design has an incredibly low output volume. Rather than controlling the output of
// the pump with a valve, the speed of the motor is varied instead.
//
// I know what you're probably thinking...Why not just use a diaphragm pump instead? The answer is
// that while these may deliver enough pressure, they don't deliver enough volume to satisfy your
// condenser's needs. Secondly, those are not intended for continous duty and will shut down after
// a few hours due to overheating. Unlike an impeller pump, diaphragm pumps aren't water cooled.
//
// No printed circuit board is necessary since you'd still have more wires coming off this circuit
// than you would have PCB traces connecting the XIAO SAMD21 to the TB6612FNG H-Bridge.
//
// Parts List:
// - Seeed Studio XIAO SAMD21: https://www.seeedstudio.com/Seeeduino-XIAO-Arduino-Microcontroller-SAMD21-Cortex-M0+-p-4426.html
// - TB6612FNG H-Bridge: https://www.amazon.com/dp/B09MJ4XPXD
// - 400ml/minute peristaltic pump: https://www.amazon.com/dp/B07HB2NM74
//
// This whole unit can be built for the same price as a half inch US Solid motorized ball valve.
// You only need to provide +12v, +5V, and GND from the RPi hat. Logic level shifter U16 (v1.3)
// isn't needed now since wires from XIAO pins 1 and 2 plug into pins 1 and 5 of the U16 socket.
// XAIO pins 3 and 4 connect to the RPi hat screw terminals LS3 and LS4. (see pin comments below)
//------------------------------------------------------------------------------------------------
const int PIN_OPEN         = 1;   // RPi-SS dephleg valve "open / forward" GPIO 25 (logic level shifter socket)
const int PIN_CLOSE        = 2;   // RPi-SS dephleg valve "close / reverse" GPIO 24 (logic level shifter socket)
const int PIN_LIMIT_CLOSED = 3;   // Simulated closed limit switch (output to RPi-SS hat LS4)
const int PIN_LIMIT_OPEN   = 4;   // Simulated open limit switch (output to RPi-SS hat LS3)
const int PIN_PWM          = 10;  // PWM output to TB6612FNG H-Bridge (PWMA or PWMB)

// Active level of the Pi control signals (true = HIGH means "move")
const bool OPEN_ACTIVE_HIGH  = true;
const bool CLOSE_ACTIVE_HIGH = true;

// Active level of the limit-switch outputs (true = LOW means "at limit")
// Most of the original L298N + limit-switch setups are active-LOW
const bool LIMIT_ACTIVE_LOW = true;

// Timing
const unsigned long MS_PER_PERCENT = 100;  // 100 ms → 1 %  (10 s full scale)

// ========== GLOBAL STATE ==========
int duty = 0;                         // Current position / PWM duty 0-100
unsigned long openAccum  = 0;         // Accumulated open-command time (ms)
unsigned long closeAccum = 0;         // Accumulated close-command time (ms)
unsigned long lastLoop   = 0;
//------------------------------------------------------------------------------------------------
void setup() {
  pinMode(PIN_OPEN, INPUT);
  pinMode(PIN_CLOSE, INPUT);
  pinMode(PIN_LIMIT_CLOSED, OUTPUT);
  pinMode(PIN_LIMIT_OPEN, OUTPUT);
  pinMode(PIN_PWM, OUTPUT);

  // Optional: higher PWM resolution on SAMD21 (default is usually fine)
  // analogWriteResolution(8);   // 0-255
  // analogWriteResolution(10);  // 0-1023

  lastLoop = millis();
  updateLimits();
  analogWrite(PIN_PWM, 0);

  // Uncomment for debugging over USB Serial
  // Serial.begin(115200);
  // while (!Serial) { ; }
  // Serial.println("XIAO Valve Emulator ready");
}
//------------------------------------------------------------------------------------------------
void updateLimits() {
  // Closed limit
  if (LIMIT_ACTIVE_LOW) {
    digitalWrite(PIN_LIMIT_CLOSED, (duty == 0) ? LOW : HIGH);
  } else {
    digitalWrite(PIN_LIMIT_CLOSED, (duty == 0) ? HIGH : LOW);
  }

  // Open limit
  if (LIMIT_ACTIVE_LOW) {
    digitalWrite(PIN_LIMIT_OPEN, (duty == 100) ? LOW : HIGH);
  } else {
    digitalWrite(PIN_LIMIT_OPEN, (duty == 100) ? HIGH : LOW);
  }
}
//------------------------------------------------------------------------------------------------
void loop() {
  unsigned long now = millis();
  unsigned long dt  = now - lastLoop;
  lastLoop = now;

  // Read Pi commands (respect active level)
  bool wantOpen  = (digitalRead(PIN_OPEN)  == HIGH) == OPEN_ACTIVE_HIGH;
  bool wantClose = (digitalRead(PIN_CLOSE) == HIGH) == CLOSE_ACTIVE_HIGH;

  // Accumulate command time only when one direction is requested
  if (wantOpen && !wantClose) {
    openAccum += dt;
    while (openAccum >= MS_PER_PERCENT && duty < 100) {
      openAccum -= MS_PER_PERCENT;
      duty++;
    }
  } else if (wantClose && !wantOpen) {
    closeAccum += dt;
    while (closeAccum >= MS_PER_PERCENT && duty > 0) {
      closeAccum -= MS_PER_PERCENT;
      duty--;
    }
  }
  // If both idle or both active → hold position, keep any partial accumulators

  // Drive PWM (0-255 for 8-bit; change map() if you raised resolution)
  analogWrite(PIN_PWM, map(duty, 0, 100, 0, 255));

  // Update simulated limit switches
  updateLimits();

  // Optional debug output (uncomment if needed)
  // static unsigned long lastPrint = 0;
  // if (now - lastPrint >= 500) {
  //   lastPrint = now;
  //   Serial.print("Duty: "); Serial.print(duty);
  //   Serial.print("%  OpenAcc: "); Serial.print(openAccum);
  //   Serial.print("  CloseAcc: "); Serial.println(closeAccum);
  // }
}
//------------------------------------------------------------------------------------------------