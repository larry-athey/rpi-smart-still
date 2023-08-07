#!/usr/bin/php
<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
require_once("voice-prompts.php");
//---------------------------------------------------------------------------------------------
set_time_limit(600);
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
//---------------------------------------------------------------------------------------------
$Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
$Settings = mysqli_fetch_assoc($Result);

$Result   = mysqli_query($DBcnx,"SELECT * FROM output_table WHERE executed=0");
while ($RS = mysqli_fetch_assoc($Result)) {
  print_r($RS);
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
          SpeakMessage(1);
        } else {
          SpeakMessage(0);
        }
      } else {
        if ($RS["direction"] == 0) {
          SpeakMessage(3);
        } else {
          SpeakMessage(2);
        }
      }
    }
    shell_exec("/usr/share/rpi-smart-still/valve " . $RS["valve_id"] . " $Direction " . $RS["duration"]);
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET executed='1' WHERE ID=" . $RS["ID"]);
    $Status = trim(shell_exec("/usr/share/rpi-smart-still/valve " . $RS["valve_id"] . " status"));
    if ($Status == 0) {
      $Update = mysqli_query($DBcnx,"UPDATE settings SET valve" . $RS["valve_id"] . "_position ='0' WHERE ID=1");
    } elseif ($Status == 1) {
      if ($RS["valve_id"] == 1) {
        $Total = $Settings["valve1_total"];
      } else {
        $Total = $Settings["valve2_total"];
      }
      $Update = mysqli_query($DBcnx,"UPDATE settings SET valve" . $RS["valve_id"] . "_position ='$Total' WHERE ID=1");
    }
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
        SpeakMessage(5);
      } else {
        SpeakMessage(4);
      }
    }
    shell_exec("/usr/share/rpi-smart-still/heating $Direction " . $RS["duration"]);
    if (Settings["active_run"] == 0) shell_exec("/usr/share/rpi-smart-still/heating disable");
    $Update = mysqli_query($DBcnx,"UPDATE output_table SET executed='1' WHERE ID=" . $RS["ID"]);
  } elseif ($RS["valve_id"] == 4) {
    // Control commands to pause and unpause a distillation run

  }
}

//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
