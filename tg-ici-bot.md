# a Telegram Bot to interact with Icinga2 (through its API)

## Before you start:

- the configuration of the script is between lines 1 and 8 (4 `define` to update) 
- script tested with PHP 7.0, should only depends on php-curl
- you need a Telegram Bot and its token (this is not covered here) (see `TG_TOKEN`)
- you need an Icinga2 API user that can read `objects/*` and do `actions/*` (this is
  not covered here as well) and the API enabled on you Icinga2 master (see `ICI_URL`)
- you need to define a secret token for this script (it's used to limit the use of
  the script) (see `ICINGA_BOT_TOKEN`)
- the script must be served over HTTPS with a valid certificate (of course, it must
  also be accessible from the Internet).

## enable the bot:

```curl -s
'https://api.telegram.org/botYYYY:ZZZZ/setWebhook?url=https://my.ho.st/some/eventual/path/tg-ici-bot.php?tok=mySuperToken'```

You can check that telegram knows about your webhook:

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
``

## using the bot:

You can simply send the command `/help` to your bot, and he should give you the user
guide.

- `/help` : show this message
- `/list hosts` : list all monitored hosts
- `/list svc hostname` : list all services monitored on a host
- acknowledge a problem on service by either:
  - sending the command: `/ack svc hostname servicename`
  - replying to the alert message with `ack svc`
- force a service check by either:
  - send the command: `/check svc hostname servicename`
  - replying to the alert message with `check svc`

When using `ack` or `check` if the answer is `200`, the alert has been acknowledged.
Any other value means that something went wrong.
