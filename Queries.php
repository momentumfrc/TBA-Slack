<?php

require 'apis.php';
require 'settings.php';

interface Query {
    public function handle();
}

abstract class TBAQuery implements Query {
    public static function construct($query) {
        switch($query["message_type"]) {
            case "verification":
                return new VerificationQuery($query["message_data"]);
            case "upcoming_match":
                return new UpcomingMatchQuery($query["message_data"]);
            case "match_score":
                return new MatchScoreQuery($query["message_data"]);
            default:
                return null;
        }
    }
}

class VerificationQuery extends TBAQuery {
    private $data;
    public function __construct($data) {
        $this->data = $data;
    }
    public function handle() {
        file_put_contents("key.txt",$this->data["verification_key"]);
    }
}

class UpcomingMatchQuery extends TBAQuery {
    private $data;
    public function __construct($data) {
        $this->data = $data;
    }
    public function handle() {
        $tba = new TBAAPI();
        $slack = new SlackAPI();

        $match = $tba->getMatchInfo($this->data["match_key"]);
        $red_alliance = array();
        foreach($match->red_alliance->team_keys as $team_key) {
            $red_alliance[] = $tba->getTeamSimple($team_key);
        }
        $blue_alliance = array();
        foreach($match->blue_alliance->team_keys as $team_key) {
            $blue_alliance[] = $tba->getTeamSimple($team_key);
        }

        $message = MessageFactory::getMatchStartMessage($red_alliance, $blue_alliance, $match);
        $slack->postToWebhooks($message);
    }
}

class MatchScoreQuery extends TBAQuery {
    private $data;
    public function __construct($data) {
        $this->data = $data;
    }
    public function handle() {
        $tba = new TBAAPI();
        $slack = new SlackAPI();

        $match = $tba->getMatchInfo($this->data["match_key"]);
        $red_alliance = array();
        foreach($match->red_alliance->team_keys as $team_key) {
            $red_alliance[] = $tba->getTeamSimple($team_key);
        }
        $blue_alliance = array();
        foreach($match->blue_alliance->team_keys as $team_key) {
            $blue_alliance[] = $tba->getTeamSimple($team_key);
        }

        $message = MessageFactory::getMatchFinishedMessage($red_alliance, $blue_alliance, $match);
        $slack->postToWebhooks($message);
    }
}

abstract class SlackQuery implements Query {
    public static function construct($query) {
        $url = $query["response_url"];
        $opts = str_getcsv($query["text"], ' ');
        switch($opts[0]) {
            case "score":
                return new TeamScoreQuery($url, $opts);
                break;
            case "team":
                return new TeamInfoQuery($url, $opts);
                break;
            case "match":
                return new NextMatchQuery($url, $opts);
                break;
            case "help":
                return new HelpQuery($url);
                break;
            default:
                return new InvalidCommandQuery($url);
                break;
        }
    }
}

class TeamScoreQuery extends SlackQuery {
    private $url;
    private $opts;
    public function __construct($url, $opts) {
        $this->url = $url;
        $this->opts = $opts;
    }
    public function handle() {
        $tba = new $TBAAPI();
        $slack = new $SlackAPI();

        $team = array_key_first(Settings::subscribedTeams);
        if(array_key_exists(1,$this->opts)) {
            $team = $this->opts[1];
        }
        $teamKey = $tba->getTeamKeyForTeam($team);
        $event = $tba->getCurrentTeamEvent($teamKey);
        $event_status = $tba->getTeamEventStatus($teamKey, $event->key);

        $status = rtrim(str_replace(array('<b>','</b>'),"*",$event_status->overall_status_str),'.');

        $message = MessageFactory::getSimpleMessage("At ".$event->name.", ".$status);
        $slack->postToURL($this->url, $message);
    }
}

class TeamInfoQuery extends SlackQuery {
    private $opts;
    public function __construct($url, $opts) {
        $this->url = $url;
        $this->opts = $opts;
    }
    public function handle() {
        // TODO
    }
}

class NextMatchQuery extends SlackQuery {
    private $url;
    private $opts;
    public function __construct($url, $opts) {
        $this->url = $url;
        $this->opts = $opts;
    }
    public function handle() {
        // TODO
    }
}

class HelpQuery extends SlackQuery {
    private $url;
    public function __construct($url) {
        $this->url = $url;
    }
    public function handle() {
        // TODO
    }
}

class InvalidCommandQuery extends SlackQuery {
    private $url;
    public function __construct($url) {
        $this->url = $url;
    }
    public function handle() {
        // TODO
    }
}

?>