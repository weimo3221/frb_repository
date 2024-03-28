<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";

function startcmd($text)	{fwrite(a,$_GLOBALS["START"],$text);}
function stopcmd($text)		{fwrite(a,$_GLOBALS["STOP"], $text);}


function infsvcs_error($errno)
{
	startcmd("exit ".$errno."\n");
	stopcmd( "exit ".$errno."\n");
}

function infsvcs_setup($name)
{
	$infp = XNODE_getpathbytarget("", "inf", "uid", $name, 0);
	$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $name, 0);
	if ($infp=="" || $stsp=="")
	{
		SHELL_info($_GLOBALS["START"], "infsvcs_setup: (".$name.") not exist.");
		SHELL_info($_GLOBALS["STOP"],  "infsvcs_setup: (".$name.") not exist.");
		infsvcs_error("9");
		return;
	}

	$addrtype = query($stsp."/inet/addrtype");

	anchor($infp);

	$web	= query("web");
	//$hnap	= query("hnap");
	$upnp	= query("upnp/count");
	$dhcps4	= query("dhcps4");
	$dhcps6	= query("dhcps6");
	$dhcpc6	= query("dhcpc6");//for RA
	$ddns4	= query("ddns4");
	$ddns6	= query("ddns6");
	$dns4	= query("dns4");
	$dns6	= query("dns6");
	$defrt	= query("defaultroute");
	$mode   = query($infp."/inf6to4/mode");
	//$inf6to4 = query($infp."/inf6to4/inf");
	$bwc 	= query("bwc");
	$sshd   = query("sshd"); //sandy add
	$neap   = query("neap"); //sam_pan add
	$nameresolve   = query("nameresolve"); //sam_pan add
    $next   = query("infnext");

	//if (""!=$next)		startcmd("service INET."	.$next." start\n");
	if (""!=$next && ""==$mode)		startcmd("service INET."	.$next." start\n");
	if (""!=$web)		startcmd("service HTTP."	.$name." start\n");
	//if ($hnap==1)		startcmd("service HNAP."	.$name." start\n");
	if ($upnp>0)		startcmd("service UPNP."	.$name." start\n");
	if ($neap>0)        startcmd("service NEAP."    .$name." start\n"); //sam_pan add
	if ($nameresolve>0) startcmd("service NAMERESOLV."    .$name." start\n");
	if (""!=$dhcpc6)	startcmd("service DHCPC6."	.$name." start\n");

	if ($addrtype=="ipv4" || $addrtype=="ppp4")
	{
		if (""!=$dhcps4)	startcmd("service DHCPS4."	.$name." start\n");
		if (""!=$ddns4)		startcmd("service DDNS4."	.$name." start\n");
		if (""!=$dns4)		startcmd("service DNS4."	.$name." start\n");
		if (""!=$sshd)    startcmd("service SSH4." .$name." start\n"); //sandy add
	}
	else if ($addrtype=="ipv6" || $addrtype=="ppp6")
	{
		//if (""!=$dhcps6)	startcmd("service DHCPS6."	.$name." start\n");
		if (""!=$dhcps6)	startcmd("service DHCPS6."	.$name." restart\n");//rbj
		if (""!=$ddns6)		startcmd("service DDNS6."	.$name." start\n");
		if (""!=$dns6)		startcmd("service DNS6."	.$name." start\n");
		if (""!=$sshd)    startcmd("service SSH6." .$name." start\n"); //sandy add
	}

	if (""!=$mode)
	{
		startcmd("service 6TO4."	.$name." start\n");
		startcmd("service IPT."	.$name." restart\n");
	}
	if ($bwc!="")	startcmd("service BWC.".$name." restart\n");

	/*
	if ($defrt=="1")
	{
		startcmd("event INET.CONNECTED\n");
		stopcmd("event INET.DISCONNECTED\n");
	}
	*/
	startcmd("event ".$name.".CONNECTED\n");
	stopcmd("event ".$name.".DISCONNECTED\n");

	/* Stop services .................................................. */
	if ($bwc!="")	stopcmd("service BWC.".$name." stop\n");
	if (""!=$next)		stopcmd("service INET."		.$next." stop\n");
	if (""!=$mode)
	{
		stopcmd("service 6TO4."		.$name." stop\n");
		stopcmd("service IPT."	.$name." restart\n");
	}
	if (""!=$dhcpc6)	stopcmd("service DHCPC6."	.$name." stop\n");

	/* These services may be started after the interface was up.
	 * Stop them even if they were not started at the interface up. */
	if ($addrtype=="ipv6" || $addrtype=="ppp6")
	{
		stopcmd("service DNS6."		.$name." stop\n");
		stopcmd("service DDNS6."	.$name." stop\n");
		stopcmd("service DHCPS6."	.$name." stop\n");
        stopcmd("service SSH6."   .$name." stop\n"); //sandy add
	}
	else if ($addrtype=="ipv4" || $addrtype=="ppp4")
	{
		stopcmd("service DNS4."		.$name." stop\n");
		stopcmd("service DDNS4."	.$name." stop\n");
		stopcmd("service DHCPS4."	.$name." stop\n");
        stopcmd("service SSH4."   .$name." stop\n"); //sandy add
	}

	stopcmd("service UPNP."		.$name." stop\n");
	stopcmd("service HTTP."		.$name." stop\n");

	//if ($hnap==1)		stopcmd("service HNAP."		.$name." stop\n");
	if ($neap>0)        stopcmd("service NEAP."     .$name." stop\n");
	if ($nameresolve>0)        stopcmd("service NAMERESOLV."     .$name." stop\n");
}

