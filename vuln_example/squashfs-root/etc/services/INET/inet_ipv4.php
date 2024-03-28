<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";

function startcmd($cmd)	{fwrite(a,$_GLOBALS["START"], $cmd."\n");}
function stopcmd($cmd)	{fwrite(a,$_GLOBALS["STOP"], $cmd."\n");}

function inet_ipv4_static($inf, $ifname, $inet, $inetp, $default)
{
	startcmd("# inet_start_ipv4_static(".$inf.",".$ifname.",".$inet.",".$inetp.")");

	/* Get INET setting */
	anchor($inetp."/ipv4");
	$ipaddr = query("ipaddr");
	$mask	= query("mask");
	$gw		= query("gateway");
	$mtu	= query("mtu");

	/* Get DNS setting */
	$cnt = query("dns/count")+0;
	foreach("dns/entry")
	{
		if ($InDeX > $cnt) break;
		if ($dns=="") $dns = $VaLuE;
		else $dns = $dns." ".$VaLuE;
	}

	/* Start script */
	startcmd("phpsh /etc/scripts/IPV4.INET.php ACTION=ATTACH".
		" STATIC=1".
		" INF=".$inf.
		" DEVNAM=".$ifname.
		" IPADDR=".$ipaddr.
		" MASK=".$mask.
		" GATEWAY=".$gw.
		" MTU=".$mtu.
		' "DNS='.$dns.'"'
		);
	/*Check LAN DHCP setting. We will resatrt DHCP server if the DNS relay is disabled*/
	foreach ("/inf")
    {
	    $disable= query("disable");
	    $active = query("active");
	    $dhcps4 = query("dhcps4");
	    $dns4 = query("dns4");
	    if ($disable != "1" && $active=="1" && $dhcps4!=""){
            if ($dns4 =="")
            {
				startcmd("event DHCPS4.RESTART");
            }
	    }
    }
	/* Stop script */
	stopcmd("phpsh /etc/scripts/IPV4.INET.php ACTION=DETACH INF=".$inf);
}

function inet_ipv4_dynamic($inf, $ifname, $inet, $inetp)
{
	startcmd("# inet_start_ipv4_dynamic(".$inf.",".$ifname.",".$inetp.")");

	/* Setup DHCP */

	/* When interface has upperlayer, WANPORT.LINKUP do nothing and
	 * upperlayer will take care this.
	 *
	 * Builder 2009/10/12 */
	$infp = XNODE_getpathbytarget("", "inf", "uid", $inf, 0);
	if (query($infp."/upperlayer")!="")
	{
		$WANPORTLINKUP = 'true';
		$WANPORTLINKDOWN = 'true';
	}
	else
	{
		$WANPORTLINKUP = '"/etc/events/DHCP4-RENEW.sh '.$inf.'"';
		$WANPORTLINKDOWN = '"/etc/events/DHCP4-RELEASE.sh '.$inf.'"';
	}

	/* Get Setting */
	$hostname	= get("s", "/device/hostname");
	$mtu		= query($inetp."/ipv4/mtu");
	/* Get DNS setting */
	$cnt = query($inetp."/ipv4/dns/count")+0;
	foreach($inetp."/ipv4/dns/entry")
	{
		if ($InDeX > $cnt) break;
		$dns = $dns.$VaLuE." ";
	}

	/* The files */
	$udhcpc_helper	= "/var/servd/".$inf."-udhcpc.sh";
	$udhcpc_pid		= "/var/servd/".$inf."-udhcpc.pid";
	$hlper			= "/etc/services/INET/inet4_dhcpc_helper.php";

	/* Generate the callback script for udhcpc. */
    fwrite(w,$udhcpc_helper,
		'#!/bin/sh\n'.
		'echo [$0]: $1 $interface $ip $subnet $router $lease $domain ... > /dev/console\n'.
		'phpsh '.$hlper.' ACTION=$1'.
			' INF='.$inf.
			' INET='.$inet.
			' MTU='.$mtu.
			' INTERFACE=$interface'.
			' IP=$ip'.
			' SUBNET=$subnet'.
			' BROADCAST=$broadcast'.
			' LEASE=$lease'.
			' "DOMAIN=$domain"'.
			' "ROUTER=$router"'.
			' "DNS='.$dns.'$dns"'.
			' "CLSSTROUT=$clsstrout"'.
			' "SSTROUT=$sstrout"\n'.
		'exit 0\n'
		);

	/* set MTU */
	if ($mtu!="") startcmd('ip link set '.$ifname.' mtu '.$mtu);
	
	/* For Henan DHCP+, China */
	$dhcpplus_pid	 = "/var/run/dhcpplus.pid";
	$dhcpplus_enable = query($inetp."/ipv4/dhcpplus/enable");
	$dhcpplus_user	 = get("s", $inetp."/ipv4/dhcpplus/username");
	$dhcpplus_pass	 = get("s", $inetp."/ipv4/dhcpplus/password");
	if ($dhcpplus_enable == 1)
	{
		startcmd('dhcpplus -U '.$dhcpplus_user.' -P '.$dhcpplus_pass.' &\n');
		$dhcpplus_cmd = '-m';
		stopcmd('/etc/scripts/killpid.sh '.$dhcpplus_pid.'\n');
	}

	/* Set event for DHCP client. Start DHCP client. */
	startcmd(
		'event '.$inf.'.DHCP.RENEW     add "kill -SIGUSR1 \\`cat '.$udhcpc_pid.'\\`"\n'.
		'event '.$inf.'.DHCP.RELEASE   add "kill -SIGUSR2 \\`cat '.$udhcpc_pid.'\\`"\n'.
		'event WANPORT.LINKUP          add '.$WANPORTLINKUP.'\n'.
		'event WANPORT.LINKDOWN        add '.$WANPORTLINKDOWN.'\n'.
		'chmod +x '.$udhcpc_helper.'\n'.
		'udhcpc -i '.$ifname.' -H '.$hostname.' -p '.$udhcpc_pid.' -s '.$udhcpc_helper.' '.$dhcpplus_cmd.' &\n'
		);

	/* Stop script */
	stopcmd(
		'/etc/scripts/killpid.sh '.$udhcpc_pid.'\n'.
		'while [ -f '.$udhcpc_pid.' ]; do sleep 1; done\n'.
		'event WANPORT.LINKUP flush\n'.
		'event WANPORT.LINKDOWN flush\n'.
		'event '.$inf.'.DHCP.RELEASE flush\n'.
		'event '.$inf.'.DHCP.RENEW flush\n'.
		'sleep 3\n'
		);
}

/* IPv4 *********************************************************/
fwrite(a,$START, "# INFNAME = [".$INET_INFNAME."]\n");
fwrite(a,$STOP,  "# INFNAME = [".$INET_INFNAME."]\n");

/* These parameter should be valid. */
$inf	= $INET_INFNAME;
$infp	= XNODE_getpathbytarget("", "inf", "uid", $inf, 0);
$phyinf	= query($infp."/phyinf");
$default= query($infp."/defaultroute");
$inet	= query($infp."/inet");
$inetp	= XNODE_getpathbytarget("/inet", "entry", "uid", $inet, 0);
$ifname	= PHYINF_getifname($phyinf);

/* Create the runtime inf. Set phyinf. */
$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 1);
set($stsp."/phyinf", $phyinf);
set($stsp."/defaultroute", $default);

$s = query($inetp."/ipv4/static");
if ($s==1)	inet_ipv4_static($inf, $ifname, $inet, $inetp, $default);
else		inet_ipv4_dynamic($inf, $ifname, $inet, $inetp);

?>
