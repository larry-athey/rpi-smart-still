<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
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
  //mysqli_close($DBcnx);
  return $Content;
}
//---------------------------------------------------------------------------------------------------
/*
      if ($DoAjax) {
        $RandID   = "input_" . generateRandomString();
        $Content  = AjaxRefreshJS($ID,$RandID); // $ID is handled in ajax.php to determine which function to call
        $Content .= "<div id=\"$RandID\">";
      } else {
        $Content = "";
      }

      // Dynamic Content...

      if ($DoAjax) {
        $Content .= "</div>";
      }
*/
//---------------------------------------------------------------------------------------------------
function ShowTemperatures($DBcnx) {
  $Result   = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
  $Settings = mysqli_fetch_assoc($Result);

  $Content  = "<table class=\"table table-sm table-borderless\">";
  $Content .=   "<tr><td>Dephleg&nbsp;Temperature:</td><td align=\"right\">" . FormatTemp($Settings["dephleg_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Column&nbsp;Temperature:</td><td align=\"right\">" . FormatTemp($Settings["column_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Boiler&nbsp;Temperature:</td><td align=\"right\">" . FormatTemp($Settings["boiler_temp"]) . "</td></tr>";
  $Content .=   "<tr><td>Distillate&nbsp;Temperature:</td><td align=\"right\">" . FormatTemp($Settings["distillate_temp"]) . "</td></tr>";
  $Content .= "</table>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
function DrawCard($DBcnx,$Body) {
  $RandID   = "card_" . generateRandomString();
  $Content  = "<div class=\"card\" style=\"width: 26em; margin-top: 0.5em;  margin-bottom: 0.5em; margin-left: 0.5em; margin-right: 0.5em;\">";
  $Content .=   "<div class=\"card-body\">";
  $Content .=     AjaxRefreshJS($Body,$RandID);
  $Content .=     "<div id=\"$RandID\">";
  if ($Body == "temperatures") {
    $Content .= ShowTemperatures($DBcnx);
  }
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</div>";
  return $Content;
}
//---------------------------------------------------------------------------------------------------
?>
