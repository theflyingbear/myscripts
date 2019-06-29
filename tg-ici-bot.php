<?php
define('ICINGA_BOT_TOKEN', 'XXXX');

define('TG_URL', 'https://api.telegram.org');
define('TG_TOKEN', 'YYYY:ZZZZ'); // botID : botSecretAuthToken

define('ICI_URL', 'https://${APIuser}:${APIpass}@{Icinga2APIendpoint}:${IcingaPort}/v1');

if (!defined($_REQUEST['tok']) && !$_REQUEST['tok'])
{
    die('nothing for you here');
}

if ($_REQUEST['tok'] !== ICINGA_BOT_TOKEN)
{
    die('nope!');
}


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
    error_log($mesg, 3, $f);
}

## talking with to telegram users ###############################################
function tgSendMessage($to, $msg, $format = 'Markdown')
{
    $M = urlencode($msg); // usefull?
    $curl = curl_init(TG_URL . "/bot" . TG_TOKEN . "/sendMessage?chat_id={$to}&parse_mode={$format}&text={$M}");
    if (!$curl)
    {
        return;
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    curl_close($curl);
}

## geters #######################################################################
function getIciHosts($who)
{
    $curl = curl_init(ICI_URL . '/objects/hosts?attrs=name&attrs=type');
    if (!$curl)
    {
        tgSendMessage($who, "curl init error / " . cerr($curl));
        return;
    }
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    $r = curl_exec($curl);
    if (!$r)
    {
        tgSendMessage($who, "no data / " . cerr($curl));
        curl_close($curl);
        return;
    }
    curl_close($curl);
    $data = json_decode($r);
    if (!$data)
    {
        tgSendMessage($who, "data is not JSON?");
        return;
    }
    $M = "";
    foreach($data as $result)
        foreach($result as $h)
            $M .= "- `{$h->name}`\n";
    tgSendMessage($who, $M);
}

function getIciSvc($host, $who)
{
    $curl = curl_init(ICI_URL . '/objects/services');
    if (!$curl)
    {
        tgSendMessage($who, "curl init error / " . cerr($curl));
        return;
    }
    $post  = "{ \"filter\" : \"match(pattern, service.host_name)\", \"filter_vars\": { \"pattern\" : \"{$host}*\" },";
    $post .= "  \"pretty\" : \"true\", \"attrs\" : [ \"name\" ] }";
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER,
        array('X-HTTP-Method-Override: GET') );
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

    $r = curl_exec($curl); // raw json in there
    if (!$r)
    {
        tgSendMessage($who, "no data / " . cerr($curl));
        curl_close($curl);
        return;
    }
    curl_close($curl);
    $data = json_decode($r);
    if (!$data)
    {
        tgSendMessage($who, "data is not JSON?");
        return;
    }
    $M = "Services monitored on {$host}:\n";
    foreach($data as $result)
        foreach($result as $svc)
            $M .= "- `{$svc->name}`\n";
    tgSendMessage($who, $M);
}

function ackProblem($host, $who, $svc = 'hostalive')
{
    $curl = curl_init(ICI_URL . '/actions/acknowledge-problem');
    if (!$curl)
    {
        tgSendMessage($who, "curl init error / " . cerr($curl));
        return;
    }
    $post  = "{ \"service\" : \"{$host}!{$svc}\",";
    $post .= "  \"pretty\" : \"true\", \"sticky\" : false, \"persistent\": false, \"verbose\" : false, \"notification\": true,";
    $post .= "  \"author\" : \"{$who}\", \"comment\": \"ACK'd from Telegram\" }";
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Accept: application/json",
        "Content-Type: application/json", 
        "Content-Length: " . strlen($post)
    ));
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

    $r = curl_exec($curl); // raw json in there
    if (!$r)
    {
        tgSendMessage($who, "no answer/ " . cerr($curl));
        curl_close($curl);
        return;
    }
    curl_close($curl);
    $data = json_decode($r);
    if (!$data)
    {
        tgSendMessage($who, "answer is not JSON?\n" . print_r($r, TRUE));
        return;
    }
    $M  = "**ACK** `{$host}!{$svc}`: {$data->results[0]->code}\n";
    tgSendMessage($who, $M);
}

function forceCheck($host, $svc, $who)
{
    $curl = curl_init(ICI_URL . '/actions/reschedule-check');
    if (!$curl)
    {
        tgSendMessage($who, "curl init error / " . cerr($curl));
        return;
    }
    $post  = "{ \"service\" : \"{$host}!{$svc}\",";
    $post .= "  \"pretty\" : \"true\", \"force\" : true }";
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Accept: application/json",
        "Content-Type: application/json", 
        "Content-Length: " . strlen($post)
    ));
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

    $r = curl_exec($curl); // raw json in there
    if (!$r)
    {
        tgSendMessage($who, "no answer/ " . cerr($curl));
        curl_close($curl);
        return;
    }
    curl_close($curl);
    $data = json_decode($r);
    if (!$data)
    {
        tgSendMessage($who, "answer is not JSON?\n" . print_r($r, TRUE));
        return;
    }
    $M  = "**CHECK** `{$host}!{$svc}`: {$data->results[0]->code}\n";
    tgSendMessage($who, $M);
}

#################################################################################

$update = json_decode(file_get_contents('php://input') ,true); # gets the data sent to the bot

$debug0 = "# " . date("Y-m-d H:i:s T") . " update:\n";

if (isset($update['edited_message'])) { // ignored
    logoutput($debug0 . "edited message {$update['edited_message']['message_id']}\n");
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
        elseif (preg_match("/^check svc$/i", $cmd))
        {
            forceCheck($h, $s, $chat_id);
        }
        else
        {
            logoutput($debug0 . "{$update['message']['message_id']} from {$name} is just noise.\n");
        }
    }
    elseif ($update['message']['text'][0] != "/")
    { // not a command - ignore
        logoutput($debug0 . "{$update['message']['message_id']} from {$name} is not a command.\n");
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
- acknowledge a problem on service by either:
  - sending the command: `/ack svc hostname servicename`
  - replying to the alert message with `ack svc`
- force a service check by either:
  - send the command: `/check svc hostname servicename`
  - replying to the alert message with `check svc`

Maybe one day I will be able to:
- acknowledge a problem on a host
- force a host check
MD;
		    tgSendMessage($chat_id, $message);
		}
		elseif (preg_match("/^\/ack host (.+)$/", $update['message']['text'], $m))
		{ // /ack command
		    tgSendMessage($chat_id, "I don't know how to acknowledge the problem on `{$m[1]}`");
		}
		elseif (preg_match("/^\/list hosts.*$/", $update['message']['text']))
		{ // /list cmd
		    getIciHosts($chat_id);
		}
		elseif (preg_match("/^\/list svc ([^ ]+)$/", $update['message']['text'], $m))
		{ // /list cmd
		    getIciSvc($m[1], $chat_id);
		}
		elseif (preg_match("/^\/check svc ([^ ]+) ([^ ]+)$/", $update['message']['text'], $m))
		{ // /check cmd
		    forceCheck($m[1], $m[2], $chat_id);
		}
		elseif (preg_match("/^\/ack svc ([^ ]+) ([^ ]+)/", $update['message']['text'], $m))
		{ // /ack command
		    ackProblem($m[1], $chat_id, $m[2]);
		}
		elseif (preg_match("/^(\/[^ ]+).*$/", $update['message']['text'], $m))
		{
		    tgSendMessage($chat_id, "unknown command `{$m[1]}`");
		}
    }
}

?>
