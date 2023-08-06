<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
//---------------------------------------------------------------------------------------------------
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
//---------------------------------------------------------------------------------------------------
if (isset($_POST["rss_edit_servos"])) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

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
  }

  // Requires a difference in Heating position to process
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
    }
  }

  exit;
}
//---------------------------------------------------------------------------------------------------
header("Location: index.php");
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------------
?>
