<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
//---------------------------------------------------------------------------------------------
function SpeakMessage($ID) {
  $DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);

  $Msg[0] = "Increasing condenser valve position";
  $Msg[1] = "Decreasing condenser valve position";
  $Msg[2] = "Increasing dephlegmator valve position";
  $Msg[3] = "Decreasing dephlegmator valve position";
  $Msg[4] = "Increasing heating stepper position";
  $Msg[5] = "Decreasing heating stepper position";

  shell_exec("/usr/bin/espeak -v english-us -s 160 \"$Msg[$ID]\" 2> /dev/null &");
  mysqli_close($DBcnx);
}
//---------------------------------------------------------------------------------------------
?>
