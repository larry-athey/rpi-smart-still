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
    $Update = mysqli_query($DBcnx,"UPDATE settings SET active_run='0',run_end=now(),paused='0',pause_return='0' WHERE ID=1");
    if ($Program["abv_managed"] == 1) {
      $Update = mysqli_query($DBcnx,"UPDATE programs SET column_temp_low='" . $Settings["saved_lower"] . "',column_temp_high='" . $Settings["saved_upper"] . "' WHERE ID=" . $Program["ID"]);
      $Update = mysqli_query($DBcnx,"UPDATE settings SET saved_lower='0',saved_upper='0' WHERE ID=1");
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
elseif (isset($_GET["pause_run"])) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);
  if ($_GET["pause_run"] == 1) {
    // Pause the current run
    $Difference = $Settings["valve2_total"] - $Settings["valve2_position"] + 500; // Extra 500 to guarantee that we hit the upper limit switch
    $Update = mysqli_query($DBcnx,"UPDATE settings SET paused='1',pause_return='" . $Settings["valve2_position"] . "',valve2_position='" . $Settings["valve2_total"] . "' WHERE ID=1");
    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_done='0',dephleg_note='Distillation run paused, increasing dephleg cooling valve to 100%' WHERE ID=1");
    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                  "VALUES (now(),'1','99','0','0','3','0','0')," .
                                         "(now(),'1','2','1','$Difference','" . $Settings["valve2_total"] . "','1','0')");
  } else {
    // Resume run from pause
    if ($Program["mode"] == 0) {
      $Message = "Not managing the dephleg temperature in this program";
    } else {
      $Message = "Distillation resumed, restarting dephleg warmup";
    }
    $Difference = $Settings["valve2_position"] - $Settings["pause_return"];
    $Update = mysqli_query($DBcnx,"UPDATE settings SET paused='0',pause_return='0',valve2_position='" . $Settings["pause_return"] . "' WHERE ID=1");
    $Update = mysqli_query($DBcnx,"UPDATE logic_tracker SET dephleg_done='0',dephleg_note='$Message' WHERE ID=1");
    $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,direction,duration,position,muted,executed) " .
                                  "VALUES (now(),'1','99','0','0','4','0','0')," .
                                         "(now(),'1','2','0','$Difference','" . $Settings["pause_return"] . "','1','0')");
  }
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_GET["reboot_hydro"])) {
  $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,muted,executed) VALUES (now(),'1','6','0','0')");
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_GET["recalibrate_hydro"])) {
  $Insert = mysqli_query($DBcnx,"INSERT INTO output_table (timestamp,auto_manual,valve_id,muted,executed) VALUES (now(),'1','7','0','0')");
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_POST["rss_edit_heating"])) {
  //echo("<pre>\n");
  //print_r($_POST);
  //echo("<pre>\n");

  $HeatingEnabled  = $_POST["HeatingEnabled"];
  $HeatingPolarity = $_POST["HeatingPolarity"];
  $HeatingAnalog   = $_POST["HeatingAnalog"];
  $HeatingTotal    = $_POST["HeatingTotal"];
  $Heating10       = $_POST["Heating10"];
  $Heating20       = $_POST["Heating20"];
  $Heating30       = $_POST["Heating30"];
  $Heating40       = $_POST["Heating40"];
  $Heating50       = $_POST["Heating50"];
  $Heating60       = $_POST["Heating60"];
  $Heating70       = $_POST["Heating70"];
  $Heating80       = $_POST["Heating80"];
  $Heating90       = $_POST["Heating90"];

  $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_enabled='$HeatingEnabled',heating_polarity='$HeatingPolarity',heating_analog='$HeatingAnalog',heating_total='$HeatingTotal' WHERE ID=1");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating10' WHERE percent=10");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating20' WHERE percent=20");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating30' WHERE percent=30");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating40' WHERE percent=40");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating50' WHERE percent=50");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating60' WHERE percent=60");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating70' WHERE percent=70");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating80' WHERE percent=80");
  $Update = mysqli_query($DBcnx,"UPDATE heating_translation SET position='$Heating90' WHERE percent=90");

  //exit;
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_POST["rss_edit_program"])) {
  //echo("<pre>\n");
  //print_r($_POST);
  //echo("<pre>\n");

  $ID              = $_POST["ID"];
  $ProgramName     = mysqli_escape_string($DBcnx,$_POST["ProgramName"]);
  $ProgramType     = $_POST["ProgramType"];
  $ABVmanaged      = $_POST["ABVmanaged"];
  $DistillateABV   = $_POST["DistillateABV"];
  $FlowManaged     = $_POST["FlowManaged"];
  $MinimumFlow     = $_POST["MinimumFlow"];
  $CondenserRate   = $_POST["CondenserRate"];
  $DephlegStart    = $_POST["DephlegStart"];
  $HeatingIdle     = $_POST["HeatingIdle"];
  $BoilerManaged   = $_POST["BoilerManaged"];
  $BoilerTempLow   = $_POST["BoilerTempLow"];
  $BoilerTempHigh  = $_POST["BoilerTempHigh"];
  $ColumnManaged   = $_POST["ColumnManaged"];
  $ColumnTempLow   = $_POST["ColumnTempLow"];
  $ColumnTempHigh  = $_POST["ColumnTempHigh"];
  $DephlegManaged  = $_POST["DephlegManaged"];
  $DephlegTempLow  = $_POST["DephlegTempLow"];
  $DephlegTempHigh = $_POST["DephlegTempHigh"];
  $Notes           = mysqli_escape_string($DBcnx,$_POST["Notes"]);

  if ($ID == 0) {

  } else {
    $Update = mysqli_query($DBcnx,"UPDATE programs SET program_name='$ProgramName',mode='$ProgramType',abv_managed='$ABVmanaged',distillate_abv='$DistillateABV'," .
                                  "flow_managed='$FlowManaged',minimum_flow='$MinimumFlow',condenser_rate='$CondenserRate',dephleg_start='$DephlegStart'," .
                                  "heating_idle='$HeatingIdle',boiler_managed='$BoilerManaged',boiler_temp_low='$BoilerTempLow',boiler_temp_high='$BoilerTempHigh'," .
                                  "column_managed='$ColumnManaged',column_temp_low='$ColumnTempLow',column_temp_high='$ColumnTempHigh',dephleg_managed='$DephlegManaged'," .
                                  "dephleg_temp_low='$DephlegTempLow',dephleg_temp_high='$DephlegTempHigh',notes='$Notes' WHERE ID=$ID");
  }

  mysqli_close($DBcnx);
  header("Location: index.php?page=programs");
  exit;
}
//---------------------------------------------------------------------------------------------------
elseif (isset($_POST["rss_edit_servos"])) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Valve1 = round($_POST["Valve1"] * $Settings["valve1_pulse"],1);
  $Valve2 = round($_POST["Valve2"] * $Settings["valve2_pulse"],1);

  // Requires a difference in Valve1 position to process
  $Difference = 0;
  if ($Valve1 > $Settings["valve1_position"]) {
    $Difference = $Valve1 - $Settings["valve1_position"];
    $Direction = 1;
  } elseif ($Valve1 < $Settings["valve1_position"]) {
    $Difference = $Settings["valve1_position"] - $Valve1;
    $Direction = 0;
  }
  $Difference = round($Difference);
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
  $Difference = round($Difference);
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
