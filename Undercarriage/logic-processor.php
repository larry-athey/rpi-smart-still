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
      if ($Settings["boiler_temp"] >= $Program["boiler_temp_low"]) {
        if ($Settings["heating_enabled"] == 1) {
          if ($Settings["speech_enabled"] == 1) SpeakMessage(10);
          $Result  = mysqli_query($DBcnx,"SELECT * FROM heating_translation WHERE percent=70");
          $Heating = mysqli_fetch_assoc($Result);
          $Difference = $Settings["heating_position"] - $Heating["position"];
          $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='" . $Heating["position"] . "' WHERE ID=1");
          $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_done='1',boiler_done_time=now(),boiler_last_temp='" . $Settings["boiler_temp"] . "',boiler_last_adjustment=now()," .
                                        "boiler_last_direction='0',boiler_last_duration='$Difference',boiler_note='Boiler has reached minimum operating temperature, reducing heat to 70%' WHERE ID=1");
          $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,executed) " .
                                        "VALUES (now(),'0','3','0','$Difference','" . $Heating["position"] . "','0')");
        } else {
          $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_done='1',boiler_done_time=now(),boiler_last_temp='" . $Settings["boiler_temp"] . "',boiler_last_adjustment=now()," .
                                        "boiler_last_direction='0',boiler_last_duration='$Difference',boiler_note='Boiler has reached minimum operating temperature, please reduce your heat to 70%' WHERE ID=1");
          if ($Settings["speech_enabled"] == 1) SpeakMessage(11);
        }
        // Open the condenser valve and dephleg valve to their starting positions

      }
    } else {
      /***** BOILER TEMPERATURE MANAGEMENT ROUTINES *****/
      if ($Program["boiler_managed"] == 1) {
        // Check boiler temperature every 300 seconds (5 minutes)
        if (time() - strtotime($Logic["boiler_last_adjustment"]) >= 300) {
          if ($Settings["boiler_temp"] < $Program["boiler_temp_low"]) {
            if ($Settings["heating_enabled"] == 1) {
              // Increase boiler power to the next higher 10% mark
              $Result = mysqli_query($DBcnx,"SELECT * FROM heating_translation WHERE position > " . $Settings["heating_position"] . " limit 1");
              if (mysqli_num_rows($Result) > 0) {
                $Heating = mysqli_fetch_assoc($Result);
              } else {
                $Heating["position"] = $Settings["heating_total"];
              }
              $Difference = $Heating["position"] - $Settings["heating_position"];
              if ($Difference > 0) {
                $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='" . $Heating["position"] . "' WHERE ID=1");
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_temp='" . $Settings["boiler_temp"] . "',boiler_last_adjustment=now()," .
                                              "boiler_last_direction='1',boiler_last_duration='$Difference',boiler_note='Boiler is under temperature, increasing heat to " . $Heating["position"] . " steps' WHERE ID=1");
                if ($Settings["speech_enabled"] == 1) SpeakMessage(12);
                $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,executed) " .
                                              "VALUES (now(),'0','3','1','$Difference','" . $Heating["position"] . "','0')");
              }
            } else {
              // Tell the user to manually turn up their heat a notch or two
              $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_note='Boiler is under temperature, please increase your heat a notch or two' WHERE ID=1");
              if ($Settings["speech_enabled"] == 1) SpeakMessage(14);
            }
          } elseif ($Settings["boiler_temp"] > $Program["boiler_temp_high"]) {
            if ($Settings["heating_enabled"] == 1) {
              // Decrease boiler power to the next lower 10% mark
              $Result = mysqli_query($DBcnx,"SELECT * FROM heating_translation WHERE position < " . $Settings["heating_position"] . " limit 1");
              if (mysqli_num_rows($Result) > 0) {
                $Heating = mysqli_fetch_assoc($Result);
              } else {
                $Heating["position"] = $Settings["heating_position"] - 3;
                if ($Heating["position"] < 0) $Heating["position"] = 0;
              }
              $Difference = $Settings["heating_position"] - $Heating["position"];
              if ($Difference > 0) {
                $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='" . $Heating["position"] . "' WHERE ID=1");
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_temp='" . $Settings["boiler_temp"] . "',boiler_last_adjustment=now()," .
                                              "boiler_last_direction='0',boiler_last_duration='$Difference',boiler_note='Boiler is over temperature, decreasing heat to " . $Heating["position"] . " steps' WHERE ID=1");
                if ($Settings["speech_enabled"] == 1) SpeakMessage(13);
                $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,executed) " .
                                              "VALUES (now(),'0','3','0','$Difference','" . $Heating["position"] . "','0')");
              }
            } else {
              // Tell the user to manually turn down their heat a notch or two
              $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_note='Boiler is over temperature, please decrease your heat a notch or two' WHERE ID=1");
              if ($Settings["speech_enabled"] == 1) SpeakMessage(15);
            }
          } else {
            // Perform micro stepping adjustments if we're not above or below boiler temerature limits
            // Update the $Logic["boiler_last_adjustment"] timestamp to start the 5 minute timer over
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_adjustment=now() WHERE ID=1");
          }
        }
      }
    }
  }
}
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
