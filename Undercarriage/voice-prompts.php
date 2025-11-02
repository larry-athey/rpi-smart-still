<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
//---------------------------------------------------------------------------------------------
function CreatePrompt($Msg) {
  $Config = parse_ini_file("/usr/share/rpi-smart-still/config.ini");
  $DBcnx  = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
  $FName  = generateRandomString(20) . ".mp3";
  if (isset($Config["VOICE_PROMPTER"])) {
    $Config["VOICE_PROMPTER"] = str_replace("{MSG}","\"$Msg\"",$Config["VOICE_PROMPTER"]);
    shell_exec($Config["VOICE_PROMPTER"] . " | /usr/bin/ffmpeg -i - -ar 44100 -ac 2 -ab 192k -f mp3 /var/www/html/voice_prompts/$FName");
  } else {
    shell_exec("/usr/bin/espeak -v english-us -s 160 \"$Msg\" --stdout | /usr/bin/ffmpeg -i - -ar 44100 -ac 2 -ab 192k -f mp3 /var/www/html/voice_prompts/$FName");
  }
  shell_exec("/usr/bin/chown www-data:www-data /var/www/html/voice_prompts/$FName");
  $Insert = mysqli_query($DBcnx,"INSERT INTO voice_prompts (timestamp,filename,seen_by) VALUES (now(),'$FName','')");
  mysqli_close($DBcnx);
}
//---------------------------------------------------------------------------------------------
function DebugMessage($Msg) {
  if (trim($Msg) == "") exit;
  CreatePrompt($Msg);
}
//---------------------------------------------------------------------------------------------
function SpeakMessage($ID) {
  $DBcnx    = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);

  $Msg[0]   = "Increasing condenser cooling valve position";
  $Msg[1]   = "Decreasing condenser cooling valve position";
  $Msg[2]   = "Increasing dephlegmator cooling valve position";
  $Msg[3]   = "Decreasing dephlegmator cooling valve position";
  $Msg[4]   = "Increasing heating controller position";
  $Msg[5]   = "Decreasing heating controller position";
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
  if ($Boilermaker["enabled"] == 1) {
    $Msg[20] = "Column is under temperature. Increasing Boilermaker target temperature by 1 degree";
  } else {
    $Msg[20] = "Column is under temperature. Increasing heat to " . $Settings["heating_position"] . " steps";
  }
  if ($Boilermaker["enabled"] == 1) {
    $Msg[21] = "Column is over temperature. Decreasing Boilermaker target temperature by 1 degree";
  } else {
    $Msg[21] = "Column is over temperature. Decreasing heat to " . $Settings["heating_position"] . " steps";
  }
  $Msg[22]  = "Dephlegmator is under temperature. Decreasing cooling water flow";
  $Msg[23]  = "Dephlegmator is over temperature. Increasing cooling water flow";
  $Msg[24]  = "Calibrating condenser valve";
  $Msg[25]  = "Condenser valve reported " . $Settings["valve1_total"] . " total pulses. Each 1% movement equals " . $Settings["valve1_pulse"] . " pulses";
  $Msg[26]  = "Calibrating dephlegmator valve";
  $Msg[27]  = "Dephlegmator valve reported " . $Settings["valve2_total"] . " total pulses. Each 1% movement equals " . $Settings["valve2_pulse"] . " pulses";
  $Msg[28]  = "Turning off the cooling valves in 2 minutes";
  $Msg[29]  = "Setting the cooling valves to their starting positions according to the current program";
  $Msg[30]  = "Manual adjustment of heating controller to " . $Settings["heating_position"] . " steps";
  $Msg[31]  = "Pot still mode minimum acceptable ABV level has been reached";
  $Msg[32]  = "Sending the command to recalibrate the hydrometer load cell";
  $Msg[33]  = "Sending the command to reboot the hydrometer";
  $Msg[34]  = "Distillate is too warm. Increasing condenser cooling water flow";
  $Msg[35]  = "Pausing the current distillation run";
  $Msg[36]  = "Resuming the currently paused distillation run";
  $Msg[37]  = "Updating the hydrometer reader zero percent calibration slot";
  $Msg[38]  = "Updating the hydrometer reader 10 percent calibration slot";
  $Msg[39]  = "Updating the hydrometer reader 20 percent calibration slot";
  $Msg[40]  = "Updating the hydrometer reader 30 percent calibration slot";
  $Msg[41]  = "Updating the hydrometer reader 40 percent calibration slot";
  $Msg[42]  = "Updating the hydrometer reader 50 percent calibration slot";
  $Msg[43]  = "Updating the hydrometer reader 60 percent calibration slot";
  $Msg[44]  = "Updating the hydrometer reader 70 percent calibration slot";
  $Msg[45]  = "Updating the hydrometer reader 80 percent calibration slot";
  $Msg[46]  = "Updating the hydrometer reader 90 percent calibration slot";
  $Msg[47]  = "Updating the hydrometer reader 100 percent calibration slot";
  $Msg[48]  = "Distillate flow has dropped below the minimum acceptable level";
  $Msg[49]  = "Distillate ABV is under proof. Adjusting dephlegmator temperature range downward";
  $Msg[50]  = "Activating auxiliary relay number one";
  $Msg[51]  = "Deactivating auxiliary relay number one";
  $Msg[52]  = "Activating auxiliary relay number two";
  $Msg[53]  = "Deactivating auxiliary relay number two";
  $Msg[54]  = "Dephlegmator is at it's minimum closed position. There is a potential water flow problem";
  $Msg[55]  = "Dephlegmator is at it's maximum open position. There is a potential water flow problem";
  $Msg[56]  = "This system will reboot in 30 seconds";
  $Msg[57]  = "This system will shut down in 30 seconds";
  $Msg[58]  = "Boilermaker starting up in constant temperature mode";
  $Msg[59]  = "Boilermaker is enabled in the configuration. But is currently unreachable";
  $Msg[60]  = "Boilermaker has been stopped";
  $Msg[61]  = "Boiler has reached minimum operating temperature and is now managed by the Boilermaker";
  $Msg[62]  = "Sending the command to reboot the Boilermaker";
  $Msg[63]  = "Boilermaker progressive temperature limit has been reached";
  $Msg[64]  = "Boilermaker starting up in constant power mode";
  $Msg[65]  = "Boiler has reached minimum operating temperature. Reducing Boilermaker power to " . $Boilermaker["fallback"] . " percent";
  $Msg[66]  = "Boiler is under temperature. Increasing Boilermaker power to " . $Settings["heating_position"] . " percent";
  $Msg[67]  = "Boiler is over temperature. Decreasing Boilermaker power to " . $Settings["heating_position"] . " percent";

  mysqli_close($DBcnx);
  CreatePrompt($Msg[$ID]);
}
//---------------------------------------------------------------------------------------------
?>
