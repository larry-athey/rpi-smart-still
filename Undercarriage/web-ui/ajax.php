<?php
//---------------------------------------------------------------------------------------------------
require_once("html.php");
//---------------------------------------------------------------------------------------------------
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if ($_GET["ID"] == "hydrometer") {
  $Content = ShowHydrometer($DBcnx);
} elseif ($_GET["ID"] == "logic_tracker") {
  $Content = LogicTracker($DBcnx);
} elseif ($_GET["ID"] == "program_temps") {
  $Content = ShowProgramTemps($DBcnx);
} elseif ($_GET["ID"] == "show_sensors") {
  $Content = ShowSensors($DBcnx);
} elseif ($_GET["ID"] == "show_serial") {
  $Content = ShowSerialData($DBcnx);
} elseif ($_GET["ID"] == "temperatures") {
  $Content = ShowTemperatures($DBcnx);
} elseif ($_GET["ID"] == "valve_positions") {
  $Content = ShowValves($DBcnx);
} elseif ($_GET["ID"] == "voice_prompter") {
  $Content = VoicePrompter($DBcnx,false);
}

echo("$Content\n");
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------------
?>
