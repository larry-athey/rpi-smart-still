#!/usr/bin/php
<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
//---------------------------------------------------------------------------------------------
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
//---------------------------------------------------------------------------------------------
$Result = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
if (mysqli_num_rows($Result) > 0) {
  $Settings = mysqli_fetch_assoc($Result);
} else {
  echo("System settings record is missing, reinstall system from GitHub clone.\n");
  mysqli_close($DBcnx);
  exit;
}

if ($Settings["speech_enabled"] == 1) {
  $Result = mysqli_query($DBcnx,"SELECT * FROM voice_prompts WHERE seen_by NOT LIKE '%localhost%' ORDER BY ID LIMIT 1");
  if (mysqli_num_rows($Result) > 0) {
    $RS = mysqli_fetch_assoc($Result);
    shell_exec("/usr/bin/mpg123 /var/www/html/voice_prompts/" . $RS["filename"]);
    $Result = mysqli_query($DBcnx,"UPDATE voice_prompts SET seen_by=CONCAT('localhost|',seen_by) WHERE ID=" . $RS["ID"]);
  }
}
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
