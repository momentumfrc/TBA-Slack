<?php

abstract class API {
    protected function getURL($url, $getdata, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $fullurl = $url;
        if(count($getdata) > 0) {
            $fullurl .= "?" . http_build_query($getdata);
        }        
        curl_setopt($ch, CURLOPT_URL, $fullurl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    protected function postURL($url, $postdata, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

class TBAAPI {
    private $baseURL = "www.thebluealliance.com/api/v3";
    private function getHeader() {
        return array( 'X-TBA-Auth-Key: '.Settings::$tbaKey);
    }

    function getTeamKeyForTeam($teamNumber) {
        return "frc" . $teamNumber;
    }

    function getTeamSimple($teamKey) {
        $url = $this->baseURL.'/team/'.$teamKey.'/simple';
        $teaminfo = json_decode(getURL($url, array(), $this->getHeader()), true);
        return new TeamSimple($teaminfo);
    }

    function getTeam($teamKey) {
        $url = $this->baseURL.'/team/'.$teamKey;
        $teaminfo = json_decode(getURL($url, array(), $this->getHeader()), true);
        return new Team($teaminfo);
    }

    function getTeamEventsSimple($teamKey) {
        $url = $this->baseURL.'/team/'.$teamKey.'/events/simple';
        $eventinfos = json_decode(getURL($url, array(), $this->getHeader()), true);
        $out = array();
        foreach($eventinfos as $eventinfo) {
            $out[] = new EventSimple($eventinfo);
        }
        return $out;
    }

    function getCurrentTeamEvent($teamKey) {
        $events = getTeamEventsSimple($teamEvent);
        $mostRecentEvent = $events[0];
        $mostRecentEventTime = abs($mostRecentEvent->start_date->getTimestamp() - time());
        foreach($events as $event) {
            if( ( abs($event->start_date->getTimestamp() - time()) < $mostRecentEventTime ) && ( $event->start_date->getTimestamp() - time() < 0 ) ) {
                $mostRecentEvent = $event;
                $mostRecentEventTime = abs($event->start_date->getTimestamp() - time());
            }
        }

        $url = $this->baseURL.'/event/'.$mostRecentEvent->key;
        $eventinfo = json_decode(getURL($url, array(), $this->getHeader()), true);
        return new Event($eventinfo);
    }

    function getTeamEventStatus($teamKey, $eventKey) {
        $url = $this->baseURL.'/team/'.$teamKey.'/event/'.$eventKey.'/status';
        $statusinfo = json_decode(getURL($url, array(), $this->getHeader()), true);
        return new EventStatus($status);
    }

    function getMatchInfo($matchKey) {
        $url = $this->baseURL.'/match/'.$matchKey;
        $matchinfo = json_decode(getURL($url, array(), $this->getHeader()), true);
        return new MatchInfo($matchinfo);
    }



}

class TeamSimple {
    function __construct($jsoninfo) {
        $this->key          = $jsoninfo["key"];
        $this->team_number  = $jsoninfo["team_number"];
        $this->nickname     = $jsoninfo["nickname"];
        $this->name         = $jsoninfo["name"];
        $this->city         = $jsoninfo["city"];
        $this->state_prov   = $jsoninfo["state_prov"];
        $this->country      = $jsoninfo["country"];
    }
}

class Team {
    function __construct($jsoninfo) {
        $this->key          = $jsoninfo["key"];
        $this->team_number  = $jsoninfo["team_number"];
        $this->nickname     = $jsoninfo["nickname"];
        $this->name         = $jsoninfo["name"];
        $this->city         = $jsoninfo["city"];
        $this->state_prov   = $jsoninfo["state_prov"];
        $this->country      = $jsoninfo["country"];
        $this->address      = $jsoninfo["address"];
        $this->postal_code  = $jsoninfo["postal_code"];
        $this->website      = $jsoninfo["website"];
        $this->rookie_year  = $jsoninfo["rookie_year"];
        $this->motto        = $jsoninfo["motto"];
    }
}

class EventSimple {
    function __construct($jsoninfo) {
        $this->key          = $jsoninfo["key"];
        $this->name         = $jsoninfo["name"];
        $this->event_code   = $jsoninfo["event_code"];
        $this->event_type   = $jsoninfo["event_type"];
        $this->city         = $jsoninfo["city"];
        $this->state_prov   = $jsoninfo["state_prov"];
        $this->country      = $jsoninfo["country"];
        $this->start_date   = DateTime::createFromFormat("Y-m-d", $jsoninfo["start_date"]);
        $this->end_date     = DateTime::createFromFormat("Y-m-d", $jsoninfo["end_date"]);
        $this->year         = $jsoninfo["year"];
    }
}

class Event {
    function __construct($jsoninfo) {
        $this->key                  = $jsoninfo["key"];
        $this->name                 = $jsoninfo["name"];
        $this->event_code           = $jsoninfo["event_code"];
        $this->event_type           = $jsoninfo["event_type"];
        $this->city                 = $jsoninfo["city"];
        $this->state_prov           = $jsoninfo["state_prov"];
        $this->country              = $jsoninfo["country"];
        $this->start_date           = DateTime::createFromFormat("Y-m-d", $jsoninfo["start_date"]);
        $this->end_date             = DateTime::createFromFormat("Y-m-d", $jsoninfo["end_date"]);
        $this->year                 = $jsoninfo["year"];
        $this->short_name           = $jsoninfo["short_name"];
        $this->event_type_string    = $jsoninfo["event_type_string"];
        $this->week                 = $jsoninfo["week"];
        $this->address              = $jsoninfo["address"];
        $this->postal_code          = $jsoninfo["postal_code"];
        $this->gmaps_place_id       = $jsoninfo["gmaps_place_id"];
        $this->gmaps_url            = $gmap_url["gmaps_url"];
        $this->lat                  = $jsoninfo["lat"];
        $this->lng                  = $jsoninfo["lng"];
        $this->location_name        = $jsoninfo["location_name"];
        $this->timezone             = $jsoninfo["timezone"];
        $this->website              = $jsoninfo["website"];
        $this->first_event_id       = $jsoninfo["first_event_id"];
        $this->first_event_code     = $jsoninfo["first_event_code"];
        $this->webcasts             = array();
        $this->division_keys        = $jsoninfo["division_keys"];
        $this->parent_event_key     = $jsoninfo["parent_event_key"];
        $this->playoff_type         = $jsoninfo["playoff_type"];
        $this->playoff_type_string  = $jsoninfo["playoff_type_string"];

        foreach($jsoninfo["webcasts"] as $webcast) {
            $this->webcasts[] = new Webcast($webcast);
        }

    }
}

class Webcast {
    function __construct($jsoninfo) {
        $this->type     = $jsoninfo["type"];
        $this->channel  = $jsoninfo["channel"];
        $this->file     = $jsoninfo["file"];
    }
}

class MatchInfo {
    function __construct($jsoninfo) {
        $this->key              = $jsoninfo["key"];
        $this->comp_level       = $jsoninfo["comp_level"];
        $this->set_number       = $jsoninfo["match_number"];
        $this->red_alliance     = new MatchAlliance($jsoninfo["red"]);
        $this->blue_alliance    = new MatchAlliance($jsoninfo["blue"]);
        $this->winning_alliance = $jsoninfo["winning_alliance"];
        $this->event_key        = $jsoninfo["event_key"];
        $this->time             = $jsoninfo["time"];
        $this->actual_time      = $jsoninfo["actual_time"];
        $this->predicted_time   = $jsoninfo["predicted_time"];
        $this->post_result_time = $jsoninfo["post_result_time"];
        $this->score_breakdown  = $jsoninfo["score_breakdown"];
        $this->videos           = $jsoninfo["videos"];
    }
}

class MatchAlliance {
    function __construct($jsoninfo) {
        $this->score                = $jsoninfo["score"];
        $this->team_keys            = $jsoninfo["team_keys"];
        $this->surrogate_team_keys  = $jsoninfo["surrogate_team_keys"];
        $this->dq_team_keys         = $jsoninfo["dq_team_keys"];
    }
}

?>