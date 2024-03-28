<?
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";

function copy_ipv4($from, $to)
{
	$t = $to."/ipv4";
	$f = $from."/ipv4";
	$static = query($f."/static");

	set($to."/addrtype","ipv4");
	set($t."/static",	$static);
	if ($static == "1")
	{
		set($t."/ipaddr",	query($f."/ipaddr"));
		set($t."/mask",		query($f."/mask"));
		set($t."/gateway",	query($f."/gateway"));
	}
	set($t."/mtu",		query($f."/mtu"));
	//del($t."/dns");
	XNODE_del_children($t."/dns", "entry");
	$cnt = query($f."/dns/count");
	if ($cnt > 0)
	{
		set($t."/dns/count", $cnt);
		$i = 0;
		while ($i < $cnt)
		{
			$i++;
			set($t."/dns/entry:".$i, query($f."/dns/entry:".$i));
		}
	}
	else set($t."/dns/count", "0");
	if (query($f."/dhcpplus/enable")!="")
	{
		set($t."/dhcpplus/enable",		query($f."/dhcpplus/enable"));
		set($t."/dhcpplus/username",	query($f."/dhcpplus/username"));
		set($t."/dhcpplus/password",	query($f."/dhcpplus/password"));
	}
}

function copy_ipv6($from, $to)
{
	$t = $to."/ipv6";
	$f = $from."/ipv6";
	$mode = query($f."/mode");

	set($to."/addrtype","ipv6");
	if ($mode == "STATIC")
	{
		set($t."/mode",			"STATIC");
		set($t."/ipaddr",		query($f."/ipaddr"));
		set($t."/prefix",		query($f."/prefix"));
		set($t."/routerlft",	query($f."/routerlft"));
		set($t."/preferlft",	query($f."/preferlft"));
		set($t."/validlft",		query($f."/validlft"));
		set($t."/gateway",		query($f."/gateway"));
		set($t."/routerlft",	query($f."/routerlft"));
		set($t."/preferlft",	query($f."/preferlft"));
		set($t."/validlft",		query($f."/validlft"));
	}
	if ($mode == "6TO4")
	{
		set($t."/mode",	"6TO4");
	}
	if ($mode == "DHCP")
	{
		set($t."/mode",	"DHCP");
	}
	if ($mode == "RA")
	{
		set($t."/mode",	"RA");
	}
	if ($mode == "6IN4")
	{
		set($t."/mode",	"6IN4");
		set($t."/ipaddr",	query($f."/ipaddr"));
		set($t."/prefix",		query($f."/prefix"));
		set($t."/gateway",	query($f."/gateway"));
		set($t."/ipv6in4/remote",	query($f."/ipv6in4/remote"));
		set($t."/ipv6in4/local",	query($f."/ipv6in4/local"));
	}
	if ($mode == "TSP")
	{
		set($t."/mode", "TSP");
		set($t."/ipv6in4/remote", query($f."/ipv6in4/remote"));
		set($t."/ipv6in4/tsp/username", query($f."/ipv6in4/tsp/username"));
		set($t."/ipv6in4/tsp/password", query($f."/ipv6in4/tsp/password"));
		set($t."/ipv6in4/tsp/prefix",   query($f."/ipv6in4/tsp/prefix"));
	}
   	if ($mode == "AUTO")
   	{
       		set($t."/mode", "AUTO");
   	}
   	if ($mode == "")
   	{
       	set($t."/mode", "");
   	}
	set($t."/dhcpopt",	query($f."/dhcpopt"));
	set($t."/mtu",		query($f."/mtu"));
	del($t."/dns");
	$cnt = query($f."/dns/count");
	if ($cnt > 0)
	{
		set($t."/dns/count", $cnt);
		$i = 0;
		while ($i < $cnt)
		{
			$i++;
			set($t."/dns/entry:".$i, query($f."/dns/entry:".$i));
		}
	}
	else set($t."/dns/count", "0");
}

function copy_ppp4($from, $to)
{
	$over = query($from."/ppp4/over");
	$t = $to."/ppp4"; $f = $from."/ppp4";

	set($to."/addrtype","ppp4");
	set($t."/over",			$over);
	set($t."/static",		query($f."/static"));
	set($t."/ipaddr",		query($f."/ipaddr"));
	set($t."/mtu",			query($f."/mtu"));
	set($t."/mru",			query($f."/mru"));
	set($t."/username",		query($f."/username"));
	set($t."/password",		query($f."/password"));
	set($t."/mppe/enable",	query($f."/mppe/enable"));
	set($t."/dialup/mode",	query($f."/dialup/mode"));
	set($t."/dialup/idletimeout",query($f."/dialup/idletimeout"));
	if (query($f."/authproto")!=""){ set($t."/authproto", query($f."/authproto")); }

	//del($t."/dns");
	XNODE_del_children($t."/dns", "entry");
	$cnt = query($f."/dns/count");
	if ($cnt > 0)
	{
		set($t."/dns/count", $cnt);
		$i = 0;
		while ($i < $cnt)
		{
			$i++;
			set($t."/dns/entry:".$i, query($f."/dns/entry:".$i));
		}
	}
	else set($t."/dns/count", "0");

	if ($over == "eth")
	{
		$t = $t."/pppoe";
		$f = $f."/pppoe";
		set($t."/acname", query($f."/acname"));
		set($t."/servicename", query($f."/servicename"));
		if (query($f."/starspeed/enable")!="")
		{
			set($t."/starspeed/enable", query($f."/starspeed/enable"));
			set($t."/starspeed/region", query($f."/starspeed/region"));
		}
		if (query($f."/netsniper/enable")!="")
		{
			set($t."/netsniper/enable", query($f."/netsniper/enable"));
		}
	}
	else if ($over == "pptp" || $over == "l2tp")
	{
		$t = $t."/".$over;
		$f = $f."/".$over;
		set($t."/server", query($f."/server"));
	}
	else if ($over == "tty")
	{
		$t = $t."/tty";
		$f = $f."/tty";
		set($t."/qos_enable", query($f."/qos_enable"));
		set($t."/qos_upstream", query($f."/qos_upstream"));
		set($t."/qos_downstream", query($f."/qos_downstream"));
		set($t."/auto_config/mode", query($f."/auto_config/mode"));
		set($t."/connect_type/mode", query($f."/connect_type/mode"));
		set($t."/mcc", query($f."/mcc"));
		set($t."/mnc", query($f."/mnc"));
		set($t."/dialno", query($f."/dialno"));
		set($t."/apn", query($f."/apn"));
		set($t."/profilename", query($f."/profilename"));
		set($t."/country", query($f."/country"));
		set($t."/simpin", query($f."/simpin"));
		set($t."/simlock", query($f."/simlock"));
	}
}

