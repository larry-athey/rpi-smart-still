#!/usr/bin/php
<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
require_once("voice-prompts.php");
//---------------------------------------------------------------------------------------------
set_time_limit(600);
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
//---------------------------------------------------------------------------------------------
$Counter  = 0;
$Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
if (mysqli_num_rows($Result) > 0) {
  $Settings = mysqli_fetch_assoc($Result);
  $Result = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);
} else {
  echo("System settings record is missing, reinstall system from GitHub clone.\n");
  mysqli_close($DBcnx);
  exit;
}
//---------------------------------------------------------------------------------------------
function RebootSystem() {
  global $DBcnx;
  mysqli_close($DBcnx);
  $Script = "#!/bin/bash\n\n" .
            "echo \"Rebooting in 30 seconds.\"\n" .
            "/usr/bin/sleep 30\n" .
            "/usr/sbin/shutdown -r now\n";
  file_put_contents("/tmp/rss-script",$Script);
  shell_exec("chmod +x /tmp/rss-script");
  shell_exec("/tmp/rss-script");
  sleep(30);
}
//---------------------------------------------------------------------------------------------
function ShutdownSystem() {
  global $DBcnx;
  mysqli_close($DBcnx);
  $Script = "#!/bin/bash\n\n" .
            "echo \"Shutting down in 30 seconds.\"\n" .
            "/usr/bin/sleep 30\n" .
            "/usr/sbin/shutdown -P now\n";
  file_put_contents("/tmp/rss-script",$Script);
  shell_exec("chmod +x /tmp/rss-script");
  shell_exec("/tmp/rss-script");
  sleep(30);
}
//---------------------------------------------------------------------------------------------
$Config    = parse_ini_file("/usr/share/rpi-smart-still/config.ini");
$HydroPort = $Config["HYDRO_PORT"];
//---------------------------------------------------------------------------------------------
$Result = mysqli_query($DBcnx,"SELECT * FROM output_table WHERE executed=0");
while ($RS = mysqli_fetch_assoc($Result)) {
  print_r($RS);
  $Counter ++;
  if ($RS["valve_id"] < 3) {
    // Control commands for the condenser and dephleg cooling valves
    if ($RS["direction"] == 0) {
      $Direction = "close";
    } else {
      $Direction = "open";
    }
    if ($Settings["speech_enabled"] == 1) {
      if ($RS["valve_id"] == 1) {
        if ($RS["direction"] == 0) {
          if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(1);
        } else {
          if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(0);
        }
      } else {
        if ($RS["direction"] == 0) {
          if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(3);
        } else {
          if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(2);
        }
      }
    }
    shell_exec("/usr/share/rpi-smart-still/valve " . $RS["valve_id"] . " $Direction " . $RS["duration"]);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 3) {
    if ($Boilermaker["enabled"] == 1) {
      // Control commands for a Boilermaker
      if ($Boilermaker["online"] == 1) {
        if ($RS["position"] > 0) {
          PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
          PingHost($Boilermaker["ip_address"]);
          PingHost($Boilermaker["ip_address"]);
          $Runtime = trim(BoilermakerQuery($Boilermaker["ip_address"],"/get-runtime")); // Get the Boilermaker current runtime
          if (($Runtime != "") && ($Runtime == 0)) {
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_16=0"); // Make sure the Boilermaker's countdown timer is disabled
            BoilermakerQuery2($Boilermaker["ip_address"],"/?data_0=0"); // Put the Boilermaker into Constant Power mode
            BoilermakerQuery2($Boilermaker["ip_address"],"/start-run"); // Start the Boilermaker
          }
          BoilermakerQuery2($Boilermaker["ip_address"],"/?power=" . $RS["position"]); // Set the Boilermaker power level
        } else {
          BoilermakerQuery2($Boilermaker["ip_address"],"/stop-run"); // Stop the Boilermaker
        }
        if ($RS["direction"] == 0) {
          if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(5);
        } else {
          if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(4);
        }
      } else {
        if ($Settings["speech_enabled"] == 1) SpeakMessage(59);
      }
    } else {
      // Control commands for the heating stepper motor
      if ($Settings["heating_polarity"] == 0) {
        if ($RS["direction"] == 0) {
          $Direction = "ccw";
        } else {
          $Direction = "cw";
        }
      } else {
        if ($RS["direction"] == 0) {
          $Direction = "cw";
        } else {
          $Direction = "ccw";
        }
      }
      if ($RS["direction"] == 0) {
        if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(5);
      } else {
        if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(4);
      }
      shell_exec("/usr/share/rpi-smart-still/heating $Direction " . $RS["duration"]);
      if ($Settings["active_run"] == 0) {
        sleep(1);
        shell_exec("/usr/share/rpi-smart-still/heating disable");
      }
      if ($Settings["heating_analog"] == 1) sleep(1);
    }
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 4) {
    // Control commands to calibrate the valves
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(24);
    $Result1 = trim(shell_exec("/usr/share/rpi-smart-still/valve 1 open calibrate"));
    $Result2 = trim(shell_exec("/usr/share/rpi-smart-still/valve 1 close calibrate"));
    $Total   = round(($Result1 + $Result2) / 2,0,PHP_ROUND_HALF_UP);
    $Pulses  = round($Total / 100,0,PHP_ROUND_HALF_UP);
    if ($Pulses > 0) $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_total='$Total',valve1_pulse='$Pulses',valve1_position ='0' WHERE ID=1");
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(25);
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(26);
    $Result1 = trim(shell_exec("/usr/share/rpi-smart-still/valve 2 open calibrate"));
    $Result2 = trim(shell_exec("/usr/share/rpi-smart-still/valve 2 close calibrate"));
    $Total   = round(($Result1 + $Result2) / 2,0,PHP_ROUND_HALF_UP);
    $Pulses  = round($Total / 100,0,PHP_ROUND_HALF_UP);
    if ($Pulses > 0) $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_total='$Total',valve2_pulse='$Pulses',valve2_position ='0' WHERE ID=1");
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(27);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 5) {
    // Reboot the Boilermaker (if one is configured)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(62);
    PingHost($Boilermaker["ip_address"]); // Wake up that damn ESP32 since they like to go WiFi lazy without activity
    PingHost($Boilermaker["ip_address"]);
    PingHost($Boilermaker["ip_address"]);
    BoilermakerQuery($Boilermaker["ip_address"],"/reboot"); // Reboot the Boilermaker
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 6) {
    // Control command to reboot the hydrometer (Load cell and LIDAR versions)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(33);
    shell_exec("/usr/bin/echo \"!\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 7) {
    // Control command to recalibrate the hydrometer (Load cell version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(32);
    shell_exec("/usr/bin/echo \"\#\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 50) {
    // Only play a voice prompt if relay 1 is activated
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(50);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 51) {
    // Only play a voice prompt if relay 1 is deactivated
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(51);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 52) {
    // Only play a voice prompt if relay 2 is activated
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(52);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 53) {
    // Only play a voice prompt if relay 2 is deactivated
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(53);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 70) {
    // Control command to calibrate the hydrometer 0% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(37);
    shell_exec("/usr/bin/echo \"\#0\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 71) {
    // Control command to calibrate the hydrometer 10% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(38);
    shell_exec("/usr/bin/echo \"\#1\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 72) {
    // Control command to calibrate the hydrometer 20% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(39);
    shell_exec("/usr/bin/echo \"\#2\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 73) {
    // Control command to calibrate the hydrometer 30% slot (LIDAR version)
    if ($RS["muted"] == 0) SpeakMessage(40);
    shell_exec("/usr/bin/echo \"\#3\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 74) {
    // Control command to calibrate the hydrometer 40% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(41);
    shell_exec("/usr/bin/echo \"\#4\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 75) {
    // Control command to calibrate the hydrometer 50% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(42);
    shell_exec("/usr/bin/echo \"\#5\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 76) {
    // Control command to calibrate the hydrometer 60% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(43);
    shell_exec("/usr/bin/echo \"\#6\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 77) {
    // Control command to calibrate the hydrometer 70% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(44);
    shell_exec("/usr/bin/echo \"\#7\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 78) {
    // Control command to calibrate the hydrometer 80% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(45);
    shell_exec("/usr/bin/echo \"\#8\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 79) {
    // Control command to calibrate the hydrometer 90% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(46);
    shell_exec("/usr/bin/echo \"\#9\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 80) {
    // Control command to calibrate the hydrometer 100% slot (LIDAR version)
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(47);
    shell_exec("/usr/bin/echo \"\#a\" > $HydroPort");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 90) {
    // Control command to reboot the system
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(56);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
    RebootSystem();
  } elseif ($RS["valve_id"] == 91) {
    // Control command to shut down the system
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(57);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
    ShutdownSystem();
  } elseif ($RS["valve_id"] == 99) {
    // Control commands to speak notifications with no other actions
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) {
      if ($RS["position"] == 1) {
        DebugMessage("Performing boiler heating controller jump to " . $RS["duration"] . "%");
      } elseif ($RS["position"] == 2) {
        SpeakMessage(30);
      } elseif ($RS["position"] == 3) {
        SpeakMessage(35);
      } elseif ($RS["position"] == 4) {
        SpeakMessage(36);
      }
    }
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  }
}
//---------------------------------------------------------------------------------------------
if ($Counter == 0) {
  // If there were no waiting tasks executed, check the limit switches on the valves and update the auxilliary relay states
  for ($x = 1; $x <= 2; $x ++) {
    $Status = trim(shell_exec("/usr/share/rpi-smart-still/valve $x status"));
    if ($Status == 0) {
      $Update = mysqli_query($DBcnx,"UPDATE settings SET valve" . $x . "_position ='0' WHERE ID=1");
    } elseif ($Status == 1) {
      if ($x == 1) {
        $Total = $Settings["valve1_total"];
      } else {
        $Total = $Settings["valve2_total"];
      }
      $Update = mysqli_query($DBcnx,"UPDATE settings SET valve" . $x . "_position ='$Total' WHERE ID=1");
    }
  }
  shell_exec("/usr/share/rpi-smart-still/relay 1 " . $Settings["relay1_state"]);
  shell_exec("/usr/share/rpi-smart-still/relay 2 " . $Settings["relay2_state"]);
}
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
