<?php
// https://www.thebluealliance.com/apidocs/webhooks

// Stored in a separate file so that it's not publically visible in the github
$slack_webhook_url = rtrim(file_get_contents("slack_url.cfg"));
$tba_api3_key=rtrim(file_get_contents("api_key.cfg"));

function writeToLog($string, $log) {
	file_put_contents($log.".log", date("d-m-Y_h:i:s")."-- ".$string."\r\n", FILE_APPEND);
}

function queryAPI($url, $key) {
  if($key !== false) {
    $url = 'https://www.thebluealliance.com/api/v3/match/' . $url;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'X-TBA-Auth-Key: '. $key ));
    $data = curl_exec($ch);
    curl_close($ch);
    writeToLog("Queried " . $url . " using api key " . $key, "curl");
    return json_decode($data, true);
  }
}


if($_SERVER["REQUEST_METHOD"] == "POST") {
  // Get the json data and decode it into an associative array
  $data = json_decode(file_get_contents('php://input'),true);
  $md = $data["message_data"];

  switch($data["message_type"]) {
    // When a webhook is added, TBA requires that you validate it by recieving a validation key and entering it into the box.
    case "verification":
      file_put_contents("key.txt",$md["verification_key"]);
      break;
    case "upcoming_match":
      $match = queryAPI($md["match_key"], $tba_api3_key);
      $data_string = '{"text": "Match ' . $match["match_number"] . ' coming up!","attachments":[';
      // BLUE
      foreach($match["alliances"]["blue"]["team_keys"] as $key) {
        $team = str_replace("frc","",$key);
        $data_string = $data_string . '{ "text": "<https://momentum4999.com/scouting/info.php?team='.$team.'|Team '.$team.'>"';
        if($team == "4999") {
          $data_string = $data_string . ', "color" : "#06ceff"';
        } else {
          $data_string = $data_string . ', "color" : "#148be5"';
        }
        $data_string = $data_string . ' },';
      }
      // RED
      foreach($match["alliances"]["red"]["team_keys"] as $key) {
        $team = str_replace("frc","",$key);
        $data_string = $data_string . '{ "text": "<https://momentum4999.com/scouting/info.php?team='.$team.'|Team '.$team.'>"';
        if($team == "4999") {
          $data_string = $data_string . ', "color" : "#ff2200"';
        } else {
          $data_string = $data_string . ', "color" : "#d60c0c"';
        }
        $data_string = $data_string . ' },';
      }
      $data_string = rtrim($data_string,",");
      $data_string = $data_string . ']}';
      writeToLog("Will POST " . $data_string . ' to ' . $slack_webhook_url, "curl" );
      $ch = curl_init($slack_webhook_url);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $result = curl_exec($ch);
      curl_close($ch);
      writeToLog("Posted " . $data_string . ' to ' . $slack_webhook_url . ' with a result of ' . $result,"curl");
      break;
  }
} else {
  if(file_exists("key.txt")) {
    echo("The most recent key is: <b>" . rtrim(file_get_contents("key.txt")) . "</b>");
  } else {
    echo("No keys recieved!");
  }
}
?>
