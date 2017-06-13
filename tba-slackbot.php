<?php
function writeToLog($string, $log) {
	file_put_contents($log.".log", date("d-m-Y_h:i:s")."-- ".$string."\r\n", FILE_APPEND);
}

$slack_webhook_url = rtrim(file_get_contents("slack_url.cfg"));

if($_SERVER["REQUEST_METHOD"] == "POST") {
  $data = json_decode(file_get_contents('php://input'),true);
  $md = $data["message_data"];
  switch($data["message_type"]) {
    case "verification":
      file_put_contents("key.txt",$md["verification_key"]);
      break;
    case "upcoming_match":
      $data_string = '{"text": "Match coming up!","attachments":[';
      foreach($md["team_keys"] as $key) {
        $team = str_replace("frc","",$key);
        $data_string = $data_string . '{ "text": "<https://momentum4999.com/scouting/info.php?team='.$team.'|Team '.$team.'>"';
        if($team == "4999") {
          $data_string = $data_string . ', "color" : "#06ceff"';
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
      //file_put_contents("curl.log","---POSTed " . $data_string . " to ". $slack_webhook_url . " with a result of" . $result, FILE_APPEND);
      break;
  }
} else {
  echo("Please use post");
}
?>
