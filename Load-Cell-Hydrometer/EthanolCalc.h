//------------------------------------------------------------------------------------------------
// Calculations based on a 64 gram (2.4 ounce) stainless steel ball reference weight suspended
// mid-way into a 125 ml (4 ounce) vessel of 20C/68F to 21C/70F distillate. Deviating from these
// specifications will affect the accuracy of the hydrometer readings inaccurate. This shouldn't
// be a problem if you have adequate ground/tap temperature water flowing through your condenser.
//
// As you likely remember from elementary school science class, metals expand with heat and will
// contract when cooled. With the load cell being made of aluminum, which is a heat conductor
// used in electronics heat sinks, temperature will affect the load cell readings. You will want
// to keep this hydrometer a fair distance from your still so it lives in a stable environment.
//
// Sorry for the overly long if-else branch, it uses less code space than a switch-case branch.
// There is no math formula that I know of to calculate this, and as you can tell by looking at
// a glass hydrometer, this isn't a linear scale. Meaning, the distance between 100% and 90% is
// much greater than the distance between 0 and 10%.
//
// Due to the precision of the HX711 library only working down to 1/100th of a gram, the ethanol
// percentage calculation can't be real accurate at really low ABV levels. This really shouldn't
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
  // 100% = 57.42
  // 90% = 57.20
  // 80% = 57.00
  // 70% = 56.80
  // 60% = 56.65
  // 50% = 56.50
  // 40% = 56.35
  // 30% = 56.20
  // 20% = 56.12
  // 10% = 56.06
  // 0% = 56.00 (Bouyancy in pure distilled water)
  if ((Weight <= 57.42) && (Weight > 57.20)) {
    return 100;
  } else if ((Weight <= 57.20) && (Weight > 57.00)) {
    return 90;
  } else if ((Weight <= 57.00) && (Weight > 56.80)) {
    return 80;
  } else if ((Weight <= 56.80) && (Weight > 56.65)) {
    return 70;
  } else if ((Weight <= 56.65) && (Weight > 56.50)) {
    return 60;
  } else if ((Weight <= 56.50) && (Weight > 56.35)) {
    return 50;
  } else if ((Weight <= 56.35) && (Weight > 56.20)) {
    return 40;
  } else if ((Weight <= 56.20) && (Weight > 56.12)) {
    return 30;
  } else if ((Weight <= 56.12) && (Weight > 56.06)) {
    return 20;
  } else if ((Weight <= 56.06) && (Weight > 56.00)) {
    return 10;
  } else {
    return 0;
  }
}
//------------------------------------------------------------------------------------------------