<?php
require_once 'queries.php';
if($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Key: ".file_get_contents("key.txt"));
}
$data = json_decode(file_get_contents('php://input'),true);
$query = TBAQuery::construct($data);
if($query === null) {
    die("Invalid webhook");
} else {
    $query->handle();
}
?>