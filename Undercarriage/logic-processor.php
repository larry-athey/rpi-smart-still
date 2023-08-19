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
      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                    "VALUES (now(),'0','3','1','" . $Settings["heating_total"] . "','" . $Settings["heating_total"] . "','0','0')");
    } else {
      if ($Settings["speech_enabled"] == 1) SpeakMessage(8);
    }
  } elseif ($Logic["run_start"] == 2) {
    // Active run stopped
    if ($Settings["speech_enabled"] == 1) SpeakMessage(7);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='0' WHERE ID=1");
    $Update = mysqli_query($DBcnx,"TRUNCATE logic_tracker");
    if ($Settings["heating_enabled"] == 1) {
      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                    "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','0','0')");
    } else {
      if ($Settings["speech_enabled"] == 1) SpeakMessage(9);
    }
    if ($Settings["speech_enabled"] == 1) {
      sleep(10);
      SpeakMessage(28);
    }
    sleep(120);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_position='0',valve2_position='0' WHERE ID=1");
    if ($Settings["speech_enabled"] == 1) SpeakMessage(1);
    shell_exec("/usr/share/rpi-smart-still/valve 1 close");
    if ($Settings["speech_enabled"] == 1) SpeakMessage(3);
    shell_exec("/usr/share/rpi-smart-still/valve 2 close");
  } else {
    // Handle the active run
    if ($Logic["boiler_done"] == 0) {
      // Don't bother managing anything else until the boiler is up to temperature
      if ($Settings["boiler_temp"] >= $Program["boiler_temp_low"]) {
        if ($Settings["heating_enabled"] == 1) {
          if ($Settings["speech_enabled"] == 1) SpeakMessage(10);
          $Difference = $Settings["heating_position"] - $Program["heating_idle"];
          $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='" . $Program["heating_idle"] . "' WHERE ID=1");
          $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_done='1',boiler_last_adjustment=now()," .
                                        "boiler_note='Boiler has reached minimum operating temperature, reducing heat to " . $Program["heating_idle"] . " steps' WHERE ID=1");
          if ($Settings["heating_analog"] == 1) { // A digital voltmeter doesn't mean that it's a digital voltage controller!
            $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                          "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','1','0')," .
                                                 "(now(),'0','3','1','" . $Program["heating_idle"] . "','" . $Program["heating_idle"] . "','1','0')");
          } else { // Digital voltage controllers and gas valves can just be adjusted up and down as necessary
            $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                          "VALUES (now(),'0','3','0','$Difference','" . $Program["heating_idle"] . "','1','0')");
          }
        } else {
          $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_timer=now(),boiler_done='1',boiler_last_adjustment=now()," .
                                        "boiler_note='Boiler has reached minimum operating temperature, please reduce your heat to " . $Program["heating_idle"] . "%' WHERE ID=1");
          if ($Settings["speech_enabled"] == 1) SpeakMessage(11);
        }
        if ($Settings["speech_enabled"] == 1) {
          sleep(10);
          SpeakMessage(29);
        }
        // Open the condenser valve to its programed position
        $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_position='" . round($Program["condenser_rate"] * $Settings["valve1_pulse"],0,PHP_ROUND_HALF_UP) . "' WHERE ID=1");
        // Open to 100% and pull down to the setting to evacuate any air in its water lines
        $Duration = $Settings["valve1_total"] - round($Program["condenser_rate"] * $Settings["valve1_pulse"],0,PHP_ROUND_HALF_UP);
        $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                      "VALUES (now(),'0','1','1','" . $Settings["valve1_total"] . "','" . $Settings["valve1_total"] . "','1','0')," .
                                             "(now(),'0','1','0','$Duration','" . round($Program["condenser_rate"] * $Settings["valve1_pulse"],0,PHP_ROUND_HALF_UP) . "','1','0')");
        // Open the dephleg valve to its starting position if this is a reflux program
        if ($Program["mode"] == 1) {
          $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='" . round($Program["dephleg_start"]  * $Settings["valve2_pulse"],0,PHP_ROUND_HALF_UP) . "' WHERE ID=1");
          // Open to 100% and pull down to the setting to evacuate any air in its water lines
          $Duration = $Settings["valve2_total"] - round($Program["dephleg_start"] * $Settings["valve2_pulse"],0,PHP_ROUND_HALF_UP);
          $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                        "VALUES (now(),'0','2','1','" . $Settings["valve2_total"] . "','" . $Settings["valve2_total"] . "','1','0')," .
                                               "(now(),'0','2','0','$Duration','" . round($Program["dephleg_start"] * $Settings["valve2_pulse"],0,PHP_ROUND_HALF_UP) . "','1','0')");
        }
        // Recalibrate the hydrometer to its reference weight
        $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                      "VALUES (now(),'0','7','0','0','0','0','0')");
      }
    } else {
      /***** BOILER TEMPERATURE MANAGEMENT ROUTINES *****/
      if ($Program["boiler_managed"] == 1) {
        // Check boiler temperature every 60 seconds
        if (time() - strtotime($Logic["boiler_timer"]) >= 30) { // Primary timer "just in case"
          $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_timer=now() WHERE ID=1");
          // Boilers are super slow to reflect temperature changes due to the thermal mass of their contents
          // Therefore, we only check every 10 minutes to see the result of the last adjustment
          if (time() - strtotime($Logic["boiler_last_adjustment"]) >= 600) {
            if ($Settings["boiler_temp"] < $Program["boiler_temp_low"]) {
              if ($Settings["heating_enabled"] == 1) {
                // Increase boiler power to the next higher 10% mark
                $Result = mysqli_query($DBcnx,"SELECT * FROM heating_translation WHERE position > " . $Settings["heating_position"] . " ORDER BY position ASC LIMIT 1");
                if (mysqli_num_rows($Result) > 0) {
                  $Heating = mysqli_fetch_assoc($Result);
                } else {
                  $Heating["position"] = $Settings["heating_total"];
                }
                $Difference = $Heating["position"] - $Settings["heating_position"];
                if ($Difference > 0) {
                  $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='" . $Heating["position"] . "' WHERE ID=1");
                  $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_adjustment=now()," .
                                                "boiler_note='Boiler is under temperature, increasing heat to " . $Heating["position"] . " steps' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(12);
                  if ($Settings["heating_analog"] == 1) { // A digital voltmeter doesn't mean that it's a digital voltage controller!
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','1','0')," .
                                                         "(now(),'0','3','1','" . $Heating["position"] . "','" . $Heating["position"] . "','1','0')");
                  } else { // Digital voltage controllers and gas valves can just be adjusted up and down as necessary
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','3','1','$Difference','" . $Heating["position"] . "','1','0')");
                  }
                }
              } else {
                // Tell the user to manually turn up their heat a notch or two
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_adjustment=now(),boiler_note='Boiler is under temperature, please increase your heat a notch or two' WHERE ID=1");
                if ($Settings["speech_enabled"] == 1) SpeakMessage(14);
              }
            } elseif ($Settings["boiler_temp"] > $Program["boiler_temp_high"]) {
              if ($Settings["heating_enabled"] == 1) {
                // Decrease boiler power to the next lower 10% mark
                $Result = mysqli_query($DBcnx,"SELECT * FROM heating_translation WHERE position < " . $Settings["heating_position"] . " ORDER BY position DESC LIMIT 1");
                if (mysqli_num_rows($Result) > 0) {
                  $Heating = mysqli_fetch_assoc($Result);
                } else {
                  $Heating["position"] = $Settings["heating_position"] - 3;
                  if ($Heating["position"] < 0) $Heating["position"] = 0;
                }
                $Difference = $Settings["heating_position"] - $Heating["position"];
                if ($Difference > 0) {
                  $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='" . $Heating["position"] . "' WHERE ID=1");
                  $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_adjustment=now()," .
                                                "boiler_note='Boiler is over temperature, decreasing heat to " . $Heating["position"] . " steps' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(13);
                  if ($Settings["heating_analog"] == 1) { // A digital voltmeter doesn't mean that it's a digital voltage controller!
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','1','0')," .
                                                         "(now(),'0','3','1','" . $Heating["position"] . "','" . $Heating["position"] . "','1','0')");
                  } else { // Digital voltage controllers and gas valves can just be adjusted up and down as necessary
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','3','0','$Difference','" . $Heating["position"] . "','1','0')");
                  }
                }
              } else {
                // Tell the user to manually turn down their heat a notch or two
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_adjustment=now(),boiler_note='Boiler is over temperature, please decrease your heat a notch or two' WHERE ID=1");
                if ($Settings["speech_enabled"] == 1) SpeakMessage(15);
              }
            } else {
              // Update the user interface status message with a current time stamp
              $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_adjustment=now(),boiler_note='Boiler temperature is within the program\'s operating range' WHERE ID=1");
            } // $Settings["boiler_temp"] vs $Program["boiler_temp_low/high"] checks
          } // $Logic["boiler_last_adjustment"]) >= 600 check
        } //$Logic["boiler_timer"] >= 30 check
      } // $Program["boiler_managed"] == 1 check
      /***** COLUMN TEMPERATURE MANAGEMENT ROUTINES *****/
      if ($Program["column_managed"] == 1) {
        if ($Logic["column_done"] == 0) {
          // Don't bother managing any dephleg or ABV stuff until the column is up to temperature
          if ($Settings["column_temp"] >= $Program["column_temp_low"]) {
            if ($Settings["speech_enabled"] == 1) SpeakMessage(16);
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_timer=now(),column_done='1',column_last_adjustment=now()," .
                                          "column_note='Column has reached minimum operating temperature',hydrometer_timer=now(),hydrometer_temp_errors='0' WHERE ID=1");
          }
        } else {
          // Check column temperature every 30 seconds
          if (time() - strtotime($Logic["column_timer"]) >= 30) { // Primary timer "just in case"
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_timer=now() WHERE ID=1");
            // Adjustments are given 5 minutes to take full effect before another
            // The column is slower to react to adjustments than the dephleg sensor
            if (time() - strtotime($Logic["column_last_adjustment"]) >= 300) {
              if ($Settings["column_temp"] < $Program["column_temp_low"]) {
                if ($Settings["heating_enabled"] == 1) {
                  // Increase boiler power to the next higher step
                  $Increase = $Settings["heating_position"] + 1;
                  if ($Increase > $Settings["heating_total"]) $Increase = $Settings["heating_total"];
                  $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='$Increase' WHERE ID=1");
                  $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_last_adjustment=now()," .
                                                "column_note='Column is under temperature, increasing heat to $Increase steps' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(20);
                  if ($Settings["heating_analog"] == 1) { // A digital voltmeter doesn't mean that it's a digital voltage controller!
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','1','0')," .
                                                         "(now(),'0','3','1','$Increase','$Increase','1','0')");
                  } else { // Digital voltage controllers and gas valves can just be adjusted up and down as necessary
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','3','1','1','$Increase','1','0')");
                  }
                } else {
                // Tell the user to manually turn up their heat a notch or two
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_last_adjustment=now(),column_note='Column is over temperature, please decrease your heat a notch or two' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(18);
                }
              } elseif ($Settings["column_temp"] > $Program["column_temp_high"]) {
                if ($Settings["heating_enabled"] == 1) {
                  // Decrease boiler power to the next lower step
                  $Decrease = $Settings["heating_position"] - 1;
                  if ($Decrease < 0) $Decrease = 0;
                  $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='$Decrease' WHERE ID=1");
                  $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_last_adjustment=now()," .
                                                "column_note='Column is over temperature, decreasing heat to $Decrease steps' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(21);
                  if ($Settings["heating_analog"] == 1) { // A digital voltmeter doesn't mean that it's a digital voltage controller!
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','1','0')," .
                                                         "(now(),'0','3','1','$Decrease','$Decrease','1','0')");
                  } else { // Digital voltage controllers and gas valves can just be adjusted up and down as necessary
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','3','1','1','$Decrease','1','0')");
                  }
                } else {
                  // Tell the user to manually turn down their heat a notch or two
                  $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_last_adjustment=now(),column_note='Column is over temperature, please decrease your heat a notch or two' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(19);
                }
              } else {
                // Update the user interface status message with a current time stamp
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_last_adjustment=now(),column_note='Column temperature is within the program\'s operating range' WHERE ID=1");
              } // $Settings["column_temp"] vs $Program["column_temp_low/high"] checks
            } // $Logic["column_last_adjustment"]) >= 300 check
          } // $Logic["column_timer"]) >= 30 check
        } // $Logic["column_done"] == 0 check
      } // $Program["column_managed"] == 1 check
      /***** DEPHLEG TEMPERATURE MANAGEMENT ROUTINES *****/
      if ($Program["dephleg_managed"] == 1) {
        if ($Logic["dephleg_done"] == 0) {
          if ($Settings["dephleg_temp"] >= $Program["dephleg_temp_low"]) {
            if ($Settings["speech_enabled"] == 1) SpeakMessage(17);
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_timer=now(),dephleg_done='1',dephleg_last_adjustment=now()," .
                                          "dephleg_note='Dephleg has reached minimum operating temperature',hydrometer_timer=now(),hydrometer_temp_errors='0' WHERE ID=1");
          }
        } else {
          // Check dephleg temperature every 30 seconds
          if (time() - strtotime($Logic["dephleg_timer"]) >= 30) { // Primary timer "just in case"
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_timer=now() WHERE ID=1");
            // Adjustments are given 2 minutes to take full effect before another
            if (time() - strtotime($Logic["dephleg_last_adjustment"]) >= 120) {
              if ($Settings["dephleg_temp"] < $Program["dephleg_temp_low"]) {
                $TempError = $Program["dephleg_temp_low"] - $Settings["dephleg_temp"];
                if ($TempError >= 1) {
                  $Difference = $Settings["valve2_pulse"];
                } else {
                  $Difference = round($Settings["valve2_pulse"] * .25,0,PHP_ROUND_HALF_UP);
                }
                $NewPosition = $Settings["valve2_position"] - $Difference;
                if ($NewPosition < 0) {
                  // If we got here, there's a water flow problem
                  mysqli_close($DBcnx);
                  exit;
                }
                $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='$NewPosition' WHERE ID=1");
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_last_adjustment=now()," .
                                              "dephleg_note='Dephleg is under temperature, decreasing cooling water flow' WHERE ID=1");
                if ($Settings["speech_enabled"] == 1) SpeakMessage(22);
                $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                              "VALUES (now(),'0','2','0','$Difference','$NewPosition','1','0')");
              } elseif ($Settings["dephleg_temp"] > $Program["dephleg_temp_high"]) {
                $TempError = $Settings["dephleg_temp"] - $Program["dephleg_temp_high"];
                if ($TempError >= 1) {
                  $Difference = $Settings["valve2_pulse"];
                } else {
                  $Difference = round($Settings["valve2_pulse"] * .25,0,PHP_ROUND_HALF_UP);
                }
                $NewPosition = $Settings["valve2_position"] + $Difference;
                if ($NewPosition >= $Settings["valve2_total"]) {
                  // If we got here, there's a water flow problem
                  mysqli_close($DBcnx);
                  exit;
                }
                $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='$NewPosition' WHERE ID=1");
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_last_adjustment=now()," .
                                              "dephleg_note='Dephleg is over temperature, increasing cooling water flow' WHERE ID=1");
                if ($Settings["speech_enabled"] == 1) SpeakMessage(23);
                $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                              "VALUES (now(),'0','2','1','$Difference','$NewPosition','1','0')");
              } else {
                // Update the user interface status message with a current time stamp
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_last_adjustment=now(),dephleg_note='Dephleg temperature is within the program\'s operating range' WHERE ID=1");
                // Perform microstepping to maintain the dephleg temperature between the upper and lower limits
                $Range = $Program["dephleg_temp_high"] - $Program["dephleg_temp_low"];
                if ($Range > 4) {
                  $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_last_adjustment=now(),dephleg_note='Dephleg temperature is within range, microstepping as needed' WHERE ID=1");
                  if ($Range % 2 == 0) { // Always better to use an odd numbered range, preferably 5 degrees between upper and lower limits
                    $RangeCenter = ($Range / 2) + $Program["dephleg_temp_low"];
                  } else {
                    $RangeCenter = round($Range / 2,0,PHP_ROUND_HALF_UP) + $Program["dephleg_temp_low"];
                  }
                  $Difference = round($Settings["valve2_pulse"] * .1,0,PHP_ROUND_HALF_DOWN);
                  if ($Settings["dephleg_temp"] < ($RangeCenter -0.5)) {
                    $NewPosition = $Settings["valve2_position"] - $Difference;
                    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_last_adjustment=now(),dephleg_note='Dephleg temperature is below range center, microstepping valve down' WHERE ID=1");
                    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='$NewPosition' WHERE ID=1");
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','2','0','$Difference','$NewPosition','1','0')");
                  } elseif ($Settings["dephleg_temp"] > ($RangeCenter + 0.5)) {
                    $NewPosition = $Settings["valve2_position"] + $Difference;
                    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_last_adjustment=now(),dephleg_note='Dephleg temperature is above range center, microstepping valve up' WHERE ID=1");
                    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='$NewPosition' WHERE ID=1");
                    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                  "VALUES (now(),'0','2','1','$Difference','$NewPosition','1','0')");
                  } // $Settings["dephleg_temp"] vs $RangeCenter check
                } // Dephleg valid range for microstepping check
              } // $Settings["dephleg_temp"] vs $Program["dephleg_temp_low/high"] checks
            } // $Logic["dephleg_last_adjustment"]) >= 120 check
          } // $Logic["dephleg_timer"]) >= 30 check
        } // $Logic["dephleg_done"] == 0 check
      } // $Program["dephleg_managed"] == 1 check
      /***** DISTILLATE MINIMUM ABV MANAGEMENT ROUTINES *****/
      if ($Program["abv_managed"] == 1) {
        if ($Logic["hydrometer_started"] == 0) {
          if ($Settings["distillate_abv"] > 0) $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_started='1' WHERE ID=1");
        } else {
          if ($Program["mode"] == 0) {
            // In pot still mode, we stop the run when we hit the minimum ABV
            if ($Settings["distillate_abv"] <= $Program["distillate_abv"]) {
              if ($Settings["speech_enabled"] == 1) SpeakMessage(31);
              $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET run_start='2' WHERE ID=1");
              $Update = mysqli_query($DBcnx,"UPDATE settings SET active_run='0',run_end=now() WHERE ID=1");
            }
          } else {
            // In reflux mode, we dynamically adjust the program's dephleg upper and lower temperature limits
            // Remember, you can only adjust the ABV up, you can't adjust it down without adding water to it
          }
        }
      }
      /***** DISTILLATE MINIMUM FLOW RATE MANAGEMENT ROUTINES *****/
      if ($Program["flow_managed"] == 1) {

      }
      /***** DISTILLATE TEMPERATURE SAFETY MANAGEMENT ROUTINES *****/
      if (($Logic["column_done"] == 1) || ($Logic["column_done"] == 1)) {
        // Check the distillate temperature every 10 minutes after column or dephleg are up to temperature
        if ((time() - strtotime($Logic["hydrometer_timer"]) >= 600) && ($Settings["distillate_abv"] > 0)) {
          // If distillate is over 24C/75F, increment the $Logic["hydrometer_temp_error"] counter
          if ($Settings["distillate_temp"] > 24) {
            $Logic["hydrometer_temp_error"] ++;
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_temp_error='" . $Logic["hydrometer_temp_error"] . "' WHERE ID=1");
          } else {
            $Logic["hydrometer_temp_error"] = 0;
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_temp_error='0' WHERE ID=1");
          }
          // If $Logic["hydrometer_temp_error"] has 3 errors, increase the condenser cooling flow 10%
          if ($Logic["hydrometer_temp_error"] == 3) {
            if ($Settings["speech_enabled"] == 1) SpeakMessage(34);
            $Difference = round($Settings["valve1_total"] * 0.1);
            if ($Settings["valve1_position"] + $Difference > $Settings["valve1_total"]) {
              $NewPosition = $Settings["valve1_position"] + $Difference;
            } else {
              $NewPosition = $Settings["valve1_total"];
            }
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_temp_error='0' WHERE ID=1");
            $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='$NewPosition' WHERE ID=1");
            $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                          "VALUES (now(),'0','1','1','$Difference','$NewPosition','1','0')");
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
