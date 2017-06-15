<?php
require 'functions.php';
$slack_token = rtrim(file_get_contents("slack_verification_token.cfg"));
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
        writeToLog('Querying TBA at ' . 'team/frc'.$team.'/events/'.date('Y') .' using key '.$tba_api3_key, 'score');
        $events = queryAPI('/team/frc'.$team.'/events/'.date('Y'),$tba_api3_key);
        $newestEvent = array(0);
        foreach($events as $event){
          $date = DateTime::createFromFormat('Y-m-d',$event["start_date"]);
          writeToLog('Event '.$event["name"]. ' at ' .$event["start_date"] . ' at ' . $date->getTimestamp(),'score');
          if($date->getTimestamp() > $newestEvent[0]) {
            $newestEvent[1] = $event;
            $newestEvent[0] = $date->getTimestamp();
          }
        }
        writeToLog('Querying event '.$newestEvent[1]["key"],"score");
        $teamEvent = queryAPI('/team/frc'.$team.'/event/'.$newestEvent[1]["key"].'/status',$tba_api3_key);
        writeToLog('Status: ' . $teamEvent["overall_status_str"],'score');
        $status = rtrim(str_replace(array('<b>','</b>'),"*",$teamEvent["overall_status_str"]),'.');
        if(isset($status) and isset($newestEvent[1]["name"])) {
          postToSlack('{"response_type": "in_channel", "text":"At '.$newestEvent[1]["name"].', '.$status.'"}', $url);
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
        // Return next upcoming match
      break;
      case "help":
        postToSlack('{"response_type": "ephemeral",
          "text":"The Blue Alliance interface bot. Connects to the Blue Alliance to retrieve data concerning the FIRST Robotics Competition.\n Usage:","attachments":[
            {
              "text" : "/tba score [team number]\nPrints a team\'s standing in the most recent match that team has competed in. If team number is not supplied, defaults to 4999."
            }, {
              "text" : "/tba team [team number]\nReturns a link to the scouting app for the given team"
            }, {
              "text": "/tba help\nDisplays this help message"
            }]}', $url);
        break;
      default:
        postToSlack('{"response_type": "ephemeral", "text":"I\'m sorry, but that\'s not a valid command\n For help, type /tba help"}', $url);
        break;
    }

  }
} else {
  echo("You're not supposed to be here.");
}
?>
