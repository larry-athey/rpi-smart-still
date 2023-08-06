<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
//---------------------------------------------------------------------------------------------------
function DrawCard($DBcnx,$Body,$DoAjax) {
  $RandID   = "card_" . generateRandomString();
  $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em;  margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  if ($DoAjax) $Content .= AjaxRefreshJS($Body,$RandID);
  $Content .=     "<div id=\"$RandID\">";
  if ($Body == "hydrometer") {
    $Content .= ShowHydrometer($DBcnx);
  } elseif ($Body == "program_temps") {
    $Content .= ShowProgramTemps($DBcnx);
  } elseif ($Body == "edit_servos") {
    $Content .= ServoPositionEditor($DBcnx);
  } elseif ($Body == "temperatures") {
    $Content .= ShowTemperatures($DBcnx);
  } elseif ($Body == "valve_positions") {
    $Content .= ShowValves($DBcnx);
  }
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function DrawMenu($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);

  $Content  = "<nav class=\"navbar navbar-expand-lg navbar-dark bg-dark\">";
  $Content .=   "<div class=\"container-fluid\">";
  $Content .=     "<a class=\"navbar-brand\" href=\"/\"><span class=\"iconify text-white\" style=\"font-size: 1.5em;\" data-icon=\"logos:raspberry-pi\"></span>&nbsp;<span class=\"text-white\" style=\"font-weight: bold;\">RPi Smart Still</span></a>";
  $Content .=     "<button class=\"navbar-toggler\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#navbarSupportedContent\" aria-controls=\"navbarSupportedContent\" aria-expanded=\"false\" aria-label=\"Toggle navigation\">";
  $Content .=       "<span class=\"navbar-toggler-icon\"></span>";
  $Content .=     "</button>";
  $Content .=     "<div class=\"collapse navbar-collapse\" id=\"navbarSupportedContent\">";
  $Content .=       "<ul class=\"navbar-nav me-auto mb-2 mb-lg-0\">";
  $Content .=         "<li class=\"nav-item\">";
  $Content .=           "<a class=\"nav-link\" aria-current=\"page\" href=\"/\">Home</a>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item\">";
  $Content .=           "<a class=\"nav-link\" href=\"?page=timeline\">Timeline</a>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item dropdown\">";
  $Content .=           "<a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Programs</a>";
  $Content .=           "<ul class=\"dropdown-menu\" style=\"background-color: #212529;\">";

  $Result = mysqli_query($DBcnx,"SELECT * FROM programs ORDER BY program_name");
  while ($RS = mysqli_fetch_assoc($Result)) {
    if ($RS["ID"] == $Settings["active_program"]) $RS["program_name"] = "&#10003 " . $RS["program_name"];
    $RS["program_name"] = str_replace(" ","&nbsp;",$RS["program_name"]);
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?program_id=" . $RS["ID"] . "\">" . $RS["program_name"] . "</a></li>";
  }

  $Content .=           "</ul>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item dropdown\">";
  $Content .=           "<a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Management</a>";
  $Content .=           "<ul class=\"dropdown-menu\" style=\"background-color: #212529;\">";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=programs\">Edit&nbsp;Programs</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=valves\">Calibrate&nbsp;Valves</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=sensors\">Configure&nbsp;Sensors</a></li>";
  $Content .=             "<li><a class=\"dropdown-item disabled\" href=\"?page=heating\"><span class=\"text-secondary\">Configure&nbsp;Heating</span></a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=hydrometer\">Calibrate&nbsp;Hydrometer</a></li>";
  if ($Settings["speech_enabled"] == 0) {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?speech=1\">Enable&nbsp;Speech</a></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?speech=0\">Disable&nbsp;Speech</a></li>";
  }
  $Content .=             "<li><hr class=\"dropdown-divider\"></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?run=1\">Start&nbsp;Run</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?run=10\">Pause&nbsp;Run</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?run=0\">Stop&nbsp;Run</a></li>";
  $Content .=           "</ul>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item\">";
  $Content .=           "<a class=\"nav-link disabled\">Current Program: " . $Program["program_name"] . "</a>";
  $Content .=         "</li>";
  $Content .=       "</ul>";
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</nav>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ServoPositionEditor($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<p style=\"font-weight: bold;\">Servo Position Editor:</p>";
  $Content .= "<form id=\"servo_editor\" method=\"post\" action=\"process.php\">";
  $Content .= "<label for=\"Valve2\" class=\"form-label\">Dephleg Cooling Valve %</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Valve2\" name=\"Valve2\" min=\"0\" max=\"100\" step=\".1\" value=\"" . PosToPct($Settings["valve2_total"],$Settings["valve2_position"]) . "\">";
  $Content .= "<label for=\"Valve1\" class=\"form-label\" style=\"margin-top: .5em;\">Condenser Cooling Valve %</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Valve1\" name=\"Valve1\" min=\"0\" max=\"100\" step=\".1\" value=\"" . PosToPct($Settings["valve1_total"],$Settings["valve1_position"]) . "\">";
  $Content .= "<label for=\"Heating\" class=\"form-label\" style=\"margin-top: .5em;\">Heating Stepper Position [0.." . $Settings["heating_total"] . "]</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Heating\" name=\"Heating\" min=\"0\" max=\"" . $Settings["heating_total"] . "\" step=\"1\" value=\"" . $Settings["heating_position"] . "\">";
  $Content .= "<button type=\"submit\" class=\"btn btn-primary\" name=\"rss_edit_servos\" style=\"margin-top: 1em; float: right;\">Submit</button>";
  $Content .= "</form>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowHydrometer($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<p>Hydrometer:</p>";
  $Content .= FormatEthanol($Settings["distillate_abv"]);
  $Content .= FormatEthanolMeter($Settings["distillate_abv"]);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowProgramTemps($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);

  $Content  = "<table class=\"table table-sm table-borderless\">";
  $Content .=   "<tr><td>Dephleg&nbsp;Range:</td><td align=\"right\" nowrap>" . FormatTempRange($Program["dephleg_temp_low"],$Program["dephleg_temp_high"]) . "</td></tr>";
  $Content .=   "<tr><td>Column&nbsp;Range:</td><td align=\"right\" nowrap>" . FormatTempRange($Program["column_temp_low"],$Program["column_temp_high"]) . "</td></tr>";
  $Content .=   "<tr><td>Boiler&nbsp;Range:</td><td align=\"right\" nowrap>" . FormatTempRange($Program["boiler_temp_low"],$Program["boiler_temp_high"]) . "</td></tr>";
  if ($Settings["active_run"] == 0) {
    $Content .= "<tr><td colspan=\"2\" align=\"right\"><span class=\"text-warning\">Distillation run not active, no temperature management</span></td></tr>";
  } else {
    $Content .= "<tr><td colspan=\"2\" align=\"right\"><span class=\"text-success blink\">Distillation run active, temperatures are being managed</span></td></tr>";
  }
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowTemperatures($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<table class=\"table table-sm table-borderless\">";
  $Content .=   "<tr><td>Dephleg&nbsp;Temperature:</td><td align=\"right\" nowrap>" . FormatTemp($Settings["dephleg_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Column&nbsp;Temperature:</td><td align=\"right\" nowrap>" . FormatTemp($Settings["column_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Boiler&nbsp;Temperature:</td><td align=\"right\" nowrap>" . FormatTemp($Settings["boiler_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Distillate&nbsp;Temperature:</td><td align=\"right\" nowrap>" . FormatTemp($Settings["distillate_temp"]) . "</td></tr>";
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowValves($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<table class=\"table table-sm table-borderless\">";
  $Content .=   "<tr><td>Dephleg&nbsp;Valve:</td><td align=\"right\" nowrap>" . FormatValvePosition($Settings["valve2_total"],$Settings["valve2_position"]) . "</td></tr>";
  $Content .=   "<tr><td>Condenser&nbsp;Valve:</td><td align=\"right\" nowrap>" . FormatValvePosition($Settings["valve1_total"],$Settings["valve1_position"]) . "</td></tr>";
  $Content .=   "<tr><td>Heating&nbsp;Stepper:</td><td align=\"right\" nowrap><span class=\"text-light\">" . $Settings["heating_position"] . " / " . $Settings["heating_total"] . "</span></td></tr>";
  $Content .=   "<tr><td colspan=\"2\" align=\"right\"><a href=\"?page=edit_servos\" class=\"btn btn-secondary\" name=\"edit_motors\" style=\"float: right; --bs-btn-padding-y: .10rem; --bs-btn-padding-x: .75rem; --bs-btn-font-size: .75rem;\"><span>Modify Servo Positions</span></a></td></tr>";
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
?>
