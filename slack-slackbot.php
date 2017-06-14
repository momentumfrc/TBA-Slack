<?php
require 'functions.php';
$slack_token = rtrim(file_get_contents("slack_verification_token.cfg"));
if($_SERVER["REQUEST_METHOD"] == "POST") {
  if($_POST["token"] == $slack_token) {
    $url = $_POST["response_url"];
    $opts = str_getcsv($_POST["command"], ' ');
    switch($opts[1]) {
      default:
        postToSlack('{"restponse_type": "ephemeral", "text":"I\'m sorry, but that\'s not a valid command"}', $url);
    }

  }
} else {
  echo("You're not supposed to be here.");
}
?>
