<?php
$slack_webhook_url = file_get_contents("slack_url.cfg");

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
        $data_string = $data_string . '{ "text": "<https://momentum4999.com/scouting/info.php?team="'.$team.'|Team '.$team.'>" },';
      }
      $data_string = rtrim($data_string,",");
      $data_string = $data_string . ']}';
      file_put_contents("curl.log","Will POST " . $data_string . " to ". $slack_webhook_url);
      $cl = curl_init($slack_webhook_url);
      curl_setopt($cl,"POST");
      curl_setopt($cl, CURLOPT_POSTFIELDS, $data_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($cl,array(
        "Content-type: application/json",
        'content-length: '.strlen($data_string)
      ));
      curl_exec($cl);
      break;
  }
} else {
  echo("Please use post");
}
?>
