<?php
// https://www.thebluealliance.com/apidocs/webhooks
require 'functions.php';

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
      $match = queryAPI('/match/'.$md["match_key"], $tba_api3_key);
      $data_string = '{"text": "Match ' . $match["match_number"] . ' coming up!","attachments":[';
      // BLUE
      foreach($match["alliances"]["blue"]["team_keys"] as $key) {
        $team = str_replace("frc","",$key);
        $data_string = $data_string . '{ "text": "<https://momentum4999.com/scouting/info.php?team='.$team.'|Team '.$team.'>"';
        if($team == "4999" || $team == "7042") {
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
        if($team == "4999" || $team == "7042") {
          $data_string = $data_string . ', "color" : "#ff2200"';
        } else {
          $data_string = $data_string . ', "color" : "#d60c0c"';
        }
        $data_string = $data_string . ' },';
      }
      $data_string = rtrim($data_string,",");
      $data_string = $data_string . ']}';
      $result = postToSlack($data_string, $slack_webhook_url);
      break;
		case "match_score":
			$match = $md["match"];
			if(in_array("frc4999",$match["alliances"]["blue"]["teams"])) {
				if($match["alliances"]["blue"]["score"] > $match["alliances"]["red"]["score"]) {
					$message = '{"text": "Congratulations Momentum!\n Match '.$match["match_number"].' won *'.$match["alliances"]["blue"]["score"].'*-'.$match["alliances"]["red"]["score"].'"}';
				} elseif($match["alliances"]["blue"]["score"] < $match["alliances"]["red"]["score"]) {
					$message = '{"text": "Better luck next time!\n Match '.$match["match_number"].' lost '.$match["alliances"]["blue"]["score"].'-*'.$match["alliances"]["red"]["score"].'*"}';
				} elseif($match["alliances"]["blue"]["score"] == $match["alliances"]["red"]["score"]) {
					$message = '{"text": "Good job Momentum!\n Match '.$match["match_number"].' tied '.$match["alliances"]["blue"]["score"].'-'.$match["alliances"]["red"]["score"].'"}';
				}
			} elseif(in_array("frc4999",$match["alliances"]["red"]["teams"])) {
				if($match["alliances"]["red"]["score"] > $match["alliances"]["blue"]["score"]) {
					$message = '{"text": "Congratulations Momentum!\n Match '.$match["match_number"].' won *'.$match["alliances"]["red"]["score"].'*-'.$match["alliances"]["blue"]["score"].'"}';
				} elseif($match["alliances"]["red"]["score"] < $match["alliances"]["blue"]["score"]) {
					$message = '{"text": "Better luck next time!\n Match '.$match["match_number"].' lost '.$match["alliances"]["red"]["score"].'-*'.$match["alliances"]["blue"]["score"].'*"}';
				} elseif($match["alliances"]["red"]["score"] == $match["alliances"]["blue"]["score"]) {
					$message = '{"text": "Good job Momentum!\n Match '.$match["match_number"].' tied '.$match["alliances"]["red"]["score"].'-'.$match["alliances"]["blue"]["score"].'"}';
				}
			} elseif(in_array("frc7042",$match["alliances"]["blue"]["teams"])) {
				if($match["alliances"]["blue"]["score"] > $match["alliances"]["red"]["score"]) {
					$message = '{"text": "Congratulations Rabbotics!\n Match '.$match["match_number"].' won *'.$match["alliances"]["blue"]["score"].'*-'.$match["alliances"]["red"]["score"].'"}';
				} elseif($match["alliances"]["blue"]["score"] < $match["alliances"]["red"]["score"]) {
					$message = '{"text": "Better luck next time!\n Match '.$match["match_number"].' lost '.$match["alliances"]["blue"]["score"].'-*'.$match["alliances"]["red"]["score"].'*"}';
				} elseif($match["alliances"]["blue"]["score"] == $match["alliances"]["red"]["score"]) {
					$message = '{"text": "Good job Rabbotics!\n Match '.$match["match_number"].' tied '.$match["alliances"]["blue"]["score"].'-'.$match["alliances"]["red"]["score"].'"}';
				}
			} elseif(in_array("frc7042",$match["alliances"]["red"]["teams"])) {
				if($match["alliances"]["red"]["score"] > $match["alliances"]["blue"]["score"]) {
					$message = '{"text": "Congratulations Rabbotics!\n Match '.$match["match_number"].' won *'.$match["alliances"]["red"]["score"].'*-'.$match["alliances"]["blue"]["score"].'"}';
				} elseif($match["alliances"]["red"]["score"] < $match["alliances"]["blue"]["score"]) {
					$message = '{"text": "Better luck next time!\n Match '.$match["match_number"].' lost '.$match["alliances"]["red"]["score"].'-*'.$match["alliances"]["blue"]["score"].'*"}';
				} elseif($match["alliances"]["red"]["score"] == $match["alliances"]["blue"]["score"]) {
					$message = '{"text": "Good job Rabbotics!\n Match '.$match["match_number"].' tied '.$match["alliances"]["red"]["score"].'-'.$match["alliances"]["blue"]["score"].'"}';
				}
			} else {
				$message = $message = '{"text": "Match Complete!\n Match '.$match["match_number"].' finished '.$match["alliances"]["blue"]["score"].'-'.$match["alliances"]["red"]["score"].'"}';
			}
			$result = postToSlack($message, $slack_webhook_url);
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
