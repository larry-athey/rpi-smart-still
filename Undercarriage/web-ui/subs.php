<?php
//---------------------------------------------------------------------------------------------------
ini_set('display_errors',1);
ini_set('error_reporting',E_ALL);
//---------------------------------------------------------------------------------------------------
function getOneWireTemp($Address) {
  if (file_exists("/sys/bus/w1/devices/$Address/w1_slave")) {
    $Poll = file_get_contents("/sys/bus/w1/devices/$Address/w1_slave");
    preg_match("/.*?t=(.*)/i",$Poll,$M);
    $Temp = $M[1];
    $Data["C"] = $Temp * .001;
    $Data["F"] = ($Data["C"] * (9 / 5)) + 32;
  } else {
    $Data["C"] = -1000;
    $Data["F"] = -1000;
  }
  return $Data;
}
//---------------------------------------------------------------------------------------------------
?>
