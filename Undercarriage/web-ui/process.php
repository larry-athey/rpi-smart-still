<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
//---------------------------------------------------------------------------------------------------
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
//---------------------------------------------------------------------------------------------------
if (isset($_GET["active_run"])) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  if ($_GET["active_run"] == 1) {
    $Update = mysqli_query($DBcnx,"TRUNCATE input_table");
    $Update = mysqli_query($DBcnx,"TRUNCATE output_table");
    $Update = mysqli_query($DBcnx,"TRUNCATE logic_tracker");
    $Update = mysqli_query($DBcnx,"UPDATE settings SET active_run='1',run_start=now(),run_end=NULL WHERE ID=1");
    $Insert = mysqli_query($DBcnx,"INSERT INTO logic_tracker (run_start,boiler_done) VALUES (1,0)");
  } else {
    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET run_start='2' WHERE ID=1");
    $Update = mysqli_query($DBcnx,"UPDATE settings SET active_run='0',run_end=now() WHERE ID=1");
  }
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_POST["rss_edit_servos"])) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  // Safety nets just in case somebody is using a seriously outdated web browser that won't enforce form value limits
  $Valve1 = round($_POST["Valve1"] * $Settings["valve1_pulse"],0);
  if ($Valve1 > $Settings["valve1_total"]) $Valve1 = $Settings["valve1_total"]; // Prevents submissions > 100%
  $Valve2 = round($_POST["Valve2"] * $Settings["valve2_pulse"],0);
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
    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,executed) " .
                                  "VALUES (now(),'1','1','$Direction','$Difference','$Valve1','0')");
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
    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,executed) " .
                                  "VALUES (now(),'1','2','$Direction','$Difference','$Valve2','0')");
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
      $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,executed) " .
                                    "VALUES (now(),'1','3','$Direction','$Difference','" . $_POST["Heating"] . "','0')");
    }
  }
}
//---------------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
header("Location: index.php");
//---------------------------------------------------------------------------------------------------
?>
