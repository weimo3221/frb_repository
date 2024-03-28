<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/dumplog.php";
include "/htdocs/phplib/trace.php";

/* Check <sys/syslog.h> & <elbox/include/asyslog.h> for the definition of facility. */

if (query("/device/log/email/to")!="" && query("/device/log/email/from")!="" &&	query("/device/log/email/smtp/server")!="")
{
	$archive = "/runtime/logfull";
}
else
{
	$archive = "";
}

if ($FACILITY==26)	// attack
{
	$base	= "/runtime/log/attack";
	$type	= "attack";
	$max	= 50;
}
else if ($FACILITY==27)	// drop
{
	$base	= "/runtime/log/drop";
	$type	= "drop";
	$max	= 50;
}
else
{
	$base	= "/runtime/log/sysact";
	//if (query("/device/log/email/to")!="")	$archive = "/runtime/logarchive";
	//else									$archive = "";
	$type	= "sysact";
	$max	= 400;
}

$cnt = query($base."/entry#");
if ($cnt=="") $cnt=0;
if ($cnt >= $max)
{
	if ($archive != "")
	{
		set($archive."/type", $type);
		$archive = $archive."/".$type;	
		del($archive);
		set($archive, "");
		movc($base, $archive);
		event("LOGFULL");
	}
	else
	{
		while ($cnt >= $max)
		{
			$cnt--;
			del($base."/entry:1");
		}
	}
}
$cnt = query($base."/entry#");
if ($cnt=="") $cnt=0;
$cnt++;
set($base."/entry:".$cnt."/time", $TIME);
set($base."/entry:".$cnt."/message", $TEXT);
?>
