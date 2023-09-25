<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
//---------------------------------------------------------------------------------------------------
function CalibrateHydrometer($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     "<p>If you are starting a new run, it is suggested that you reboot the hydrometer since barrometric pressure can affect a load cell's reference resistance.</p>";
  $Content .=     "<p>Do not use the calibrate function if there is any distillate in the parrot cup, only use this to clear load cell drift before any output begins.</p>";
  $Content .=     "<div class=\"row\">";
  $Content .=       "<div class=\"col\"><a href=\"process.php?reboot_hydro=1\" class=\"btn btn-primary\" name=\"cancel_action\">Reboot Hydrometer</a></div>";
  $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?recalibrate_hydro=1\" class=\"btn btn-primary\" name=\"cancel_action\">Recalibrate Load Cell</a></div>";
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= "<div>" . DrawCard($DBcnx,"show_serial",true) . "<div>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function DrawCard($DBcnx,$Body,$DoAjax) {
  $RandID   = "card_" . generateRandomString();
  $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  if ($DoAjax) $Content .= AjaxRefreshJS($Body,$RandID,4500);
  $Content .=     "<div id=\"$RandID\">";
  if ($Body == "hydrometer") {
    $Content .= ShowHydrometer($DBcnx);
  } elseif ($Body == "program_temps") {
    $Content .= ShowProgramTemps($DBcnx);
  } elseif ($Body == "edit_servos") {
    $Content .= ServoPositionEditor($DBcnx);
  } elseif ($Body == "show_sensors") {
    $Content .= ShowSensors($DBcnx);
  } elseif ($Body == "show_serial") {
    $Content .= ShowSerialData($DBcnx);
  } elseif ($Body == "temperatures") {
    $Content .= ShowTemperatures($DBcnx);
  } elseif ($Body == "valve_positions") {
    $Content .= ShowValves($DBcnx);
  } elseif ($Body == "start_run") {
    $Content .= StartRun($DBcnx);
  } elseif ($Body == "stop_run") {
    $Content .= StopRun($DBcnx);
  }
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function DrawLogicTracker($DBcnx) {
  $RandID   = "card_" . generateRandomString();
  $Content  = "<div class=\"card\" style=\"width: 98.5%; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     AjaxRefreshJS("logic_tracker",$RandID,4500);
  $Content .=     "<div id=\"$RandID\">";
  $Content .=     LogicTracker($DBcnx);
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
    if ($Settings["active_run"] == 1) {
      $Content .=         "<li><a class=\"dropdown-item disabled\" href=\"?program_id=" . $RS["ID"] . "\"><span class=\"text-secondary\">" . $RS["program_name"] . "</span></a></li>";
    } else {
      $Content .=         "<li><a class=\"dropdown-item\" href=\"?program_id=" . $RS["ID"] . "\">" . $RS["program_name"] . "</a></li>";
    }
  }

  $Content .=           "</ul>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item dropdown\">";
  $Content .=           "<a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Management</a>";
  $Content .=           "<ul class=\"dropdown-menu\" style=\"background-color: #212529;\">";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=programs\">Edit&nbsp;Programs</a></li>";
  if ($Settings["active_run"] == 1) {
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"process.php?calibrate_valves=1\"><span class=\"text-secondary\">Calibrate&nbsp;Valves</span></a></li>";
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"?page=sensors\"><span class=\"text-secondary\">Configure&nbsp;Sensors</span></a></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?calibrate_valves=1\">Calibrate&nbsp;Valves</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=sensors\">Configure&nbsp;Sensors</a></li>";
  }
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=heating\">Configure&nbsp;Heating</a></li>";
  if ($Settings["active_run"] == 1) {
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"?page=hydrometer\"><span class=\"text-secondary\">Calibrate&nbsp;Hydrometer</a></span></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=hydrometer\">Calibrate&nbsp;Hydrometer</a></li>";
  }
  if ($Settings["speech_enabled"] == 0) {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?speech=1\">Enable&nbsp;Speech</a></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?speech=0\">Disable&nbsp;Speech</a></li>";
  }
  $Content .=             "<li><hr class=\"dropdown-divider\"></li>";
  if ($Settings["active_run"] == 1) {
    $Content .=           "<li><a  class=\"dropdown-item disabled\" href=\"?page=start_run\"><span class=\"text-secondary\">Start&nbsp;Run</span></a></li>";
    if ($Settings["paused"] == 0) {
      $Content .=         "<li><a class=\"dropdown-item\" href=\"process.php?pause_run=1\">Pause&nbsp;Run</a></li>";
    } else {
      $Content .=         "<li><a class=\"dropdown-item\" href=\"process.php?pause_run=0\">Resume&nbsp;Run</a></li>";
    }
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=stop_run\">Stop&nbsp;Run</a></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=start_run\">Start&nbsp;Run</a></li>";
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"process.php?pause_page=start_run\"><span class=\"text-secondary\">Pause&nbsp;Run</span></a></li>";
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"?page=stop_run\"><span class=\"text-secondary\">Stop&nbsp;Run</span></a></li>";
  }
  if ($Settings["heating_enabled"] == 1) {
    $Content .=           "<li><hr class=\"dropdown-divider\"></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=100\">Heat&nbsp;Jump&nbsp;To&nbsp;100%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=90\">Heat&nbsp;Jump&nbsp;To&nbsp;90%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=80\">Heat&nbsp;Jump&nbsp;To&nbsp;80%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=70\">Heat&nbsp;Jump&nbsp;To&nbsp;70%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=60\">Heat&nbsp;Jump&nbsp;To&nbsp;60%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=50\">Heat&nbsp;Jump&nbsp;To&nbsp;50%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=40\">Heat&nbsp;Jump&nbsp;To&nbsp;40%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=30\">Heat&nbsp;Jump&nbsp;To&nbsp;30%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=20\">Heat&nbsp;Jump&nbsp;To&nbsp;20%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=10\">Heat&nbsp;Jump&nbsp;To&nbsp;10%</a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=0\">Heat&nbsp;Jump&nbsp;To&nbsp;0%</a></li>";
  }
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
function EditHeating($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM heating_translation ORDER BY percent");
  while ($RS = mysqli_fetch_assoc($Result)) $Position[] = $RS["position"];

  $Content  = "<form id=\"edit_heating\" method=\"post\" action=\"process.php\">";
  $Content .= "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     "<div class=\"row\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"HeatingEnabled\" class=\"form-label\">Heat Management Enabled</label>";
  $Content .=         YNselector($Settings["heating_enabled"],"HeatingEnabled");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"HeatingPolarity\" class=\"form-label\">Inverted Stepper Rotation</label>";
  $Content .=         YNselector($Settings["heating_polarity"],"HeatingPolarity");
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"HeatingAnalog\" class=\"form-label\">Analog Heat Controller</label>";
  $Content .=         YNselector($Settings["heating_analog"],"HeatingAnalog");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"HeatingTotal\" class=\"form-label\">Total Adjustment Steps</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"HeatingTotal\" name=\"HeatingTotal\" min=\"0\" max=\"1000\" step=\"1\" value=\"" . $Settings["heating_total"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating10\" class=\"form-label\">10% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating10\" name=\"Heating10\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[0]\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating20\" class=\"form-label\">20% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating20\" name=\"Heating20\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[1]\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating30\" class=\"form-label\">30% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating30\" name=\"Heating30\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[2]\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating40\" class=\"form-label\">40% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating40\" name=\"Heating40\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[3]\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating50\" class=\"form-label\">50% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating50\" name=\"Heating50\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[4]\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating60\" class=\"form-label\">60% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating60\" name=\"Heating60\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[5]\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating70\" class=\"form-label\">70% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating70\" name=\"Heating70\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[6]\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating80\" class=\"form-label\">80% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating80\" name=\"Heating80\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[7]\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"Heating90\" class=\"form-label\">90% Position</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"Heating90\" name=\"Heating90\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[8]\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<a href=\"index.php\" style=\"margin-top: 2em;\" class=\"btn btn-danger\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" style=\"margin-top: 2em;\" class=\"btn btn-primary\" name=\"rss_edit_heating\">Submit</button>";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= "</form>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function EditProgram($DBcnx,$ID) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  if ($ID > 0) {
    $Result  = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=$ID");
    $Program = mysqli_fetch_assoc($Result);
  } else {
    $Program["program_name"] = "New Program";
    $Program["mode"] = 0;
    $Program["distillate_abv"] = 0;
    $Program["abv_managed"] = 0;
    $Program["minimum_flow"] = 0;
    $Program["flow_managed"] = 0;
    $Program["dephleg_start"] = 0;
    $Program["condenser_rate"] = 0;
    $Program["boiler_managed"] = 0;
    $Program["boiler_temp_low"] = 0;
    $Program["boiler_temp_high"] = 0;
    $Program["dephleg_managed"] = 0;
    $Program["dephleg_temp_low"] = 0;
    $Program["dephleg_temp_high"] = 0;
    $Program["column_managed"] = 0;
    $Program["column_temp_low"] = 0;
    $Program["column_temp_high"] = 0;
    $Program["heating_idle"] = 0;
    $Program["notes"] = "";
  }

  $Content  = "<form id=\"edit_program\" method=\"post\" action=\"process.php\">";
  $Content .= "<input type=\"hidden\" name=\"ID\" value=\"$ID\">";
  $Content .= "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     "<div>";
  $Content .=       "<label for=\"ProgramName\" class=\"form-label\">Program Name</label>";
  $Content .=       "<input type=\"text\" class=\"form-control\" id=\"ProgramName\" name=\"ProgramName\" maxlength=\"100\" value=\"" . $Program["program_name"] . "\">";
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"ProgramType\" class=\"form-label\">Program Type</label>";
  $Content .=       ProgramTypeSelector($Program["mode"]);
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"ABVmanaged\" class=\"form-label\">ABV Managed</label>";
  $Content .=         YNselector($Program["abv_managed"],"ABVmanaged");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"DistillateABV\" class=\"form-label\">Target % ABV</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"DistillateABV\" name=\"DistillateABV\" min=\"0\" max=\"100\" step=\"1\" value=\"" . $Program["distillate_abv"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"FlowManaged\" class=\"form-label\">Flow Managed</label>";
  $Content .=         YNselector($Program["flow_managed"],"FlowManaged");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"MinimumFlow\" class=\"form-label\">Minimum Flow %</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"MinimumFlow\" name=\"MinimumFlow\" min=\"0\" max=\"100\" step=\"1\" value=\"" . $Program["minimum_flow"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"CondenserRate\" class=\"form-label\">Condenser Valve %</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"CondenserRate\" name=\"CondenserRate\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["condenser_rate"] . "\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"DephlegStart\" class=\"form-label\">Dephleg Valve Start %</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"DephlegStart\" name=\"DephlegStart\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["dephleg_start"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=         "<label for=\"HeatingIdle\" class=\"form-label\">Heating Idle Position <span class=\"text-secondary\"><i>(after boiler is up to temp 0.." . $Settings["heating_total"] . ")</i></span></label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"HeatingIdle\" name=\"HeatingIdle\" min=\"0\" max=\"" . $Settings["heating_total"] . "\" step=\"1\" value=\"" . $Program["heating_idle"] . "\">";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"BoilerManaged\" class=\"form-label\">Boiler Managed</label>";
  $Content .=         YNselector($Program["boiler_managed"],"BoilerManaged");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"BoilerTempLow\" class=\"form-label\">Boiler Low (C)</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"BoilerTempLow\" name=\"BoilerTempLow\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["boiler_temp_low"] . "\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"BoilerTempHigh\" class=\"form-label\">Boiler High (C)</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"BoilerTempHigh\" name=\"BoilerTempHigh\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["boiler_temp_high"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div id=\"ColumnDiv\">";
  $Content .=       "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"ColumnManaged\" class=\"form-label\">Column Managed</label>";
  $Content .=           YNselector($Program["column_managed"],"ColumnManaged");
  $Content .=         "</div>";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"ColumnTempLow\" class=\"form-label\">Column Low (C)</label>";
  $Content .=           "<input type=\"number\" class=\"form-control\" id=\"ColumnTempLow\" name=\"ColumnTempLow\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["column_temp_low"] . "\">";
  $Content .=         "</div>";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"ColumnTempHigh\" class=\"form-label\">Column High (C)</label>";
  $Content .=           "<input type=\"number\" class=\"form-control\" id=\"ColumnTempHigh\" name=\"ColumnTempHigh\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["column_temp_high"] . "\">";
  $Content .=         "</div>";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div id=\"DephlegDiv\">";
  $Content .=       "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"DephlegManaged\" class=\"form-label\">Dephleg Managed</label>";
  $Content .=           YNselector($Program["dephleg_managed"],"DephlegManaged");
  $Content .=         "</div>";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"DephlegTempLow\" class=\"form-label\">Dephleg Low (C)</label>";
  $Content .=           "<input type=\"number\" class=\"form-control\" id=\"DephlegTempLow\" name=\"DephlegTempLow\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["dephleg_temp_low"] . "\">";
  $Content .=         "</div>";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"DephlegTempHigh\" class=\"form-label\">Dephleg High (C)</label>";
  $Content .=           "<input type=\"number\" class=\"form-control\" id=\"DephlegTempHigh\" name=\"DephlegTempHigh\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["dephleg_temp_high"] . "\">";
  $Content .=         "</div>";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"Notes\" class=\"form-label\">Program Notes</label>";
  $Content .=       "<textarea class=\"form-control\" id=\"Notes\" name=\"Notes\" style=\"height: 150px;\">" . $Program["notes"] . "</textarea>";
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: 1em; float: right;\"><a href=\"?page=programs\" class=\"btn btn-danger\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" class=\"btn btn-primary\" name=\"rss_edit_program\">Save Program</button></div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= "</form>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function EditSensors($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<form id=\"edit_sensors\" method=\"post\" action=\"process.php\">";
  $Content .= "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"DephlegAddr\" class=\"form-label\">Dephleg Sensor Address</label>";
  $Content .=       SensorSelector($Settings["dephleg_addr"],"DephlegAddr");
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"ColumnAddr\" class=\"form-label\">Column Sensor Address</label>";
  $Content .=       SensorSelector($Settings["column_addr"],"ColumnAddr");
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"BoilerAddr\" class=\"form-label\">Boiler Sensor Address</label>";
  $Content .=       SensorSelector($Settings["boiler_addr"],"BoilerAddr");
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: 1em; float: right;\"><a href=\"index.php\" class=\"btn btn-danger\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" class=\"btn btn-primary\" name=\"rss_edit_sensors\">Submit</button></div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= "</form>";
  $Content .= "<div>" . DrawCard($DBcnx,"show_sensors",true) . "<div>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function LogicTracker($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);

  if ($Program["mode"] == 0) {
    $RunType = "Pot Still Mode";
  } else {
    $RunType = "Reflux Mode";
  }
  if ($Settings["active_run"] == 1) {
    $Result = mysqli_query($DBcnx,"SELECT * FROM logic_tracker WHERE ID=1");
    $Logic  = mysqli_fetch_assoc($Result);
    $Content  = "<table class=\"table table-sm table-borderless\">";
    $Content .=   "<tr><td nowrap width=\"15%\"><span class=\"text-white-50\">$RunType Run Time</span></td>" .
                  "<td width=\"1%\"><span class=\"text-primary\">:</span></td><td><span class=\"text-light\">" . SecsToTime(time() - strtotime($Settings["run_start"])) . "</span></td></tr>";
    $Content .=   "<tr><td nowrap><span class=\"text-white-50\">" . $Logic["dephleg_last_adjustment"] . "</span></td>" .
                  "<td><span class=\"text-primary\">:</span></td><td><span class=\"text-light\">" . $Logic["dephleg_note"] . "</span></td></tr>";
    $Content .=   "<tr><td nowrap><span class=\"text-white-50\">" . $Logic["column_last_adjustment"] . "</span></td>" .
                  "<td><span class=\"text-primary\">:</span></td><td><span class=\"text-light\">" . $Logic["column_note"] . "</span></td></tr>";
    $Content .=   "<tr><td nowrap><span class=\"text-white-50\">" . $Logic["boiler_last_adjustment"] . "</span></td>" .
                  "<td><span class=\"text-primary\">:</span></td><td><span class=\"text-light\">" . $Logic["boiler_note"] . "</span></td></tr>";
    $Content .= "</table>";
  } else {
    $Content = "<p>No distillation run currently active</p>" .
               "<p>Last run started at " . $Settings["run_start"] . " and ended at " . $Settings["run_end"] . "</p>";
  }
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ServoPositionEditor($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<form id=\"servo_editor\" method=\"post\" action=\"process.php\">";
  $Content .= "<label for=\"Valve2\" class=\"form-label\">Dephleg Cooling Valve %</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Valve2\" name=\"Valve2\" min=\"0\" max=\"100\" step=\".1\" value=\"" . PosToPct($Settings["valve2_total"],$Settings["valve2_position"]) . "\">";
  $Content .= "<label for=\"Valve1\" class=\"form-label\" style=\"margin-top: .5em;\">Condenser Cooling Valve %</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Valve1\" name=\"Valve1\" min=\"0\" max=\"100\" step=\".1\" value=\"" . PosToPct($Settings["valve1_total"],$Settings["valve1_position"]) . "\">";
  $Content .= "<label for=\"Heating\" class=\"form-label\" style=\"margin-top: .5em;\">Heating Stepper Position [0.." . $Settings["heating_total"] . "]</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Heating\" name=\"Heating\" min=\"0\" max=\"" . $Settings["heating_total"] . "\" step=\"1\" value=\"" . $Settings["heating_position"] . "\">";
  $Content .= "<div style=\"margin-top: 1em; float: right;\"><a href=\"index.php\" class=\"btn btn-danger\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" class=\"btn btn-primary\" name=\"rss_edit_servos\">Submit</button></div>";
  $Content .= "</form>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowHydrometer($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<p>Hydrometer Flow Rate: " . $Settings["distillate_flow"] . "%</p>";
  $Content .= FormatEthanol($Settings["distillate_abv"]);
  $Content .= FormatEthanolMeter($Settings["distillate_abv"]);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowPrograms($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Counter  = 0;
  $Content  = "<a href=\"?page=edit_program&ID=0\" class=\"btn btn-outline-secondary\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\" name=\"create_program\">Create New Program</a><br>";
  $Content .= "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";

  $Result = mysqli_query($DBcnx,"SELECT * FROM programs ORDER BY program_name");
  while ($RS = mysqli_fetch_assoc($Result)) {
    $Counter ++;
    $Disabled = "";
    if ($RS["ID"] == $Settings["active_program"]) {
      $Disabled = "disabled";
      $BtnColor = "btn-outline-danger";
      $RS["program_name"] = "&#10003 " . $RS["program_name"];
    } else {
      $Disabled = "";
      $BtnColor = "btn-danger";
    }
    $Content .= "<div class=\"card\">";
    $Content .=   "<div class=\"card-body\">";
    $Content .=     "<p>" . $RS["program_name"] . "</p>";
    $Content .=     "<p style=\"float: right;\"><a href=\"?page=delete_confirm&ID=" . $RS["ID"] . "\" class=\"btn $BtnColor $Disabled\" name=\"delete_program\" style=\"--bs-btn-padding-y: .10rem; --bs-btn-padding-x: .75rem; --bs-btn-font-size: .75rem;\"><span>Delete</span></a>&nbsp;&nbsp;&nbsp;&nbsp;";
    $Content .=     "<a href=\"?page=edit_program&ID=" . $RS["ID"] . "\" class=\"btn btn-primary\" name=\"edit_program\" style=\"--bs-btn-padding-y: .10rem; --bs-btn-padding-x: .75rem; --bs-btn-font-size: .75rem;\"><span>Edit</span></a></p>";
    $Content .=   "</div>";
    $Content .= "</div>";
  }

  if ($Counter == 0) $Content .= "No programs found...";
  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowProgramTemps($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);

  $Content  = "<table class=\"table table-sm table-borderless\">";
  $Content .=   "<tr><td>Dephleg&nbsp;Range:</td><td align=\"right\" nowrap>" . FormatTempRange($Program["dephleg_temp_low"],$Program["dephleg_temp_high"],$Program["dephleg_managed"]) . "</td></tr>";
  $Content .=   "<tr><td>Column&nbsp;Range:</td><td align=\"right\" nowrap>" . FormatTempRange($Program["column_temp_low"],$Program["column_temp_high"],$Program["column_managed"]) . "</td></tr>";
  $Content .=   "<tr><td>Boiler&nbsp;Range:</td><td align=\"right\" nowrap>" . FormatTempRange($Program["boiler_temp_low"],$Program["boiler_temp_high"],$Program["boiler_managed"]) . "</td></tr>";
  if ($Settings["active_run"] == 0) {
    $Content .= "<tr><td colspan=\"2\" align=\"right\"><span class=\"text-warning\">Distillation run not active, no temperature management</span></td></tr>";
  } else {
    if ($Settings["paused"] == 1) {
      $Content .= "<tr><td colspan=\"2\" align=\"right\"><span class=\"text-danger blink\">The active distillation run is currently paused</span></td></tr>";
    } else {
      $Content .= "<tr><td colspan=\"2\" align=\"right\"><span class=\"text-success blink\">Distillation run active, temperatures are being managed</span></td></tr>";
    }
  }
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowSensors($DBcnx) {
  $Sensors  = getSensorList();
  $Content  = "<table class=\"table table-sm\">";
  if (count($Sensors) > 0) {
    for ($x = 0; $x <= (count($Sensors) - 1); $x++) {
      $Data = getOneWireTemp($Sensors[$x]);
      $Content .= "<tr><td>$Sensors[$x]</td><td align=\"right\">" . $Data["C"] . "C / " . $Data["F"] . "F</td></tr>";
    }
  } else {
    $Content .= "<tr><td>No DS18B20 temperature sensors found</td></tr>";
  }
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowSerialData($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Settings["serial_data"] = nl2br(trim($Settings["serial_data"]));
  $Content = "<div style=\"margin-left: 0.75em; font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace;\">" . $Settings["serial_data"] . "</div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowTemperatures($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);

  $Content  = "<table class=\"table table-sm table-borderless\">";
  $Content .=   "<tr><td>Dephleg&nbsp;Temperature:</td><td align=\"right\" nowrap>" . FormatTemp($Settings["dephleg_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Column&nbsp;Temperature:</td><td align=\"right\" nowrap>" . FormatTemp($Settings["column_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Boiler&nbsp;Temperature:</td><td align=\"right\" nowrap>" . FormatTemp($Settings["boiler_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Distillate&nbsp;Temperature:</td><td align=\"right\" nowrap>" . FormatTemp($Settings["distillate_temp"]) . "</td></tr>";
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowTimelines($DBcnx) {
  $Timestamps1 = "";
  $Timestamps2 = "";
  $BoilerTemps = "";
  $ColumnTemps = "";
  $DephlegTemps = "";
  $DistillateTemps = "";
  $DistillateABVs = "";
  $DistillateFlows = "";
  $HeatingSteps = "";
  $Valve1Steps = "";
  $Valve2Steps = "";

  $Color[] = "38,166,68";
  $Color[] = "255,159,64";
  $Color[] = "153,102,255";
  $Color[] = "250,201,6";
  $Color[] = "3,112,251";
  $Color[] = "213,57,73";

  $Chart   = file_get_contents("timeline_multiple.html");
  $Content = "";
  $Result  = mysqli_query($DBcnx,"SELECT * FROM input_table ORDER BY ID");
  if (mysqli_num_rows($Result) > 0) {
    while ($RS = mysqli_fetch_assoc($Result)) {
      $Timestamps1 .= "'" . $RS["timestamp"] . "',";
      $BoilerTemps .= $RS["boiler_temp"] . ",";
      $ColumnTemps .= $RS["column_temp"] . ",";
      $DephlegTemps .= $RS["dephleg_temp"] . ",";
      $DistillateTemps .= $RS["distillate_temp"] . ",";
      $DistillateABVs .= $RS["distillate_abv"] . ",";
      $DistillateFlows .= $RS["distillate_flow"] . ",";
    }
    $Timestamps1 = trim($Timestamps1,",");
    $BoilerTemps = trim($BoilerTemps,",");
    $ColumnTemps = trim($ColumnTemps,",");
    $DephlegTemps = trim($DephlegTemps,",");
    $DistillateTemps = trim($DistillateTemps,",");
    $DistillateABVs = trim($DistillateABVs,",");
    $DistillateFlows = trim($DistillateFlows,",");

    $Valve1Prev = 0;
    $Valve2Prev = 0;
    $HeatingPrev = 0;
    $Result = mysqli_query($DBcnx,"SELECT * FROM output_table WHERE valve_id <= 3 ORDER BY ID");
    while ($RS = mysqli_fetch_assoc($Result)) {
      $SaveIt =  false;
      if ($RS["valve_id"] == 1) {
        $Valve1Prev = $RS["position"];
        $SaveIt = true;
      } elseif ($RS["valve_id"] == 2) {
        $Valve2Prev = $RS["position"];
        $SaveIt = true;
      } elseif ($RS["valve_id"] == 3) {
        $HeatingPrev = $RS["position"] * 10;
        $SaveIt = true;
      }
      if ($SaveIt) {
        $Timestamps2 .= "'" . $RS["timestamp"] . "',";
        $Valve1Steps .= $Valve1Prev . ",";
        $Valve2Steps .= $Valve2Prev . ",";
        $HeatingSteps .= $HeatingPrev . ",";
      }
    }
    $Timestamps2 = trim($Timestamps2,",");
    $Valve1Steps = trim($Valve1Steps,",");
    $Valve2Steps = trim($Valve2Steps,",");
    $HeatingSteps = trim($HeatingSteps,",");

    $Content .= $Chart;
    $Content = str_replace("{TimelineName}","TempChart",$Content);
    $Content = str_replace("{Timestamps}",$Timestamps1,$Content);
    $Content = str_replace("{Label1}","Boiler Temperature",$Content);
    $Content = str_replace("{Data1}",$BoilerTemps,$Content);
    $Content = str_replace("{RGB1}",$Color[5],$Content);
    $Content = str_replace("{Label2}","Column Temperature",$Content);
    $Content = str_replace("{Data2}",$ColumnTemps,$Content);
    $Content = str_replace("{RGB2}",$Color[3],$Content);
    $Content = str_replace("{Label3}","Dephleg Temperature",$Content);
    $Content = str_replace("{Data3}",$DephlegTemps,$Content);
    $Content = str_replace("{RGB3}",$Color[1],$Content);

    $Content .= $Chart;
    $Content = str_replace("{TimelineName}","ValveChart",$Content);
    $Content = str_replace("{Timestamps}",$Timestamps2,$Content);
    $Content = str_replace("{Label1}","Condenser Valve",$Content);
    $Content = str_replace("{Data1}",$Valve1Steps,$Content);
    $Content = str_replace("{RGB1}",$Color[0],$Content);
    $Content = str_replace("{Label2}","Dephleg Valve",$Content);
    $Content = str_replace("{Data2}",$Valve2Steps,$Content);
    $Content = str_replace("{RGB2}",$Color[4],$Content);
    $Content = str_replace("{Label3}","Heating Stepper",$Content);
    $Content = str_replace("{Data3}",$HeatingSteps,$Content);
    $Content = str_replace("{RGB3}",$Color[2],$Content);

    $Content .= $Chart;
    $Content = str_replace("{TimelineName}","HydroChart",$Content);
    $Content = str_replace("{Timestamps}",$Timestamps1,$Content);
    $Content = str_replace("{Label1}","Distillate ABV",$Content);
    $Content = str_replace("{Data1}",$DistillateABVs,$Content);
    $Content = str_replace("{RGB1}",$Color[5],$Content);
    $Content = str_replace("{Label2}","Distillate Temperature",$Content);
    $Content = str_replace("{Data2}",$DistillateTemps,$Content);
    $Content = str_replace("{RGB2}",$Color[3],$Content);
    $Content = str_replace("{Label3}","Distillate Flow Rate",$Content);
    $Content = str_replace("{Data3}",$DistillateFlows,$Content);
    $Content = str_replace("{RGB3}",$Color[1],$Content);
  } else {
    $Content .= "<p></p><p>No data found for any previous distillation run</p>";
  }
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
function StartRun($DBcnx) {
  $Content  = "<p class=\"text-light\">Before you start your run, you must complete the pre-flight checklist first. Failing to do so will guarantee poor results. " .
              "Remember, you are making a computer perform the physical actions that a human being would manually perform. Make sure that you always start with a clean and accurate slate!</p>";
  $Content .= "<ol>";
  $Content .=   "<li>Make sure that your still is completely cooled down.</li>";
  $Content .=   "<li>Zero the heating stepper motor (if enabled).</li>";
  $Content .=   "<li>Confirm that your water lines are pressurized.</li>";
  $Content .=   "<li>Calibrate the condenser and dephleg cooling valves.</li>";
  $Content .=   "<li>Calibrate the hydrometer.</li>";
  $Content .= "</ol>";
  $Content .= "<div style=\"float: right;\"><a href=\"index.php\" class=\"btn btn-danger\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"process.php?active_run=1\" class=\"btn btn-primary\" name=\"start_run\">Start Distillation Run</a></div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function StopRun($DBcnx) {
  $Content  = "<p class=\"text-light\">Are you sure that you want to stop the current run rather than pausing it? Starting a new distillation run will clear all data currently stored in the timeline.</p>";
  $Content .= "<div style=\"float: right;\"><a href=\"index.php\" class=\"btn btn-danger\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"process.php?active_run=0\" class=\"btn btn-primary\" name=\"start_run\">Stop Distillation Run</a></div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function VoicePrompter($DBcnx,$Ajax) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $RandID   = "card_" . generateRandomString();
  $Content  = "";
  if ($Ajax) $Content .= AjaxRefreshJS("voice_prompter",$RandID,9500);
  $Content .= "<div id=\"$RandID\">";
  // Check for waiting voice prompts if speech is enabled (web browser must have autoplay enabled in its settings)
  if ($Settings["speech_enabled"] == 1) {
    $Result = mysqli_query($DBcnx,"SELECT * FROM voice_prompts WHERE seen_by NOT LIKE '%" . $_COOKIE["client_id"] . "%' ORDER BY ID LIMIT 1");
    if (mysqli_num_rows($Result) > 0) {
      $RS = mysqli_fetch_assoc($Result);
      $Content .= "<audio autoplay id=\"VoicePrompt\"><source src=\"voice_prompts/" . $RS["filename"] . "\" type=\"audio/mpeg\"></audio>";
      $Result = mysqli_query($DBcnx,"UPDATE voice_prompts SET seen_by=CONCAT('" . $_COOKIE["client_id"] . "|',seen_by) WHERE ID=" . $RS["ID"]);
    }
  }
  $Content .= "</div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
?>
