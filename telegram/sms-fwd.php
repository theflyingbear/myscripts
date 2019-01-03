<?php // forwarded dest is: $_REQUEST['PhoneNumber' or 'To']
include('cal.php'); // gets information about who is oncall/duty from an ICS calendar
header("Content-Type: text/xml; charset=utf-8");
$d = gmdate('N'); // 1:monday .. 7:sunday
$h = gmdate('G'); // hour, without leading 0
$w = gmdate('I') == 0; // winter time
/* if $d is not weekend (Sat/Sun) and $h is >= 8 and $h <= 16
 *   notify everyone
 * else
 *   notify the person on-call
 * endif
 */
if (($d <= 6) && ($h >= 8) && ($h <= 16))
{
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>\n";
	foreach($contacts as $ph)
	{
		echo " <Message to='{$ph}'>fwd message from {$_REQUEST['From']}: {$_REQUEST['Body']}</Message>\n";
	}
	echo "</Response>\n";
}
else
{
	echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
 <Message to='{$cur['phone']}'>from {$_REQUEST['From']}:
	{$_REQUEST['Body']}</Message>
</Response>
XML;
}
?>
