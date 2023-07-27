<?php
//---------------------------------------------------------------------------------------------------
ini_set('display_errors',1);
ini_set('error_reporting',E_ALL);
//---------------------------------------------------------------------------------------------------
define("DB_HOST","localhost");
define("DB_NAME","rpismartstill");
define("DB_USER","rssdbuser");
define("DB_PASS","rssdbpasswd");
//---------------------------------------------------------------------------------------------------
function getOneWireTemp($Address) {
  if (file_exists("/sys/bus/w1/devices/$Address/w1_slave")) {
    $Poll = file_get_contents("/sys/bus/w1/devices/$Address/w1_slave");
    preg_match("/.*?t=(.*)/i",$Poll,$M);
    $Temp = $M[1];
    $Data["C"] = round($Temp * .001,2);
    $Data["F"] = round(($Data["C"] * (9 / 5)) + 32,2);
  } else {
    $Data["C"] = -1000;
    $Data["F"] = -1000;
  }
  return $Data;
}
//---------------------------------------------------------------------------------------------------
?>
