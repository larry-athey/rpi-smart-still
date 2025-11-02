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
  $Logic       = mysqli_fetch_assoc($Result);
  $Result      = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings    = mysqli_fetch_assoc($Result);
  $Result      = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program     = mysqli_fetch_assoc($Result);
  $Result      = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);
  $ExtBoilMgmt = false;

  if ($Logic["run_start"] == 1) {
    // New run started
    if ($Settings["speech_enabled"] == 1) SpeakMessage(6);
    if ($Boilermaker["enabled"] == 1) $Settings["heating_total"] = 0;
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_position='0',valve2_position='0',heating_position='" . $Settings["heating_total"] . "' WHERE ID=1");
    if ($Settings["speech_enabled"] == 1) SpeakMessage(1);
    shell_exec("/usr/share/rpi-smart-still/valve 1 close");
    if ($Settings["speech_enabled"] == 1) SpeakMessage(3);
    shell_exec("/usr/share/rpi-smart-still/valve 2 close");
    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET run_start='0' WHERE ID=1");
    if ($Settings["heating_enabled"] == 1) {
      if ($Boilermaker["enabled"] == 1) {
        if ($Boilermaker["online"] == 1) {
          PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
          PingHost($Boilermaker["ip_address"]);
          PingHost($Boilermaker["ip_address"]);
          $Runtime = trim(BoilermakerQuery($Boilermaker["ip_address"],"/get-runtime")); // Get the Boilermaker current runtime
          if (($Runtime != "") && ($Runtime > 0)) BoilermakerQuery2($Boilermaker["ip_address"],"/stop-run"); // Stop the Boilermaker if it's already running
          if ($Boilermaker["op_mode"] == 1) {  // Constant temperature mode
            $ExtBoilMgmt =  true;
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_0=1"); // Put the Boilermaker into Constant Temp mode
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_2=" . $Boilermaker["startup"]); // Set the Boilermaker startup power level
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_3=" . $Boilermaker["fallback"]); // Set the Boilermaker fallback power level
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_1=" . $Program["boiler_temp_low"]); // Set the Boilermaker initial target temperature
            BoilermakerQuery2($Boilermaker["ip_address"],"/start-run"); // Start the Boilermaker
            if ($Boilermaker["fixed_temp"] == 0) {
              $Range = $Program["boiler_temp_high"] - $Program["boiler_temp_low"];
              $IncTemp = $Range / ($Boilermaker["time_spread"] * 12);
              $Update = mysqli_query($DBcnx,"UPDATE boilermaker SET target_temp='" . $Program["boiler_temp_low"] . "',inc_temp='$IncTemp' WHERE ID=1");
            } else {
              $Update = mysqli_query($DBcnx,"UPDATE boilermaker SET target_temp='" . $Program["boiler_temp_low"] . "',inc_temp='0' WHERE ID=1");
            }
            if ($Settings["speech_enabled"] == 1) SpeakMessage(58);
          } else { // Constant power mode (we're basically using the Boilermaker as a digital SCR power controller in this case)
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_0=0"); // Put the Boilermaker into Constant Power mode
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_2=" . $Boilermaker["startup"]); // Set the Boilermaker startup power level
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_3=" . $Boilermaker["fallback"]); // Set the Boilermaker fallback power level
            BoilermakerQuery2($Boilermaker["ip_address"],"/start-run"); // Start the Boilermaker
            if ($Settings["speech_enabled"] == 1) SpeakMessage(64);
          }
        } else {
          // Boilermaker enabled but unresponsive, cancel the distillation run
          if ($Settings["speech_enabled"] == 1) {
            SpeakMessage(59);
            SpeakMessage(7);
          }
          $Update = mysqli_query($DBcnx,"TRUNCATE logic_tracker");
        }
      } else {
        $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                      "VALUES (now(),'0','3','1','" . $Settings["heating_total"] . "','" . $Settings["heating_total"] . "','0','0')");
      }
    } else {
      if ($Settings["speech_enabled"] == 1) SpeakMessage(8);
    }
  } elseif ($Logic["run_start"] == 2) {
    // Active run stopped
    if ($Settings["speech_enabled"] == 1) SpeakMessage(7);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='0' WHERE ID=1");
    $Update = mysqli_query($DBcnx,"TRUNCATE logic_tracker");
    if ($Settings["heating_enabled"] == 1) {
      if ($Boilermaker["enabled"] == 1) {
        if ($Boilermaker["online"] == 1) {
          PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
          PingHost($Boilermaker["ip_address"]);
          PingHost($Boilermaker["ip_address"]);
          BoilermakerQuery2($Boilermaker["ip_address"],"/stop-run"); // Stop the Boilermaker
          $Update = mysqli_query($DBcnx,"UPDATE boilermaker SET target_temp='0',inc_temp='0' WHERE ID=1");
          if ($Settings["speech_enabled"] == 1) SpeakMessage(60);
        } else {
          if ($Settings["speech_enabled"] == 1) SpeakMessage(59);
        }
      } else {
        $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                      "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','0','0')");
      }
    } else {
      if ($Settings["speech_enabled"] == 1) SpeakMessage(9);
    }
    if ($Settings["speech_enabled"] == 1) SpeakMessage(28);
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
          if ($Boilermaker["enabled"] == 1) {
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_done='1',boiler_last_adjustment=now(),boiler_note='Boilermaker has reached minimum operating temperature' WHERE ID=1");
            if ($Boilermaker["op_mode"] == 1) {
              if ($Settings["speech_enabled"] == 1) SpeakMessage(61);
            } else {
              if ($Boilermaker["online"] == 1) {
                PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
                PingHost($Boilermaker["ip_address"]);
                PingHost($Boilermaker["ip_address"]);
                BoilermakerQuery2($Boilermaker["ip_address"],"/?data_2=" . $Boilermaker["fallback"]); // Perform manual Boilermaker power fallback
                if ($Settings["speech_enabled"] == 1) SpeakMessage(65);
              } else {
                if ($Settings["speech_enabled"] == 1) SpeakMessage(59);
              }
            }
          } else {
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
          }
        } else {
          $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_timer=now(),boiler_done='1',boiler_last_adjustment=now()," .
                                        "boiler_note='Boiler has reached minimum operating temperature, please reduce your heat to " . $Program["heating_idle"] . "%' WHERE ID=1");
          if ($Settings["speech_enabled"] == 1) SpeakMessage(11);
        }
        if ($Settings["speech_enabled"] == 1) SpeakMessage(29);
        // Open the condenser valve to its program position
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
        if ($Settings["hydro_type"] == 0) {
          // Recalibrate the load cell hydrometer to its reference weight
          $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                        "VALUES (now(),'0','7','0','0','0','0','0')");
        } else {
          // Reboot the LIDAR hydrometer reader
          $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                        "VALUES (now(),'0','6','0','0','0','0','0')");
        }
      }
    } else {
      /***** BOILER TEMPERATURE MANAGEMENT ROUTINES *****/
      if (($Program["boiler_managed"] == 1) && (! $ExtBoilMgmt)) {
        // Check boiler temperature every 30 seconds
        if (time() - strtotime($Logic["boiler_timer"]) >= 30) { // Primary timer, in case it's needed for future development
          $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_timer=now() WHERE ID=1");
          // Boilers are super slow to reflect temperature changes due to the thermal mass of their contents
          // Therefore, we only check every 15 minutes to see the result of the last adjustment
          if (time() - strtotime($Logic["boiler_last_adjustment"]) >= 900) {
            if ($Settings["boiler_temp"] < $Program["boiler_temp_low"]) {
              if ($Settings["heating_enabled"] == 1) {
                if ($Boilermaker["enabled"] == 1) {
                  if ($Boilermaker["online"] == 1) {
                    // Increase Boilermaker power by 5%
                    if ($Settings["heating_position"] < 100) {
                      $BoilerPower = $Settings["heating_position"] + 5; // Boilermaker is more accurate than SCR controllers and only needs 5% adjustments
                      if ($BoilerPower > 100) $BoilerPower = 100;
                      $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='$BoilerPower' WHERE ID=1");
                      PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
                      PingHost($Boilermaker["ip_address"]);
                      PingHost($Boilermaker["ip_address"]);
                      BoilermakerQuery2($Boilermaker["ip_address"],"/?data_2=$BoilerPower"); // Perform manual Boilermaker power adjustment
                      if ($Settings["speech_enabled"] == 1) SpeakMessage(66);
                    }
                  } else {
                    if ($Settings["speech_enabled"] == 1) SpeakMessage(59);
                  }
                } else {
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
                    } else { // Digital voltage controllers can just be adjusted up and down as necessary
                      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                    "VALUES (now(),'0','3','1','$Difference','" . $Heating["position"] . "','1','0')");
                    }
                  }
                }
              } else {
                // Tell the user to manually turn up their heat a notch or two
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_adjustment=now(),boiler_note='Boiler is under temperature, please increase your heat a notch or two' WHERE ID=1");
                if ($Settings["speech_enabled"] == 1) SpeakMessage(14);
              }
            } elseif ($Settings["boiler_temp"] > $Program["boiler_temp_high"]) {
              if ($Settings["heating_enabled"] == 1) {
                if ($Boilermaker["enabled"] == 1) {
                  if ($Boilermaker["online"] == 1) {
                    // Decrease Boilermaker power by 5%
                    if ($Settings["heating_position"] > 10) {
                      $BoilerPower = $Settings["heating_position"] - 5; // Boilermaker is more accurate than SCR controllers and only needs 5% adjustments
                      if ($BoilerPower < 10) $BoilerPower = 10;
                      $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='$BoilerPower' WHERE ID=1");
                      PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
                      PingHost($Boilermaker["ip_address"]);
                      PingHost($Boilermaker["ip_address"]);
                      BoilermakerQuery2($Boilermaker["ip_address"],"/?data_2=$BoilerPower"); // Perform manual Boilermaker power adjustment
                      if ($Settings["speech_enabled"] == 1) SpeakMessage(67);
                    }
                  } else {
                    if ($Settings["speech_enabled"] == 1) SpeakMessage(59);
                  }
                } else {
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
                    } else { // Digital voltage controllers can just be adjusted up and down as necessary
                      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                    "VALUES (now(),'0','3','0','$Difference','" . $Heating["position"] . "','1','0')");
                    }
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
          } // $Logic["boiler_last_adjustment"]) >= 900 check
        } //$Logic["boiler_timer"] >= 30 check
      } // $Program["boiler_managed"] == 1 && ! $ExtBoilMgmt check
      /***** BOILERMAKER PROGRESSIVE TEMPERATURE MANAGEMENT ROUTINES *****/
      if (($Program["boiler_managed"] == 1) && ($Boilermaker["enabled"] == 1) && ($Boilermaker["fixed_temp"] == 0)) {
        // Perform progressive temperature increases every 5 minutes
        // This works similar to Mode 3 in my "Airhead" controller for Air Stills (which runs on 15 minute intervals)
        if (time() - strtotime($Logic["boiler_last_adjustment"]) >= 300) {
          if ($Boilermaker["target_temp"] < $Program["boiler_temp_high"]) {
            $TargetTemp = $Boilermaker["target_temp"] + $Boilermaker["inc_temp"];
            $TempC = round($TargetTemp,1) . "C";
            $TempF = round(($TargetTemp * (9 / 5)) + 32,1) . "F";
            $Update = mysqli_query($DBcnx,"UPDATE boilermaker SET target_temp='$TargetTemp' WHERE ID=1");
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET boiler_last_adjustment=now(),boiler_note='Boilermaker target temperature progressively incremented to $TempC / $TempF' WHERE ID=1");
            PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
            PingHost($Boilermaker["ip_address"]);
            PingHost($Boilermaker["ip_address"]);
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_1=$TargetTemp"); // Update the Boilermaker target temperature
            if (($TargetTemp >= $Program["boiler_temp_high"]) && ($Settings["speech_enabled"] == 1)) SpeakMessage(63);
          }
        }
      } // $Program["boiler_managed"] == 1 && $Boilermaker["enabled"] == 1 && $Boilermaker["fixed_temp"] == 1 check
      /***** COLUMN TEMPERATURE MANAGEMENT ROUTINES *****/
      if ($Program["column_managed"] == 1) {
        if ($Logic["column_done"] == 0) {
          // Don't bother managing any dephleg or ABV stuff until the column is up to temperature
          if ($Settings["column_temp"] >= $Program["column_temp_low"]) {
            if ($Settings["speech_enabled"] == 1) SpeakMessage(16);
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_timer=now(),column_done='1',column_last_adjustment=now()," .
                                          "column_note='Column has reached minimum operating temperature',hydrometer_timer=now(),hydrometer_abv_errors='0',hydrometer_temp_errors='0',flow_sensor_errors='0' WHERE ID=1");
          }
        } else {
          // Check column temperature every 30 seconds
          if (time() - strtotime($Logic["column_timer"]) >= 30) { // Primary timer, in case it's needed for future development
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_timer=now() WHERE ID=1");
            // Adjustments are given 5 minutes to take full effect before another
            // The column is slower to react to adjustments than the dephleg sensor
            if (time() - strtotime($Logic["column_last_adjustment"]) >= 300) {
              if ($Settings["column_temp"] < $Program["column_temp_low"]) {
                if (($Program["boiler_managed"] == 1) && ($Settings["heating_enabled"] == 1)) {
                  // Increase boiler power to raise the column temperature
                  if ($Boilermaker["enabled"] == 1) {
                    $TargetTemp = $Boilermaker["target_temp"] + 1;
                    if ($TargetTemp <= $Program["boiler_temp_high"]) {
                      $TempC = round($TargetTemp,1) . "C";
                      $TempF = round(($TargetTemp * (9 / 5)) + 32,1) . "F";
                      $Update = mysqli_query($DBcnx,"UPDATE boilermaker SET target_temp='$TargetTemp' WHERE ID=1");
                      $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_last_adjustment=now(),boiler_note='Boilermaker target temperature increased to $TempC / $TempF due to column under temp' WHERE ID=1");
                      PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
                      PingHost($Boilermaker["ip_address"]);
                      PingHost($Boilermaker["ip_address"]);
                      BoilermakerQuery2($Boilermaker["ip_address"],"/?data_1=$TargetTemp"); // Update the Boilermaker target temperature
                      if ($Settings["speech_enabled"] == 1) SpeakMessage(20);
                    }
                  } else {
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
                  }
                } else {
                // Tell the user to manually turn up their heat a notch or two
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_last_adjustment=now(),column_note='Column is over temperature, please decrease your heat a notch or two' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(18);
                }
              } elseif ($Settings["column_temp"] > $Program["column_temp_high"]) {
                if (($Program["boiler_managed"] == 1) && ($Settings["heating_enabled"] == 1)) {
                  // Decrease boiler power to lower the column temperature
                  if ($Boilermaker["enabled"] == 1) {
                    $TargetTemp = $Boilermaker["target_temp"] - 1;
                    if ($TargetTemp >= $Program["boiler_temp_low"]) {
                      $TempC = round($TargetTemp,1) . "C";
                      $TempF = round(($TargetTemp * (9 / 5)) + 32,1) . "F";
                      $Update = mysqli_query($DBcnx,"UPDATE boilermaker SET target_temp='$TargetTemp' WHERE ID=1");
                      $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_last_adjustment=now(),boiler_note='Boilermaker target temperature reduced to $TempC / $TempF due to column over temp' WHERE ID=1");
                      PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
                      PingHost($Boilermaker["ip_address"]);
                      PingHost($Boilermaker["ip_address"]);
                      BoilermakerQuery2($Boilermaker["ip_address"],"/?data_1=$TargetTemp"); // Update the Boilermaker target temperature
                      if ($Settings["speech_enabled"] == 1) SpeakMessage(21);
                    }
                  } else {
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
                                          "dephleg_note='Dephleg has reached minimum operating temperature',hydrometer_timer=now(),hydrometer_abv_errors='0',hydrometer_temp_errors='0',flow_sensor_errors='0' WHERE ID=1");
          }
        } else {
          // Check dephleg temperature every 30 seconds
          if (time() - strtotime($Logic["dephleg_timer"]) >= 30) { // Primary timer, in case it's needed for future development
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_timer=now() WHERE ID=1");
            // Adjustments are given 2 minutes to take full effect before another
            if (time() - strtotime($Logic["dephleg_last_adjustment"]) >= 120) {
              if ($Settings["dephleg_temp"] < $Program["dephleg_temp_low"]) {
                $TempError = $Program["dephleg_temp_low"] - $Settings["dephleg_temp"];
                if ($TempError >= 1) {
                  $Difference = round($Settings["valve2_pulse"] * $TempError);
                } else {
                  $Difference = round($Settings["valve2_pulse"] * .25,0,PHP_ROUND_HALF_UP);
                }
                $NewPosition = $Settings["valve2_position"] - $Difference;
                if ($NewPosition < 0) {
                  // If we got here, there's a water flow problem
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(54);
                } else {
                  $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='$NewPosition' WHERE ID=1");
                  $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_last_adjustment=now()," .
                                                "dephleg_note='Dephleg is under temperature, decreasing cooling water flow' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(22);
                  $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                "VALUES (now(),'0','2','0','$Difference','$NewPosition','1','0')");
                }
              } elseif ($Settings["dephleg_temp"] > $Program["dephleg_temp_high"]) {
                $TempError = $Settings["dephleg_temp"] - $Program["dephleg_temp_high"];
                if ($TempError >= 1) {
                  $Difference = round($Settings["valve2_pulse"] * $TempError);
                } else {
                  $Difference = round($Settings["valve2_pulse"] * .25,0,PHP_ROUND_HALF_UP);
                }
                $NewPosition = $Settings["valve2_position"] + $Difference;
                if ($NewPosition >= $Settings["valve2_total"]) {
                  // If we got here, there's a water flow problem
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(55);
                } else {
                  $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='$NewPosition' WHERE ID=1");
                  $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_last_adjustment=now()," .
                                                "dephleg_note='Dephleg is over temperature, increasing cooling water flow' WHERE ID=1");
                  if ($Settings["speech_enabled"] == 1) SpeakMessage(23);
                  $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                                "VALUES (now(),'0','2','1','$Difference','$NewPosition','1','0')");
                }
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
          if ($Settings["distillate_abv"] >= $Program["distillate_abv"]) $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_started='1' WHERE ID=1");
        } else {
          if ($Program["mode"] == 0) {
            if ((time() - strtotime($Logic["hydrometer_timer"]) >= 300) && ($Logic["hydrometer_started"] == 1)) {
              if ($Settings["distillate_abv"] <= $Program["distillate_abv"]) {
                $Logic["hydrometer_abv_errors"] ++;
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_abv_errors='" . $Logic["hydrometer_abv_errors"] . "' WHERE ID=1");
              }
              // In pot still mode, we stop the run after 3 checks showing that we've hit or dropped below the minimum ABV
              if ($Logic["hydrometer_abv_errors"] == 3) {
                if ($Settings["speech_enabled"] == 1) SpeakMessage(31);
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET run_start='2' WHERE ID=1");
                $Update = mysqli_query($DBcnx,"UPDATE settings SET active_run='0',run_end=now() WHERE ID=1");
              }
            } else {
              if ($Settings["distillate_abv"] > $Program["distillate_abv"]) {
                $Logic["hydrometer_abv_errors"] = 0;
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_abv_errors='0' WHERE ID=1");
              }
            }
          } else {
            // In reflux mode, we dynamically adjust the program's dephleg upper and lower temperature limits downward
            // Remember, you can only adjust the ABV up, you can't adjust it down without adding distilled water to it
            if ((time() - strtotime($Logic["hydrometer_timer"]) >= 300) && ($Logic["hydrometer_started"] == 1)) {
              if ($Settings["distillate_abv"] <= $Program["distillate_abv"]) {
                $Logic["hydrometer_abv_errors"] ++;
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_abv_errors='" . $Logic["hydrometer_abv_errors"] . "' WHERE ID=1");
              }
              if ($Logic["hydrometer_abv_errors"] == 3) {
                $NewLower = $Program["dephleg_temp_low"] - 0.5;
                $NewUpper = $Program["dephleg_temp_high"] - 0.5;
                $Update = mysqli_query($DBcnx,"UPDATE programs SET dephleg_temp_low='$NewLower',dephleg_temp_high='$NewUpper' WHERE ID = " . $Program["ID"]);
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_abv_errors='0' WHERE ID=1");
              }
            } else {
              if ($Settings["distillate_abv"] > $Program["distillate_abv"]) {
                $Logic["hydrometer_abv_errors"] = 0;
                $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_abv_errors='0' WHERE ID=1");
              }
            }
          }
        }
      }
      /***** DISTILLATE MINIMUM FLOW RATE MANAGEMENT ROUTINES *****/
      if ($Program["flow_managed"] == 1) {
        if ((time() - strtotime($Logic["hydrometer_timer"]) >= 300) && ($Logic["hydrometer_started"] == 1)) {
          if ($Settings["distillate_flow"] < $Program["minimum_flow"]) {
            $Logic["flow_sensor_errors"] ++;
          } else {
            $Logic["flow_sensor_errors"] = 0;
          }
          $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET flow_sensor_errors='" . $Logic["flow_sensor_errors"] . "' WHERE ID=1");
          // If there are 3 distillate flow level failures in a row, stop the run
          if ($Logic["flow_sensor_errors"] == 3) {
            if ($Settings["speech_enabled"] == 1) SpeakMessage(48);
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET run_start='2' WHERE ID=1");
            $Update = mysqli_query($DBcnx,"UPDATE settings SET active_run='0',run_end=now() WHERE ID=1");
          }
        }
      }
      /***** DISTILLATE TEMPERATURE MANAGEMENT ROUTINES *****/
      /***** THIS FINAL CODE BRANCH CONTROLS THE RESET OF $Logic["hydrometer_timer"] *****/
      if (($Logic["column_done"] == 1) || ($Logic["dephleg_done"] == 1)) {
        // Check the distillate temperature every 5 minutes after column or dephleg are up to temperature
        if ((time() - strtotime($Logic["hydrometer_timer"]) >= 300) && ($Logic["hydrometer_started"] == 1)) {
          // If distillate is over 24C/75F, increment the $Logic["hydrometer_temp_errors"] counter
          // This is for both safety and to maintain the accuracy of the hydrometer, hot distillate is less dense and reads a higher proof
          if ($Settings["distillate_temp"] > 24) {
            $Logic["hydrometer_temp_errors"] ++;
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_timer=now(),hydrometer_temp_errors='" . $Logic["hydrometer_temp_errors"] . "' WHERE ID=1");
          } else {
            $Logic["hydrometer_temp_errors"] = 0;
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_timer=now(),hydrometer_temp_errors='0' WHERE ID=1");
          }
          // If $Logic["hydrometer_temp_errors"] has 3 errors, increase the condenser cooling flow 10%
          if ($Logic["hydrometer_temp_errors"] == 3) {
            if ($Settings["speech_enabled"] == 1) SpeakMessage(34);
            $Difference = round($Settings["valve1_total"] * 0.1);
            if ($Settings["valve1_position"] + $Difference < $Settings["valve1_total"]) {
              $NewPosition = $Settings["valve1_position"] + $Difference;
            } else {
              $NewPosition = $Settings["valve1_total"];
            }
            $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET hydrometer_timer=now(),hydrometer_temp_errors='0' WHERE ID=1");
            $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_position='$NewPosition' WHERE ID=1");
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
