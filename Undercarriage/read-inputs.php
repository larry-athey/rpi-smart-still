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
  $Result = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);
} else {
  echo("System settings record is missing, reinstall system from GitHub clone.\n");
  mysqli_close($DBcnx);
  exit;
}

// ESP32 seems to put its WiFi to sleep if it's not kept active, so we ping it every time this script runs to keep it awake
if ($Boilermaker["enabled"] == 1) {
  if (PingHost($Boilermaker["ip_address"])) {
    $Update = mysqli_query($DBcnx,"UPDATE boilermaker SET online='1' WHERE ID=1");
    $Boilermaker["online"] = 1;
  } else {
    $Update = mysqli_query($DBcnx,"UPDATE boilermaker SET online='0' WHERE ID=1");
    $Boilermaker["online"] = 0;
  }
}

if ($Boilermaker["enabled"] == 1) {
  // If a Boilermaker is configured, we get the boiler temperature from that instead of the DS18B20
  if ($Boilermaker["online"] == 1) {
    $BoilerTemp = trim(BoilermakerQuery($Boilermaker["ip_address"],"/get-tempc"));
    if ($BoilerTemp == "") $BoilerTemp = 0;
  } else {
    $BoilerTemp = 0;
  }
  // If a Boilermaker is configured, get its current power level during an active run since it manages its own power
  if ($Settings["active_run"] == 1) {
    if ($Boilermaker["online"] == 1) {
      $BoilerPower = trim(BoilermakerQuery($Boilermaker["ip_address"],"/get-power"));
      if (is_numeric($BoilerPower)) $Update = mysqli_query($DBcnx,"UPDATE settings SET heating_position='$BoilerPower' WHERE ID=1");
    }
  }
} else {
  // Read the three DS18B20 temperature sensors and update the settings table individually so as to keep -1000 failure readings out of the timeline
  $Data = getOneWireTemp($Settings["boiler_addr"]);
  $BoilerTemp = $Data["C"];
}

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
    // If the flow sensor is absolutely dry, the capacitance will nose dive into the range of valid readings.
    // Therefore, we ignore any flow sensor readings until the hydrometer has begun floating.
    if ($DistillateAbv == 0) $DistillateFlow = 0;
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
