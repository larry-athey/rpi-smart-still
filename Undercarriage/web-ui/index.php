<?php
//---------------------------------------------------------------------------------------------------
require_once("html.php");
//---------------------------------------------------------------------------------------------------
// Not designed for desktop use, but if you are using Firefox you can press CTRL+SHIFT+M to switch it
// into mobile view. Set the resolution to 1025x650 and it will condense the view to match the format
// you see on an iPad Air in landscape orientation.
//---------------------------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Raspberry Pi Smart Still Controller System</title>
  <meta http-equiv="cache-control" content="max-age=0">
  <meta http-equiv="cache-control" content="no-cache">
  <meta http-equiv="expires" content="0">
  <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
  <meta http-equiv="pragma" content="no-cache">
  <meta http-equiv="refresh" content="3600">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
  <link rel="stylesheet" href="https://unpkg.com/bootstrap-darkmode@0.7.0/dist/darktheme.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.8/css/all.css">
  <script src="https://code.iconify.design/2/2.0.3/iconify.min.js"></script>
  <!-- I know that the following is an old version of Charts.js, but it's needed for older iOS devices that can't be upgraded -->
  <script src="https://unpkg.com/chart.js@2.9.4/dist/Chart.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
  <link rel="icon" href="favicon.ico?v=1.1">
  <script type="text/javascript">
    //---------------------------------------------------------------------------------------------------
    $(function() {
      $("#rssFG").addClass("text-purple");
      $("#rssBG").addClass("bg-purple");
    });
    //---------------------------------------------------------------------------------------------------
<?php if ($_GET["page"] == "edit_program") { ?>
    window.onload = function() {
      ToggleInputFields(document.getElementById('ProgramType'));
    }
    //---------------------------------------------------------------------------------------------------
    function ToggleInputFields(ProgramType) {
      var Value = ProgramType.options[ProgramType.selectedIndex].value;
      if (Value == 0) {
        DephlegDiv.style.display  = 'none';
        ColumnDiv.style.display   = 'inline';
      } else {
        DephlegDiv.style.display  = 'inline';
        ColumnDiv.style.display   = 'none';
      }
    }
    //---------------------------------------------------------------------------------------------------
<?php } ?>
  </script>
  <style>
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
<body data-theme="dark">
<?php
$DBcnx = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
$Result = mysqli_query($DBcnx,"SELECT * FROM settings WHERE ID=1");
if (mysqli_num_rows($Result) > 0) {
  $Settings = mysqli_fetch_assoc($Result);
  // Speech enable/disable requested
  if (isset($_GET["speech"])) {
    $Result = mysqli_query($DBcnx,"UPDATE settings SET speech_enabled='" . $_GET["speech"] . "' WHERE ID=1");
    $Settings["speech_enabled"] = $_GET["speech"];
  }
  // Program change requested
  if (($Settings["active_run"] == 0) && (isset($_GET["program_id"])) && (is_numeric($_GET["program_id"]))) {
    $Result = mysqli_query($DBcnx,"UPDATE settings SET active_program='" . $_GET["program_id"] . "' WHERE ID=1");
    $Settings["active_program"] = $_GET["program_id"];
  }
  $Result   = mysqli_query($DBcnx,"SELECT * FROM programs WHERE ID=" . $Settings["active_program"]);
  $Program  = mysqli_fetch_assoc($Result);
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
  $Content .= DrawLogicTracker($DBcnx);
  // Full width card to show run logic tracking info
} else {
  if ($_GET["page"] == "edit_program") {
    $Content .= EditProgram($DBcnx,$_GET["ID"]);
  } elseif ($_GET["page"] == "edit_servos") {
    $Content .= DrawCard($DBcnx,"edit_servos",false);
  } elseif ($_GET["page"] == "heating") {
    $Content .= EditHeating($DBcnx);
  } elseif ($_GET["page"] == "hydrometer") {
    $Content .= CalibrateHydrometer($DBcnx);
  } elseif ($_GET["page"] == "programs") {
    $Content = ShowPrograms($DBcnx);
  } elseif ($_GET["page"] == "sensors") {
    $Content .= EditSensors($DBcnx);
  } elseif ($_GET["page"] == "start_run") {
    $Content .= DrawCard($DBcnx,"start_run",false);
  } elseif ($_GET["page"] == "stop_run") {
    $Content .= DrawCard($DBcnx,"stop_run",false);
  } elseif ($_GET["page"] == "timeline") {
    $Content .= ShowTimelines($DBcnx);
  }
}

$Content .=   "</div>";
$Content .= "</div>";

echo("$Content\n");
mysqli_close($DBcnx);
?>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-mQ93GR66B00ZXjt0YO5KlohRA5SY2XofN4zfuZxLkoj1gXtW8ANNCe9d5Y3eG5eD" crossorigin="anonymous"></script>
</body>
</html>
