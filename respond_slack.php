<?php
require_once 'settings.php';
require_once 'queries.php';

function verifySlack() {
    $slack_signing_secret = Settings::$slack_signing_key;
    $headers = getallheaders();
    if(! (isset($headers['X-Slack-Request-Timestamp']) && isset($headers['X-Slack-Signature']))) {
      die("Invalid headers");
    }
    if(abs(time() - $headers['X-Slack-Request-Timestamp']) > 60 * 5) {
      die("Request too old");
    }
    $signature = 'v0:' . $headers['X-Slack-Request-Timestamp'] . ":" . file_get_contents('php://input');
    $signature_hashed = 'v0=' . hash_hmac('sha256', $signature, $slack_signing_secret);
    if(!hash_equals($signature_hashed, $headers['X-Slack-Signature'])) {
        die("Invalid signature");
    }
}

if($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method");
}
verifySlack();
SlackQuery::construct($_POST)->handle();
?>