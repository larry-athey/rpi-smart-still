<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
//---------------------------------------------------------------------------------------------
function DebugMessage($Msg) {
  if (trim($Msg) == "") exit;
  shell_exec("/usr/bin/espeak -v english-us -s 160 \"$Msg\" 2> /dev/null &");
}
//---------------------------------------------------------------------------------------------
function SpeakMessage($ID) {
  $DBcnx    = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM logic_tracker WHERE ID=1");
  $Logic    = mysqli_fetch_assoc($Result);

  $Msg[0]   = "Increasing condenser cooling valve position";
  $Msg[1]   = "Decreasing condenser cooling valve position";
  $Msg[2]   = "Increasing dephlegmator cooling valve position";
  $Msg[3]   = "Decreasing dephlegmator cooling valve position";
  $Msg[4]   = "Increasing heating stepper motor position";
  $Msg[5]   = "Decreasing heating stepper motor position";
  $Msg[6]   = "Starting a new distillation run using the program named. " . $Program["program_name"];
  $Msg[7]   = "Stopping the current distillation run. Shutting down the boiler and cooling valves";
  $Msg[8]   = "Please turn on your boiler's heating control to its highest setting at this time";
  $Msg[9]   = "Please turn off your boiler's heating control at this time";
  $Msg[10]  = "Boiler has reached minimum operating temperature. Reducing heat to " . $Program["heating_idle"] . " steps";
  $Msg[11]  = "Boiler has reached minimum operating temperature. Please reduce your heat to " . $Program["heating_idle"] . " steps";
  $Msg[12]  = "Boiler is under temperature. Increasing heat to " . $Settings["heating_position"] . " steps";
  $Msg[13]  = "Boiler is over temperature. Decreasing heat to " . $Settings["heating_position"] . " steps";
  $Msg[14]  = "Boiler is under temperature. Please increase your heat a notch or two";
  $Msg[15]  = "Boiler is over temperature. Please decrease your heat a notch or two";
  $Msg[16]  = "Column has reached minimum operating temperature";
  $Msg[17]  = "Dephlegmator has reached minimum operating temperature";
  $Msg[18]  = "Column is under temperature. Please increase your heat a notch or two";
  $Msg[19]  = "Column is over temperature. Please decrease your heat a notch or two";
  $Msg[20]  = "Column is under temperature. Increasing heat to " . $Settings["heating_position"] . " steps";
  $Msg[21]  = "Column is over temperature. Decreasing heat to " . $Settings["heating_position"] . " steps";
  $Msg[22]  = "Dephlegmator is under temperature. Decreasing cooling water flow";
  $Msg[23]  = "Dephlegmator is over temperature. Increasing cooling water flow";
  $Msg[24]  = "Calibrating condenser valve";
  $Msg[25]  = "Condenser valve reported " . $Settings["valve1_total"] . " total pulses. Each 1% movement equals " . $Settings["valve1_pulse"] . " pulses";
  $Msg[26]  = "Calibrating dephlegmator valve";
  $Msg[27]  = "Dephlegmator valve reported " . $Settings["valve2_total"] . " total pulses. Each 1% movement equals " . $Settings["valve2_pulse"] . " pulses";
  $Msg[28]  = "Turning off the cooling valves in 2 minutes";
  $Msg[29]  = "Setting the cooling valves to their starting positions according to the current program";
  $Msg[30]  = "Manual adjustment of heating stepper motor to " . $Settings["heating_position"] . " steps";
  $Msg[31]  = "Pot still mode minimum ABV has been reached";
  $Msg[32]  = "Sending the command to recalibrate the hydrometer load cell";
  $Msg[33]  = "Sending the command to reboot the hydrometer";
  $Msg[34]  = "Distillate is too warm. Increasing condenser cooling water flow";

  shell_exec("/usr/bin/espeak -v english-us -s 160 \"$Msg[$ID]\" 2> /dev/null &");
  mysqli_close($DBcnx);
}
//---------------------------------------------------------------------------------------------
?>
