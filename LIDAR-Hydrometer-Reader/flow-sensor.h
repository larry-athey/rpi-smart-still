//------------------------------------------------------------------------------------------------
// RPi Smart Still Controller | (CopyLeft) 2024-Present | Larry Athey (https://panhandleponics.com)
// Bird Brain v1.2.1 - LIDAR Hydrometer Reader and Parrot Flow Monitor - Released November 23, 2024
// 
// This is a custom library for turning the capacitance readings from the custom flow sensor into
// a coherent 0% to 100% reading. Please use Capacitance_Meter.ino to fine tune your flow sensor.
//------------------------------------------------------------------------------------------------
#define SENSE_PIN 39  // Capacitor under test is connected between this pin and ESP32 +3.3v pin
                      // Shielded cable should be used if the capacitor connection is distant
#define CHARGE_PIN 23 // 1 or 2 meg resistor connected between this pin and SENSE_PIN
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
int getFlowSensor() { // Convert the flow sensor capacitance value to a coherent flow rate
  int emptyValue = 34; // Sensor reading when the vessel is empty, adjust as necessary
  int fullValue = 24;  // Sensor reading when the vessel is full, adjust as necessary
  int range = emptyValue - fullValue; // Calculate the range
  // Ensure that we're not dividing by zero
  if (range == 0) {
    return 0;
  }

  doubleLong ADCvalues = measureADC(numMeasurements,CHARGE_PIN,SENSE_PIN,chargeTime_us,dischargeTime_ms);
  capVoltage = (Vref * ADCvalues.charged_value) / (float)MAX_ADC_VALUE;
  capacitance_pf = -1.0 * ((chargeTime_us) / resistor_mohm ) / log(1 - (capVoltage / Vref));
  if (capacitance_pf > 0.0) capacitance_pf -= 9.0;

  // Calculate percentage. Since lower numbers indicate fuller, we subtract from emptyValue
  int percentage = round(((emptyValue - capacitance_pf) / range) * 100);
  // Ensure the percentage is between 0% and 100%
  return min(max(percentage,0),100);
}
//------------------------------------------------------------------------------------------------
