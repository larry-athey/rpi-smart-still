<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
//---------------------------------------------------------------------------------------------
function SpeakMessage($ID) {
  $DBcnx    = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM logic_tracker WHERE ID=1");
  $Logic    = mysqli_fetch_assoc($Result);

  $Msg[0]   = "Increasing condenser valve position";
  $Msg[1]   = "Decreasing condenser valve position";
  $Msg[2]   = "Increasing dephlegmator valve position";
  $Msg[3]   = "Decreasing dephlegmator valve position";
  $Msg[4]   = "Increasing heating stepper motor position";
  $Msg[5]   = "Decreasing heating stepper motor position";
  $Msg[6]   = "Starting a new distillation run using the program named. " . $Program["program_name"];
  $Msg[7]   = "Stopping the current distillation run. Shutting down the boiler and cooling valves";
  $Msg[8]   = "Please turn on your boiler's heating control to its highest setting at this time";
  $Msg[9]   = "Please turn off your boiler's heating control at this time";
  $Msg[10]  = "Boiler has reached minimum operating temperature. Reducing heat to 70%";
  $Msg[11]  = "Boiler has reached minimum operating temperature. Please reduce your heat to 70%";

  shell_exec("/usr/bin/espeak -v english-us -s 160 \"$Msg[$ID]\" 2> /dev/null &");
  mysqli_close($DBcnx);
}
//---------------------------------------------------------------------------------------------
?>
