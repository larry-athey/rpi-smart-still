<?php
//---------------------------------------------------------------------------------------------------
require_once("subs.php");
//---------------------------------------------------------------------------------------------------
function rss_menu_bar() {
  //$DBcnx  = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,"ClimateCzar");
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
  $Content .=           "<a class=\"nav-link\" href=\"/?page=timeline\">Timeline</a>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item dropdown\">";
  $Content .=           "<a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Programs</a>";
  $Content .=           "<ul class=\"dropdown-menu\" style=\"background-color: #212529;\">";
/*
  $Result = mysqli_query($DBcnx,"SELECT * FROM DeviceGroups ORDER BY Name");
  while ($Group = mysqli_fetch_assoc($Result)) {
    if ($Group["ID"] == CZ_GROUP) $GroupName =$Group["Name"];
    $Group["Name"] = str_replace(" ","&nbsp;",$Group["Name"]);
    $Content .=           "<li><a class=\"dropdown-item\" href=\"" . selfURL() . "?CZ_GROUP=" . $Group["ID"] . "\">" . $Group["Name"] . "</a></li>";
  }
*/
  $Content .=           "</ul>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item dropdown\">";
  $Content .=           "<a class=\"nav-link dropdown-toggle\" href=\"#\" role=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">Management</a>";
  $Content .=           "<ul class=\"dropdown-menu\" style=\"background-color: #212529;\">";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"/?page=programs\">Edit&nbsp;Programs</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"/?page=valves\">Calibrate&nbsp;Valves</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"/?page=sensors\">Configure&nbsp;Sensors</a></li>";
  $Content .=             "<li><hr class=\"dropdown-divider\"></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"/?run=1\">Start&nbsp;Run</a></li>";
  $Content .=             "<li><a class=\"dropdown-item\" href=\"/?run=0\">Stop&nbsp;Run</a></li>";
  $Content .=           "</ul>";
  $Content .=         "</li>";
  $Content .=         "<li class=\"nav-item\">";
  $Content .=           "<a class=\"nav-link disabled\">Current Program: Maximum Reflux</a>";
  $Content .=         "</li>";
  $Content .=       "</ul>";
  $Content .=     "</div>";
  $Content .=   "</div>";
  $Content .= "</nav>";
  //mysqli_close($DBcnx);
  return $Content;
}
//---------------------------------------------------------------------------------------------------

//---------------------------------------------------------------------------------------------------
?>
