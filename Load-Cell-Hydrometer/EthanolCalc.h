//------------------------------------------------------------------------------------------------
// *** Currently rebuilding this function due to a change in load cell weight capacity.
//
// Calculations based on a 64 gram (2.4 ounce) stainless steel ball reference weight suspended
// mid-way into a 125 ml (4 ounce) vessel of 21C/70F to 24C/75F distillate. Deviating from these
// specifications will render the hydrometer readings inaccurate. This shouldn't be a problem if
// you have adequate ground/tap temperature water flowing through your condenser.
//
// Sorry for the overly long if-else branch, it uses less code space than a switch-case branch.
// There is no math formula that I know of to calculate this, and as you can tell by looking at
// a glass hydrometer, this isn't a linear scale. Meaning, the distance between 100% and 90% is
// much longer than the distance between 0 and 10%.
//
// NOTE: If you are using an Arduino Uno or Nano, you'll need to trim down the calculation range
// to the realistic distillate range that you will actually be producing. As in, change the first
// useful "else if" to an "if" and then comment out everything else outside of the range that you
// expect to produce. Adjust as necessary until the code fits into the memory of your device.
//------------------------------------------------------------------------------------------------
byte CalcEthanol(float Weight) {
  // 57.49 = 100%
  // 57.27 = 90%
  // 57.05 = 80%
  // 56.83 = 70%
  // 56.61 = 60%
  // 56.55 = 50%
  // 56.40 = 40%
  // = 30%
  // = 20%
  // = 10%
  if ((Weight >= 57.11) && (Weight < 57.15)) {
    return 71;
  } else if ((Weight >= 57.15) && (Weight < 57.19)) {
    return 72;
  } else if ((Weight >= 57.19) && (Weight < 57.25)) {
    return 73;
  } else if ((Weight >= 57.25) && (Weight < 57.30)) {
    return 74;
  } else { // Bouyancy in pure distilled water is 56.00
    return 0;
  }
}
//------------------------------------------------------------------------------------------------