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

function stopTimeout() {
  ignore_user_abort(true);
  ob_end_clean();
  ob_start();
  header('Connection: close');
  header('Content-Length: '.ob_get_length());
  http_response_code(200);
  ob_end_flush();
  ob_flush();
  flush();
}

if($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method");
}
stopTimeout();
verifySlack();
SlackQuery::construct($_POST)->handle();
?>