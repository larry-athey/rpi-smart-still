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
// Due to the precision of the HX711 library only working down to 1/100th of a gram, the ethanol
// percentage calculation can't be dead accurate at really low ABV levels. This really shouldn't
// be a problem since you're so far into unwanted tails at that point. If I'm doing a pot still
// run, I stop at 35% and then start a new reflux mode run to strip out what's left at 90% with
// absolutely no flavor. I usually dump that in with the rest of the distillate for an ABV bump.
//
// NOTE: If you are using an Arduino Uno or Nano, you'll need to trim down the calculation range
// to the realistic distillate range that you will actually be producing. As in, change the first
// useful "else if" to an "if" and then comment out everything else outside of the range that you
// expect to produce. Adjust as necessary until the code fits into the memory of your device.
//------------------------------------------------------------------------------------------------
byte CalcEthanol(float Weight) {
  // 100% = 57.49
  // 90% = 57.27
  // 80% = 57.05
  // 70% = 56.83
  // 60% = 56.61
  // 50% = 56.55
  // 40% = 56.40
  // 30% =
  // 20% =
  // 10% =
  if ((Weight <= 57.49) && (Weight > 57.27)) {
    return 100;
  } else if ((Weight <= 57.27) && (Weight > 57.05)) {
    return 90;
  } else if ((Weight <= 57.05) && (Weight > 56.83)) {
    return 80;
  } else if ((Weight <= 56.83) && (Weight > 56.61)) {
    return 70;
  } else if ((Weight <= 56.61) && (Weight > 56.55)) {
    return 60;
  } else if ((Weight <= 56.55) && (Weight > 56.40)) {
    return 50;
  } else if ((Weight <= 56.40) && (Weight > 56.30)) {
    return 40;
  } else { // Bouyancy in pure distilled water is 56.00
    return 0;
  }
}
//------------------------------------------------------------------------------------------------