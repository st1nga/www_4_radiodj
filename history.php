<?php
/*
List the history table

===========================================================================
Modifications
08-Mar-2020 mikep
Added the option to click on artist or title and open songs.php.

07-Apr-2020 mikep
Changed duration to hh:mm:ss so it is more useful.
*/

include("/coastfm/phplib/general.php");

$config = parse_ini_file("/home/coastfm/etc/www_4_rdj.conf", true);


//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//return error message if values passed are not valid
//---------------------------------------------------------------------------
function chk_values($length_s, $start, $end)
{

$error_msg = '';

if (empty($length_s))
{
  $error_msg = $error_msg . "<font class='base'>Invalid Ad length.&nbsp;</font><br>";
}
if (empty($start) or !strtotime($start))
{
  $error_msg = $error_msg . "<font class='base'>Invalid Start time;</font><br>";
}
if (empty($end) or !strtotime($end))
{
  $error_msg = $error_msg . "<font class='base'>Invalid End time;</font><br>";
}

if (!empty($error_msg))
{
  return "<div class='post'><h2>Error messages</h2>" . $error_msg . "</div>";
}

}
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Create list of unique users
//---------------------------------------------------------------------------
function user_selector($dbh, $user)
{

$sql = "select distinct user from history order by user";
$html = "<select name='active_studio'>";

if ($user == 'Active Studio')
{
  $html = $html . "<option selected value='Active Studio'>Active Studio</option>";
} else
{
  $html = $html . "<option value='Active Studio'>Active Studio</option>";
}

if ($user == 'All')
{
  $html = $html . "<option selected value='All'>All</option>";
} else
{
  $html = $html . "<option value='All'>All</option>";
}

$q = $dbh->query($sql);
while ($f = $q->fetch())
{
  if ($f['user'] == $user)
  {
    $html = $html . "<option selected value='" . $f['user'] . "'>" . $f['user'] . "</option>";
  } else
  {
    $html = $html . "<option value='" . $f['user'] . "'>" . $f['user'] . "</option>";
  }
}

$html = $html . "</select>";
return $html;

}
//---------------------------------------------------------------------------

logit(0, "starting");

$dsn = 'mysql:dbname=' . $config['sql']['db_name'] . ';host=' . $config['sql']['host'];
$error_msg = '';

/*
echo "<pre>";
var_dump($_POST);
echo "</pre>";
*/

try
{
  $dbh = new PDO($dsn, $config['sql']['username'], $config['sql']['passwd']);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
  echo "Connection failed '" . $config['sql']['username'] . "' $dsn";
}

if (empty($_POST['ACTION']))
{
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Just display what we have already, no action to be taken
---------------------------------------------------------------------------*/

  $start = '';
  $sql_start = 'now() - interval 60 minute';
  $end = '';
  $sql_end = 'now()';
  $user = 'Active Studio';
} else
{
  $start = $_POST['start'];
  if ($start == '')
  {
    $sql_start = 'now() - interval 60 minute';
  } else
  {
    $sql_start = "'$start'";
  }

  $end = $_POST['end'];
  if ($end == '')
  {
    $sql_end = 'now()';
  } else
  {
    $sql_end = "'$end'";
  }

  $user = $_POST['active_studio'];
}

$submit_button = '<td width="50%" align="right"><input type="hidden" name="ACTION" value="search" /><input type="submit" name="SUBMIT" value="Search" /></td>';

?>
<!DOCTYPE html>
<html>
  <head>
  <title>RadioDJ - History</title>

  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
  <link rel="shortcut icon" href="/coastfm.ico" />
  <script type="text/javascript" src="/ads/include/jquery.js"></script>

  <script type="text/javascript">
		function swapVisibility(id) {
			$('#' + id).toggle();
		}
  </script>


  <link href="/style.css" rel="stylesheet" type="text/css" />
  <link href="/css/style_now.css" rel="stylesheet" type="text/css" />
  </head>
  <body>
    <div id="header" class="fixed">
    <div id="logo">
      <img src="/images/logo.png" style="float:left; margin-left: -3px; margin-top: -3px;"/>


