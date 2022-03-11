<?php

require_once 'apis.php';
require_once 'settings.php';

interface Query {
    public function handle();
}

abstract class TBAQuery implements Query {
    public static function construct($query) {
        writeToLog("[TBA] Received webhook of type ".$query["message_type"], "queries");
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

        $event = $tba->getEventInfo($match->event_key);

        $message = MessageFactory::getMatchMessage("coming up!",$red_alliance, $blue_alliance, $match, $event->webcasts);
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
        $match = $tba->getMatchInfo($this->data["match"]["key"]);
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
        writeToLog("[Slack] Received slashcommand of type ".$opts[0], "queries");
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
                return new HelpQuery($url, $query["command"]);
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
        $tba = new TBAAPI();
        $slack = new SlackAPI();

        reset(Settings::$subscribedTeams);
        $team = key(Settings::$subscribedTeams);
        if(array_key_exists(1,$this->opts)) {
            $team = $this->opts[1];
        }
        $teamKey = $tba->getTeamKeyForTeam($team);
        if(!$tba->checkValidTeamKey($teamKey)) {
            $message = MessageFactory::getEphemeralMessage($team . " is not a valid team");
        } else {
            $event = $tba->getCurrentTeamEvent($teamKey);
            $event_status = $tba->getTeamEventStatus($teamKey, $event->key);

            $status = rtrim(str_replace(array('<b>','</b>'),"*",$event_status->overall_status_str),'.');

            $message = MessageFactory::getSimpleMessage("At ".$event->name.", ".$status);
        }
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
        if(!array_key_exists(1,$this->opts)) {
            (new InvalidCommandQuery($this->url))->handle();
        } else {
            $tba = new TBAAPI();
            $slack = new SlackAPI();

            $teamkey = $tba->getTeamKeyForTeam($this->opts[1]);
            if(!$tba->checkValidTeamKey($teamkey)) {
                $message = MessageFactory::getEphemeralMessage($this->opts[1]. " is not a valid team");
            } else {

                $teaminfo = $tba->getTeamSimple($teamkey);
                $teamurl = TBAAPI::$tba_base_team_url . $teaminfo->team_number;
                $message = MessageFactory::getEphemeralMessage("<".$teamurl."|".$teaminfo->team_number."> - ".$teaminfo->nickname);
            }
            $slack->postToURL($this->url, $message);
        }
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
        $tba = new TBAAPI();
        $slack = new SlackAPI();

        reset(Settings::$subscribedTeams);
        $team = key(Settings::$subscribedTeams);
        if(array_key_exists(1,$this->opts)) {
            $team = $this->opts[1];
        }
        $teamKey = $tba->getTeamKeyForTeam($team);
        if(!$tba->checkValidTeamKey($teamKey)) {
            $message = MessageFactory::getEphemeralMessage($team." is not a valid team");
        } else {
            $teaminfo = $tba->getTeamSimple($teamKey);
            $teamurl = TBAAPI::$tba_base_team_url . $teaminfo->team_number;
            $event = $tba->getCurrentTeamEvent($teamKey);
            $event_status = $tba->getTeamEventStatus($teamKey, $event->key);

            if($event_status->next_match_key === null) {
                $message = MessageFactory::getSimpleMessage("<".$teamurl."|".$teaminfo->team_number."> - ".$teaminfo->nickname." has no more matches at ".$event->name);
            } else {
                $match = $tba->getMatchInfo($event_status->next_match_key);
                $red_alliance = array();
                foreach($match->red_alliance->team_keys as $team_key) {
                    $red_alliance[] = $tba->getTeamSimple($team_key);
                }
                $blue_alliance = array();
                foreach($match->blue_alliance->team_keys as $team_key) {
                    $blue_alliance[] = $tba->getTeamSimple($team_key);
                }
                $postfix = "at ".$event->name." is up next for <".$teamurl."|".$teaminfo->team_number."> - ".$teaminfo->nickname.", and will occur at ".MessageFactory::getDateString($match->predicted_time);
                $message = MessageFactory::getMatchMessage($postfix, $red_alliance, $blue_alliance, $match, $event->webcasts);
            }
        }

        $slack->postToURL($this->url, $message);
    }
}

class HelpQuery extends SlackQuery {
    private $url;
    private $command;
    public function __construct($url, $command) {
        $this->url = $url;
        $this->command = $command;
    }
    public function handle() {
        $slack = new SlackAPI();
        $message = MessageFactory::getHelpMessage($this->command);
        $slack->postToURL($this->url, $message);
    }
}

class InvalidCommandQuery extends SlackQuery {
    private $url;
    public function __construct($url) {
        $this->url = $url;
    }
    public function handle() {
        $slack = new SlackAPI();
        $message = MessageFactory::getEphemeralMessage("I'm sorry, but that's not a valid command.\nFor help, type `/tba help`");
        $slack->postToURL($this->url, $message);
    }
}

?>
