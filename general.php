<?php

define('TIMING_LOG', '/tmp/web_ads.log');
define('START_TIME', microtime(true));

define ('APP_MENU', '<li class="has-sub"><a href="#"><span>Apps</span></a><ul>
  <li><a href="/ads/index.php">Adamatic</a></li>
  <li><a href="/cgi/history.php">Track History</a></li>
  <li><a href="/cgi/songs.php">Songs</a></li>
  <li><a href="/cgi/switch_rdj.pl">Studio Switch</a></li>
  <li><a href="/schedule/schedule.php">Schedule</a></li>
  <li><a href="/cgi/events.php">Show Events</a></li>
</li>
');

//<li><span><a href="logs.php">Logs</a></span></li>');

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//return millitime
//---------------------------------------------------------------------------
function millitime()
{

$m = explode(' ',microtime());
return date("d-m-Y H:i:s", $m[1]) . ".".(int)round($m[0]*1000,3);
}//---------------------------------------------------------------------------

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//Display a info message line
//---------------------------------------------------------------------------
function logit($level=0, $msg='')
{
$stack = debug_backtrace(1);
file_put_contents(TIMING_LOG, millitime()."|".$_SERVER['REMOTE_ADDR']."|". $stack[0]['file'] ."|".$stack[0]['line']."|".getElapsedTime()."| ".$msg."\n", FILE_APPEND);
}

//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
//---------------------------------------------------------------------------
function getElapsedTime()
{
$elapsed =  microtime(true) - START_TIME;
return $elapsed;
}
//--------------------------------------------------------------------------

?>