<h1>RADIODJ - History</h1>			</div>
		</div>

<div id="cssmenu" class="bigbox fixed">
<ul>
<?php echo APP_MENU ?>
</div>

<div class="bigbox fixed">
<div id="main_inner" class="fixed">

<?php
if (!empty($error_msg))
{
  echo $error_msg;
}

$user_selector = user_selector($dbh, $user);

?>

<div class='post' align='center'>
<h2>Select dates</h2>


<form method='post' action='/cgi/history.php'>
<table width='100%'>
<tr>
    <td class='base'><div class="tooltip">Start:<span class="tooltiptext">Start date and time. Format is YYYY-MM-DD HH:MM:SS</span></div>&nbsp;<img src='/ads/images/blob.gif' alt='*' /></td>
    <td><input type='text' name='start' value='<?php echo $start ?>' /></td>

    <td class='base'><div class="tooltip">End:<span class="tooltiptext">End date and time. Format is YYYY-MM-DD HH:MM:SS</span></div>&nbsp;<img src='/ads/images/blob.gif' alt='*' /></td>
    <td><input type='text' name='end' value='<?php echo $end ?>' /></td>
</tr>
<tr>
    <td class='base'>Studio:</td>
    <td><?php echo $user_selector ?></td>
</tr>
</table><br>

<hr />

<?php

echo $submit_button;

?>

</form>
</div>

<div class='post'>

<table class='tbl' style='width:100%;'>
  <tr>
    <th>ID</th>
    <th>Played</th>
    <th>Duration<br>(hh:mm:ss)</th>
    <th>Artist</th>
    <th>Title</th>
    <th>Studio</th>
  </tr>


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
</style>
<?php

$sql = "select songid, date_played,sec_to_time(truncate(duration,0)) duration, artist,title, user from history where date_played between $sql_start and $sql_end";

if ($user == 'Active Studio')
{
  $sql = $sql . " and active = 1";
} else
{
  if ($user != 'All')
  {
    $sql = $sql . " and user = '$user'";
  }
}

echo $sql;

$q = $dbh->query($sql);
$line = 1;
while ($row = $q->fetch())
{
  echo '<tr>';

  if (($line / 2) == (intval($line / 2)))
  {
    $line_color = '#D6D6D6';
  } else
  {
    $line_color = '#F0F0F0';
  }

  echo '<td bgcolor="', $line_color, '" style=text-align:center;">';
  echo $row["songid"];
  echo '</td>';

  echo '<td bgcolor="', $line_color, '" style=text-align:center;">';
  echo $row["date_played"];
  echo '</td>';

  echo '<td bgcolor="', $line_color, '" style=text-align:center;">';
  echo $row["duration"];
  echo '</td>';

  echo '<td bgcolor="', $line_color, '" style=text-align:left;">';
  echo '<form target="_blank" action="songs.php" method="post">';
  echo '<button type="submit" name="search_for_artist" class = "btn-link" value="';
  echo $row["artist"];
  echo '">';
  echo '<input type="hidden" name="and_or" value="or"</input>';
  echo '<input type="hidden" name="ACTION" value="search"</input>';
  echo '<input type="hidden" name="search_for_title" value=""</input>';
  echo $row["artist"];
  echo '</button>';
  echo '</form>';
  echo '</td>';

  echo '<td bgcolor="', $line_color, '" style=text-align:left;">';
  echo '<form target="_blank" action="songs.php" method="post">';
  echo '<button type="submit" name="search_for_title" class = "btn-link" value="';
  echo $row["title"];
  echo '">';
  echo '<input type="hidden" name="and_or" value="or"</input>';
  echo '<input type="hidden" name="ACTION" value="search"</input>';
  echo '<input type="hidden" name="search_for_artist" value=""</input>';
  echo $row["title"];
  echo '</button>';
  echo '</form>';
  echo '</td>';

  echo '<td bgcolor="', $line_color, '" style=text-align:center;">';
  echo $row["user"];
  echo '</td>';

  echo "</form>";
  echo "</td>";

  echo '</tr>';
  $line = $line + 1;
}
?>
</table>

</div>

</body>
</html>