function infsvcs_wan($index)
{
	$infp = XNODE_getpathbytarget("", "inf", "uid", "WAN-".$index, 0);
	$upper= query($infp."/upperlayer");
	$lower= query($infp."/lowerlayer");
	
	/* Firewall */
	$fw = query("/acl/firewall/count");
	if ($fw=="" || $fw=="0")
	{
		$fw = query("/acl/firewall2/count");
		if ($fw=="" || $fw=="0")
		{
			$fw = query("/acl/firewall3/count");
			if ($fw=="") $fw=0;
		}
	}
	/* IPv6 Firewall */
	$fw6 = query("/acl6/firewall/count");
	if ($fw6=="") $fw6=0;

	/* Tell everybody, we are going down.
	 * Trigger this event before anything. */
	stopcmd("event INFSVCS.WAN-".$index.".DOWN\n");
	/* Some 3G adapters cannot disconnect ,they need to cmd some AT command. */
	if($index == "3")
		stopcmd("event DIALINIT\n");
	stopcmd("event UPNP.IGD.NOTIFY.WANIPCONN1\n");
	stopcmd("event UPDATERESOLV\n");

	/* To make sure "Automatic Uplink Speed" of QOS can re-detect when wan up/down. (sam_pan)*/
	set("/runtime/device/qos/bwup","0");
	set("/runtime/device/qos/monitor","0");

	/* If we have no lowerlayer, we need to restart these services. */
	if ($lower=="")
	{
		stopcmd("service QOS     		restart\n");
		stopcmd("service MULTICAST		restart\n");
		stopcmd("service ROUTE.STATIC	restart\n");
		stopcmd("service ROUTE.DESTNET	restart\n");
		stopcmd("service ROUTE.DOMAIN	restart\n");
		stopcmd("service ROUTE.IPUNNUMBERED	restart\n");
		stopcmd("service IPTDEFCHAIN	restart\n");
		if ($fw>0) stopcmd("service FIREWALL restart\n");
	}

	/* Walk through the WAN services */
	infsvcs_setup("WAN-".$index);

	/* If we have no upperlayer, we need to restart these services. */
	if ($upper=="")
	{
		if ($fw>0) startcmd("service FIREWALL restart\n");
		startcmd("service IPTDEFCHAIN	restart\n");
		if (isfile("/proc/net/if_inet6")==1)
		{
			if ($fw6>0) startcmd("service FIREWALL6 restart\n");
			startcmd("service IP6TDEFCHAIN	restart\n");
		}
		startcmd("service ROUTE.STATIC	restart\n");
		startcmd("service ROUTE.DESTNET	restart\n");
		startcmd("service ROUTE.DOMAIN	restart\n");
		startcmd("service ROUTE.IPUNNUMBERED	restart\n");
		startcmd("service MULTICAST		restart\n");
		startcmd("service QOS    		restart\n");
		startcmd("service DEVICE.TIME	start\n");
	}
	startcmd("event UPDATERESOLV\n");
	startcmd("event INFSVCS.WAN-".$index.".UP\n");
	startcmd("event UPNP.IGD.NOTIFY.WANIPCONN1\n");
}

function infsvcs_lan($index)
{
	/* Tell everybody, we are going down. */
	stopcmd("event INFSVCS.LAN-".$index.".DOWN\n");
	stopcmd("service IPTDEFCHAIN	restart\n");
	stopcmd("service MULTICAST		restart\n");
	stopcmd("service ROUTE.STATIC	restart\n");
	stopcmd("service ROUTE.IPUNNUMBERED	restart\n");

	startcmd("service ENLAN start\n");

	infsvcs_setup("LAN-".$index);
	/* Update the routing tables */
	startcmd("service ROUTE.STATIC	restart\n");
	startcmd("service ROUTE.IPUNNUMBERED	restart\n");
	startcmd("service MULTICAST		restart\n");
	startcmd("service IPTDEFCHAIN	restart\n");

	if (isfile("/proc/net/if_inet6")==1)
		startcmd("service IP6TDEFCHAIN	restart\n");

	startcmd("event INFSVCS.LAN-".$index.".UP\n");
}
?>
