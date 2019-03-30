<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', TRUE);

class Settings {

    public static $subscribedTeams = array("4999"=>"Momentum","7042"=>"Rabbotics");

    public static $webhooks = array("https://hooks.slack.com/services/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");

    public static $slack_signing_key = "473d60dc0176f81df0e5c8a1956d6e51";

    public static $tbaKey = "dlr3RuZvgMgsZI8B2ujFCSqJz3ZIbhZVnT9BiAHx5aramoicOm4VPi9A2TRFe1PJ ";

    public static $imageURL = "https://momentum4999.com/slack-bots/BlueAllianceBot/images/";

}

?>