<?php
require 'functions.php';
date_default_timezone_set("America/Los_Angeles");

function getCurrentEvent($team) {
  global $tba_api3_key;
  writeToLog('Querying TBA at ' . 'team/frc'.$team.'/events/simple using key '.$tba_api3_key, 'event');
  $events = queryAPI('/team/frc'.$team.'/events/simple',$tba_api3_key);
  $date = DateTime::createFromFormat('Y-m-d',$events[0]["start_date"]);
  writeToLog("Event ".$events[0]["name"]." at ".$events[0]["start_date"]." is ".abs($date->getTimestamp()-time()).'ms away','event');
  $nEv = $events[0];
  $nEvTime = abs($date->getTimestamp()-time());
  #writeToLog("newestEvent:(".$nEvTime.', '.json_encode($nEv).')','event');
  foreach($events as $event){
    $date = DateTime::createFromFormat('Y-m-d',$event["start_date"]);
    writeToLog('Event '.$event["name"]. ' at ' .$event["start_date"] . ' is ' . abs(time()-$date->getTimestamp()).'ms away','event');
    if(abs(time()-$date->getTimestamp()) < $nEvTime && $date->getTimestamp()-time()<0) {
      $nEv = $event;
      $nEvTime = abs(time()-$date->getTimestamp());
    }
  }
  return $nEv;
}
if($_SERVER["REQUEST_METHOD"] == "POST") {
  if($_POST["token"] == $slack_token) {
    $url = $_POST["response_url"];
    $opts = str_getcsv($_POST["text"], ' ');
    stopTimeout();
    writeToLog($_POST["command"].' has a verb of ' . $opts[0] . ' and options of '.$opts[1],"slash");
    switch($opts[0]) {
      case "score":
        if(!array_key_exists(1,$opts)) {
          $team = 4999;
        } else {
          $team = $opts[1];
        }
        writeToLog('Getting the score of '.$team,'score');
        $nEv = getCurrentEvent($team);
        writeToLog('Querying event '.$nEv["key"],"score");
        $teamEvent = queryAPI('/team/frc'.$team.'/event/'.$nEv["key"].'/status',$tba_api3_key);
        writeToLog('Status: ' . $teamEvent["overall_status_str"],'score');
        $status = rtrim(str_replace(array('<b>','</b>'),"*",$teamEvent["overall_status_str"]),'.');
        if(isset($status) and isset($nEv["name"]) and !empty($status)) {
          postToSlack('{"response_type": "in_channel", "text":"At '.$nEv["name"].', '.$status.'"}', $url);
        } else {
          postToSlack('{"response_type": "ephemeral", "text":"An error ocurred while retrieving data for team '.$team.'"}', $url);
        }
        break;
      case "team":
        if(!array_key_exists(1,$opts)) {
          postToSlack('{"response_type" : "ephemeral", "text" : "Usage: ", "attachments":[{"text":"/tba team [team number]\nReturns a link to the scouting app for the given team"}]}', $url);
        } else {
          postToSlack('{"response_type" : "ephemeral", "attachments":[{"text" : "<https://momentum4999.com/scouting/info.php?team='.urlencode($opts[1]).'|Team '.$opts[1].'>"}]}', $url);
        }
        break;
      case "match":
        stopTimeout();
        // Return next upcoming match
        if(!array_key_exists(1,$opts)) {
          $team = 4999;
        } else {
          $team = $opts[1];
        }
        $nEv = getCurrentEvent($team);
        writeToLog('Querying '.'/team/frc'.$team.'/event/'.$nEv["key"].'/matches', 'matches');
        $matches = queryAPI('/team/frc'.$team.'/event/'.$nEv["key"].'/matches',$tba_api3_key);
        $tmatch = NULL;
        foreach($matches as $match) {
          // I'm not sure if this will work. I can't check the api right now to see what "actual_time" is set to on a planned match that hasn't occured yet
          writeToLog("Match ".$match["match_number"]." started at ".$match["actual_time"], 'matches');
          if(is_null($match["actual_time"]) && empty($match["winning_alliance"])) {
            if(isset($tmatch) && !is_null($tmatch)) {
              if($match["match_number"] < $tmatch["match_number"]){
                $tmatch = $match;
              }
            } else {
              $tmatch = $match;
            }
          }
        }
        writeToLog("Match: ".json_encode($tmatch), 'matches');
        if(!is_null($tmatch) && isset($tmatch)) {
          $alliances = array();
          $index = 0;
          $reqteam = $team;
          foreach($tmatch["alliances"]["blue"]["team_keys"] as $key) {
            $team = str_replace("frc","",$key);
            $alliances[$index] = array("text" => '<https://momentum4999.com/scouting/info.php?team='.$team.'|Team '.$team.'>');
            if($team == $requteam) {
              $alliances[$index]["color"] = '#06ceff';
            } else {
              $alliances[$index]["color"] = '#148be5';
            }
            $index++;
          }
          // RED
          foreach($tmatch["alliances"]["red"]["team_keys"] as $key) {
            $team = str_replace("frc","",$key);
            $alliances[$index] = array("text" => '<https://momentum4999.com/scouting/info.php?team='.$team.'|Team '.$team.'>');
            if($team == $reqteam) {
              $alliances[$index]["color"] = '#ff2200';
            } else {
              $alliances[$index]["color"] = '#d60c0c';
            }
            $index++;
          }
            postToSlack(json_encode(array(
              "response_type"=>"in_channel",
              "text"=>"At ".$nEv["name"].", team ".$reqteam."'s next match, number ".$tmatch["match_number"].", will occur at ".date("g:i A",$tmatch["predicted_time"].'\n Alliances: '),
              "attachments"=> $alliances
            ), JSON_UNESCAPED_SLASHES),$url);
        } else {
          postToSlack(json_encode(array(
            "response_type"=>"ephemeral",
            "text"=>"There don't seem to be any upcoming matches"
          )), $url);
        }
      break;
      case "help":
        postToSlack(json_encode(array(
          "response_type"=>"ephemeral",
          "text"=>"The Blue Alliance interface bot. Connects to the Blue Alliance to retrieve data concerning the FIRST Robotics Competition.\n Usage:",
          "attachments" => array(
              array("text" => "/tba score [team number]\nPrints a team's standing in the most recent match that team has competed in. If team number is not supplied, defaults to 4999."),
              array("text" => "/tba team [team number]\nReturns a link to the scouting app for the given team"),
              array("text" => "/tba match [team number]\nReturns info about the next upcoming match. If no team is specified, defaults to 4999"),
              array("text" => "/tba help\nDisplays this help message")
          )
        ), JSON_UNESCAPED_SLASHES), $url);
        break;
      default:
        postToSlack(json_encode(array('response_type' => 'ephemeral', 'text' => "I'm sorry, but that's not a valid command\n For help, type /tba help"), JSON_UNESCAPED_SLASHES), $url);
        break;
    }

  }
} else {
  echo("You're not supposed to be here.");
}
?>
