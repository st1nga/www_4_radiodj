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
$sql = "select data, type, name, time, hours from events where enabled = True and day like '%" . $day . "%' and date = '2002-01-01'";
$q = $dbh->query($sql);

while ($f = $q->fetch())
{
  if ($f['type'] == 1)
  {
    insert_row($f['data'], $uuid, $f['name'], $f['time'], $day, $dbh);
  } else
  {
    if ($f['type'] == 2)
    {
      events_type_2($f['data'], $f['type'], $f['name'], $f['time'], $f['hours'], $dbh, $day, $uuid);
    }
  }
}

}

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Event type 2
//---------------------------------------------------------------------------
function events_type_2($data, $type, $name, $time, $hours, $dbh, $day, $uuid)
{
$hours = ltrim($hours, '&');
$hrs = explode("&", $hours);
$hhmmss = explode(":", $time);
foreach ($hrs as $h)
{
  insert_row($data, $uuid, $name, sprintf("%02u:%02u:%02u", $h, $hhmmss[1], $hhmmss[2]), $day, $dbh);
}

}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Display events from work table
//---------------------------------------------------------------------------
function display_events($dbh, $uuid)
{
$sql = sprintf("select name, time, data from cfm_events_work where uuid = '%s' order by time", $uuid);
$q = $dbh->query($sql);

?>
<table class='tbl' style='width:100%;'>
  <tr>
    <th style="width:15%">Time</th>
    <th style=text-align:left;>Name</th>
  </tr>

<?php

$row_id = 1;
$hour = '';
while ($row = $q->fetch())
{
  if (substr($row['time'],0,2) != $hour)
  {
    echo '<tr><td style="text-align:left; background-color:lightgray" colspan=2 >';
    echo '<span style="font-weight:bold">' . substr($row['time'],0,2) . ":00</span>";
    echo '</td></tr>';
    $hour = substr($row['time'],0,2);
  }
  echo '<tr><td style="text-align:center; vertical-align:top;">';
  echo $row['time'];
  echo '<input type="checkbox" onclick="return toggleMe(\'event_' . $row_id . '\')">';
  echo '</td>';

  echo '<td style="text-align:left;">';
  echo $row['name'];
  echo '<div id="event_' . $row_id . '" style="display: none;">';
  echo '<div style="margin-left: 20px;">';
  $data_rows = preg_split('/\n/', $row['data']);
  $show_br = false;
  foreach($data_rows as $data_line)
  {
    if (substr($data_line, 0, 16) == 'Load Track By ID')
    {
      $load_track_by_id = preg_split('/\|/', $data_line);
      echo '<form method="post" action="/cgi/songs.php" target="_blank">';
      echo $load_track_by_id[0] . "&nbsp;??=" . $load_track_by_id[1] . "&nbsp;ID=";
      echo '<input type="hidden" name="ACTION" value="search">';
      echo '<input type="hidden" name="and_or" value="or">';
      echo '<input type="hidden" name="search_for_artist" value="">';
      echo '<input type="hidden" name="search_for_title" value="">';
      echo '<button type="submit" name="song_id" class="btn-link" value="'. $load_track_by_id[2] . '">' . $load_track_by_id[2] . '</button>';
      echo "&nbsp;filename=" . $load_track_by_id[3] . "&nbsp;" . $load_track_by_id[4];
      echo '</form>';
      $show_br = false;
    } else
    {
      if ($show_br == true)
      {
          echo "<br>";
      }
      echo $data_line;
      $show_br = true;
      if (substr($data_line, 0, 13) == 'Load Rotation')
      {
        echo '<input style="margin-top: -0.8em" type="checkbox" onclick="return toggleMe(\'event_rotation_' . $row_id . '\')">';
      } elseif (substr($data_line, 0, 13) == 'Load Playlist')
      {
        echo '<input type="checkbox" onclick="return toggleMe(\'event_playlist_' . $row_id . '\')">';
      }
    }

    if (substr($data_line, 0, 13) == 'Load Rotation')
    {
      show_rotation($data_line, $row_id, $dbh);
    }
    if (substr($data_line, 0, 13) == 'Load Playlist')
    {
      show_playlist($data_line, $row_id, $dbh);
    }
  }
  echo '</div></div>';
  echo '</td>';

  echo '</tr>';

  $row_id = $row_id +1;
}
echo '</table>';
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Show rotation
//---------------------------------------------------------------------------
function show_rotation($event_data, $row_id, $dbh)
{
$event_line_data = explode('|', $event_data);
$rotation_id = $event_line_data[1];

$sql = sprintf("select r.name 'Rotation', c.name 'Category', sb.name 'SubCategory', g.name 'Genre', rl.selType, rl.sweeper, rl.repeatRule RepeatRule, rl.data from rotations_list rl left join genre g on rl.genID = g.id, category c, subcategory sb, rotations r where rl.pid = %s and rl.catID = c.id and rl.subid = sb.id and rl.pid = r.id order by rl.ord", $rotation_id);

$sql = sprintf("
select r.name 'Rotation', c.name 'Category', sb.name 'SubCategory', g.name 'Genre', rl.selType, rl.sweeper, rl.repeatRule RepeatRule, rl.data 
from 
rotations_list rl
left join genre g on rl.genID = g.id
left join category c on rl.catID = c.id
left join subcategory sb on rl.subid = sb.id,
rotations r 
where 
rl.pid = %s and rl.pid = r.id order by rl.ord
" , $rotation_id);

$q = $dbh->query($sql);

$html = '<div id="event_rotation_' . $row_id . '" style="display: none;">';
$html .= '<div style="margin-left: 4px;">';

$html .= '<div class="divTable blueTable">';

$html .= '<div class="divTableHeading">';
$html .= '<div class="divTableRow">';
$html .= '<div class="divTableHead">Category</div>';
$html .= '<div class="divTableHead">SubCategory</div>';
$html .= '<div class="divTableHead">Genre</div>';
$html .= '<div class="divTableHead">Repeat</div>';
$html .= '</div></div>';
$html .= '<div class="divTableBody">';
$html .= '<div class="divTableRow">';

while ($row = $q->fetch())
{
    if ($row['data'] == '')
    {
        $html .= '<div class="divTableCell">' . $row['Category'] . '</div>';
        $html .= '<div class="divTableCell">' . $row['SubCategory'] . '</div>';
        $html .= '<div class="divTableCell">' . $row['Genre'] . '</div>';
        $html .= '<div class="divTableCell">' . $row['RepeatRule'] . '</div>';
    } else
    {
        $html .= '<div class="divTableCell">' . $row['data'] . '</div>';
        $html .= '<div class="divTableCell"></div><div class="divTableCell"></div><div class="divTableCell">' . $row['RepeatRule'] . '</div>';
    }

    $html .= '</div><div class="divTableRow">';
}

$html .= '</div></div></div></div></div>';
echo $html;
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Show playlist details
//---------------------------------------------------------------------------
function show_playlist($event_data, $row_id, $dbh)
{
$event_line_data = explode('|', $event_data);

$sql = sprintf("select s.artist artist, s.title title, pl.sid sid from playlists_list pl, songs s, playlists p where p.id = %s and p.id=pl.pid and pl.sid = s.id order by pl.ord", $event_line_data[2]);
$q = $dbh->query($sql);

$html = '<div id="event_playlist_' . $row_id . '" style="display: none;">';
$html .= '<div style="margin-left: 4px;">';

$html .= '<div class="divTable blueTable">';

$html .= '<div class="divTableHeading">';
$html .= '<div class="divTableRow">';
$html .= '<div class="divTableHead">Song ID</div>';
$html .= '<div class="divTableHead">Artist</div>';
$html .= '<div class="divTableHead">Title</div>';
$html .= '</div></div>';
$html .= '<div class="divTableBody">';
$html .= '<div class="divTableRow">';

while ($row = $q->fetch())
{
$html .= '<div class="divTableCell">';
$html .= '<form method="post" action="/cgi/songs.php" target="_blank">';
$html .= '<input type="hidden" name="ACTION" value="search">';
$html .= '<input type="hidden" name="search_for_artist" value="">';
$html .= '<input type="hidden" name="search_for_title" value="">';
$html .= '<button type="submit" name="song_id" class="btn-link" value="' . $row['sid'] . '">' . $row['sid'];
$html .= '</button></form></div>';
    $html .= '<div class="divTableCell">' . $row['artist']. '</div>';
    $html .= '<div class="divTableCell">' . $row['title'] . '</div>';

    $html .= '</div><div class="divTableRow">';
}

$html .= '</div></div></div></div></div>';
echo $html;
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Insert a row into the work table
//---------------------------------------------------------------------------
function insert_row($data, $uuid, $name, $time, $day, $dbh)
{
$sql = sprintf("insert into cfm_events_work (uuid, name, time, day, data) values ('%s', \"%s\", '%s', %u, \"%s\")", $uuid, $name, $time, $day, htmlentities($data));
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

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>RadioDJ - Events</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="shortcut icon" href="/coastfm.ico" />

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <script>
      function swapVisibility(id) {
        $('#' + id).toggle();
      }
    </script>

  <link href="/style.css" rel="stylesheet" type="text/css" />

<style>

.btn-link {
    border: none;
    outline: none;
    background: none;
    cursor: pointer;
    color: #0000EE;
    padding: 0;
    text-decoration: underline;
    font-family: inherit;
    font-size: inherit;
}

div.blueTable {
  border: 1px solid #1C6EA4;
  background-color: #EEEEEE;
  width: 100%;
  text-align: left;
  border-collapse: collapse;
}
.divTable.blueTable .divTableCell, .divTable.blueTable .divTableHead {
  padding: 3px 2px;
}
.divTable.blueTable .divTableBody .divTableCell {
  vertical-align: middle;
}
.divTable.blueTable .divTableRow:nth-child(even) {
  background: #D0E4F5;
}
.divTable.blueTable .divTableHeading {
  background: #1C6EA4;
  background: -moz-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
  background: -webkit-linear-gradient(top, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
  background: linear-gradient(to bottom, #5592bb 0%, #327cad 66%, #1C6EA4 100%);
  border-bottom: 2px solid #444444;
display: table-header-group;
column-count:4;
}
.divTable.blueTable .divTableHeading .divTableHead {
  font-size: 12px;
  font-weight: bold;
  color: #FFFFFF;
  border-left: 2px solid #D0E4F5;
}
.divTable.blueTable .divTableHeading .divTableHead:first-child {
  border-left: none;
}

/* DivTable.com */
.divTable{ display: table; }
.divTableRow { display: table-row; }
.divTableHeading { display: table-header-group;}
.divTableCell, .divTableHead { display: table-cell;}
.divTableHeading { display: table-header-group;}
.divTableBody { display: table-row-group;}

</style>

</head>
<body>
  <div id="header" class="fixed">
    <div id="logo">
      <img alt="CoastFM logo" src="/ads/images/coastfm_logo.png" style="float:left; margin-left: -3px; margin-top: -3px;"/>
      <h1>RADIODJ - Events</h1>
    </div>
  </div>

  <div id="cssmenu" class="bigbox fixed">
  <ul>
  <?php echo APP_MENU ?>
  </div>

<!--xxxx -->

<script>
function toggleMe(a) {
   var e = document.getElementById(a);
   if(!e) return true;

   if(e.style.display == "none") {
      e.style.display = "block";
   }
   else {
      e.style.display = "none";
   }
   return true;
}
</script>
<!--xxxx -->

  <div class="bigbox fixed">
  <div id="main_inner" class="fixed">

<?php
   $day_selector = day_selector($day);
?>

  <div class='post' style="text-align:center">
  <h2>Select Day</h2>
  <form method='post' action='/cgi/events.php'>
    <?php echo $day_selector?>
  </form>
  </div>
  <div class='post' style="text-align:center"> 

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
</div>
</div>
</div>
</body>
</html>
