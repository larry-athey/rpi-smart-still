<?php
//---------------------------------------------------------------------------------------------------
require_once("html.php");
//---------------------------------------------------------------------------------------------------
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);

if ($_GET["ID"] == "temperatures") {
  $Content = ShowTemperatures($DBcnx);
} elseif ($_GET["ID"] == "valve_positions") {
  $Content = ShowValves($DBcnx);
}

echo("$Content\n");
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------------
?>
