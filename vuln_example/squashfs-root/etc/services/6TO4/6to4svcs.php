<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";

function startcmd($cmd)    {fwrite(a,$_GLOBALS["START"], $cmd."\n");}
function stopcmd($cmd)     {fwrite(a,$_GLOBALS["STOP"], $cmd."\n");}

function 6to4_error($errno)
{
	startcmd("exit ".$errno."\n");
	stopcmd( "exit ".$errno."\n");
}

function 6to4setup($name)
{
	$infp = XNODE_getpathbytarget("", "inf", "uid", $name, 0);
	$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $name, 0);
	if ($infp=="" || $stsp=="")
	{
		SHELL_info($_GLOBALS["START"], "6to4_setup: (".$name.") no interface.");
		SHELL_info($_GLOBALS["STOP"],  "6to4_setup: (".$name.") no interface.");
		6to4_error("9");
		return;
	}

	//$inf6to4 = query($infp."/inf6to4/inf");
	$inf6to4 = query($infp."/infnext");
	if ($inf6to4=="")
	{
		SHELL_info($_GLOBALS["START"], "6to4_setup: (".$name.") inf6to4 node not exist.");
		SHELL_info($_GLOBALS["STOP"],  "6to4_setup: (".$name.") inf6to4 node not exist.");
		6to4_error("8");
		return;
	}
	$w6to4_infp = XNODE_getpathbytarget("", "inf", "uid", $inf6to4, 0);

	$addrtype = query($stsp."/inet/addrtype");
	if ($addrtype == "ipv4" ) // static ip or dhcp
		$ipv4addr = query($stsp."/inet/ipv4/ipaddr");
	else if ($addrtype == "ppp4" ) // ppp
		$ipv4addr = query($stsp."/inet/ppp4/local");
	else // other addrtype is wrong
	{
		SHELL_info($_GLOBALS["START"], "6to4_setup: (".$name.") wrong addrtype.");
		SHELL_info($_GLOBALS["STOP"],  "6to4_setup: (".$name.") wrong addrtype.");
		6to4_error("8");
		return;
	}

	$mode = query($infp."/inf6to4/mode");
    $relay = query($infp."/inf6to4/relay");
    /*
	$ip0 = cut($ipv4addr, "0", "."); $ip1 = cut($ipv4addr, "1", ".");
	$ip2 = cut($ipv4addr, "2", "."); $ip3 = cut($ipv4addr, "3", ".");
	$w6to4addr = "2002:".dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).":".dec2strf("%02x", $ip2).dec2strf("%02x", $ip3)."::1";
	$l6to4addr = "2002:".dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).":".dec2strf("%02x", $ip2).dec2strf("%02x", $ip3)."::2";
    */
    if("6TO4"==$mode)
    {
		$ip0 = cut($ipv4addr, "0", "."); $ip1 = cut($ipv4addr, "1", ".");
		$ip2 = cut($ipv4addr, "2", "."); $ip3 = cut($ipv4addr, "3", ".");
		$w6to4addr = "2002:".dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).":".dec2strf("%02x", $ip2).dec2strf("%02x", $ip3)."::1";
		$l6to4addr = "2002:".dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).":".dec2strf("%02x", $ip2).dec2strf("%02x", $ip3)."::2";
    }
    else if("6RD"==$mode)
    {
    	$6rdprfx = query($infp."/inf6to4/prefix");
		$ip0 = cut($ipv4addr, "0", "."); $ip1 = cut($ipv4addr, "1", ".");
		$ip2 = cut($ipv4addr, "2", "."); $ip3 = cut($ipv4addr, "3", ".");
        $ip6rd = query($infp."/inf6to4/ipaddr");
        $rdmask = query($infp."/inf6to4/mask");
		$ip6rd0 = cut($ip6rd, "0", ":");
		$ip6rd1 = cut($ip6rd, "1", ":");
		$sla = dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).dec2strf("%02x", $ip2).dec2strf("%02x", $ip3);
		if($rdmask !="" && $rdmask != 0)
		{
			startcmd("w6to4addr=`ipv6pdip -n ".$ip6rd." -l ".$6rdprfx." -s ".$sla." -m ".$rdmask." -p 64 -h 1 | cut -d / -f 1`"); 
			startcmd("l6to4addr=`ipv6pdip -n ".$ip6rd." -l ".$6rdprfx." -s ".$sla." -m ".$rdmask." -p 64 -h 2 | cut -d / -f 1`"); 
		}
		else
		{
			if($ip6rd1 != "")
			{
				$w6to4addr = $ip6rd0.":".$ip6rd1.":".dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).":".dec2strf("%02x", $ip2).dec2strf("%02x", $ip3)."::1";
				$l6to4addr = $ip6rd0.":".$ip6rd1.":".dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).":".dec2strf("%02x", $ip2).dec2strf("%02x", $ip3)."::2";
			}
			else  //special case: 3000::/16
			{
				$w6to4addr = $ip6rd0.":".dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).":".dec2strf("%02x", $ip2).dec2strf("%02x", $ip3)."::1";
				$l6to4addr = $ip6rd0.":".dec2strf("%02x", $ip0).dec2strf("%02x", $ip1).":".dec2strf("%02x", $ip2).dec2strf("%02x", $ip3)."::2";
			}
    	}
	}
    else
    {
		SHELL_info($_GLOBALS["START"], "6to4_setup: (".$name.") wrong mode.");
		SHELL_info($_GLOBALS["STOP"],  "6to4_setup: (".$name.") wrong mode.");
		6to4_error("8");
		return;
    }

	/* set IPv6 WAN: ip, tunnel ... */
    /*
	startcmd("ip tunnel add tun6to4 mode sit ttl 128 remote any local ".$ipv4addr);
	startcmd("echo 0 > /proc/sys/net/ipv6/conf/tun6to4/disable_ipv6");
	startcmd("ip link set dev tun6to4 up");
	startcmd("ip -6 addr add ".$w6to4addr."/16 dev tun6to4");
	startcmd("ip -6 route add default via ::192.88.99.1 dev tun6to4 metric 1");
    */
    if("6TO4"==$mode)
    {
		startcmd("ip tunnel add tun6to4 mode sit ttl 128 remote any local ".$ipv4addr);
		startcmd("echo 0 > /proc/sys/net/ipv6/conf/tun6to4/disable_ipv6");
		startcmd("ip link set dev tun6to4 up");
		startcmd("ip -6 addr add ".$w6to4addr."/16 dev tun6to4");
        if(""==$relay)
			startcmd("ip -6 route add default via ::192.88.99.1 dev tun6to4 metric 1");
        else 
			startcmd("ip -6 route add default via ::".$relay." dev tun6to4 metric 1");
    }
    else if("6RD"==$mode)
    {
    	$6rdip = query($infp."/inf6to4/ipaddr");
    	$6rdprfx = query($infp."/inf6to4/prefix");
		startcmd("ip tunnel add tun6rd mode sit ttl 128 remote any local ".$ipv4addr);
		startcmd("echo 0 > /proc/sys/net/ipv6/conf/tun6rd/disable_ipv6");
		startcmd("ip link set dev tun6rd up");
		//startcmd("ip -6 addr add $w6to4addr/".$6rdprfx." dev tun6rd");
		if($rdmask !="" && $rdmask != 0)
		{
			startcmd("ip -6 addr add $w6to4addr/".$6rdprfx." dev tun6rd");
		}
		else
		{
			startcmd("ip -6 addr add ".$w6to4addr."/".$6rdprfx." dev tun6rd");
		}
		startcmd("ip tunnel 6rd dev tun6rd 6rd-prefix ".$6rdip."/".$6rdprfx." dev tun6rd");
        if(""==$relay)
			startcmd("ip -6 route add default via ::192.88.99.1 dev tun6rd metric 1");
        else 
			startcmd("ip -6 route add default via ::".$relay." dev tun6rd metric 1");
    }
    
	/* unset IPv6 WAN */
    /*
	stopcmd("ip -6 route del default via ::192.88.99.1 dev tun6to4 metric 1");
	stopcmd("ip -6 addr del ".$w6to4addr."/16 dev tun6to4");
	stopcmd("ip link set dev tun6to4 down");
	stopcmd("echo 1 > /proc/sys/net/ipv6/conf/tun6to4/disable_ipv6");
	stopcmd("ip tunnel del tun6to4 mode sit ttl 128 remote any local ".$ipv4addr);
    */
    if("6TO4"==$mode)
    {
        if(""==$relay)
			stopcmd("ip -6 route del default via ::192.88.99.1 dev tun6to4 metric 1");
        else
			stopcmd("ip -6 route del default via ".$relay." dev tun6to4 metric 1");
		stopcmd("ip -6 addr del ".$w6to4addr."/16 dev tun6to4");
		stopcmd("ip link set dev tun6to4 down");
		stopcmd("echo 1 > /proc/sys/net/ipv6/conf/tun6to4/disable_ipv6");
		stopcmd("ip tunnel del tun6to4 mode sit ttl 128 remote any local ".$ipv4addr);
    }
    else if("6RD"==$mode)
    {
        if(""==$relay)
			stopcmd("ip -6 route del default via ::192.88.99.1 dev tun6rd metric 1");
        else
			stopcmd("ip -6 route del default via ".$relay." dev tun6rd metric 1");
		//stopcmd("ip -6 addr del $w6to4addr/".$6rdprfx." dev tun6rd");
		stopcmd("ip -6 addr del `ip -f inet6 addr show dev tun6rd scope global | scut -p inet6` dev tun6rd");
		stopcmd("ip link set dev tun6rd down");
		stopcmd("echo 1 > /proc/sys/net/ipv6/conf/tun6rd/disable_ipv6");
		stopcmd("ip tunnel del tun6rd mode sit ttl 128 remote any local ".$ipv4addr);
    }
    
	/* create phyinf for IPv6 WAN */
	$w6to4phyinf = "TUN-1"; // a unique name may be better
    if("6TO4"==$mode)
		PHYINF_setup($w6to4phyinf, "tun", "tun6to4");
    else if("6RD"==$mode)
		PHYINF_setup($w6to4phyinf, "tun", "tun6rd");
	/* fill in IPv6 WAN runtime node*/
	$w6to4_stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf6to4, 1);
	startcmd("xmldbc -s ".$w6to4_stsp."/phyinf ".$w6to4phyinf);
	startcmd('xmldbc -X '.$w6to4_stsp.'/inet');
	startcmd('xmldbc -s '.$w6to4_stsp.'/inet/uid '.'""'); // set uid as empty
	startcmd('xmldbc -s '.$w6to4_stsp.'/inet/addrtype ipv6');
	startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/valid 1');
	startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/mode 6TO4');
	//startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/ipaddr "'.$w6to4addr.'"');
	//startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/ipaddr $w6to4addr');
    if("6TO4"==$mode)
	{
		startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/ipaddr "'.$w6to4addr.'"');
		startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/prefix 16');
	}
	else
	{
		if($rdmask !="" && $rdmask != 0)
			startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/ipaddr $w6to4addr');
		else
			startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/ipaddr "'.$w6to4addr.'"');

		startcmd('xmldbc -s '.$w6to4_stsp.'/inet/ipv6/prefix '.$6rdprfx);
	}
	startcmd('UPTIME=`xmldbc -g /runtime/device/uptime`');
	startcmd('xmldbc -s '.$w6to4_stsp.'/inet/uptime $UPTIME');
	/* clean IPv6 WAN runtime node*/
	stopcmd("sh /etc/scripts/delpathbytarget.sh "."/runtime "."inf "."uid ".$inf6to4);
	/* delete phyinf for IPv6 WAN */
	stopcmd("sh /etc/scripts/delpathbytarget.sh "."/runtime "."phyinf "."uid ".$w6to4phyinf);

	/* up IPv6 WAN */
	startcmd("event ".$inf6to4.".UP");
	startcmd("echo 1 > /var/run/".$inf6to4.".UP");
	/* down IPv6 WAN */
	stopcmd("event ".$inf6to4.".DOWN");
	stopcmd("rm -f /var/run/".$inf6to4.".UP");

	$child = query( $w6to4_infp."/child" );
	if ($child!="")
	{
		/* set IPv6 LAN: ip ... */
		$l6to4phyinfname = PHYINF_getphyinf($child);
		startcmd("echo 0 > /proc/sys/net/ipv6/conf/".$l6to4phyinfname."/disable_ipv6"); // just make sure ipv6 is enabled.
    	if("6TO4"==$mode)
		{
			startcmd("ip -6 addr add ".$l6to4addr."/64 dev ".$l6to4phyinfname);
			/* unset IPv6 LAN */
			stopcmd("ip -6 addr del ".$l6to4addr."/64 dev ".$l6to4phyinfname);
		}
		else if("6RD"==$mode)
		{
			if($rdmask !="" && $rdmask != 0)
				startcmd("ip -6 addr add $l6to4addr/64 dev ".$l6to4phyinfname);
			else
				startcmd("ip -6 addr add ".$l6to4addr."/64 dev ".$l6to4phyinfname);
				
			/* unset IPv6 LAN */
			//stopcmd("ip -6 addr del $l6to4addr/64 dev ".$l6to4phyinfname);
			stopcmd("ip -6 addr del `ip -f inet6 addr show dev ".$l6to4phyinfname." scope global | scut -p inet6` dev ".$l6to4phyinfname);
		}

		/* get phyinf of IPv6 LAN */
		$l6to4phyinf = query(XNODE_getpathbytarget("", "inf", "uid", $child, 0)."/phyinf");

		/* fill in IPv6 LAN runtime node */
		$l6to4_stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $child, 1);
		startcmd("xmldbc -s ".$l6to4_stsp."/phyinf ".$l6to4phyinf);
		startcmd('xmldbc -X '.$l6to4_stsp.'/inet');
		startcmd('xmldbc -s '.$l6to4_stsp.'/inet/uid '.'""'); // set uid as empty
		startcmd('xmldbc -s '.$l6to4_stsp.'/inet/addrtype ipv6');
		startcmd('xmldbc -s '.$l6to4_stsp.'/inet/ipv6/valid 1');
		startcmd('xmldbc -s '.$l6to4_stsp.'/inet/ipv6/mode 6TO4');
		if("6TO4"==$mode)
		{
			startcmd('xmldbc -s '.$l6to4_stsp.'/inet/ipv6/ipaddr "'.$l6to4addr.'"');
		}
		else if("6RD"==$mode)
		{
			if($rdmask !="" && $rdmask != 0)
				startcmd('xmldbc -s '.$l6to4_stsp.'/inet/ipv6/ipaddr $l6to4addr');
			else
				startcmd('xmldbc -s '.$l6to4_stsp.'/inet/ipv6/ipaddr "'.$l6to4addr.'"');
		}
		startcmd('xmldbc -s '.$l6to4_stsp.'/inet/ipv6/prefix 64');
		startcmd('UPTIME=`xmldbc -g /runtime/device/uptime`');
		startcmd('xmldbc -s '.$l6to4_stsp.'/inet/uptime $UPTIME');
		/* clean IPv6 LAN runtime node */
		stopcmd("sh /etc/scripts/delpathbytarget.sh "."/runtime "."inf "."uid ".$child);

		/* up IPv6 LAN */
		startcmd("event ".$child.".UP");
		startcmd("echo 1 > /var/run/".$child.".UP");
		/* down IPv6 LAN */
		stopcmd("event ".$child.".DOWN");
		stopcmd("rm -f /var/run/".$child.".UP");
	}
}
?>
