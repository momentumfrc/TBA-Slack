<?php

interface Query {
    public function handle();
}

abstract class TBAQuery implements Query {
    public static function construct($query) {
        $data = json_decode(file_get_contents('php://input'),true);
        switch($data["message_type"]) {
            case "verification":
                return new VerificationQuery($data["message_data"]);
            case "upcoming_match":
                return new UpcomingMatchQuery($data["message_data"]);
            case "match_score":
                return new MatchScoreQuery($data["message_data"]);
            default:
                return null;
        }
    }
}

class VerificationQuery extends TBAQuery {
    public function __construct($data) {
        $this->data = $data;
    }
    public function handle() {
        file_put_contents("key.txt",$this->data["verification_key"]);
    }
}

class UpcomingMatchQuery extends TBAQuery {
    public function __construct($data) {
        $this->data = $data;
    }
    public function handle() {
        // TODO
    }
}

?>