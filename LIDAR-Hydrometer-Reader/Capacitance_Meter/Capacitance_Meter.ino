//------------------------------------------------------------------------------------------------
// RPi Smart Still Controller | (CopyLeft) 2024-Present | Larry Athey (https://panhandleponics.com)
// Bird Brain v1.2.1 - LIDAR Hydrometer Reader and Parrot Flow Monitor - Released November 3, 2024
//
// Compile and upload this code to your ESP32 and watch the capacitance value in the IDE terminal.
//
// Edit the emptyValue and fullValue variables in the getFlowSensor() function to fine-tune it to
// your flow sensor, then carry those values over to the same variables in the function by the same
// name in the flow-sensor.h library. After that, you can compile and upload the LIDAR Hydrometer
// Reader code to your ESP32 and your flow sensor will be properly calibrated to work with it.
//
// Remember, you are actually building an air-gap capacitor here. The length, width, thickness of
// the plates and the gap between the plates all have an effect on its readings. The beauty is the
// readings don't have to be precise. You just examine your readings as the unit fills and drains,
// then adjust the above mentioned varables to suit your flow sensor.
//
// Also keep in mind that a 100% dry flow sensor will read higher than one with moisture inside.
// Temperature will also affect this reading due to expansion and contraction of the plates. You
// should perform your calibration using distillate (not water) roughly the same temperature as
// it is when it comes out of your still.
//------------------------------------------------------------------------------------------------
#define SENSE_PIN 39   // Capacitor under test is connected between this pin and ESP32 +3.3v pin
                       // Shielded cable should be used if the capacitor connection is distant
#define CHARGE_PIN 23  // 1 or 2 meg resistor connected between this pin and SENSE_PIN
//------------------------------------------------------------------------------------------------
struct doubleLong {
  long charged_value;
  long discharged_value;
};
//------------------------------------------------------------------------------------------------
const byte chargeTime_us = 84; // ESP32 CPU instructions take ~54us
const byte dischargeTime_ms = 40;

const byte numMeasurements = 40;
const uint16_t MAX_ADC_VALUE = 4095;
const float resistor_mohm = 1.03; // 1 meg resistor seems to produce tighter capacitance values //2.035;
const float Vref = 3.3;

float capacitance_pf = 0;
float capVoltage = 0;
//------------------------------------------------------------------------------------------------
doubleLong measureADC(int num_measurements,byte charge_pin,byte sense_pin,byte charge_time_us,byte discharge_time_ms) {
  long charged_val = 0;
  long discharged_val = 0;
  for (int i = 0; i < num_measurements; i ++) {
    digitalWrite(charge_pin,HIGH); // Charge
    delayMicroseconds(charge_time_us - 54);
    charged_val += analogRead(sense_pin); // Read value and store
    digitalWrite(charge_pin,LOW); // Discharge
    delay(discharge_time_ms);
    discharged_val += analogRead(sense_pin);
    //if (analogRead(sense_pin) > 150) Serial.println("Error: discharged voltage too high");
  }
  return {charged_val /= num_measurements,discharged_val /= num_measurements};
}
//------------------------------------------------------------------------------------------------
int getFlowSensor(float sensorReading) { // Convert the capacitance to a coherent flow rate
  int emptyValue = 35; // Sensor reading when the vessel is empty, adjust as necessaey
  int fullValue = 18;  // Sensor reading when the vessel is full, adjust as necessaey
  // Sensor reading when the vessel is full
  int range = emptyValue - fullValue; // Calculate the range
  // Ensure that we're not dividing by zero
  if (range == 0) {
    return 0;
  }
  // Calculate percentage. Since lower numbers indicate fuller, we subtract from emptyValue
  int percentage = round(((emptyValue - sensorReading) / range) * 100);
  // Ensure the percentage is between 0% and 100%
  return min(max(percentage,0),100);
}
//------------------------------------------------------------------------------------------------
void setup() {
  Serial.begin(9600);
  while (! Serial) delay(10);

  pinMode(SENSE_PIN,INPUT);
  pinMode(CHARGE_PIN,OUTPUT);

  digitalWrite(CHARGE_PIN,LOW);
  delay(100);
}
//------------------------------------------------------------------------------------------------
void loop() {
  doubleLong ADCvalues = measureADC(numMeasurements,CHARGE_PIN,SENSE_PIN,chargeTime_us,dischargeTime_ms);

  // Calculate capacitance
  capVoltage = (Vref * ADCvalues.charged_value) / (float)MAX_ADC_VALUE;
  capacitance_pf = -1.0 * ((chargeTime_us) / resistor_mohm ) / log(1 - (capVoltage / Vref));
  if (capacitance_pf > 0.0) capacitance_pf -= 9.0;

  // Capacitance is more accurate the closer the capacitor is to the ESP32 or when connected with shielded cable if at a distance
  // Less than 500pf is 0% flow, 1000pf would be 100% flow. Only appears to be reactive to ethanol, not plain water.
  Serial.print("\n\n");
  Serial.print(capacitance_pf); Serial.print("pf | "); Serial.print(ADCvalues.charged_value); Serial.print(" | "); Serial.println(ADCvalues.discharged_value);
  Serial.print("Flow Rate: "); Serial.println(getFlowSensor(capacitance_pf));
  Serial.print("Uptime: "); Serial.println(millis());
  Serial.flush();

  digitalWrite(CHARGE_PIN,LOW);
  delay(1000);
}
//------------------------------------------------------------------------------------------------
