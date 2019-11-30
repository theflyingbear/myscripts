<?php // vim:ft=php:

define('TG_URL', 'https://api.telegram.org');

$ICINGA_BOT_TOKEN = 'dummyToken';
$TG_TOKEN = 'botID:BotSecretAuthToken';
$ICI_URL = 'https?://{$user}:{$pass}@{$host}[:{$port}]/v1';

if (file_exists('/etc/tg-ici-bot.cfg.inc'))
    require_once('/etc/tg-ici-bot.cfg.inc');

define('ICINGA_BOT_TOKEN', $ICINGA_BOT_TOKEN);
define('TG_TOKEN', $TG_TOKEN);
define('ICI_URL', $ICI_URL);

## Tools ########################################################################
function cerr($ch)
{
    return curl_strerror( curl_errno($ch) );
}

function logoutput($mesg, $overwrite = FALSE, $f = "/tmp/bot.log")
{
    if (!file_exists($f) or $overwrite)
    {
        $h = fopen($f, "w");
        fwrite($h, "");
        fclose($h);
    }
    error_log('['.date('c').'] '.$mesg, 3, $f);
}

## auth ##
if (empty($_REQUEST['tok']) || !$_REQUEST['tok'])
{
    die('nada');
}
if ($_REQUEST['tok'] !== ICINGA_BOT_TOKEN)
{
    die('nope');
}
if ($_REQUEST['tok'] != ICINGA_BOT_TOKEN)
{
    die('nope');
}



