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

$Data = getOneWireTemp($Settings["boiler_addr"]);
$BoilerTemp = $Data["C"];
echo("Boiler: $BoilerTemp\n");
$Data = getOneWireTemp($Settings["dephleg_addr"]);
$DephlegTemp = $Data["C"];
echo("Dephleg: $DephlegTemp\n");
$Data = getOneWireTemp($Settings["column_addr"]);
$ColumnTemp = $Data["C"];
echo("Column: $ColumnTemp\n\n");

$Update = mysqli_query($DBcnx,"UPDATE settings SET boiler_temp='$BoilerTemp',dephleg_temp='$DephlegTemp',column_temp='$ColumnTemp' WHERE ID=1");

$Hydrometer = str_replace("\r","",trim(shell_exec("/usr/share/rpi-smart-still/hydro-read")));

if (($Hydrometer != "") && (mb_substr($Hydrometer,-1) == "#")) {
  $Hydrometer = trim(str_replace("#","",$Hydrometer));
  $Data = explode("\n",$Hydrometer);
  $Hydrometer = addslashes($Hydrometer);
  if (count($Data) == 5) {
    print_r($Data);
    $Data2 = explode(": ",$Data[2]);
    $DistillateFlowing = trim($Data2[1]);
    $Data2 = explode(": ",$Data[3]);
    $DistillateAbv = trim($Data2[1]);
    $Data2 = explode(": ",$Data[4]);
    $DistillateTemp = trim($Data2[1]);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET distillate_flowing='$DistillateFlowing',distillate_abv='$DistillateAbv',distillate_temp='$DistillateTemp',serial_data='$Hydrometer' WHERE ID=1");
  } else {
    echo($Hydrometer);
    $Update = mysqli_query($DBcnx,"UPDATE settings SET serial_data='$Hydrometer' WHERE ID=1");
  }
}

$Sec = date("s",time());
if (($Settings["active_run"] ==  1) && ($Sec <= 10) && (count($Data) == 5)) {
  //$Insert = mysqli_query($DBcnx,"INSERT INTO input_table (DeviceID,TimeStamp,Reading,RawText) VALUES (" . $Sensor["ID"] . ",now(),'$Reading','$RawText')");
}
//---------------------------------------------------------------------------------------------
mysqli_close($DBcnx);
//---------------------------------------------------------------------------------------------
?>
