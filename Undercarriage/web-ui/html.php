<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
//---------------------------------------------------------------------------------------------------
function CalibrateHydrometer($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  if ($Settings["hydro_type"] == 0) {
    $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\">";
    $Content .=   "<div class=\"card-body\">";
    $Content .=     "<p class=\"fw-bolder\">If you are starting a new run, it is suggested that you reboot the hydrometer since barrometric pressure can affect a load cell's reference resistance.</p>";
    $Content .=     "<p class=\"fw-bolder\">Do not use the calibrate function if there is any distillate in the parrot cup, only use this to clear load cell drift before any output begins.</p>";
    $Content .=     "<div class=\"row\">";
    $Content .=       "<div class=\"col\"><a href=\"process.php?reboot_hydro=1\" class=\"btn btn-outline-danger\">Reboot Hydrometer</a></div>";
    $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?recalibrate_hydro=1\" class=\"btn btn-primary\">Recalibrate Load Cell</a></div>";
    $Content .=     "</div>";
    $Content .=   "</div>";
    $Content .= "</div>";
  } else {
    $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\">";
    $Content .=   "<div class=\"card-body\">";
    $Content .=     "<p class=\"fw-bolder\">By default, the LIDAR hydrometer reader is calibrated with a hydrometer using a 146mm scale. A reflector 10mm above the 100% line will track within +/- 1%.</p>";
    $Content .=     "<p class=\"fw-bolder\">Use the buttons below to recalibrate the 10% division lines. Float your hydrometer with water, use the top of your parrot as the reference point. Watch for the blue light to flash on the reader chip when updating.</p>";
    $Content .=     "<div class=\"row\">";
    $Content .=       "<div class=\"col\"><a href=\"process.php?reboot_hydro=1\" class=\"btn btn-outline-danger btn-sm fw-bolder\">Reboot Device</a></div>";
    $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?recalibrate_hydro=1&slot=0\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 0%</a></div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: 0.5em;\">";
    $Content .=       "<div class=\"col\"><a href=\"process.php?recalibrate_hydro=1&slot=1\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 10%</a></div>";
    $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?recalibrate_hydro=1&slot=2\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 20%</a></div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: 0.5em;\">";
    $Content .=       "<div class=\"col\"><a href=\"process.php?recalibrate_hydro=1&slot=3\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 30%</a></div>";
    $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?recalibrate_hydro=1&slot=4\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 40%</a></div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: 0.5em;\">";
    $Content .=       "<div class=\"col\"><a href=\"process.php?recalibrate_hydro=1&slot=5\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 50%</a></div>";
    $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?recalibrate_hydro=1&slot=6\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 60%</a></div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: 0.5em;\">";
    $Content .=       "<div class=\"col\"><a href=\"process.php?recalibrate_hydro=1&slot=7\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 70%</a></div>";
    $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?recalibrate_hydro=1&slot=8\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 80%</a></div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: 0.5em;\">";
    $Content .=       "<div class=\"col\"><a href=\"process.php?recalibrate_hydro=1&slot=9\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 90%</a></div>";
    $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?recalibrate_hydro=1&slot=10\" class=\"btn btn-primary btn-sm fw-bolder\">Calibrate 100%</a></div>";
    $Content .=     "</div>";
    $Content .=   "</div>";
    $Content .= "</div>";
  }
  $Content .= "<div>" . DrawCard($DBcnx,"show_serial",true) . "<div>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function Confirmation($DBcnx,$Type,$Data) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  if ($Type == 1) {
    $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=$Data");
    $Program  = mysqli_fetch_assoc($Result);
    $Msg = "Are you sure that you want to delete the program<br>\"" . $Program["program_name"] . "\"?";
    $Btn = "delete_program";
  } else {
    if ($Data == 1) {
      $Msg = "Are you sure that you want to reboot the system?";
      $Btn = "reboot_system";
    } else {
      $Msg = "Are you sure that you want to shut down the system?";
      $Btn = "shutdown_system";
    }
  }

  $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     "<p class=\"fw-bolder\">$Msg</p>";
  $Content .=     "<div class=\"row\" style=\"margin-top: 0.5em;\">";
  $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"index.php\" class=\"btn btn-danger btn-sm fw-bolder\">Cancel</a></div>";
  $Content .=       "<div class=\"col\"><a href=\"process.php?$Btn=1&ID=$Data\" class=\"btn btn-primary btn-sm fw-bolder\">Confirm</a></div>";
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ControlRelays($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $OnLabel  = "<span class=\"text-success blink fw-bolder\">On</span>";
  $OffLabel = "<span class=\"text-warning fw-bolder\">Off</span>";
  if ($Settings["relay1_state"] == 1) {
    $Relay1 = $OnLabel;
  } else {
    $Relay1 = $OffLabel;
  }
  if ($Settings["relay2_state"] == 1) {
    $Relay2 = $OnLabel;
  } else {
    $Relay2 = $OffLabel;
  }

  $Content  = "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     "<p class=\"fw-bolder\">The auxiliary relays can be used to control low-current DC pumps independent of your program settings. These will also resume their switched state across reboots.</p>";
  $Content .=     "<p class=\"fw-bolder\">Keep in mind that high current AC power loads should not be switched with the Pi Hat onboard relays. These should be used to switch external solid state relays instead.</p>";
  $Content .=     "<div class=\"row\" style=\"margin-top: 1.5em;\">";
  $Content .=       "<div class=\"col\"><span class=\" fw-bolder\">Auxiliary&nbsp;Relay&nbsp;#1</span></div>";
  $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?control_relay=1&state=0\" class=\"btn btn-danger btn-sm fw-bolder\">Deactivate</a></div>";
  $Content .=       "<div class=\"col\"><a href=\"process.php?control_relay=1&state=1\" class=\"btn btn-primary btn-sm fw-bolder\">Activate</a></div>";
  $Content .=       "<div class=\"col\">$Relay1</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: 0.5em;\">";
  $Content .=       "<div class=\"col\"><span class=\"fw-bolder\">Auxiliary&nbsp;Relay&nbsp;#2</span></div>";
  $Content .=       "<div class=\"col\"><a style=\"float: right;\" href=\"process.php?control_relay=2&state=0\" class=\"btn btn-danger btn-sm fw-bolder\">Deactivate</a></div>";
  $Content .=       "<div class=\"col\"><a href=\"process.php?control_relay=2&state=1\" class=\"btn btn-primary btn-sm fw-bolder\">Activate</a></div>";
  $Content .=       "<div class=\"col\">$Relay2</div>";
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</div>";
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
  $Result   = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);
  $Content  = "";

  if ($Settings["heating_enabled"] == 1) {
    $Content .= "<script type=\"text/javascript\">\n";
    $Content .= "function handleHeatJump(value) {\n";
    $Content .= "  if (value) {\n";
    $Content .= "    const url = `/process.php?heat_jump=1&value=\${value}`;\n";
    $Content .= "    window.location.href = url;\n";
    $Content .= "  }\n";
    $Content .= "}\n";
    $Content .= "</script>\n";
  }

  $Content .= "<div class=\"row\" style=\"margin-left: 0.5em; margin-right: 0.5em;\"><nav class=\"navbar navbar-expand-lg navbar-dark bg-dark\" style=\"background-color: #121212;\">";
  $Content .=   "<div class=\"container-fluid\">";
  $Content .=     "<a class=\"navbar-brand\" href=\"/\"><span class=\"iconify text-white\" style=\"font-size: 1.5em;\" data-icon=\"logos:raspberry-pi\"></span>&nbsp;<span class=\"text-white\" style=\"font-weight: bold;\">RPi Smart Still</span></a>";
  $Content .=     "<button class=\"navbar-toggler\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#navbarSupportedContent\" aria-controls=\"navbarSupportedContent\" aria-expanded=\"false\" aria-label=\"Toggle navigation\">";
  $Content .=       "<span class=\"navbar-toggler-icon\"></span>";
  $Content .=     "</button>";
  $Content .=     "<div class=\"collapse navbar-collapse\" id=\"navbarSupportedContent\">";
  $Content .=       "<ul class=\"navbar-nav me-auto mb-2 mb-lg-0\">";
  $Content .=         "<li class=\"nav-item\">";
  $Content .=           "<a class=\"nav-link\" aria-current=\"page\" href=\"/\"><span class=\"fw-bolder\">Home</span></a>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item\">";
  $Content .=           "<a class=\"nav-link\" href=\"?page=timeline\"><span class=\"fw-bolder\">Timeline</span></a>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item dropdown\">";
  $Content .=           "<a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\"><span class=\"fw-bolder\">Programs</span></a>";
  $Content .=           "<ul class=\"dropdown-menu\" style=\"background-color: #212529;\">";

  $Result = mysqli_query($DBcnx,"SELECT * FROM programs ORDER BY program_name");
  while ($RS = mysqli_fetch_assoc($Result)) {
    if ($RS["ID"] == $Settings["active_program"]) $RS["program_name"] = "&#10003 " . $RS["program_name"];
    $RS["program_name"] = str_replace(" ","&nbsp;",$RS["program_name"]);
    if ($Settings["active_run"] == 1) {
      $Content .=         "<li><a class=\"dropdown-item disabled\" href=\"?program_id=" . $RS["ID"] . "\"><span class=\"text-secondary fw-bolder\">" . $RS["program_name"] . "</span></a></li>";
    } else {
      $Content .=         "<li><a class=\"dropdown-item\" href=\"?program_id=" . $RS["ID"] . "\"><span class=\"fw-bolder\">" . $RS["program_name"] . "</span></a></li>";
    }
  }

  $Content .=           "</ul>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item dropdown\">";
  $Content .=           "<a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\"><span class=\"fw-bolder\">Management</span></a>";
  $Content .=           "<ul class=\"dropdown-menu\" style=\"background-color: #212529;\">";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=programs\"><span class=\"fw-bolder\">Edit&nbsp;Programs</span></a></li>";
  if ($Settings["active_run"] == 1) {
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"process.php?calibrate_valves=1\"><span class=\"text-secondary fw-bolder\">Calibrate&nbsp;Valves</span></a></li>";
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"?page=sensors\"><span class=\"text-secondary fw-bolder\">Configure&nbsp;Sensors</span></a></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"process.php?calibrate_valves=1\"><span class=\"fw-bolder\">Calibrate&nbsp;Valves</span></a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=sensors\"><span class=\"fw-bolder\">Configure&nbsp;Sensors</span></a></li>";
  }
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=heating\"><span class=\"fw-bolder\">Configure&nbsp;Heating</span></a></li>";
  if ($Settings["active_run"] == 1) {
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"?page=hydrometer\"><span class=\"text-secondary fw-bolder\">Calibrate&nbsp;Hydrometer</a></span></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=hydrometer\"><span class=\"fw-bolder\">Calibrate&nbsp;Hydrometer</span></a></li>";
  }
  $Content .=             "<li><a class=\"dropdown-item\" href=\"?page=relays\"><span class=\"fw-bolder\">Control&nbsp;Relays</span></a></li>";
  if ($Boilermaker["enabled"] == 1) {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"http://" . $Boilermaker["ip_address"] . "\" target=\"_blank\"><span class=\"fw-bolder\">Open&nbsp;Boilermaker</span></a></li>";
  }
  if ($Settings["speech_enabled"] == 0) {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?speech=1\"><span class=\"fw-bolder\">Enable&nbsp;Speech</span></a></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?speech=0\"><span class=\"fw-bolder\">Disable&nbsp;Speech</span></a></li>";
  }
  $Content .=             "<li><hr class=\"dropdown-divider\"></li>";
  if ($Settings["active_run"] == 1) {
    $Content .=           "<li><a  class=\"dropdown-item disabled\" href=\"?page=start_run\"><span class=\"text-secondary fw-bolder\">Start&nbsp;Run</span></a></li>";
    if ($Settings["paused"] == 0) {
      $Content .=         "<li><a class=\"dropdown-item\" href=\"process.php?pause_run=1\"><span class=\"fw-bolder\">Pause&nbsp;Run</span></a></li>";
    } else {
      $Content .=         "<li><a class=\"dropdown-item\" href=\"process.php?pause_run=0\"><span class=\"fw-bolder\">Resume&nbsp;Run</span></a></li>";
    }
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=stop_run\"><span class=\"fw-bolder\">Stop&nbsp;Run</span></a></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=start_run\"><span class=\"fw-bolder\">Start&nbsp;Run</span></a></li>";
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"process.php?pause_page=start_run\"><span class=\"text-secondary fw-bolder\">Pause&nbsp;Run</span></a></li>";
    $Content .=           "<li><a class=\"dropdown-item disabled\" href=\"?page=stop_run\"><span class=\"text-secondary fw-bolder\">Stop&nbsp;Run</span></a></li>";
  }
  $Content .=             "<li><hr class=\"dropdown-divider\"></li>";
  if ($Settings["heating_enabled"] == 1) {
    $Content .=           "<li>";
    $Content .=             "<div class=\"px-3 py-1\">";
    $Content .=               "<select class=\"form-select\" id=\"heatJumpSelect\" onchange=\"handleHeatJump(this.value)\">";
    $Content .=                 "<option value=\"\" disabled selected>Heat Jump To...</option>";
    $Content .=                 "<option value=\"100\">Heat Jump To 100%</option>";
    $Content .=                 "<option value=\"90\">Heat Jump To 90%</option>";
    $Content .=                 "<option value=\"80\">Heat Jump To 80%</option>";
    $Content .=                 "<option value=\"70\">Heat Jump To 70%</option>";
    $Content .=                 "<option value=\"60\">Heat Jump To 60%</option>";
    $Content .=                 "<option value=\"50\">Heat Jump To 50%</option>";
    $Content .=                 "<option value=\"40\">Heat Jump To 40%</option>";
    $Content .=                 "<option value=\"30\">Heat Jump To 30%</option>";
    $Content .=                 "<option value=\"20\">Heat Jump To 20%</option>";
    $Content .=                 "<option value=\"10\">Heat Jump To 10%</option>";
    $Content .=                 "<option value=\"0\">Heat Jump To 0%</option>";
    $Content .=               "<select>";
    $Content .=             "</div>";
    $Content .=           "</li>";
  }
  if ($Settings["active_run"] == 1) {
    $Content .=           "<li><a  class=\"dropdown-item disabled\" href=\"?page=system_confirm&option=1\"><span class=\"text-secondary fw-bolder\">Reboot&nbsp;System</span></a></li>";
    $Content .=           "<li><a  class=\"dropdown-item disabled\" href=\"?page=system_confirm&option=2\"><span class=\"text-secondary fw-bolder\">Shutdown&nbsp;System</span></a></li>";
  } else {
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=system_confirm&option=1\"><span class=\"fw-bolder\">Reboot&nbsp;System</span></a></li>";
    $Content .=           "<li><a class=\"dropdown-item\" href=\"?page=system_confirm&option=2\"><span class=\"fw-bolder\">Shutdown&nbsp;System</span></a></li>";
  }
  $Content .=           "</ul>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item\">";
  $Content .=           "<a class=\"nav-link disabled\"><span class=\"fw-bolder\">Current Program: " . $Program["program_name"] . "</span></a>";
  $Content .=         "</li>";
  $Content .=       "</ul>";
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</nav></div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function EditHeating($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM heating_translation ORDER BY percent");
  while ($RS = mysqli_fetch_assoc($Result)) $Position[] = $RS["position"];

  $Content  = "<form id=\"edit_heating\" method=\"post\" action=\"process.php\">";
  $Content .= "<div class=\"card\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     "<div class=\"row\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"HeatingEnabled\" class=\"form-label fw-bolder\">Heat Management Enabled</label>";
  $Content .=         YNselector($Settings["heating_enabled"],"HeatingEnabled");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"BMenabled\" class=\"form-label fw-bolder\">Boilermaker Enabled</label>";
  $Content .=         YNselector($Boilermaker["enabled"],"BMenabled");
  $Content .=       "</div>";
  $Content .=     "</div>";
  if ($Boilermaker["enabled"] == 1) {
    $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"BMip_address\" class=\"form-label fw-bolder\">IP Address</label>"; $Pattern = '^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$';
    $Content .=         "<input type=\"text\" class=\"form-control fw-bolder\" id=\"BMip_address\" name=\"BMip_address\" minlength=\"7\" maxlength=\"15\" pattern=\"$Pattern\" required value=\"". $Boilermaker["ip_address"] . "\">";
    $Content .=       "</div>";
    //$Content .=       "<div class=\"col\">";

    //$Content .=       "</div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"BMfixed_temp\" class=\"form-label fw-bolder\">Fixed Temperature</label>";
    $Content .=         YNselector($Boilermaker["fixed_temp"],"BMfixed_temp");
    $Content .=       "</div>";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"BMtime_spread\" class=\"form-label fw-bolder\">Time Spread (hours)</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"BMtime_spread\" name=\"BMtime_spread\" min=\"1\" max=\"24\" step=\"1\" value=\"" . $Boilermaker["time_spread"] . "\">";
    $Content .=       "</div>";
    $Content .=     "</div>";
  } else {
    $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"HeatingPolarity\" class=\"form-label fw-bolder\">Inverted Stepper Rotation</label>";
    $Content .=         YNselector($Settings["heating_polarity"],"HeatingPolarity");
    $Content .=       "</div>";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"HeatingAnalog\" class=\"form-label fw-bolder\">Analog Heat Controller</label>";
    $Content .=         YNselector($Settings["heating_analog"],"HeatingAnalog");
    $Content .=       "</div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"HeatingTotal\" class=\"form-label fw-bolder\">Total Adjustment Steps</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"HeatingTotal\" name=\"HeatingTotal\" min=\"0\" max=\"1000\" step=\"1\" value=\"" . $Settings["heating_total"] . "\">";
    $Content .=       "</div>";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating10\" class=\"form-label fw-bolder\">10% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating10\" name=\"Heating10\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[0]\">";
    $Content .=       "</div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating20\" class=\"form-label fw-bolder\">20% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating20\" name=\"Heating20\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[1]\">";
    $Content .=       "</div>";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating30\" class=\"form-label fw-bolder\">30% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating30\" name=\"Heating30\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[2]\">";
    $Content .=       "</div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating40\" class=\"form-label fw-bolder\">40% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating40\" name=\"Heating40\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[3]\">";
    $Content .=       "</div>";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating50\" class=\"form-label fw-bolder\">50% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating50\" name=\"Heating50\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[4]\">";
    $Content .=       "</div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating60\" class=\"form-label fw-bolder\">60% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating60\" name=\"Heating60\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[5]\">";
    $Content .=       "</div>";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating70\" class=\"form-label fw-bolder\">70% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating70\" name=\"Heating70\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[6]\">";
    $Content .=       "</div>";
    $Content .=     "</div>";
    $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating80\" class=\"form-label fw-bolder\">80% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating80\" name=\"Heating80\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[7]\">";
    $Content .=       "</div>";
    $Content .=       "<div class=\"col\">";
    $Content .=         "<label for=\"Heating90\" class=\"form-label fw-bolder\">90% Position</label>";
    $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating90\" name=\"Heating90\" min=\"0\" max=\"1000\" step=\"1\" value=\"$Position[8]\">";
    $Content .=       "</div>";
    $Content .=     "</div>";
  }
  $Content .=     "<div class=\"row\" style=\"margin-top: 1em;\">";
  if ($Boilermaker["enabled"] == 1) {
    if ($Settings["active_run"] == 1) {
      $Disabled = "disabled";
    } else {
      $Disabled = "";
    }
    $Content .=     "<div class=\"col\">";
    $Content .=       "<a href=\"process.php?reboot_boilermaker=1\" class=\"btn btn-outline-danger fw-bolder $Disabled\" name=\"cancel_action\">Reboot Boilermaker</a>";
    $Content .=     "</div>";
  }
  $Content .=       "<div class=\"col\" style=\"text-align: right;\">";
  $Content .=         "<a href=\"index.php\" class=\"btn btn-danger fw-bolder\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" class=\"btn btn-primary fw-bolder\" name=\"rss_edit_heating\">Submit</button>";
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
  $Result   = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);

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
  $Content .=       "<label for=\"ProgramName\" class=\"form-label fw-bolder\">Program Name</label>";
  $Content .=       "<input type=\"text\" class=\"form-control fw-bolder\" id=\"ProgramName\" name=\"ProgramName\" maxlength=\"100\" value=\"" . $Program["program_name"] . "\">";
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"ProgramType\" class=\"form-label fw-bolder\">Program Type</label>";
  $Content .=       ProgramTypeSelector($Program["mode"]);
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"ABVmanaged\" class=\"form-label fw-bolder\">ABV Managed</label>";
  $Content .=         YNselector($Program["abv_managed"],"ABVmanaged");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"DistillateABV\" class=\"form-label fw-bolder\">Target % ABV</label>";
  $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"DistillateABV\" name=\"DistillateABV\" min=\"0\" max=\"100\" step=\"1\" value=\"" . $Program["distillate_abv"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"FlowManaged\" class=\"form-label fw-bolder\">Flow Managed</label>";
  $Content .=         YNselector($Program["flow_managed"],"FlowManaged");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"MinimumFlow\" class=\"form-label fw-bolder\">Minimum Flow %</label>";
  $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"MinimumFlow\" name=\"MinimumFlow\" min=\"0\" max=\"100\" step=\"1\" value=\"" . $Program["minimum_flow"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"CondenserRate\" class=\"form-label fw-bolder\">Condenser Valve %</label>";
  $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"CondenserRate\" name=\"CondenserRate\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["condenser_rate"] . "\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"DephlegStart\" class=\"form-label fw-bolder\">Dephleg Valve Start %</label>";
  $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"DephlegStart\" name=\"DephlegStart\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["dephleg_start"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  if ($Boilermaker["enabled"] == 0) {
    $Content .=   "<div style=\"margin-top: .5em;\">";
    $Content .=     "<label for=\"HeatingIdle\" class=\"form-label fw-bolder\">Heating Idle Position <span class=\"text-secondary\"><i>(after boiler is up to temp 0.." . $Settings["heating_total"] . ")</i></span></label>";
    $Content .=     "<input type=\"number\" class=\"form-control fw-bolder\" id=\"HeatingIdle\" name=\"HeatingIdle\" min=\"0\" max=\"" . $Settings["heating_total"] . "\" step=\"1\" value=\"" . $Program["heating_idle"] . "\">";
    $Content .=   "</div>";
  }
  $Content .=     "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"BoilerManaged\" class=\"form-label fw-bolder\">Boiler Managed</label>";
  $Content .=         YNselector($Program["boiler_managed"],"BoilerManaged");
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"BoilerTempLow\" class=\"form-label fw-bolder\">Boiler Low (C)</label>";
  $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"BoilerTempLow\" name=\"BoilerTempLow\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["boiler_temp_low"] . "\">";
  $Content .=       "</div>";
  $Content .=       "<div class=\"col\">";
  $Content .=         "<label for=\"BoilerTempHigh\" class=\"form-label fw-bolder\">Boiler High (C)</label>";
  $Content .=         "<input type=\"number\" class=\"form-control fw-bolder\" id=\"BoilerTempHigh\" name=\"BoilerTempHigh\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["boiler_temp_high"] . "\">";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div id=\"ColumnDiv\">";
  $Content .=       "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"ColumnManaged\" class=\"form-label fw-bolder\">Column&nbsp;Managed</label>";
  $Content .=           YNselector($Program["column_managed"],"ColumnManaged");
  $Content .=         "</div>";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"ColumnTempLow\" class=\"form-label fw-bolder\">Column Low (C)</label>";
  $Content .=           "<input type=\"number\" class=\"form-control fw-bolder\" id=\"ColumnTempLow\" name=\"ColumnTempLow\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["column_temp_low"] . "\">";
  $Content .=         "</div>";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"ColumnTempHigh\" class=\"form-label fw-bolder\">Column High (C)</label>";
  $Content .=           "<input type=\"number\" class=\"form-control fw-bolder\" id=\"ColumnTempHigh\" name=\"ColumnTempHigh\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["column_temp_high"] . "\">";
  $Content .=         "</div>";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div id=\"DephlegDiv\">";
  $Content .=       "<div class=\"row\" style=\"margin-top: .5em;\">";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"DephlegManaged\" class=\"form-label fw-bolder\">Dephleg&nbsp;Managed</label>";
  $Content .=           YNselector($Program["dephleg_managed"],"DephlegManaged");
  $Content .=         "</div>";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"DephlegTempLow\" class=\"form-label fw-bolder\">Dephleg Low (C)</label>";
  $Content .=           "<input type=\"number\" class=\"form-control fw-bolder\" id=\"DephlegTempLow\" name=\"DephlegTempLow\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["dephleg_temp_low"] . "\">";
  $Content .=         "</div>";
  $Content .=         "<div class=\"col\">";
  $Content .=           "<label for=\"DephlegTempHigh fw-bolder\" class=\"form-label\">Dephleg High (C)</label>";
  $Content .=           "<input type=\"number\" class=\"form-control fw-bolder\" id=\"DephlegTempHigh\" name=\"DephlegTempHigh\" min=\"0\" max=\"100\" step=\".1\" value=\"" . $Program["dephleg_temp_high"] . "\">";
  $Content .=         "</div>";
  $Content .=       "</div>";
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"Notes\" class=\"form-label fw-bolder\">Program Notes</label>";
  $Content .=       "<textarea class=\"form-control fw-bolder\" id=\"Notes\" name=\"Notes\" style=\"height: 150px;\">" . $Program["notes"] . "</textarea>";
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
  $Content .=       "<label for=\"DephlegAddr\" class=\"form-label fw-bolder\">Dephleg Sensor Address</label>";
  $Content .=       SensorSelector($Settings["dephleg_addr"],"DephlegAddr");
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"ColumnAddr\" class=\"form-label fw-bolder\">Column Sensor Address</label>";
  $Content .=       SensorSelector($Settings["column_addr"],"ColumnAddr");
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: .5em;\">";
  $Content .=       "<label for=\"BoilerAddr\" class=\"form-label fw-bolder\">Boiler Sensor Address</label>";
  $Content .=       SensorSelector($Settings["boiler_addr"],"BoilerAddr");
  $Content .=     "</div>";
  $Content .=     "<div style=\"margin-top: 1em; float: right;\"><a href=\"index.php\" class=\"btn btn-danger fw-bolder\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" class=\"btn btn-primary fw-bolder\" name=\"rss_edit_sensors\">Submit</button></div>";
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
    $Content .=   "<tr><td nowrap width=\"15%\"><span class=\"text-white-50 fw-bolder\">$RunType Run Time</span></td>" .
                  "<td width=\"1%\"><span class=\"text-primary fw-bolder\">:</span></td><td><span class=\"text-light fw-bolder\">" . SecsToTime(time() - strtotime($Settings["run_start"])) . "</span></td></tr>";
    $Content .=   "<tr><td nowrap><span class=\"text-white-50 fw-bolder\">" . $Logic["dephleg_last_adjustment"] . "</span></td>" .
                  "<td><span class=\"text-primary fw-bolder\">:</span></td><td><span class=\"text-light fw-bolder\">" . $Logic["dephleg_note"] . "</span></td></tr>";
    $Content .=   "<tr><td nowrap><span class=\"text-white-50 fw-bolder\">" . $Logic["column_last_adjustment"] . "</span></td>" .
                  "<td><span class=\"text-primary fw-bolder\">:</span></td><td><span class=\"text-light fw-bolder\">" . $Logic["column_note"] . "</span></td></tr>";
    $Content .=   "<tr><td nowrap><span class=\"text-white-50 fw-bolder\">" . $Logic["boiler_last_adjustment"] . "</span></td>" .
                  "<td><span class=\"text-primary fw-bolder\">:</span></td><td><span class=\"text-light fw-bolder\">" . $Logic["boiler_note"] . "</span></td></tr>";
    $Content .= "</table>";
  } else {
    $Content = "<p class=\"fw-bolder\">No distillation run currently active</p>" .
               "<p class=\"fw-bolder\">Last run started at " . $Settings["run_start"] . " and ended at " . $Settings["run_end"] . "</p>";
  }
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ServoPositionEditor($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);

  $Content  = "<form id=\"servo_editor\" method=\"post\" action=\"process.php\">";
  $Content .= "<label for=\"Valve2\" class=\"form-label fw-bolder\">Dephleg Cooling Valve %</label>";
  $Content .= "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Valve2\" name=\"Valve2\" min=\"0\" max=\"100\" step=\".1\" value=\"" . PosToPct($Settings["valve2_total"],$Settings["valve2_position"]) . "\">";
  $Content .= "<label for=\"Valve1\" class=\"form-label fw-bolder\" style=\"margin-top: .5em;\">Condenser Cooling Valve %</label>";
  $Content .= "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Valve1\" name=\"Valve1\" min=\"0\" max=\"100\" step=\".1\" value=\"" . PosToPct($Settings["valve1_total"],$Settings["valve1_position"]) . "\">";
  if ($Boilermaker["enabled"] == 1) {
    if ($Settings["active_run"] == 1) {
      $Disabled = "disabled";
    } else {
      $Disabled = "";
    }
    $Content .= "<label for=\"Heating\" class=\"form-label fw-bolder\" style=\"margin-top: .5em;\">Heating Controller Position %</label>";
    $Content .= "<input type=\"number\" $Disabled class=\"form-control fw-bolder\" id=\"Heating\" name=\"Heating\" min=\"0\" max=\"100\" step=\"1\" value=\"" . $Settings["heating_position"] . "\">";
  } else {
    $Content .= "<label for=\"Heating\" class=\"form-label fw-bolder\" style=\"margin-top: .5em;\">Heating Controller Position [0.." . $Settings["heating_total"] . "]</label>";
    $Content .= "<input type=\"number\" class=\"form-control fw-bolder\" id=\"Heating\" name=\"Heating\" min=\"0\" max=\"" . $Settings["heating_total"] . "\" step=\"1\" value=\"" . $Settings["heating_position"] . "\">";
  }
  $Content .= "<div style=\"margin-top: 1em; float: right;\"><a href=\"index.php\" class=\"btn btn-danger fw-bolder\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<button type=\"submit\" class=\"btn btn-primary fw-bolder\" name=\"rss_edit_servos\">Submit</button></div>";
  $Content .= "</form>";
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowHydrometer($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<p class=\"fw-bolder\">Hydrometer Flow Rate: " . $Settings["distillate_flow"] . "%</p>";
  $Content .= FormatEthanol($Settings["distillate_abv"]);
  $Content .= FormatEthanolMeter($Settings["distillate_abv"]);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowPrograms($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Counter  = 0;
  $Content  = "<a href=\"?page=edit_program&ID=0\" class=\"btn btn-outline-secondary fw-bolder\" style=\"width: 31em; margin-top: 0.5em; margin-bottom: 0.5em; margin-left: 1.25em; margin-right: 0.5em;\" name=\"create_program\">Create New Program</a><br>";
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
    $Content .=     "<p class=\"fw-bolder\">" . $RS["program_name"] . "</p>";
    $Content .=     "<p style=\"float: right;\"><a href=\"?page=delete_confirm&ID=" . $RS["ID"] . "\" class=\"btn $BtnColor $Disabled fw-bolder\" name=\"delete_program\" style=\"--bs-btn-padding-y: .10rem; --bs-btn-padding-x: .75rem; --bs-btn-font-size: .75rem;\">Delete</a>&nbsp;&nbsp;&nbsp;&nbsp;";
    $Content .=     "<a href=\"?page=edit_program&ID=" . $RS["ID"] . "\" class=\"btn btn-primary fw-bolder\" name=\"edit_program\" style=\"--bs-btn-padding-y: .10rem; --bs-btn-padding-x: .75rem; --bs-btn-font-size: .75rem;\">Edit</a></p>";
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
  $Content .=   "<tr><td><span class=\"fw-bolder\">Dephleg&nbsp;Range:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatTempRange($Program["dephleg_temp_low"],$Program["dephleg_temp_high"],$Program["dephleg_managed"]) . "</span></td></tr>";
  $Content .=   "<tr><td><span class=\"fw-bolder\">Column&nbsp;Range:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatTempRange($Program["column_temp_low"],$Program["column_temp_high"],$Program["column_managed"]) . "</span></td></tr>";
  $Content .=   "<tr><td><span class=\"fw-bolder\">Boiler&nbsp;Range:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatTempRange($Program["boiler_temp_low"],$Program["boiler_temp_high"],$Program["boiler_managed"]) . "</span></td></tr>";
  if ($Settings["active_run"] == 0) {
    $Content .= "<tr><td colspan=\"2\" align=\"center\"><span class=\"text-warning fw-bolder\">Distillation run not active, no temperature management</span></td></tr>";
  } else {
    if ($Settings["paused"] == 1) {
      $Content .= "<tr><td colspan=\"2\" align=\"center\"><span class=\"text-danger blink fw-bolder\">The active distillation run is currently paused</span></td></tr>";
    } else {
      $Content .= "<tr><td colspan=\"2\" align=\"center\"><span class=\"text-success blink fw-bolder\">Distillation run active, temperatures are being managed</span></td></tr>";
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
      $Content .= "<tr><td><span class=\"fw-bolder\">$Sensors[$x]</span></td><td align=\"right\"><span class=\"fw-bolder\">" . $Data["C"] . "C / " . $Data["F"] . "F</span></td></tr>";
    }
  } else {
    $Content .= "<tr><td><span class=\"fw-bolder\">No DS18B20 sensors found</span></td></tr>";
  }
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowSerialData($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Settings["serial_data"] = nl2br(trim($Settings["serial_data"]));
  $Content = "<div style=\"margin-left: 0.75em; font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace;\"><span class=\"fw-bolder\">" . $Settings["serial_data"] . "</span></div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowTemperatures($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);

  $Content  = "<table class=\"table table-sm table-borderless\">";
  $Content .=   "<tr><td><span class=\"fw-bolder\">Dephleg&nbsp;Temperature:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatTemp($Settings["dephleg_temp"]) . "</span></td></tr>";
  $Content .=   "<tr><td><span class=\"fw-bolder\">Column&nbsp;Temperature:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatTemp($Settings["column_temp"]) . "</span></td></tr>";
  $Content .=   "<tr><td><span class=\"fw-bolder\">Boiler&nbsp;Temperature:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatTemp($Settings["boiler_temp"]) . "</span></td></tr>";
  $Content .=   "<tr><td><span class=\"fw-bolder\">Distillate&nbsp;Temperature:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatTemp($Settings["distillate_temp"]) . "</span></td></tr>";
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
    $Content = str_replace("{Label3}","Heating Controller",$Content);
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
  $Content .= VoicePrompter($DBcnx,true);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function ShowValves($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);

  if ($Settings["relay1_state"] == 1) {
    $Relay1 = "<span class=\"fw-bolder text-success\">Relay 1 On</span>";
  } else {
    $Relay1 = "<span class=\"fw-bolder text-warning\">Relay 1 Off</span>";
  }
  if ($Settings["relay2_state"] == 1) {
    $Relay2 = "<span class=\"fw-bolder text-success\">Relay 2 On</span>";
  } else {
    $Relay2 = "<span class=\"fw-bolder text-warning\">Relay 2 Off</span>";
  }

  $Content  = "<table class=\"table table-sm table-borderless\">";
  $Content .=   "<tr><td><span class=\"fw-bolder\">Dephleg&nbsp;Valve:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatValvePosition($Settings["valve2_total"],$Settings["valve2_position"]) . "</span></td></tr>";
  $Content .=   "<tr><td><span class=\"fw-bolder\">Condenser&nbsp;Valve:</span></td><td align=\"right\" nowrap><span class=\"fw-bolder\">" . FormatValvePosition($Settings["valve1_total"],$Settings["valve1_position"]) . "</span></td></tr>";
  if ($Boilermaker["enabled"] == 1) {
    $Content .= "<tr><td><span class=\"fw-bolder\">Heating&nbsp;Controller:</span></td><td align=\"right\" nowrap><span class=\"text-light fw-bolder\">" . $Settings["heating_position"] . "%</span></td></tr>";
  } else {
    $Content .= "<tr><td><span class=\"fw-bolder\">Heating&nbsp;Controller:</span></td><td align=\"right\" nowrap><span class=\"text-light fw-bolder\">" . $Settings["heating_position"] . " / " . $Settings["heating_total"] . "</span></td></tr>";
  }
  $Content .=   "<tr><td>$Relay1 &diams; $Relay2</td><td align=\"right\"><a href=\"?page=edit_servos\" class=\"btn btn-secondary\" name=\"edit_motors\" style=\"float: right; --bs-btn-padding-y: .10rem; --bs-btn-padding-x: .75rem; --bs-btn-font-size: .75rem;\"><span class=\"fw-bolder\">Modify Values</span></a></td></tr>";
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function StartRun($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);
  $Result   = mysqli_query($DBcnx,"SELECT * FROM boilermaker WHERE ID=1");
  $Boilermaker = mysqli_fetch_assoc($Result);

  if (($Settings["valve1_total"] == 0) || ($Settings["valve1_pulse"] == 0) || ($Settings["valve2_total"] == 0) || ($Settings["valve2_pulse"] == 0)) {
    $Content  = "<p class=\"text-danger fw-bolder\"><b>SYSTEM ERROR</b></p>";
    $Content .= "<p class=\"text-light fw-bolder\">The condenser and dephleg valve calibration is incomplete!</p>";
    $Content .= "<p class=\"text-light fw-bolder\">Please run the \"Calibrate Valves\" function with your valves under water pressure and start the run again.</p>";
  } else {
    $Content  = "<p class=\"text-light fw-bolder\">Before you start your run, you must complete the pre-flight checklist first. Failing to do so will guarantee poor results. " .
                "Remember, you are making a computer perform the physical actions that a human being would manually perform. Make sure that you always start with a clean and accurate slate!</p>";
    $Content .= "<ol>";
    $Content .=   "<li><span class=\"fw-bolder\">Make sure that your still is completely cooled down.</span></li>";
    if ($Boilermaker["enabled"] == 1) {
      $Content .= "<li><span class=\"fw-bolder\">Reboot your Boilermaker.</span></li>";
    } else {
      $Content .= "<li><span class=\"fw-bolder\">Zero the heating stepper motor (if enabled).</span></li>";
    }
    $Content .=   "<li><span class=\"fw-bolder\">Confirm that your water lines are pressurized.</span></li>";
    $Content .=   "<li><span class=\"fw-bolder\">Calibrate the condenser and dephleg cooling valves.</span></li>";
    $Content .=   "<li><span class=\"fw-bolder\">Calibrate the hydrometer.</span></li>";
    $Content .= "</ol>";
    $Content .= "<div style=\"float: right;\"><a href=\"index.php\" class=\"btn btn-danger fw-bolder\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"process.php?active_run=1\" class=\"btn btn-primary fw-bolder\" name=\"start_run\">Start Distillation Run</a></div>";
  }
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function StopRun($DBcnx) {
  $Content  = "<p class=\"text-light fw-bolder\">Are you sure that you want to stop the current run rather than pausing it? Starting a new distillation run will clear all data currently stored in the timeline.</p>";
  $Content .= "<div style=\"float: right;\"><a href=\"index.php\" class=\"btn btn-danger fw-bolder\" name=\"cancel_action\">Cancel</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"process.php?active_run=0\" class=\"btn btn-primary fw-bolder\" name=\"start_run\">Stop Distillation Run</a></div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function VoicePrompter($DBcnx,$Ajax) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $RandID   = "card_" . generateRandomString();
  $Content  = "";
  if ($Ajax) $Content .= AjaxRefreshJS("voice_prompter",$RandID,10000);
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
