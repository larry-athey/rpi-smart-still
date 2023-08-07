#!/usr/bin/php
<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
require_once("voice-prompts.php");
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
    if ($Settings["speech_enabled"] == 1) SpeakMessage(6);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_position='0',valve2_position='0',heating_position='" . $Settings["heating_total"] . "' WHERE ID=1");
    if ($Settings["speech_enabled"] == 1) SpeakMessage(1);
    shell_exec("/usr/share/rpi-smart-still/valve 1 close");
    if ($Settings["speech_enabled"] == 1) SpeakMessage(3);
    shell_exec("/usr/share/rpi-smart-still/valve 2 close");
    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET run_start='0' WHERE ID=1");
    if ($Settings["heating_enabled"] == 1) {
      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,executed) " .
                                    "VALUES (now(),'0','3','1','" . $Settings["heating_total"] . "','" . $Settings["heating_total"] . "','0')");
    } else {
      if ($Settings["speech_enabled"] == 1) SpeakMessage(8);
    }
  } elseif ($Logic["run_start"] == 2) {
    // Active run stopped
    if ($Settings["speech_enabled"] == 1) SpeakMessage(7);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_position='0',valve2_position='0',heating_position='0' WHERE ID=1");
    if ($Settings["speech_enabled"] == 1) SpeakMessage(1);
    shell_exec("/usr/share/rpi-smart-still/valve 1 close");
    if ($Settings["speech_enabled"] == 1) SpeakMessage(3);
    shell_exec("/usr/share/rpi-smart-still/valve 2 close");
    $Update = mysqli_query($DBcnx,"TRUNCATE logic_tracker");
    if ($Settings["heating_enabled"] == 1) {
      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,executed) " .
                                    "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','0')");
    } else {
      if ($Settings["speech_enabled"] == 1) SpeakMessage(9);
    }
  } else {
    // Handle the active run
    if ($Logic["boiler_done"] == 0) {
      // Don't bother managing anything else until the boiler is up to temperature

    } else {

    }
  }
}
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
