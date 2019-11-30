# a Telegram Bot to interact with Icinga2 (through its API)

## Before you start:

- the configuration of the script is done in the file `/etc/tg-ici-bot.cfg.inc`
  (location and name can be changed lines 9+10) 
- script tested with PHP 7.3, should only depends on php-curl
- you need a Telegram Bot and its token (this is not covered here) (see `TG_TOKEN`)
- you need an Icinga2 API user that can read `objects/*` and do `actions/*` (this is
  not covered here as well) and the API enabled on you Icinga2 master (see `ICI_URL`)
- you need to define a secret token for this script (it's used to limit the use of
  the script) (see `ICINGA_BOT_TOKEN`)
- the script must be served over HTTPS with a valid certificate (of course, it must
  also be accessible from the Internet).

## enable the bot:

It's as simple as (you defined mySuperToken as `ICINGA_BOT_TOKEN` earlier):

`curl -s 'https://api.telegram.org/botYYYY:ZZZZ/setWebhook?url=https://my.ho.st/some/eventual/path/tg-ici-bot.php?tok=mySuperToken'`

You can check that telegram knows about your webhook/bot:

```% curl -s 'https://api.telegram.org/botYYYY:ZZZZ/getWebhookInfo' | json_pp
{
   "result" : {
      "last_error_message" : "... whatever it is...",
      "max_connections" : 40,
      "pending_update_count" : 0,
      "url" : "https://my.ho.st/some/eventual/path/tg-ici-bot.php?tok=mySuperToken",
      "last_error_date" : 1561716094,
      "has_custom_certificate" : false
   },
   "ok" : true
}
```

## using the bot:

You can simply send the command `/help` to your bot, and he should give you the user
guide.

Some command are sent directly to the bot, others can be sent as reply to a
telegram/icinga notification (see
[tg-icinga2.sh](https://github.com/theflyingbear/sprouter/blob/master/tg-icinga2.sh))

### Commands:

- `/help` : show this message
- `/list hosts` : list all monitored hosts
- `/list svc hostname` : list all services monitored on a host
- acknowledge a problem by either:
  - sending the command: `/ack svc hostname servicename`
  - sending the command: `/ack host hostname`
  - replying to the alert message with `ack svc` or `ack host`
- force a check by either:
  - send the command: `/check svc hostname servicename`
  - send the command: `/check host hostname`
  - replying to the alert message with `check svc` or `check host`
- get the result of the last check, by sending:
  `/result hotname [servicename]`
- get the actual status of a host/service:
  `/status host [servicename]`
- set a host/service in downtime from now for 1 year:
  - `/down host` (will also set a downtime for child host and all services)
  - `/down host service` (will set a downtime for a given service)

