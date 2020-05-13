<?php
/*
Maintain the songs table
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

/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Connect to database
---------------------------------------------------------------------------*/
$dsn = 'mysql:dbname=' . $config['sql']['db_name'] . ';host=' . $config['sql']['host'];
$error_msg = '';

/*echo "<pre>";
var_dump($_POST);
echo "</pre>";
*/

try
{
  $dbh = new PDO($dsn, $config['sql']['admin_username'], $config['sql']['admin_passwd']);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
  echo "Connection failed $user $passwd $dsn";
}

$search_for_title = $_POST['search_for_title'];
$search_for_artist = $_POST['search_for_artist'];
$and_or = $_POST['and_or'];
$song_id = $_POST['song_id'];

if (empty($_POST['ACTION']))
{
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Just display what we have already, no action to be taken
---------------------------------------------------------------------------*/

  $search_for_title = '';
  $search_for_artist = '';
  $and_or =  '';
  $song_id = '';
} elseif ($_POST['ACTION'] == 'search')
{
} elseif ($_POST['ACTION'] == 'Edit')
{
  $sql = "select se.year_made_number_1, s.artist artist, s.title title, s.path path from songs s left join songs_extra se on s.id = se.song_id where s.id = " . $song_id;
  $q = $dbh->query($sql);
  $f = $q->fetch();
  $year_made_number_1 = $f['year_made_number_1'];
  $artist = $f['artist'];
  $title = $f['title'];
  $path = $f['path'];
} elseif ($_POST['ACTION'] == 'update')
{
  $year_made_number_1 = $_POST['year_made_number_1'];


  $sql = "insert into songs_extra (song_id, year_made_number_1) values ($song_id, $year_made_number_1)";
  try
  {
    $q = $dbh->query($sql);
  } catch (PDOException $e)
  {
    $sql = "update songs_extra set year_made_number_1 = $year_made_number_1 where song_id = $song_id";
    try
    {
      $q = $dbh->query($sql);
    } catch (PDOException $e)
    {
      echo "Can't update or insert we are buggered!!!";
    }
  }
  $year_made_number_1 = '';
}

?>
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE html>

<html>
	<head>
	<title>RadioDJ - Tracks</title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link rel="shortcut icon" href="/coastfm.ico" />

  <script type = "text/javascript" src = "https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>

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
      <img src="/images/logo.png" style="float:left; margin-left: -3px; margin-top: -3px;"/>
      <h1>RADIODJ - Tracks</h1>
    </div>
  </div>

<div id="cssmenu" class="bigbox fixed">
<ul>
<?php echo APP_MENU ?>
</div>

<div class="bigbox fixed">
<div id="main_inner" class="fixed">
<h1>Track Maintenance</h1>

<?php
if (!empty($error_msg))
{
  echo $error_msg;
}

?>

<div class='post' align='center'>
<h2>Search for Track</h2>

<form method='post' action='/cgi/songs.php'>
<table width='100%'>
<tr>
  <td class='base'>Title:</td>
  <td><input type='text' size='50' name='search_for_title' id='search_for_title' value="<?php echo $search_for_title ?>" /></td>
  <td><button onclick="document.getElementById('search_for_title').value = ''">Clear input field</button></td>
</tr>
<tr>
<?php
  if ($and_or == 'and')
  {
    echo "<td><select name='and_or'><option value='or'>or</option><option selected value='and'>and</opton></select></td>";
  } else
  {
    echo "<td><select name='and_or'><option selected value='or'>or</option><<option value='and'>and</opton></select></td>";
  }
?>
</tr>
<tr>
  <td class='base'>Artist:</td>
  <td><input type='text' size='50' onfocus="this.value=''" id='search_for_artist' name='search_for_artist' value="<?php echo $search_for_artist ?>" /></td>
  <td><button onclick="document.getElementById('search_for_artist').value = ''">Clear input field</button></td>
</tr>
</table>
<br>

<hr />
<table width='100%'>
<tr>
  <td width="50%" align="right"><input type="hidden" name="ACTION" value="search" /><input type="submit" name="SUBMIT" value="Search" /></td>
</tr>
</table>
</form>
</div>

<div class='post' align='center'>
<h2>Update Track</h2>

<form method='post' action='/cgi/songs.php'>
<table width='100%'>
<tr>
    <td class='base'>Artist:</td>
    <td><input size='50' type='text' name='artist' value="<?php echo $artist ?>" /></td>
    <td class='base'>Title:</td>
    <td><input size='50' type='text' name="title" value="<?php echo $title ?>" /></td>
</tr>
<tr>
    <td class='base'>Year made number 1:</td>
    <td><input type='text' name='year_made_number_1' value='<?php echo $year_made_number_1 ?>' /></td>
</tr>
<tr>
    <td class='base'>Path</td>
    <td><?php echo $path ?></td>
</tr>
</table>
<hr>
<table width='100%'>
<tr>
  <td align="right"><input type="hidden" name="ACTION" value="update" /><input type="submit" name="SUBMIT" value="Update" /></td>
</tr>
</table>

<?php
echo "<input type='hidden' name='search_for_title' value='$search_for_title'>";
echo "<input type='hidden' name='song_id' value='", $song_id, "' />";
?>
</form>
</div>

<div class='post'>
<h2>Tracks</h2>

<table class='tbl' style='width:100%;'>
  <tr>
    <th>Artist</th>
    <th>Title</th>
    <th>Path</th>
    <th>Action</th>
  </tr>

<?php

if (!empty($_POST['ACTION']))
{
  $artitst_query = "";
  if (!empty($search_for_artist))
  {
    $artitst_query = " s.artist like \"%$search_for_artist%\"";
  }
  $title_query = "";
  if (!empty($search_for_title))
  {
    $title_query = "s.title like \"%$search_for_title%\"";
  }

  if (empty($search_for_title) or empty($search_for_artist))
  {
    $and_or = "";
  } else
  {
    $and_or_query = $and_or;
  }
    
  $sql = "select s.id song_id, s.artist artist, s.title title, s.path path " .
         "from songs s " .
         "where s.enabled = 1 and ($title_query $and_or $artitst_query) order by title";


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

    echo '<td bgcolor="', $line_color, '" style=text-align:left;">';
    echo $row["artist"];
    echo '</td>';

    echo '<td bgcolor="', $line_color, '" style=text-align:left;">';
    echo $row["title"];
    echo '</td>';

    echo '<td bgcolor="', $line_color, '" style=text-align:left;">';
    echo $row["path"];
    echo '</td>';

    echo "<td align='center' bgcolor='", $line_color, "'>";
    echo "<form method='post' action='/cgi/songs.php'>";
    echo "<input type='hidden' name='ACTION' value='Edit' />";
    echo "<input type='hidden' name='search_for_title' value='$search_for_title'>";
    echo "<input type='image' name='Edit' src='/ads/images/edit.gif' alt='Edit' title='Edit' />";
    echo "<input type='hidden' name='song_id' value='", $row["song_id"], "' />";
    echo "</form>";
    echo "</td>";

    echo '</tr>';
    $line = $line + 1;
  }
}
?>

</table>

<tr>
    <td class='boldbase'>&nbsp;<b>Legend:&nbsp;</b></td>
    <td><img src='/ads/images/edit.gif' alt='Edit' /></td>
    <td class='base'>Edit</td>
</tr>
</table>

</div>

</body>
</html>
