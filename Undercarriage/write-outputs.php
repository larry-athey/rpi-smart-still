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
$Settings = mysqli_fetch_assoc($Result);

$Result   = mysqli_query($DBcnx,"SELECT * FROM output_table WHERE executed=0");
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
          if ($RS["muted"] == 0) SpeakMessage(1);
        } else {
          if ($RS["muted"] == 0) SpeakMessage(0);
        }
      } else {
        if ($RS["direction"] == 0) {
          if ($RS["muted"] == 0) SpeakMessage(3);
        } else {
          if ($RS["muted"] == 0) SpeakMessage(2);
        }
      }
    }
    shell_exec("/usr/share/rpi-smart-still/valve " . $RS["valve_id"] . " $Direction " . $RS["duration"]);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 3) {
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
    if ($Settings["speech_enabled"] == 1) {
      if ($RS["direction"] == 0) {
        if ($RS["muted"] == 0) SpeakMessage(5);
      } else {
        if ($RS["muted"] == 0) SpeakMessage(4);
      }
    }
    shell_exec("/usr/share/rpi-smart-still/heating $Direction " . $RS["duration"]);
    if ($Settings["active_run"] == 0) {
      shell_exec("/usr/share/rpi-smart-still/heating disable");
    } else {
      if (($Settings["heating_analog"] == 1) && ($RS["auto_manual"]) == 0) {
        //DebugMessage("Sleeping 1 second1");
        sleep(1);
      }
    }
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 4) {
    // Control commands to calibrate the valves
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(24);
    $Result1 = trim(shell_exec("/usr/share/rpi-smart-still/valve 1 open calibrate"));
    $Result2 = trim(shell_exec("/usr/share/rpi-smart-still/valve 1 close calibrate"));
    $Total   = round(($Result1 + $Result2) / 2,0,PHP_ROUND_HALF_UP);
    $Pulses  = round($Total / 100,0,PHP_ROUND_HALF_UP);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_total='$Total',valve1_pulse='$Pulses',valve1_position ='0' WHERE ID=1");
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(25);
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(26);
    $Result1 = trim(shell_exec("/usr/share/rpi-smart-still/valve 2 open calibrate"));
    $Result2 = trim(shell_exec("/usr/share/rpi-smart-still/valve 2 close calibrate"));
    $Total   = round(($Result1 + $Result2) / 2,0,PHP_ROUND_HALF_UP);
    $Pulses  = round($Total / 100,0,PHP_ROUND_HALF_UP);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_total='$Total',valve2_pulse='$Pulses',valve2_position ='0' WHERE ID=1");
    if (($Settings["speech_enabled"] == 1) && ($RS["muted"] == 0)) SpeakMessage(27);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET timestamp=now(),executed='1' WHERE ID=" . $RS["ID"]);
  }  elseif ($RS["valve_id"] == 5) {
    // Control commands to pause and unpause a run
  } elseif ($RS["valve_id"] == 6) {
    // Control commands to reboot the hydrometer
  } elseif ($RS["valve_id"] == 7) {
    // Control commands to recalibrate the hydrometer
  }
}
//---------------------------------------------------------------------------------------------
if ($Counter == 0) {
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
}
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
