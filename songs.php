<?php
/*
Maintain the songs table
*/

include("/coastfm/phplib/general.php");

$config = parse_ini_file("/home/coastfm/etc/www_4_rdj.conf", true);

$path = '';
$artist = '';
$title = '';
$year_made_number_1 = 0;

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Create list of song type (PHP)
//---------------------------------------------------------------------------
function song_type_selector($dbh, $song_type, $id)
{

  $sql = "select id, name from song_type";
  $html = "<select name='song_type' onchange='update_song_type($id, this.value)'>";

$q = $dbh->query($sql);
while ($f = $q->fetch())
{
  if ($f['id'] == $song_type)
  {
    $html = $html . "<option selected value='" . $f['name'] . "'>" . $f['name'] . "</option>";
  } else
  {
    $html = $html . "<option value='" . $f['name'] . "'>" . $f['name'] . "</option>";
  }
}

$html = $html . "</select>";
return $html;

}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Create category/subcategory selector (PHP)
//---------------------------------------------------------------------------
function subcategory_selector($dbh, $subcategory, $id)
{
  $html = "<select name='subcategory' onchange='update_subcategory($id, this.value)'>";

  $sql = "select c.name cat_name, sc.name sub_name, sc.id subcat_id from subcategory sc, category c where sc.parentid = c.id order by c.name, sc.name";
  $q = $dbh->query($sql);
  $save_category = '';
  while ($f = $q->fetch())
  {
    if ($f['cat_name'] != $save_category)
    {
      $html = $html . "<optgroup label='" . $f['cat_name'] . "'>";
      $save_category = $f['cat_name'];
    }

    if ($f['sub_name'] == $subcategory)
    {
      $html = $html . "<option selected value='" . $f['subcat_id'] . "'>" . $f['sub_name'] . "</option>";
    } else
    {
      $html = $html . "<option value='" . $f['subcat_id'] . "'>" . $f['sub_name'] . "</option>";
    }
  }

  $html = $html . "</select>";
  return $html;
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Display/accept update track info (PHP)
//---------------------------------------------------------------------------
function update_track($dbh, $song_id)
{
  $sql = "select s.song_type, se.year_made_number_1, if(se.retire_until = '0000-00-00', null, date(se.retire_until)) 'retire_until',
          s.artist artist, s.title title, s.path path, s.start_date start_date, s.end_date end_date,
          g.name genre_name, sc.name sub_cat_name, c.name cat_name, year(se.retire_until) retire_until_year, month(se.retire_until) retire_until_month,
          day(se.retire_until) retire_until_day, s.year, s.id, se.christmas_number_1
          from songs s left join songs_extra se on s.id = se.song_id, genre g, subcategory sc, category c
          where s.id_genre = g.id and s.id_subcat = sc.id and c.id = sc.parentid and s.id = " . $song_id;

  try
  {
    $q = $dbh->query($sql);
    $f = $q->fetch();
  } catch (PDOException $e)
  {
    echo "<pre>$e\n$sql</pre>";
  }

?>

<div class='post' align='center'>

<div align='left' style='font-size:1.6em'>Update Track
  <div style='float:right; margin-top: -15px'>
    <a target="_blank" href="https://www.google.com/search?q=date;released;<?php echo str_replace('&', '', $f['artist']) . ';' . str_replace('&', '', $f['title']) ?>">
      <img alt='Search' src='/search.jpg' width='150' border='0'>
    </a>
  </div>
</div>
<script type="text/javascript">
function fill_box(id_to_fill, val)
{
  document.getElementById(id_to_fill).value = val;
}

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Update DB on change of year (JS)
//---------------------------------------------------------------------------
function update_year(id, year)
{
  if (!/^[0-9]+$/.test(year) && year != '')
  {
    alert("Year must be between 1900 and 2999");
  } else
  {
    if (((year < 1900) || (year > 2999)) && year != '')
    {
      alert("Year must be between 1900 and 2999.");
    } else
    {
      message = new Paho.MQTT.Message("{\"type\":\"year\", \"id\":\"" + id + "\", \"year\":\"" + year + "\"}");
      message.destinationName = "db_update";
      mqtt.send(message);
    }
  }

}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Update DB on change of christmas_number_1 (JS)
//---------------------------------------------------------------------------
function update_christmas_number_1(id, value)
{
  message = new Paho.MQTT.Message("{\"type\":\"christmas_number_1\", \"id\":\"" + id + "\", \"christmas_number_1\":\"" + value + "\"}");
  message.destinationName = "db_update";
  mqtt.send(message);
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Update song_type
//---------------------------------------------------------------------------
function update_song_type(id, song_type)
{
  message = new Paho.MQTT.Message("{\"type\":\"song_type\", \"id\":\"" + id + "\", \"song_type\":\"" + song_type + "\"}");
  message.destinationName = "db_update";
  mqtt.send(message);
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// subcategory
//---------------------------------------------------------------------------
function update_subcategory(id, subcategory)
{
  message = new Paho.MQTT.Message("{\"type\":\"subcategory\", \"id\":\"" + id + "\", \"subcategory\":\"" + subcategory + "\"}");
  message.destinationName = "db_update";
  mqtt.send(message);
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Update Year made number 1
//---------------------------------------------------------------------------
function update_year_made_number_1(id, year_made_number_1) {

  if (!/^[0-9]+$/.test(year_made_number_1))
  {
    alert("Number 1 year between 1900 and 2999");
  } else
  {
    if ((year_made_number_1 < 1900) || (year_made_number_1 > 2999))
    {
      alert("Number 1 year between 1900 and 2999");
    } else
    {
      message = new Paho.MQTT.Message("{\"type\":\"year_made_number_1\", \"id\":\"" + id + "\", \"year\":\"" + year_made_number_1 + "\"}");
      message.destinationName = "db_update";
      mqtt.send(message);
    }
  }
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Update DB on change of retire until date
//---------------------------------------------------------------------------
function update_retire_until(id, retire_until)
{
  test_date = Date.parse(retire_until)
  if (isNaN(test_date) == true && retire_until != '')
  {
    alert("Invalid retire until year entered... try harder!")
    document.getElementById("retire_datepicker").focus();
  } else
  {
    message = new Paho.MQTT.Message("{\"type\":\"retire_until\", \"id\":\"" + id + "\", \"retire_until\":\"" + retire_until + "\"}");
    message.destinationName = "db_update";
    mqtt.send(message);
  }
}
//---------------------------------------------------------------------------

</script>

<table width='100%'>
<tr>
    <td class='base' align='right'>Artist:</td>
    <td class='base'><input size='50' type='text' name='artist' value="<?php echo $f['artist'] ?>" disabled/></td>
    <td class='base' align='right'>Title:</td>
    <td class='base'><input size='50' type='text' name="title" value="<?php echo $f['title'] ?>" disabled/></td>
</tr>
<tr>
  <td class='base'>Year made number 1:</td>
  <td>
    <table>
      <tr>
        <td class='base'><input type='text' name='year_made_number_1' value='<?php echo $f['year_made_number_1'] ?>' onchange="update_year_made_number_1(<?php echo $f['id']?>, this.value)"/></td>
<?php
if ($f['year_made_number_1'] != '')
{
  echo "<td id='christmas_number_1_text' class='base' align='right' style='padding-left: 10px;'>Christmas #1:</td>";

  if ($f['christmas_number_1'])
  {
    echo "<td id='christmas_number_1_value' class='base'><input checked type='checkbox' id='christmas_number_1' onclick='update_christmas_number_1(" . $f['id'] . ", this.checked)'></td>";
  } else
  {
    echo "<td id='christmas_number_1_value' class='base'><input type='checkbox' id='christmas_number_1' onclick='update_christmas_number_1(" . $f['id'] . ", this.checked)'></td>";
  }
} else
{
  echo "<td id='christmas_number_1_text' class='base' align='right' style='padding-left: 10px; visibility:hidden'>Christmas #1:</td>";
  echo "<td id='christmas_number_1_value' class='base'><input type='checkbox' id='christmas_number_1' style='visibility:hidden' onclick='update_christmas_number_1(" . $f['id'] . ", this.value)'></td>";
}
?>
      </tr>
    </table>
  </td>

  <td class='base' align='right'>Rest until:</td>

  <td>
    <div id="input-outer">
      <div id="retire_until_field">
        <input size="40" id="retire_datepicker" type="text" name='retire_until' value="<?php echo $f['retire_until'] ?>" onchange="update_retire_until(<?php echo $f['id']?>, this.value)">
      </div>
      <div id="block_retire_until"></div>
      <div id="clear_retire_until"></div>
      <div id="retire_plus_1_month"></div>
    </div>
  </td>

</tr>
<tr>
  <td class='base' align='right'>Song type:</td>
  <td class='base'><?php echo song_type_selector($dbh, $f['song_type'], $f['id']) ?> </td>
  <td class='base' align='right'>Genre:</td>
  <td class='base'><input size='50' type='text' name='genre' value="<?php echo $f['genre_name'] ?>" disabled/></td>
</tr>
<tr>
  <td class='base' align='right'>Category:</td>
 <td class='base'><input size='50' type='text' name='category' value="<?php echo $f['cat_name'] ?>" disabled/></td>
  <td class='base' align='right'>Subcategory:</td>
  <td class='base'><?php echo subcategory_selector($dbh, $f['sub_cat_name'], $f['id']) ?> </td>
</tr>
<tr>
  <td class='base' align='right'>Start Date:</td>
  <td class='base'><input size='50' type='text' name='start_date' value="
<?php
if ($f['start_date'] == '2002-01-01 00:00:01') { echo ""; } else { echo $f['start_date']; }
?>" disabled/></td>
  <td class='base' align='right'>End Date:</td>
  <td class='base'><input size='50' type='text' name='end_date' value="
<?php if ($f['end_date'] == '2002-01-01 00:00:01') { echo ""; } else { echo $f['end_date'];} ?>" disabled/></td>
</tr>

<tr>
  <td class='base' align='right'>Year:</td>
  <td class='base'><input size='10' type='text' name='year' title="Enter Year 1900 -2999" pattern="^[1,2][0-9][0-9][0-9]$" value="<?php echo $f['year'] ?>" onchange="update_year(<?php echo $f['id']?>, this.value)"/></td>
</tr>

<tr>
    <td class='base' align='right' class='base'>Path:</td>
    <td class='base'><input type='text' size='80' name='path' value="<?php echo $f['path'] ?>" disabled/></td>
    <td class='base' align='right'>Mod. Date:</td>
    <td class='base'> <input type='text' value='
<?php
if ($f['path'] <> '')
{
  $path = str_replace("m:", "", $f['path']);
  $path = str_replace("M:", "", $path);
  $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
  $path = "/mp3" . $path;
  echo date("F d Y H:i:s", filemtime($path));
}
?>' disabled/></td>

</tr>
</tr>
  <td class='base' align='right' class='base'>Linux:</td>
  <td class='base'><input type='text' size='80' name='linux' value="<?php echo "$path" ?>" disabled/></td>
</tr>
</table>
<hr>
<table width='100%'>
<tr>
  <td align="right"><input type="hidden" name="ACTION" value="update" /><input type="submit" name="SUBMIT" value="Update" /></td>
</tr>
</table>

<?php
echo "<input type='hidden' name='search_for_title' value='" . $_POST['search_for_title'] . "'>";
echo "<input type='hidden' name='song_id' value='", $song_id, "' />";
echo "<input type='hidden' name='search_for_artist' value='" . $_POST['search_for_artist'] . "'>";
echo "<input type='hidden' name='and_or' value='" . $_POST['and_or'] . "'>";

?>
</table>
</div>

<script>
function hide_christmas_number_1()
{
$('#christmas_number_1_text'.show)
$('#christmas_number_1_value'.show)
}

$('#clear_retire_until').click(function () {
  $('#retire_until_field input').val('');
  $('#retire_until_field input').focus();
  update_retire_until(<?php echo $f['id'] ?>, "");
});

$('#block_retire_until').click(function () {
  $('#retire_until_field input').val('2399-12-31');
  $('#retire_until_field input').focus();
  update_retire_until(<?php echo $f['id']?>, "2399-12-31");

});

$('#retire_plus_1_month').click(function () {
  current_value = $('#retire_datepicker').val()
  if (current_value == '')
  {
    var current_value = new Date()
  }

  var dt = new Date(current_value);
  dt = addMonths(dt, 1);
  new_date = dt.getFullYear() + "-" + ("0" + (dt.getMonth() + 1)).slice(-2) + "-" + dt.getDate();

  update_retire_until(<?php echo $f['id']?>, new_date);

  $('#retire_until_field input').val(new_date);
  $('#retire_until_field input').focus();
});

function addMonths(dateObj, num) {
    var currentMonth = dateObj.getMonth() + dateObj.getFullYear() * 12;
    dateObj.setMonth(dateObj.getMonth() + num);
    var diff = dateObj.getMonth() + dateObj.getFullYear() * 12 - currentMonth;

    // If don't get the right number, set date to 
    // last day of previous month
    if (diff != num) {
        dateObj.setDate(0);
    } 
    return dateObj;
} 
</script>

<?php

}
//---------------------------------------------------------------------------

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

/*
echo "<pre>"; var_dump($_POST); echo "</pre>";
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

if (empty($_POST['search_for_title']))
{
  $search_for_title =  '';
} else
{
  $search_for_title = $_POST['search_for_title'];
}

if (empty($_POST['search_for_artist']))
{
  $search_for_artist = '';
} else
{
  $search_for_artist = $_POST['search_for_artist'];
}

if (!empty($_POST['song_id']))
{
  $song_id = $_POST['song_id'];
} else
{
  $song_id = 0;
}

if (!empty($_POST['and_or']))
{
  $and_or = $_POST['and_or'];
} else
{
  $and_or = '';
}

if (empty($_POST['ACTION']))
{
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
Just display what we have already, no action to be taken
---------------------------------------------------------------------------*/

  $search_for_title = '';
  $search_for_artist = '';
  $and_or =  '';
  $song_id = '';
//} elseif ($_POST['ACTION'] == 'search')
//{
//} elseif ($_POST['ACTION'] == 'Edit')
//{
//  $sql = "select se.year_made_number_1, if(se.retire_until = '0000-00-00 00:00:00', null, se.retire_until) 'retire_until', 
//          s.artist artist, s.title title, s.path path, s.start_date start_date 
//          from songs s left join songs_extra se on s.id = se.song_id where s.id = " . $song_id;
//  $q = $dbh->query($sql);
//  $f = $q->fetch();
//  $year_made_number_1 = $f['year_made_number_1'];
//  $artist = $f['artist'];
//  $title = $f['title'];
//  $path = $f['path'];
//} elseif ($_POST['ACTION'] == 'update')
//{
//  try
//  {
//    $sql = "select song_id from songs_extra where song_id = ?";
//    $sth = $dbh->prepare($sql);
//    $sth->bindParam(1, $song_id);
//    $sth->execute();
//    $f = $sth->fetch();
//  } catch (PDOException $e)
//  {
//    echo "Can't select! we are buggered!!!";
//    echo $sql;
//  }

//  if (empty($f['song_id']))
//  {
//    try
//    {
//      $sql = "insert into songs_extra (song_id) values (?)";
//      $sth = $dbh->prepare($sql);
//      $sth->bindParam(1, $song_id);
//      $sth->execute();
//    } catch (PDOException $e)
//    {
//      echo "Can't insert! we are buggered!!!";
//      echo $sql;
//    }
//  }

//  if (!empty($_POST['year_made_number_1']))
//  {
//    try
//    {
//      $sql = "update songs_extra set year_made_number_1 = :year_made_number_1 where song_id = :song_id";
//      $sth = $dbh->prepare($sql);
//      if (strtoupper($_POST['year_made_number_1']) == 'NULL')
//      {
//        $sth->bindValue(":year_made_number_1", null, PDO::PARAM_NULL);
//      } else
//      {
//        $sth->bindValue(":year_made_number_1", $_POST['year_made_number_1']);
//      }
//
//      $sth->bindValue(":song_id", $song_id);
//      $sth->execute();
//    } catch (PDOException $e)
//    {
//      echo "Can't update songs_extra! we are buggered!!!";
//      echo $sql;
//    }
//  }
//
//  $sql = "update songs_extra set retire_until = :retire_until where song_id = :song_id";
//  try
//  {
//    $sth = $dbh->prepare($sql);
//    $sth->bindValue(":song_id", $song_id);
//    if ($_POST['retire_until'] == '')
//    {
//      $sth->bindValue(":retire_until", null, PDO::PARAM_NULL);
//    } else
//    {
//       $sth->bindValue(":retire_until", $_POST['retire_until']);
//    }

//    $sth->execute();
//  } catch (PDOException $e)
//  {
//    echo "Can't update songs_extra! we are buggered!!!<br>";
//    echo "$e<br>";
//    echo $sql;
//  }
}

?>
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE html>

<html>
	<head>
	<title>RadioDJ - Tracks</title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<link rel="shortcut icon" href="/coastfm.ico" />

  <script type = "text/javascript" src = "https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js" type="text/javascript"></script>

	<script type="text/javascript">
		function swapVisibility(id) {
			$('#' + id).toggle();
		}
	</script>

<script>
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//---------------------------------------------------------------------------
function onConnectionLost()
{
  connected_flag=0;
  setTimeout(MQTTconnect, reconnectTimeout);
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//---------------------------------------------------------------------------
function onFailure(message)
{
  console.log("Connection Failed- Retrying");
  setTimeout(MQTTconnect, reconnectTimeout);
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Once a connection has been made, make a subscription and send a message.
//---------------------------------------------------------------------------
function onConnect() {
  console.log("Connected to '" + host + "' on port '" + port + "'");
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//---------------------------------------------------------------------------
function onConnected(recon,url)
{
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//---------------------------------------------------------------------------
function MQTTconnect()
{
  mqtt = new Paho.MQTT.Client(host,port,"np_web_" + create_UUID());
  var options = {
    timeout: 3,
    cleanSession: true,
    onSuccess: onConnect,
    onFailure: onFailure,
  };

  options.userName = username;
  options.password = passwd;

  mqtt.onConnectionLost = onConnectionLost;
  mqtt.onConnected = onConnected;

  mqtt.connect(options);
  return false;
}
//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//---------------------------------------------------------------------------
function create_UUID()
{
    var dt = new Date().getTime();
    var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (dt + Math.random()*16)%16 | 0;
        dt = Math.floor(dt/16);
        return (c=='x' ? r :(r&0x3|0x8)).toString(16);
    });
    return uuid;
}//---------------------------------------------------------------------------


//+
//When we first run this webpage call the mosquitto connection
//-
  const username = 'radiodj_update';
  const host = 'nostromo.coastfm.co.uk';
  const port = 9001;
  const passwd = 'eiqueG2elai4eaFu0b';

  var connected_flag=0;
  var mqtt;
  var reconnectTimeout = 2000;
  var row=0;
  var out_msg="";
  var mcount=0;

  window.onload = MQTTconnect();
</script>



<link href="/style.css" rel="stylesheet" type="text/css" />
<link href="/css/clear_button.css" rel="stylesheet" type="text/css" />

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
  <td>
    <div id="input-outer">
      <div id="title_field">
        <input size="50" type="text" name='search_for_title' value="<?php echo $_POST['search_for_title'] ?>" >
      </div>
      <div id="clear_title"></div>
    </div>
  </td>

<?php
  if ($and_or == 'and')
  {
    echo "<td><select name='and_or'><option value='or'>or</option><option selected value='and'>and</opton></select></td>";
  } else
  {
    echo "<td><select name='and_or'><option selected value='or'>or</option><<option value='and'>and</opton></select></td>";
  }
?>
  <td class='base'>Artist:</td>
  <td>
    <div id="input-outer">
      <div id="artist_field">
      <input size="50" type="text" name='search_for_artist' value="<?php echo $_POST['search_for_artist'] ?>" >
      </div>
      <div id="clear_artist"></div>
    </div>
  </td>
</tr>

</table>

<script>
$('#clear_artist').click(function () {
    $('#artist_field input').val('');
    $('#artist_field input').focus();
});
$('#clear_title').click(function () {
    $('#title_field input').val('');
    $('#title_field input').focus();
});
</script>
<br>

<hr />
<table width='100%'>
<tr>
  <td width="50%" align="center"><input type="hidden" name="ACTION" value="search" /><input type="submit" name="SUBMIT" value="Search" /></td>
</tr>
</table>
</form>
</div>

<?php
if ($_POST['song_id'] != "")
{
  update_track($dbh, $_POST['song_id']);
}
?>

<div class='post' align='center'>
<table class='tbl' style='width:100%;'>
  <tr>
    <th>ID</th>
    <th>Artist</th>
    <th>Title</th>
    <th>Path</th>
    <th>Enabled</th>
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
  }

  if (!empty($search_for_title) or empty(!$search_for_artist))
  {
    $sql = "select s.id song_id, s.artist artist, s.title title, s.path path, s.enabled " .
           "from songs s " .
           "where ($title_query $and_or $artitst_query) order by title";
  } else
  {
    if ($_POST['song_id'] != "")
      $sql = "select s.id song_id, s.artist artist, s.title title, s.path path, s.enabled enabled " .
             "from songs s " .
             "where s.id = " . $_POST['song_id'];
  }

echo "sql=$sql";
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
    echo '<form target="_self" action="songs.php" method="post">';
    echo '<button type="submit" name="song_id" class = "btn-link" value="';
    echo $row["song_id"];
    echo '">';
    echo '<input type="hidden" name="and_or" value="or">';
    echo '<input type="hidden" name="ACTION" value="search">';
    echo '<input type="hidden" name="search_for_title" value="' . $_POST['search_for_title'] . '">';
    echo '<input type="hidden" name="search_for_artist" value="' . $_POST['search_for_artist'] . '">';
    echo $row["song_id"];
    echo '</button>';
    echo '</form>'; 
    echo '</td>';


  echo '<td bgcolor="', $line_color, '" style=text-align:left;">';
  echo '<form target="_self" action="songs.php" method="post">';
  echo '<button type="submit" name="search_for_artist" class = "btn-link" value="';
  echo $row["artist"];
  echo '">';
  echo '<input type="hidden" name="and_or" value="or">';
  echo '<input type="hidden" name="ACTION" value="search">';
  echo '<input type="hidden" name="search_for_title" value="' . $_POST['search_for_title'] . '">';
  echo '<input type="hidden" name="song_id" value="">';
  echo $row["artist"];
  echo '</button>';
  echo '</form>';
  echo '</td>';

  echo '<td bgcolor="', $line_color, '" style=text-align:left;">';
  echo $row["title"];
  echo '</td>';

  echo '<td bgcolor="', $line_color, '" style=text-align:left;">';
  echo $row["path"];
  echo '</td>';

  echo "<td align='center' bgcolor='", $line_color, "'>";
  echo "<form method='post' action='/cgi/songs.php'>";

  if ($row["enabled"] == 1)
  {
    echo "<input type='image' name='disable' src='/images/on.gif' alt='enabled' title='disable 'width='14' height='14' style='margin-top:0; margin-bottom:0'/>";
  } else
  {
    echo "<input type='image' name='enable' src='/images/off.gif' alt='disabled' title='enable' 'width='14' height='14' style='margin-top:0; margin-bottom:0'/>";
  }

    echo "</td>";

    echo "<input type='hidden' name='ACTION' value='Edit' />";
    echo "<input type='hidden' name='search_for_title' value='" . $_POST['search_for_title'] . "'>";
    echo "<input type='hidden' name='song_id' value='", $row["song_id"], "' />";
    echo "<input type='hidden' name='search_for_artist' value='$search_for_artist'>";
    echo "<input type='hidden' name='and_or' value='$and_or'>";
    echo "</form>";

    echo '</tr>';
    $line = $line + 1;
  }
}
?>

</table>

<tr>
    <td class='boldbase'>&nbsp;<b>Legend:&nbsp;</b></td>
    <td><img src='/ads/images/edit.gif' alt='Edit' width='20' height='20'/>Edit</td>
    <td><img src='/images/on.gif' alt='Enable' width='20' height='20'/>Enable</td>
    <td><img src='/images/off.gif' alt='Disable' width='20' height='20'/></td>
    <td class='base'>Disable</td>
</tr>
</table>

</div>

</body>
</html>
