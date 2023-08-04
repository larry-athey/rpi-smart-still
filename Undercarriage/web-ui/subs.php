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
    $Data["C"] = round($Temp * .001,1);
    $Data["F"] = round(($Data["C"] * (9 / 5)) + 32,1);
  } else {
    $Data["C"] = -1000;
    $Data["F"] = -1000;
  }
  return $Data;
}
//---------------------------------------------------------------------------------------------------
function generateRandomString($length = 10) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}
//---------------------------------------------------------------------------------------------------
function AjaxRefreshJS($ID,$RandID) {
  $Content  = "\n<script type=\"text/javascript\">\n";
  //$Content .= "  // Random 4.5 to 5.5 second refresh time per card so things\n";
  //$Content .= "  // don't have such a robotic look by updating simultaneously.\n";
  //$Content .= "  // The sensor and logic loops run on 10 second delay cycles.\n";
  $Content .= "  jQuery(document).ready(function() {\n";
  $Content .= "    RandomDelay = 4500 + Math.floor(Math.random() * 1000) + 1;\n";
  $Content .= "    function refresh() {\n";
  $Content .= "      jQuery('#$RandID').load('./ajax.php?ID=$ID');\n";
  $Content .= "    }\n";
  $Content .= "    setInterval(function() {\n";
  $Content .= "      refresh()\n";
  $Content .= "    },RandomDelay);\n";
  $Content .= "  });\n";
  $Content .= "</script>\n";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function FormatTemp($TempC) {
  $TempF = round(($TempC * (9 / 5)) + 32,1);
  return $TempC . "C / " . $TempF . "F";
}
//---------------------------------------------------------------------------------------------------
?>
