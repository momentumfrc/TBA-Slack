# TBA-Slack
Receives webhooks from the blue alliance about upcoming events and scores, and posts them to slack.
Responds to the following [events](https://www.thebluealliance.com/apidocs/webhooks):
- Upcoming Match Alert
- Match Score Alert

Also responds to the following commands:
- /tba score [team number]
- /tba team [team number]
- /tba match
- /tba help

## Setup
1. Clone the repo into the document root for a webserver that supports PHP execution
2. Make a slack app, add a slash command, and point it at the file slack-slackbot.php
3. In auths.php, paste in your APIv3 key from the blue alliance, the webhook url setup in the bot settings for slack, and the slash command verification key from the bot settings for slack.
4. Add a tba webhook, subscribed to the events listed above, and pointing at the file tba-slackbot.php
5. The most recent verification key sent by the blue alliance can be accessed by navigating to tba-slackbot.php
