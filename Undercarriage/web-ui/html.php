<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
//---------------------------------------------------------------------------------------------------
function CalibrateHydrometer($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $RandID   = "card_" . generateRandomString();
  $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";

  $Content .=   "</div>";
  $Content .= "</div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function DrawCard($DBcnx,$Body,$DoAjax) {
  $RandID   = "card_" . generateRandomString();
  $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
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
  $Content .= AjaxRefreshJS("logic_tracker",$RandID);
  $Content .=     "<div id=\"$RandID\">";
  $Content .= LogicTracker($DBcnx);
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
  $Content .=             "<li><hr class=\"dropdown-divider\"></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=100\">Heat&nbsp;Jump&nbsp;To&nbsp;100%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=90\">Heat&nbsp;Jump&nbsp;To&nbsp;90%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=80\">Heat&nbsp;Jump&nbsp;To&nbsp;80%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=70\">Heat&nbsp;Jump&nbsp;To&nbsp;70%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=60\">Heat&nbsp;Jump&nbsp;To&nbsp;60%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=50\">Heat&nbsp;Jump&nbsp;To&nbsp;50%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=40\">Heat&nbsp;Jump&nbsp;To&nbsp;40%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=30\">Heat&nbsp;Jump&nbsp;To&nbsp;30%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=20\">Heat&nbsp;Jump&nbsp;To&nbsp;20%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=10\">Heat&nbsp;Jump&nbsp;To&nbsp;10%</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"process.php?heat_jump=1&value=0\">Heat&nbsp;Jump&nbsp;To&nbsp;0%</a></li>";
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

  $Content  = "<form id=\"edit_heating\" method=\"post\" action=\"process.php\">";
  $Content .= "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";

  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= "</form>";
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
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"ColumnManaged\" class=\"form-label\">Column Managed</label>";
  $Content .=         YNselector($Program["column_managed"],"ColumnManaged");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"ColumnTempLow\" class=\"form-label\">Column Low (C)</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"ColumnTempLow\" name=\"ColumnTempLow\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["column_temp_low"] . "\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"ColumnTempHigh\" class=\"form-label\">Column High (C)</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"ColumnTempHigh\" name=\"ColumnTempHigh\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["column_temp_high"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"DephlegManaged\" class=\"form-label\">Dephleg Managed</label>";
  $Content .=         YNselector($Program["dephleg_managed"],"DephlegManaged");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"DephlegTempLow\" class=\"form-label\">Dephleg Low (C)</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"DephlegTempLow\" name=\"DephlegTempLow\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["dephleg_temp_low"] . "\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"DephlegTempHigh\" class=\"form-label\">Dephleg High (C)</label>";
  $Content .=         "<input type=\"number\" class=\"form-control\" id=\"DephlegTempHigh\" name=\"DephlegTempHigh\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["dephleg_temp_high"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"Notes\" class=\"form-label\">Program Notes</label>";
  $Content .=       "<textarea class=\"form-control\" id=\"Notes\" name=\"Notes\" style=\"height: 150px;\">" . $Program["notes"] . "</textarea>";
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: 1em; float: right;\"><a href=\"?page=programs\" class=\"btn btn-danger\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" class=\"btn btn-primary\" name=\"rss_save_program\">Save Program</button></div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= "</form>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function EditSensors($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<form id=\"edit_sensors\" method=\"post\" action=\"process.php\">";
  $Content .= "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";

  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= "</form>";
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

  $Content  = "<p style=\"font-weight: bold;\">Servo Position Editor:</p>";
  $Content .= "<form id=\"servo_editor\" method=\"post\" action=\"process.php\">";
  $Content .= "<label for=\"Valve2\" class=\"form-label\">Dephleg Cooling Valve %</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Valve2\" name=\"Valve2\" min=\"0\" max=\"100\" step=\".1\" value=\"" . PosToPct($Settings["valve2_total"],$Settings["valve2_position"]) . "\">";
  $Content .= "<label for=\"Valve1\" class=\"form-label\" style=\"margin-top: .5em;\">Condenser Cooling Valve %</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Valve1\" name=\"Valve1\" min=\"0\" max=\"100\" step=\".1\" value=\"" . PosToPct($Settings["valve1_total"],$Settings["valve1_position"]) . "\">";
  $Content .= "<label for=\"Heating\" class=\"form-label\" style=\"margin-top: .5em;\">Heating Stepper Position [0.." . $Settings["heating_total"] . "]</label>";
  $Content .= "<input type=\"number\" class=\"form-control\" id=\"Heating\" name=\"Heating\" min=\"0\" max=\"" . $Settings["heating_total"] . "\" step=\"1\" value=\"" . $Settings["heating_position"] . "\">";
  $Content .= "<div style=\"margin-top: 1em; float: right;\"><a href=\"index.php\" class=\"btn btn-danger\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" class=\"btn btn-primary\" name=\"rss_edit_servos\">Submit</button></div>";
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
function ShowPrograms($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Counter  = 0;
  $Content  = "<a href=\"?page=edit_program&ID=0\" class=\"btn btn-outline-secondary\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\" name=\"create_program\">Create New Program</a><br>";
  $Content .= "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
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
    $Content .= "<tr><td colspan=\"2\" align=\"right\"><span class=\"text-success blink\">Distillation run active, temperatures are being managed</span></td></tr>";
  }
  $Content .= "</table>";
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
?>