function copy_ppp6($from, $to)
{
        $t = $to."/ppp6"; $f = $from."/ppp6";
	set($to."/addrtype", "ppp6");
	set($t."/static",   query($f."/static"));
	set($t."/ipaddr",   query($f."/ipaddr"));
	set($t."/mtu",   query($f."/mtu"));
	set($t."/mru",   query($f."/mru"));
	set($t."/username",   query($f."/username"));
	set($t."/password",   query($f."/password"));
	set($t."/dialup/mode",   query($f."/dialup/mode"));
	set($t."/dialup/idletimeout",   query($f."/dialup/idletimeout"));
	
	$cnt = query($f."/dns/count");
	if ($cnt > 0)
	{
		set($t."/dns/count", $cnt);
		$i = 0;
		while ($i < $cnt)
		{
			$i++;
			set($t."/dns/entry:".$i, query($f."/dns/entry:".$i));
		}
	}
	else set($t."/dns/count", "0");
}

function inet_setcfg($prefix, $n_infp)
{
	/* INF setting */
	$uid = query($n_infp."/uid");
	$inf = XNODE_getpathbytarget("", "inf", "uid", $uid, 0);
	if ($inf=="") TRACE_error("SETCFG/INET: no inf entry for [".$uid."] found!");
	else
	{
		anchor($n_infp);
		$active = query("active");
		set($inf."/active",	$active);
		if ($active!="1") return;

		set($inf."/defaultroute",	query("defaultroute"));
		set($inf."/lowerlayer",		query("lowerlayer"));
		set($inf."/upperlayer",		query("upperlayer"));
		set($inf."/schedule",		query("schedule"));
		set($inf."/inet",			query("inet"));
		set($inf."/bwc",			query("bwc"));
		set($inf."/dhcps4",			query("dhcps4"));
		set($inf."/dhcps6",			query("dhcps6"));
		set($inf."/ddns4",			query("ddns4"));
		set($inf."/ddns6",			query("ddns6"));
		set($inf."/dns4",			query("dns4"));
		set($inf."/dns6",			query("dns6"));
		set($inf."/nat",			query("nat"));
		set($inf."/web",			query("web"));
		set($inf."/weballow/hostv4ip", query("weballow/hostv4ip"));
		set($inf."/flets",			query("flets"));
		set($inf."/icmp",			query("icmp"));
		set($inf."/backup",			query("backup"));
		set($inf."/chkinterval",	query("chkinterval"));
		set($inf."/child",			query("child"));
		set($inf."/dhcpc6",			query("dhcpc6"));
		set($inf."/dhcpdetect",         query("dhcpdetect"));//sandy 2010_07_13 dhcpdetect
		set($inf."/inf6to4/mode",		query($n_infp."/inf6to4/mode"));
		set($inf."/inf6to4/relay",		query($n_infp."/inf6to4/relay"));
		set($inf."/inf6to4/ipaddr",		query($n_infp."/inf6to4/ipaddr"));
		set($inf."/inf6to4/prefix",		query($n_infp."/inf6to4/prefix"));
		set($inf."/inf6to4/mask",		query($n_infp."/inf6to4/mask"));
		set($inf."/infprevious",		query($n_infp."/infprevious"));
		set($inf."/infnext",		query($n_infp."/infnext"));
		set($inf."/phyinf",		query($n_infp."/phyinf"));
		set($inf."/disable",		query("disable"));
		while (query($inf."/upnp/entry#") > 0) del($inf."/upnp/entry");
		$cnt = query("upnp/count");
		if ($cnt=="") $cnt = 0;
		set($inf."/upnp/count", $cnt);
		foreach ("upnp/entry")
		{
			if ($InDeX > $cnt) break;
			add($inf."/upnp/entry", $VaLuE);
		}
	}

	/* INET settings */
	$inet = query($n_infp."/inet");
	if ($inet != "")
	{
		/* INET setting */
		$inetsrc = XNODE_getpathbytarget($prefix."/inet", "entry", "uid", $inet, 0);
		$inetdst = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, 0);
		if ($inetdst!="")
		{
			if ($inetsrc!="")
			{
				/* copy the inet profile. */
				$addrtype	= query($inetsrc."/addrtype");
				if		($addrtype == "ipv4") copy_ipv4($inetsrc, $inetdst);
				else if	($addrtype == "ipv6") copy_ipv6($inetsrc, $inetdst);
				else if	($addrtype == "ppp4") copy_ppp4($inetsrc, $inetdst);
				else if	($addrtype == "ppp6") copy_ppp6($inetsrc, $inetdst);
				else TRACE_error("SETCFG/INET: unknown addrtype - ".$addrtype);
			}
		}
		else TRACE_error("SETCFG/INET: no inet entry for [".$inet."] found!");
	}
}
?>
