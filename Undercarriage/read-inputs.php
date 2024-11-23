#!/usr/bin/php
<?php
//---------------------------------------------------------------------------------------------
require_once("/var/www/html/subs.php");
//---------------------------------------------------------------------------------------------
set_time_limit(600);
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

// Read the three DS18B20 temperature sensors and update the settings table individually so as to keep -1000 failure readings out of the timeline
$Data = getOneWireTemp($Settings["boiler_addr"]);
$BoilerTemp = $Data["C"];
echo("Boiler: $BoilerTemp\n");
if ($BoilerTemp > 0) {
  $Update = mysqli_query($DBcnx,"UPDATE settings SET boiler_temp='$BoilerTemp' WHERE ID=1");
} else {
  $BoilerTemp = $Settings["boiler_temp"];
}

$Data = getOneWireTemp($Settings["dephleg_addr"]);
$DephlegTemp = $Data["C"];
echo("Dephleg: $DephlegTemp\n");
if ($DephlegTemp > 0) {
  $Update = mysqli_query($DBcnx,"UPDATE settings SET dephleg_temp='$DephlegTemp' WHERE ID=1");
} else {
  $DephlegTemp = $Settings["dephleg_temp"];
}

$Data = getOneWireTemp($Settings["column_addr"]);
$ColumnTemp = $Data["C"];
echo("Column: $ColumnTemp\n\n");
if ($ColumnTemp > 0) {
  $Update = mysqli_query($DBcnx,"UPDATE settings SET column_temp='$ColumnTemp' WHERE ID=1");
} else {
  $ColumnTemp = $Settings["column_temp"];
}

// Read any waiting serial data from the digital hydrometer
$Hydrometer = str_replace("\r","",trim(shell_exec("/usr/bin/timeout 10s /usr/share/rpi-smart-still/hydro-read") . " "));

// If we got a valid data block from the hydrometer, explode it and update the settings table
if (($Hydrometer != "") && (mb_substr($Hydrometer,-1) == "#")) {
  $Hydrometer = trim(str_replace("#","",$Hydrometer));
  $Data = explode("\n",$Hydrometer);
  $Hydrometer = addslashes($Hydrometer);
  if (count($Data) == 5) {
    print_r($Data);
    $Data2 = explode(": ",$Data[2]);
    $DistillateFlow = trim($Data2[1]);
    $Data2 = explode(": ",$Data[3]);
    $DistillateAbv = trim($Data2[1]);
    $Data2 = explode(": ",$Data[4]);
    $DistillateTemp = trim($Data2[1]);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET distillate_flow='$DistillateFlow',distillate_abv='$DistillateAbv',distillate_temp='$DistillateTemp',serial_data='$Hydrometer' WHERE ID=1");
  } else {
    echo("$Hydrometer\n");
    $Update = mysqli_query($DBcnx,"UPDATE settings SET serial_data='$Hydrometer' WHERE ID=1");
  }
}

// If we have an active run, create a record in the input_table every minute for the timeline graphs
$Sec = date("s",time());
if (($Settings["active_run"] ==  1) && ($Sec <= 10)) {
  $Flow = 0;
  $Data = explode("|",trim($Settings["distillate_flow"],"|"));
  if (count($Data) == 100) {
    for ($x = 0; $x <= 99; $x ++) {
      if ($Data[$x] ==  1) $Flow ++;
    }
  }
  if (count($Data) == 5) {
    $Insert = mysqli_query($DBcnx,"INSERT INTO input_table (timestamp,boiler_temp,dephleg_temp,column_temp,distillate_temp,distillate_abv,distillate_flow) " .
                                  "VALUES (now(),'$BoilerTemp','$DephlegTemp','$ColumnTemp','$DistillateTemp','$DistillateAbv','$Flow')");
  } else {
    echo("No hydrometer data\n");
    $Insert = mysqli_query($DBcnx,"INSERT INTO input_table (timestamp,boiler_temp,dephleg_temp,column_temp,distillate_temp,distillate_abv,distillate_flow) " .
                                  "VALUES (now(),'$BoilerTemp','$DephlegTemp','$ColumnTemp','" . $Settings["distillate_temp"] . "','" . $Settings["distillate_abv"] . "','$Flow')");
  }
}
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
