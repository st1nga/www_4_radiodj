<?php
/*
Presenter information
*/

include("/coastfm/phplib/general.php");

$config = parse_ini_file("/home/coastfm/etc/www_4_rdj.conf", true);

/*echo "<pre>";
var_dump($_GET);
echo "</pre>";
*/

?>
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<html>
        <head>
        <title>Now Next Before</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <link rel="shortcut icon" href="/coastfm.ico" />

<script type = "text/javascript" src = "https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script type = "text/javascript" src = "/js/progressbar.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/paho-mqtt/1.0.1/mqttws31.js" type="text/javascript"></script>

<script type="text/javascript">
  function swapVisibility(id) {
    $('#' + id).toggle();
  }
</script>

<?php include("/coastfm/phplib/inc_now_playing_mq.html"); ?>

<script type = "text/javascript">

function onConnectionLost()
{
  document.getElementById("status").innerHTML = "Connection Lost";
  var d = new Date();
  document.getElementById("last_update").innerHTML = "Last update:" + d.toLocaleString()
  connected_flag=0;
  setTimeout(MQTTconnect, reconnectTimeout);
}

function onFailure(message)
{
  console.log("Connection Failed- Retrying");
  setTimeout(MQTTconnect, reconnectTimeout);
}

function onMessageArrived(r_message)
{
  console.log("In onMessageArrived. topic = " + r_message.destinationName);
  var d = new Date();
  document.getElementById("last_update").innerHTML = "Last update:" + d.toLocaleString()

  if (r_message.destinationName == 'pi/now')
  {
    document.getElementById("now").innerHTML = r_message.payloadString;
  } else if (r_message.destinationName == 'pi/prev')
  {
    document.getElementById("prev").innerHTML = r_message.payloadString;
  } else if (r_message.destinationName == 'pi/next')
  {
    document.getElementById("next").innerHTML = r_message.payloadString;
  } else if (r_message.destinationName == 'pi/last_5')
  {
    document.getElementById("last_5").innerHTML = r_message.payloadString;
  } else if (r_message.destinationName == 'pi/now_short')
  {
    data = r_message.payloadString;
    data_split = data.split(/:/);
    document.getElementById("now_short").innerHTML = data_split[2];
    var start_time = Math.round(Number(data_split[0]));
    const now = new Date();
    var now_time = Math.round(now.getTime() / 1000);

    var into_track_ms = ((now_time - start_time) * 1000);
    duration_ms = data_split[1] * 1000;
    duration_ms = duration_ms - into_track_ms;

    if (duration_ms > 0)
    {
      var bar = new ProgressBar.Line(now_short,
      {
        strokeWidth: 1,
        duration: duration_ms,
        color: '#000000',
        trailColor: '#89abf5',
        trailWidth: 0.2,
        svgStyle: {width: '100%', height: '100%'},
        from: {color: '#00f93c'},
        to: {color: '#f90000'},
        step: (state, bar) =>
        {
          bar.path.setAttribute('stroke', state.color);
        }
      });
      bar.animate(1.0);
    }

  } else if (r_message.destinationName == 'pi/ta_flag')
  {
    document.getElementById("ta_flag").innerHTML = "TA Flag: " + r_message.payloadString;
  } else if (r_message.destinationName == 'pi/active_studio')
  {

    document.getElementById("active_studio").innerHTML = "Active Studio: " + r_message.payloadString;
//    document.getElementById("active_studio").innerHTML = "Active Studio: "
  } else
  {
    console.log("topic = " + r_message.destinationName + " " + d.toLocaleString());
    document.getElementById("status").innerHTML = "Unknown " + r_message.destinationName + " message type recieved";
  }
}

function sub_topics()
{
  var soptions={qos:1,};
  mqtt.subscribe("pi/+",soptions);
  return false;
}

//+
//When we first run this webpage call the mosquitto connection
//-
<?php
  echo "  const username = '" . $config['mqtt']['username'] . "';\n";
  echo "  const host = '" . $config['mqtt']['host'] . "';\n";
  echo "  const port = " . $config['mqtt']['port'] . ";\n";
  echo "  const passwd = '" . $config['mqtt']['passwd'] . "';\n";
?>

  var connected_flag=0;
  var mqtt;
  var reconnectTimeout = 2000;
  var row=0;
  var out_msg="";
  var mcount=0;

  window.onload = MQTTconnect();

</script>

  <link href="/style.css" rel="stylesheet" type="text/css" />
  <link href="css/style_now.css" rel="stylesheet" type="text/css" />
  </head>
  <body>
      <div id="header" class="fixed">
        <div id="logo">
           <img src="images/logo.png" style="float:left; margin-left: -3px; margin-top: -3px;"/>

<h1>Now Next Before</h1></div>
         </div>


<?php
if (!isset($_GET['nomenu']))
{
  echo '<div id="cssmenu" class="bigbox fixed">';
  echo '<ul>';
  echo APP_MENU;
  echo '</div>';
}
?>


<?php include "/coastfm/phplib/inc_pi_box.html"?>

<div class="bigbox fixed">
<div id="main_inner" class="fixed">
<table>
  <tr><td id="connect" width="300"></td></tr>
</table>

<table style="width:100%">
  <tr valign="top">
    <td width="50%">
      <h2>Now Playing</h2>
      <div id="now"></div>
    </td>
    <td width="50%">
      <h2>Next</h2>
     <div id="next"></div>
    </td>
  <tr>
    <td>
      <h2>Previous</h2>
      <div id="prev">
    </td>
    <td>
      <h2>Previously on CoastFM</h2
      <span id="next_5"></span>
      <br>
      <span id="last_5"></span>
    </td>
  </tr>
</table>
</div>
  </body>
</html>

