//------------------------------------------------------------------------------------------------
// *** Currently rebuilding this function due to a change in load cell weight capacity.
//
// Calculations based on a 64 gram (2.4 ounce) stainless steel ball reference weight suspended
// mid-way into a 125 ml (4 ounce) vessel of 18C/65F to 21C/70F distillate. Deviating from these
// specifications will render the hydrometer readings inaccurate. This shouldn't be a problem if
// you have adequate ground/tap temperature water flowing through your condenser.
//
// NOTE: If you are using an Arduino Uno or Nano, you'll need to trim down the calculation range
// to the realistic distillate range that you will actually be producing. As in, change the first
// useful "else if" to an "if" and then comment out everything else outside of the range that you
// expect to produce. Adjust as necessary until the code fits into the memory of your device.
//
// *** Sorry for the overly long if-else branch, it uses less memory than a switch-case branch.
//------------------------------------------------------------------------------------------------
byte CalcEthanol(float Weight) {
  if ((Weight >= 57.11) && (Weight < 57.15)) {
    return 71;
  } else if ((Weight >= 57.15) && (Weight < 57.19)) {
    return 72;
  } else if ((Weight >= 57.19) && (Weight < 57.25)) {
    return 73;
  } else if ((Weight >= 57.25) && (Weight < 57.30)) {
    return 74;
  } else {
    return 0;
  }
}
//------------------------------------------------------------------------------------------------