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
function FormatEthanol($Value) {
  if ($Value <= 19) {
    $Color = "text-primary";
  } elseif (($Value > 19) && ($Value <= 29)) {
    $Color = "text-info";
  } elseif (($Value > 29) && ($Value <= 49)) {
    $Color = "text-success";
  } elseif (($Value > 49) && ($Value <= 64)) {
    $Color = "text-warning";
  } elseif (($Value > 64) && ($Value <= 84)) {
    $Color = "text-magenta";
  } else {
    $Color = "text-danger";
  }
  $Content = "<p class=\"card-text $Color\" style=\"font-size:3em;text-align:center;\">$Value % /  " . $Value * 2 . " P</p>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function FormatEthanolMeter($Value) {
  if ($Value <= 19) {
    $Color = "bg-primary";
  } elseif (($Value > 19) && ($Value <= 29)) {
    $Color = "bg-info";
  } elseif (($Value > 29) && ($Value <= 49)) {
    $Color = "bg-success";
  } elseif (($Value > 49) && ($Value <= 64)) {
    $Color = "bg-warning";
  } elseif (($Value > 64) && ($Value <= 84)) {
    $Color = "bg-magenta";
  } else {
    $Color = "bg-danger";
  }
  $Content  = "<div class=\"progress\" role=\"progressbar\" aria-label=\"\" aria-valuenow=\"$Value\" aria-valuemin=\"0\" aria-valuemax=\"100\">";
  $Content .=   "<div class=\"progress-bar $Color\" style=\"width: $Value%\"></div>";
  $Content .= "</div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function FormatTemp($TempC) {
  $TempF = round(($TempC * (9 / 5)) + 32,1);
  return "<span class=\"text-light\">" . $TempC . "C / " . $TempF . "F</span>";
}
//---------------------------------------------------------------------------------------------------
function FormatTempRange($Lower,$Upper,$Managed) {
  $LowerTempF = round(($Lower * (9 / 5)) + 32,1) . "F";
  $UpperTempF = round(($Upper * (9 / 5)) + 32,1) . "F";
  $Lower .= "C";
  $Upper .= "C";
  if ($Managed == 1) {
    $Content  = "<span class=\"text-success\">$Lower</span> / <span class=\"text-success\">$LowerTempF</span> to ";
    $Content .= "<span class=\"text-danger\">$Upper</span> / <span class=\"text-danger\">$UpperTempF</span>";
  } else {
    $Content  = "<span class=\"text-secondary\">$Lower / $LowerTempF to $Upper / $UpperTempF</span>";
  }
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function FormatValvePosition($Total,$Position) {
  return "<span class=\"text-light\">" . round($Position / $Total * 100,1) . "%</span>";
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
function getSensorList() {
  $Files = array_diff(scandir("/sys/bus/w1/devices/"),array('.','..','w1_bus_master1'));
  $Files = array_values($Files);
  return $Files;
}
//---------------------------------------------------------------------------------------------------
function PosToPct($Total,$Position) {
  return round($Position / $Total * 100,1);
}
//---------------------------------------------------------------------------------------------------
function ProgramTypeSelector($Selected) {
  $PType[] = "Pot Still Mode (No Dephleg Management)";
  $PType[] = "Reflux Mode (No Column Management)";
  $Content = "<select style=\"width: 100%;margin-bottom: 0.5em;\" size=\"1\" class=\"form-control form-select\" id=\"ProgramType\" name=\"ProgramType\" onChange=\"ToggleInputFields(this)\">";
  for ($x = 0; $x <= 1; $x ++) {
    if ($x == $Selected) {
      $Content .= "<option selected value=\"$x\">$PType[$x]</option>";
    } else {
      $Content .= "<option value=\"$x\">$PType[$x]</option>";
    }
  }
  $Content .= "</select>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function SecsToTime($Seconds) {
  $dtF = new \DateTime('@0');
  $dtT = new \DateTime("@$Seconds");
  $Out = $dtF->diff($dtT)->format('%a Days + %h:%i');
  $Tmp = explode(":",$Out);
  if (strlen($Tmp[1]) == 1) $Tmp[1] = "0" . $Tmp[1];
  return $Tmp[0] . ":" . $Tmp[1];
}
//---------------------------------------------------------------------------------------------------
function YNSelector($Selected,$ID) {
  if ($Selected == 0) {
    $S0 = "selected";
    $S1 = "";
  } else {
    $S0 = "";
    $S1 = "selected";
  }
  $Content  = "<select style=\"width: 100%;\" size=\"1\" class=\"form-control form-select\" id=\"$ID\" name=\"$ID\" aria-describedby=\"$ID" . "Help\">";
  $Content .= "<option $S1 value=\"1\">Yes</option>";
  $Content .= "<option $S0 value=\"0\">No</option>";
  $Content .= "</select>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
?>
