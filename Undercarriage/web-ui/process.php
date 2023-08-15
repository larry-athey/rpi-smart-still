<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
//---------------------------------------------------------------------------------------------------
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
//---------------------------------------------------------------------------------------------------
if (isset($_GET["active_run"])) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);
  if ($_GET["active_run"] == 1) {
    $Update = mysqli_query($DBcnx,"TRUNCATE input_table");
    $Update = mysqli_query($DBcnx,"TRUNCATE output_table");
    $Update = mysqli_query($DBcnx,"TRUNCATE logic_tracker");
    $Update = mysqli_query($DBcnx,"UPDATE settings SET active_run='1',run_start=now(),run_end=NULL WHERE ID=1");
    $Insert = mysqli_query($DBcnx,"INSERT INTO logic_tracker (run_start,boiler_done,boiler_last_adjustment,boiler_note,hydrometer_started) " .
                                  "VALUES (1,0,now(),'Waiting for the boiler to reach its minimum temperature',0)");
    if ($Program["dephleg_managed"] == 1) {
      $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_done='0',dephleg_last_adjustment=now(),dephleg_note='Waiting for the dephleg sensor to reach its minimum temperature' WHERE ID=1");
    } else {
      $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_done='0',dephleg_last_adjustment=now(),dephleg_note='Not managing the dephleg temperature in this program' WHERE ID=1");
    }
    if ($Program["column_managed"] == 1) {
      $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_done='0',column_last_adjustment=now(),column_note='Waiting for the column to reach its minimum temperature' WHERE ID=1");
    } else {
      $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET column_done='0',column_last_adjustment=now(),column_note='Not managing the column temperature in this program' WHERE ID=1");
    }
    if ($Program["abv_managed"] == 1) {
      $Update = mysqli_query($DBcnx,"UPDATE settings SET saved_lower='" . $Program["column_temp_low"] . "',saved_upper='" . $Program["column_temp_high"] . "' WHERE ID=1");
    }
  } elseif ($_GET["active_run"] == 0) {
    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET run_start='2' WHERE ID=1");
    $Update = mysqli_query($DBcnx,"UPDATE settings SET active_run='0',run_end=now() WHERE ID=1");
    if ($Program["abv_managed"] == 1) {
      $Update = mysqli_query($DBcnx,"UPDATE programs SET column_temp_low='" . $Settings["saved_lower"] . "',column_temp_high='" . $Settings["saved_upper"] . "' WHERE ID=" . $Program["ID"]);
    }
  }
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_GET["calibrate_valves"])) {
  $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,muted,executed) VALUES (now(),'1','4','0','0')");
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_GET["heat_jump"])) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM heating_translation WHERE percent=" . $_GET["value"]);
  $Heating  = mysqli_fetch_assoc($Result);

  if ($_GET["value"] == 0) {
    $Heating["position"] = 0;
  } elseif ($_GET["value"] == 100) {
    $Heating["position"] = $Settings["heating_total"];
  }

  // Requires a difference in heating stepper position to process
  $Difference = 0;
  if ($Settings["heating_enabled"] == 1) {
    if ($Heating["position"] > $Settings["heating_position"]) {
      $Difference = $Heating["position"] - $Settings["heating_position"];
      $Direction = 1;
    } elseif ($Heating["position"] < $Settings["heating_position"]) {
      $Difference = $Settings["heating_position"] - $Heating["position"];
      $Direction = 0;
    }
    if ($Difference > 0) {
      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                    "VALUES (now(),'1','99','0','" . $_GET["value"] . "','1','0','0')");
      $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='" . $Heating["position"] . "' WHERE ID=1");
      if ($Settings["heating_analog"] == 1) { // A digital voltmeter doesn't mean that it's a digital voltage controller!
        $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                      "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','1','0')," .
                                             "(now(),'1','3','1','" . $Heating["position"] . "','" . $Heating["position"] . "','1','0')");
      } else { // Digital voltage controllers and gas valves can just be adjusted up and down as necessary
        $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                      "VALUES (now(),'1','3','$Direction','$Difference','" . $Heating["position"] . "','1','0')");
      }
    }
  }
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_POST["rss_edit_servos"])) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  // Safety nets just in case somebody is using a seriously outdated web browser that won't enforce form value limits
  $Valve1 = round($_POST["Valve1"] * $Settings["valve1_pulse"],1,PHP_ROUND_HALF_UP);
  if ($Valve1 > $Settings["valve1_total"]) $Valve1 = $Settings["valve1_total"]; // Prevents submissions > 100%
  $Valve2 = round($_POST["Valve2"] * $Settings["valve2_pulse"],1,PHP_ROUND_HALF_UP);
  if ($Valve2 > $Settings["valve2_total"]) $Valve2 = $Settings["valve2_total"]; // Prevents submissions > 100%
  if ($_POST["Heating"] > $Settings["heating_total"]) $_POST["Heating"] = $Settings["heating_total"]; // Same as above but different

  // Requires a difference in Valve1 position to process
  $Difference = 0;
  if ($Valve1 > $Settings["valve1_position"]) {
    $Difference = $Valve1 - $Settings["valve1_position"];
    $Direction = 1;
  } elseif ($Valve1 < $Settings["valve1_position"]) {
    $Difference = $Settings["valve1_position"] - $Valve1;
    $Direction = 0;
  }
  if ($Difference > 0) {
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve1_position='$Valve1' WHERE ID=1");
    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                  "VALUES (now(),'1','1','$Direction','$Difference','$Valve1','0','0')");
  }

  // Requires a difference in Valve2 position to process
  $Difference = 0;
  if ($Valve2 > $Settings["valve2_position"]) {
    $Difference = $Valve2 - $Settings["valve2_position"];
    $Direction = 1;
  } elseif ($Valve2 < $Settings["valve2_position"]) {
    $Difference = $Settings["valve2_position"] - $Valve2;
    $Direction = 0;
  }
  if ($Difference > 0) {
    $Update = mysqli_query($DBcnx,"UPDATE settings SET valve2_position='$Valve2' WHERE ID=1");
    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                  "VALUES (now(),'1','2','$Direction','$Difference','$Valve2','0','0')");
  }

  // Requires a difference in heating stepper position to process
  $Difference = 0;
  if ($Settings["heating_enabled"] == 1) {
    if ($_POST["Heating"] > $Settings["heating_position"]) {
      $Difference = $_POST["Heating"] - $Settings["heating_position"];
      $Direction = 1;
    } elseif ($_POST["Heating"] < $Settings["heating_position"]) {
      $Difference = $Settings["heating_position"] - $_POST["Heating"];
      $Direction = 0;
    }
    if ($Difference > 0) {
      $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='" . $_POST["Heating"] . "' WHERE ID=1");
      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                    "VALUES (now(),'1','99','0','0','2','0','0')");
      if ($Settings["heating_analog"] == 1) { // A digital voltmeter doesn't mean that it's a digital voltage controller!
        $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                      "VALUES (now(),'0','3','0','" . $Settings["heating_position"] . "','0','1','0')," .
                                             "(now(),'1','3','1','" . $_POST["Heating"] . "','" . $_POST["Heating"] . "','1','0')");
      } else { // Digital voltage controllers and gas valves can just be adjusted up and down as necessary
        $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                      "VALUES (now(),'1','3','$Direction','$Difference','" . $_POST["Heating"] . "','1','0')");
      }
    }
  }
}
//---------------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
header("Location: index.php");
//---------------------------------------------------------------------------------------------------
?>
