<?
/* setcfg is used to move the validated session data to the configuration database.
 * The variable, 'SETCFG_prefix',  will indicate the path of the session data. */
include "/htdocs/phplib/xnode.php";
$infp = XNODE_getpathbytarget("", "inf", "uid", "LAN-1", 0);
if ($infp!="")
{
	$cnt = query($SETCFG_prefix."/inf/upnp/count");
	$entry1 = query($SETCFG_prefix."/inf/upnp/entry:1");
	$entry2 = query($SETCFG_prefix."/inf/upnp/entry:2");
	set($infp."/upnp/count", $cnt);
	set($infp."/upnp/entry:1", $entry1);
	set($infp."/upnp/entry:2", $entry2);
}
?>
