<?php
// Stored in a separate file so that it's not publically visible in the github
require 'auths.php';

$scoutingAppInfoBase = "https://momentum4999.com/scouting/info.php?team=";

function writeToLog($string, $log) {
	file_put_contents($log.".log", date("d-m-Y_h:i:s")."-- ".$string."\r\n", FILE_APPEND);
}

function queryAPI($url, $key) {
  if($key !== false) {
    $url = 'https://www.thebluealliance.com/api/v3' . $url;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'X-TBA-Auth-Key: '. $key ));
    $data = curl_exec($ch);
    curl_close($ch);
    writeToLog("Queried " . $url . " using api key " . $key, "curl");
    return json_decode($data, true);
  }
}
function postToSlack($json, $url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
function stopTimeout() {
  ignore_user_abort(true);
  ob_start();
  header('Connection: close');
  header('Content-Length: '.ob_get_length());
  ob_end_flush();
  ob_flush();
  flush();
}
?>