## talking with to telegram users ###############################################
function tgSendMessage($to, $msg, $format = 'Markdown')
{
    $M = urlencode($msg);
    $curl = curl_init(TG_URL . "/bot" . TG_TOKEN . "/sendMessage?chat_id={$to}&parse_mode={$format}&text={$M}");
    if (!$curl)
    {
        return;
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    curl_close($curl);
}

function sendCmdResult($who, $status, $desc, $format = 'Markdown')
{
    $M  = "{$desc}: ";
    if (intval($status) == 200)
    {
        $M .= " ✔";
    }
    else
    {
        $M .= " KO";
    }
    tgSendMessage($who, $M, $format);
}


/**
 * 
 */
function sendIcingaCommand($cmdUri, $data, $method = 'GET', $format = '', $resIsHash = FALSE)
{
    $curl = curl_init(ICI_URL . $cmdUri);
    if (!$curl)
    {
        logoutput("{$method} {$cmdUri} : curl init error -- ". cerr($curl));
        return;
    }
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $curl_xtra_hdr = array();
    $postData = $data;
    if ($format == "json")
    {
        $curl_xtra_hdr[] = 'Content-Type: application/json';
        $curl_xtra_hdr[] = 'Accept: application/json';
        $postData = json_encode($data);
    }
    if ($method == 'POST') {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        $curl_xtra_hdr[] = 'Content-Length: '.strlen($postData);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_xtra_hdr);

    $r = curl_exec($curl);
    if (!$r)
    {
        logoutput("{$method} {$cmdUri} : no data returned -- ". cerr($curl));
        curl_close($curl);
        return;
    }
    curl_close($curl);
    $resData = json_decode($r, $resIsHash);
    if (!$resData)
    {
        logoutput("{$method} {$cmdUri} : data is not JSON?\n". print_r($r, TRUE));
        return;
    }
    return $resData;
}

## talking to icinga  $############################################################
function getIciHosts($who)
{
    $data = sendIcingaCommand('/objects/hosts?attrs=name&attrs=type', NULL, 'GET');
    $M = "monitored hosts:\n";
    foreach($data as $result)
        foreach($result as $h)
            $M .= "- `{$h->name}`\n";
    tgSendMessage($who, $M);
}

function getIciSvc($host, $who)
{
    $post = array(
        'pretty'  => 0,
        'verbose' => 0,
        'attrs'  => array( "name" => "" ),
        'filter' => "service.host_name==\"{$host}\""
    );
    $data = sendIcingaCommand('/objects/services', $post, 'POST', 'json', TRUE);
    $M = "Services monitored on {$host}:\n";
    foreach($data as $result)
        foreach($result as $svc)
            $M .= "- `{$svc['name']}`\n";
    tgSendMessage($who, $M);
}

function ackProblem($host, $who, $svc = 'hostalive')
{
    $post = array(
        'service'      => "{$host}!{$svc}",
        'host'         => $host,
        'pretty'       => 1,
        'sticky'       => 0,
        'persistent'   => 0,
        'verbose'      => 0,
        'notification' => 1,
        'author'       => "{$who}",
        'comment'      => "ACK'd from Telegram");
    $data = sendIcingaCommand("/actions/acknowledge-problem", $post, 'POST', 'json', TRUE);
    sendCmdResult($who, $data['results'][1]['code'], "**ACK** `{$host}!{$svc}`");
}

function ackHostProblem($host, $who)
{
    $post = array(
        'host' => $host,
        'pretty'       => 1,
        'sticky'       => 0,
        'persistent'   => 0,
        'verbose'      => 0,
        'notification' => 1,
        'author'       => "{$who}",
        'comment'      => "ACK'd from Telegram");
    $data = sendIcingaCommand("/actions/acknowledge-problem", $post, 'POST', 'json', TRUE);
    sendCmdResult($who, $data['results'][1]['code'], "**ACK** `{$host}`");
}


function forceCheck($host, $svc, $who)
{
    $post = array(
        'service' => "{$host}!{$svc}",
        'pretty'  => 1,
        'force'   => 1);
    $data = sendIcingaCommand('/actions/reschedule-check', $post, 'POST', 'json');
    sendCmdResult($who, $data->results[0]->code, "**CHECK** `{$host}!{$svC}`");
}

function forceHostCheck($host, $who)
{
    $post = array(
        'host'   => $host,
        'pretty' => 1,
        'force'  => 1);
    $data = sendIcingaCommand('/actions/reschedule-check', $post, 'POST', 'json');
    sendCmdResult($who, $data->results[0]->code, "**CHECK** `{$host}`");
}

function getCheckResult($host, $svc, $who)
{ // if the check is successfull but without output, it can look weird
    $data = NULL;
    if (!$svc)
        $data = sendIcingacommand("/objects/hosts/{$host}", NULL, 'GET', '', TRUE);
    else
        $data = sendIcingacommand("/objects/services/{$host}!{$svc}", NULL, 'GET', '', TRUE);
    $M = "last check result for {$host} {$svc} - \u{1F6A7}:".
        "<pre>{$data['results'][0]['attrs']['last_check_result']['output']}</pre>";
    tgSendMessage($who, $M, 'HTML');
}

function getStatus($host, $svc, $who)
{
    $data = NULL;
    if (!$svc)
        $data = sendIcingacommand("/objects/hosts/{$host}", NULL, 'GET', '', TRUE);
    else
        $data = sendIcingacommand("/objects/service/{$host}!{$svc}", NULL, 'GET', '', TRUE);

    $x = $data['results'][0]['attrs'];
    $y = array();
    $y['last'] = date('Y-m-d H:i:s T', intval($x['last_check']));
    $y['type'] = intval($x['state_type']) == 1? "hard": "soft";
    $y['ack'] = "undef";
    if (intval($x['acknowledgement']) == 0)
        $y['ack'] = "none";
    elseif (intval($x['acknowledgement']) == 1)
        $y['ack'] = "normal";
    elseif (intval($x['acknowledgement']) == 2)
        $y['ack'] = "sticky";
    else
        $y['ack'] = "wtf";
    $y['status'] = "undef";
    if (!$svc)
        $y['status'] = intval($x['state']) == 0? "UP": "down";
    else
        if (intval($x['state']) == 0)
            $y['status'] = "OK";
        elseif(intval($x['state']) == 1)
            $y['status'] = "Warning";
        elseif(intval($x['state']) == 2)
            $y['status'] = "Critical";
        elseif(intval($x['state']) == 3)
            $y['status'] = "unknown";
    $y['indt'] = intval($x['downtime_depth']) == 0? "no": "yes";
    $y['perf'] = join("\n    ", $x['last_check_result']['performance_data']);
    $M = <<<TXT
<pre>
status for $host $svc:
- last_check:
  - on: {$y['last']}
  - from: {$x['last_check_result']['check_source']}
  - output: {$x['last_check_result']['output']}
  - perf data:
    {$y['perf']}
- {$y['status']} - {$y['type']}
- acknowledgement: {$y['ack']}
- in downtime: {$y['indt']}
</pre>
TXT;
    tgSendMessage($who, $M, 'HTML');
}


function setDowntime($host, $svc, $who)
{
    $start = date('U'); // now in seconds
    $end = $start + 365*24*3600; // downtime is set for a year
    $post = array(
        'author'     => $who,
        'comment'    => "Downtime set from Telegram",
        'start_time' => $start,
        'end_time'   => $end,
        'pretty'     => 1,
        'verbose'    => 1);
    if (!$svc) {
        $post['all_services'] = 1;
        $post['child_options'] = "DowntimeTriggeredChildren";
        $post['host'] = "$host";
    } else {
        $post['type'] = "Service";
        $post['filter'] = "service.name==\"$svc\" && host.name==\"$host\"";
    }
    $data = sendIcingaCommand('/actions/schedule-downtime', $post, 'POST', 'json');
    $M  = "**Downtime** `{$host}";
    if ($svc)
        $M .= "!{$svc}";
    $M .= '`';
    sendCmdResult($who, $data->results[0]->code, $M);
}
#################################################################################

$update = json_decode(file_get_contents('php://input') ,true); # gets the data sent to the bot


if (isset($update['edited_message'])) {
    logoutput("edited message#{$update['edited_message']['message_id']}\n");
}
else {
    $chat_id = $update['message']['chat']['id']; // Telegram chat id
    $name = $update['message']['from']['first_name']; // Telegram user who sent the data
    if(isset($update['message']['reply_to_message']))
    { // maybe an ack or a check
        $src = $update['message']['reply_to_message']['text'];
        $l = explode('\n', $src)[0];
        $t = explode(' ', $l);
        $h = $t[0]; $s = trim($t[2], ':');
        $cmd = $update['message']['text'];
        if (preg_match("/^ack svc(.*)$/i", $cmd, $m))
        {
            ackProblem($h, $chat_id, $s);
        }
        elseif (preg_match("/^ack host$/i", $cmd))
        {
            ackHostProblem($h, $chat_id);
        }
        elseif (preg_match("/^check svc$/i", $cmd))
        {
            forceCheck($h, $s, $chat_id);
        }
        elseif (preg_match("/^check host (.*)$/i", $cmd, $m))
        { // doesn't make sens here
            forceHostCheck($h, $chat_id);
        }

        else
        {
            logoutput("update#{$update['message']['message_id']} from {$name} is just noise.\n");
        }
    }
    elseif ($update['message']['text'][0] != "/")
    { // not a command - ignore
        logoutput("update#{$update['message']['message_id']} from {$name} is not a command.\n");
    }
    else
    {
        if (preg_match("/^\/help(.*)$/", $update['message']['text'], $m))
        { // /help command
            $message = <<<MD
I know how to do that:
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
- get the result of the last check, by sending either:
  - `/result hostname`
  - `/result hotname servicename`
- get the actual status of a host/service:
  - `/status host [servicename]`
- set a host/service in downtime from now for 1 year:
  - `/down host` (will also set a downtime for child host and all services)
  - `/down host service` (will set a downtime for a given service)
MD;
            tgSendMessage($chat_id, $message);
        }
        elseif (preg_match("/^\/ack host (.+)$/", $update['message']['text'], $m))
        { // ack host pb
            ackHostProblem($m[1], $chat_id);
        }
        elseif (preg_match("/^\/list hosts.*$/", $update['message']['text']))
        { // list hosts
            getIciHosts($chat_id);
        }
        elseif (preg_match("/^\/list svc ([^ ]+)$/", $update['message']['text'], $m))
        { // list services for host
            getIciSvc($m[1], $chat_id);
        }
        elseif (preg_match("/^\/check svc ([^ ]+) ([^ ]+)$/", $update['message']['text'], $m))
        { // check service on host
            forceCheck($m[1], $m[2], $chat_id);
        }
        elseif (preg_match("/^\/check host ([^ ]+)$/", $update['message']['text'], $m))
        { // check host
            forceHostCheck($m[1], $chat_id);
        }
        elseif (preg_match("/^\/ack svc ([^ ]+) ([^ ]+)/", $update['message']['text'], $m))
        { // ack service pb on host
            ackProblem($m[1], $chat_id, $m[2]);
        }
        elseif (preg_match("/^\/result ([^ ]+)( ([^ ])+)?/", $update['message']['text'], $m))
        { // get the result of the last check
            if (!isset($m[2]))
                getCheckResult(trim($m[1]), '', $chat_id);
            else
                getCheckResult(trim($m[1]), trim($m[2]), $chat_id);
        }
        elseif (preg_match("/^\/status ([^ ]+)( ([^ ])+)?/", $update['message']['text'], $m))
        { // get the status of a host/service
            if (!isset($m[2]))
                getStatus(trim($m[1]), '', $chat_id);
            else
                getStatus(trim($m[1]), trim($m[2]), $chat_id);
        }
        elseif (preg_match("/^\/down ([^ ]+)( [^ ]+)?/", $update['message']['text'], $m))
        { // put a host/service in downtime
            if (!isset($m[2]))
                setDowntime(trim($m[1]), '', $chat_id);
            else
                setDowntime(trim($m[1]), trim($m[2]), $chat_id);
        }
        elseif (preg_match("/^(\/[^ ]+).*$/", $update['message']['text'], $m))
        { // unknow command
            tgSendMessage($chat_id, "unknown command '`{$update['message']['text']}`'");
        }
    }
}

?>
