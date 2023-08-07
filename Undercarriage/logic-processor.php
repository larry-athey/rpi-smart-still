#!/usr/bin/php
<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
//---------------------------------------------------------------------------------------------
set_time_limit(600);
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
//---------------------------------------------------------------------------------------------
$Result = mysqli_query($DBcnx,"SELECT * FROM logic_tracker WHERE ID=1");
if (mysqli_num_rows($Result) > 0) {
  $Logic    = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);
  if ($Logic["run_start"] == 1) {
    // New run started
    SpeakMessage(6);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_position='0',valve2_position='0' WHERE ID=1");
    shell_exec("/usr/share/rpi-smart-still/valve 1 close");
    shell_exec("/usr/share/rpi-smart-still/valve 2 close");
    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET run_start='0' WHERE ID=1");
  } elseif ($Logic["run_start"] == 2) {
    // Active run stopped
    SpeakMessage(7);
    shell_exec("/usr/share/rpi-smart-still/valve 1 close");
    shell_exec("/usr/share/rpi-smart-still/valve 2 close");
    $Update = mysqli_query($DBcnx,"TRUNCATE logic_tracker");
  } else {
    // Handle the active run

  }
}
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
