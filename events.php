<?php
/*
List all the events
*/

include("/coastfm/phplib/general.php");

/*
echo "<pre>";
var_dump($_POST);
echo "</pre>";
*/
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Day of week passed in number format, return the day name
// dayofweek(x) function returns the weekday index for a given date (a number from 0 to 6
// 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday
//---------------------------------------------------------------------------
function get_day_name($day_number)
{
  $day_of_week = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

  return $day_of_week[$day_number];
}

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Day number is passed, gets events for day and list them out
//---------------------------------------------------------------------------
function do_events($day, $dbh, $uuid)
{
$sql = "select type, name, time, hours from events where enabled = True and day like '%" . $day . "%' and date = '2002-01-01'";
$q = $dbh->query($sql);

while ($f = $q->fetch())
{
  if ($f['type'] == 1)
  {
    insert_row($uuid, $f['name'], $f['time'], $day, $dbh);
  } else
  {
    if ($f['type'] == 2)
    {
      events_type_2($f['type'], $f['name'], $f['time'], $f['hours'], $dbh, $day, $uuid);
    }
  }
}

}

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Event type 2
//---------------------------------------------------------------------------
function events_type_2($type, $name, $time, $hours, $dbh, $day, $uuid)
{
$hours = ltrim($hours, '&');
$hrs = explode("&", $hours);
$hhmmss = explode(":", $time);
foreach ($hrs as $h)
{
  insert_row($uuid, $name, sprintf("%02u:%02u:%02u", $h, $hhmmss[1], $hhmmss[2]), $day, $dbh);
}

}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Display events from work table
//---------------------------------------------------------------------------
function display_events($dbh, $uuid)
{
$sql = sprintf("select name, time from cfm_events_work where uuid = '%s' order by time", $uuid);
$q = $dbh->query($sql);

?>
<table class='tbl' style='width:100%;'>
  <tr>
    <th>Time</th>
    <th style=text-align:left;>Name</th>
  </tr>

<?php

$hour = '';
while ($row = $q->fetch())
{
  if (substr($row['time'],0,2) != $hour)
  {
    echo '<tr><td style=text-align:left;, colspan=2 bgcolor="lightgray">';
    echo '<span style="font-weight:bold">' . substr($row['time'],0,2) . ":00</span>";
    echo '</td></tr>';
    $hour = substr($row['time'],0,2);
  }
  echo '<td style=text-align:center;">';
  echo $row['time'];
  echo '</td>';

  echo '<td style=text-align:left;">';
  echo $row['name'];
  echo '</td>';

  echo '</tr>';
}
echo '</table>';
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Insert a row into the work table
//---------------------------------------------------------------------------
function insert_row($uuid, $name, $time, $day, $dbh)
{
$sql = sprintf("insert into cfm_events_work (uuid, name, time, day) values ('%s', \"%s\", '%s', %u)", $uuid, $name, $time, $day);
try
{
  $q = $dbh->query($sql);
} catch (PDOException $e)
{
  echo "Can't update or insert we are buggered!!!\n";
  echo $sql."\n";
}

}

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Create a list of days, 0 = sunday
//---------------------------------------------------------------------------
function day_selector($day)
{

$html = "<select name='day' onchange='this.form.submit()'>";

for($i = 0; $i<=6; $i++)
{
  if ($day == $i)
  {
    $html = $html . "<option selected value='" . $i . "'>" . get_day_name($i) . "</option>";
  } else
  {
    $html = $html . "<option value='" . $i . "'>" . get_day_name($i) . "</option>";
  }
}

$html = $html . "</select>";
return $html;

}
//---------------------------------------------------------------------------


//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//MAIN Main main
//---------------------------------------------------------------------------
logit(0, "starting");
$config = parse_ini_file("/home/coastfm/etc/www_4_rdj.conf", true);

if (isset($_POST['day']))
{
  $day = $_POST['day'];
} else
{
  $day = date("w");
}

$dsn = 'mysql:dbname=' . $config['sql']['db_name'] . ';host=' . $config['sql']['host'];

try
{
  $dbh = new PDO($dsn, $config['sql']['username'], $config['sql']['passwd']);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
  echo "Connection failed $user $passwd $dsn";
}

$uuid = bin2hex(random_bytes(16));

?>

<html>
  <head>
    <title>RadioDJ - Events</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="shortcut icon" href="/coastfm.ico" />
    <script type="text/javascript" src="/ads/include/jquery.js"></script>
    <script type = "text/javascript" src = "https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js" type="text/javascript"></script>
    <script type="text/javascript">
      function swapVisibility(id) {
        $('#' + id).toggle();
      }
    </script>

  <link href="/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
  <div id="header" class="fixed">
    <div id="logo">
      <img src="/ads/images/coastfm_logo.png" style="float:left; margin-left: -3px; margin-top: -3px;"/>
      <h1>RADIODJ - Events</h1>     </div>
  </div>

  <div id="cssmenu" class="bigbox fixed">
  <ul>
  <?php echo APP_MENU ?>
  </div>

  <div class="bigbox fixed">
  <div id="main_inner" class="fixed">

<?php
   $day_selector = day_selector($day);
?>

  <div class='post' align='center'>
  <h2>Select Day</h2>
  <form method='post' action='/cgi/events.php'>
  <?php echo $day_selector?>

  </div>
  <div class='post' align='center'> 

<?php
do_events($day, $dbh, $uuid);
display_events($dbh, $uuid);

$sql = sprintf("delete from cfm_events_work where uuid = '%s'", $uuid);

try
{
  $q = $dbh->query($sql);
} catch (PDOException $e)
{
  echo "Can't delete... we are buggered!!!\n";
  echo $sql."\n";
}

?>

</body>
</html>
