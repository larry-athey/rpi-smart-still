<?php
//---------------------------------------------------------------------------------------------------
require_once("html.php");
//---------------------------------------------------------------------------------------------------
// Not designed for desktop use, but if you are using Firefox you can press CTRL+SHIFT+M to switch it
// into mobile view. Set the resolution to 1025x650 and it will condense the view to match the format
// you see on an iPad Air in landscape orientation.
//---------------------------------------------------------------------------------------------------
if (! isset($_COOKIE["client_id"])) {
  setcookie("client_id",generateRandomString(32),0,"/");
  header("Location: index.php");
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
  <title>Raspberry Pi Smart Still Controller System</title>
  <meta http-equiv="cache-control" content="max-age=0">
  <meta http-equiv="cache-control" content="no-cache">
  <meta http-equiv="expires" content="0">
  <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
  <meta http-equiv="pragma" content="no-cache">
  <meta http-equiv="refresh" content="3600">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <script src="/js/iconify.min.js"></script>
  <script src="/js/chart.js"></script>
  <script src="/js/jquery.min.js"></script>
  <link rel="icon" href="favicon.ico?v=1.1">
  <script type="text/javascript">
    //---------------------------------------------------------------------------------------------------
    $(function() {
      $("#rssFG").addClass("text-purple");
      $("#rssBG").addClass("bg-purple");
    });
    //---------------------------------------------------------------------------------------------------
<?php if (($_GET) && ($_GET["page"] == "edit_program")) { ?>
    window.onload = function() {
      ToggleInputFields(document.getElementById('ProgramType'));
    }
    //---------------------------------------------------------------------------------------------------
    function ToggleInputFields(ProgramType) {
      var Value = ProgramType.options[ProgramType.selectedIndex].value;
      if (Value == 0) {
        DephlegDiv.style.display = 'none';
        ColumnDiv.style.display  = 'inline';
      } else {
        DephlegDiv.style.display = 'inline';
        ColumnDiv.style.display  = 'none';
      }
    }
    //---------------------------------------------------------------------------------------------------
<?php } ?>
  </script>
  <style>
    [data-bs-theme="dark"] {
      --bs-body-bg: #121212; /* Darker background */
      --bs-body-color: #e0e0e0; /* Optional: Lighter text for contrast */
    }
    [data-bs-theme="dark"] .navbar.bg-dark {
      background-color: #121212 !important; /* Makes the menu bar match the body background */
    }

    .text-magenta {
      color: purple !important;
    }
    .bg-magenta {
      background-color: purple !important;
    }

    @-webkit-keyframes blinker {
      from {opacity: 1.0;}
      to {opacity: 0.0;}
    }

    .blink {
      text-decoration: blink;
      -webkit-animation-name: blinker;
      -webkit-animation-duration: 0.6s;
      -webkit-animation-iteration-count:infinite;
      -webkit-animation-timing-function:ease-in-out;
      -webkit-animation-direction: alternate;
    }
    a, a:hover {text-decoration: none;}
  </style>
</head>
<body>
<?php
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
$Result = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
if (mysqli_num_rows($Result) > 0) {
  $Settings = mysqli_fetch_assoc($Result);
  // Hydrometer type change requested, 0 = Load Cell, 1 = LIDAR
  if ((isset($_GET["hydro_type"])) && ($_GET["hydro_type"] >= 0) && ($_GET["hydro_type"] <= 1)) {
    $Result = mysqli_query($DBcnx,"UPDATE settings SET hydro_type='" . $_GET["hydro_type"] . "' WHERE ID=1");
  }
  // Speech enable/disable requested
  if ((isset($_GET["speech"])) && ($_GET["speech"] >= 0) && ($_GET["speech"] <= 1)) {
    $Result = mysqli_query($DBcnx,"UPDATE settings SET speech_enabled='" . $_GET["speech"] . "' WHERE ID=1");
    $Settings["speech_enabled"] = $_GET["speech"];
  }
  // Program change requested
  if (($Settings["active_run"] == 0) && (isset($_GET["program_id"])) && (is_numeric($_GET["program_id"]))) {
    $Result = mysqli_query($DBcnx,"UPDATE settings SET active_program='" . $_GET["program_id"] . "' WHERE ID=1");
    $Settings["active_program"] = $_GET["program_id"];
  }
  $Result  = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program = mysqli_fetch_assoc($Result);
} else {
  echo("<br><h3>System settings record is missing, reinstall system from GitHub clone.</h3>");
  mysqli_close($DBcnx);
  exit;
}

echo(DrawMenu($DBcnx) . "\n");

$Content  = "<div class=\"container-fluid\" style=\"align: left;\">";
$Content .=   "<div class=\"row\">";

if (! isset($_GET["page"])) {
  $Content .= DrawCard($DBcnx,"hydrometer",true);
  $Content .= DrawCard($DBcnx,"temperatures",true);
  $Content .= DrawCard($DBcnx,"valve_positions",true);
  $Content .= DrawCard($DBcnx,"program_temps",true);
  // Full width card to show run logic tracking info
  $Content .= DrawLogicTracker($DBcnx);
  // Hidden div to play voice prompts (web browser must have autoplay enabled in its settings)
  $Content .= VoicePrompter($DBcnx,true);
} else {
  if ($_GET["page"] == "delete_confirm") {
    $Content .= Confirmation($DBcnx,1,$_GET["ID"]);
  } elseif ($_GET["page"] == "edit_program") {
    $Content .= EditProgram($DBcnx,$_GET["ID"]);
  } elseif ($_GET["page"] == "edit_servos") {
    $Content .= DrawCard($DBcnx,"edit_servos",false);
  } elseif ($_GET["page"] == "heating") {
    $Content .= EditHeating($DBcnx);
  } elseif ($_GET["page"] == "hydrometer") {
    $Content .= CalibrateHydrometer($DBcnx);
  } elseif ($_GET["page"] == "programs") {
    $Content .= ShowPrograms($DBcnx);
  } elseif ($_GET["page"] == "relays") {
    $Content .= ControlRelays($DBcnx);
  } elseif ($_GET["page"] == "sensors") {
    $Content .= EditSensors($DBcnx);
  } elseif ($_GET["page"] == "start_run") {
    $Content .= DrawCard($DBcnx,"start_run",false);
  } elseif ($_GET["page"] == "stop_run") {
    $Content .= DrawCard($DBcnx,"stop_run",false);
  } elseif ($_GET["page"] == "system_confirm") {
    $Content .= Confirmation($DBcnx,2,$_GET["option"]);
  } elseif ($_GET["page"] == "timeline") {
    $Content .= ShowTimelines($DBcnx);
  }
}

$Content .=   "</div>";
$Content .= "</div>";

echo("$Content\n");
mysqli_close($DBcnx);
?>
  <script src="/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
