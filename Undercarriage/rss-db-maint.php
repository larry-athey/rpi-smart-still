#!/usr/bin/php
<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
//---------------------------------------------------------------------------------------------
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
//---------------------------------------------------------------------------------------------
$Result = mysqli_query($DBcnx,"OPTIMIZE TABLE heating_translation");
$Result = mysqli_query($DBcnx,"OPTIMIZE TABLE input_table");
$Result = mysqli_query($DBcnx,"OPTIMIZE TABLE logic_tracker");
$Result = mysqli_query($DBcnx,"OPTIMIZE TABLE output_table");
$Result = mysqli_query($DBcnx,"OPTIMIZE TABLE programs");
$Result = mysqli_query($DBcnx,"OPTIMIZE TABLE settings");

$Result = mysqli_query($DBcnx,"SELECT * FROM voice_prompts WHERE timestamp < (NOW() - INTERVAL 5 MINUTE)");
while ($RS = mysqli_fetch_assoc($Result)) {
  unlink("/var/www/html/voice_prompts/" . $RS["filename"]);
}
$Result = mysqli_query($DBcnx,"DELETE FROM voice_prompts WHERE timestamp < (NOW() - INTERVAL 5 MINUTE)");
$Result = mysqli_query($DBcnx,"OPTIMIZE TABLE voice_prompts");
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
