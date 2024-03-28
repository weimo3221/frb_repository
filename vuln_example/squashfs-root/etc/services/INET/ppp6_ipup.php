#!/bin/sh
<? /* $IFNAME, $DEVICE, $SPEED, $IP, $REMOTE, $PARAM */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";

$infp = XNODE_getpathbytarget("", "inf", "uid", $PARAM, 0);
if ($infp == "") exit;
$inet = query($infp."/inet");
if ($inet == "") exit;

$defaultroute = query($infp."/defaultroute");
/* create phyinf */
PHYINF_setup("PPP.".$PARAM, "ppp", $IFNAME);

/* get mtu value*/
$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, 0);
$mtu = query($inetp."/ppp6/mtu");

/* create inf */
$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $PARAM, 1);
del($stsp."/inet");
set($stsp."/inet/uid",			$inet);
set($stsp."/inet/addrtype",		"ppp6");
set($stsp."/inet/uptime",		query("/runtime/device/uptime"));
set($stsp."/inet/ppp6/valid",	"1");
set($stsp."/inet/ppp6/mtu",           	$mtu);
set($stsp."/inet/ppp6/local",	$IP);
set($stsp."/inet/ppp6/peer",	$REMOTE);
set($stsp."/phyinf",			"PPP.".$PARAM);
set($stsp."/defaultroute",		$defaultroute);

/* backup*/
set($stsp."/ppp6/uid",			$inet);
set($stsp."/ppp6/addrtype",		"ppp6");
set($stsp."/ppp6/uptime",		query("/runtime/device/uptime"));
set($stsp."/ppp6/ppp6/valid",	"1");
set($stsp."/ppp6/ppp6/mtu",           	$mtu);
set($stsp."/ppp6/ppp6/local",	$IP);
set($stsp."/ppp6/ppp6/peer",	$REMOTE);

/* Add this network in 'LOCAL' */
echo 'ip -6 route add default via '.$REMOTE.' dev '.$IFNAME.'\n';

/* user dns */
$cnt = 0;
if ($inetp != "")
{
	$cnt = query($inetp."/ppp6/dns/count");
	if ($cnt=="") $cnt = 0;
	$i = 0;
	while ($i < $cnt)
	{
		$i++;
		$value = query($inetp."/ppp6/dns/entry:".$i);
		if ($value != "") add($stsp."/inet/ppp6/dns", $value);
	}
}


/* auto dns */
if ($cnt == 0 && isfile("/etc/ppp/resolv.conf.".$PARAM)==1)
{
	$dnstext = fread("r", "/etc/ppp/resolv.conf.".$PARAM);
	$cnt = scut_count($dnstext, "");
	$i = 0;
	while ($i < $cnt)
	{
		$token = scut($dnstext, $i, "");
		if ($token == "nameserver")
		{
			$i++;
			$token = scut($dnstext, $i, "");
			add($stsp."/inet/ppp6/dns", $token);
		}
		$i++;
	}
}

/* We use PING peer IP to trigger the dailup at 'ondemand' mode.
 * So we need to update the command to PING the new gateway. */
$dial = XNODE_get_var($PARAM.".DIALUP");
if ($dial=="") $dial = query($inetp."/ppp6/dialup/mode");
if ($dial=="ondemand")
	echo 'event '.$PARAM.'.PPP.DIALUP add "ping '.$REMOTE.'"\n';

echo "event ".$PARAM.".UP\n";
echo "echo 1 > /var/run/".$PARAM.".UP\n";
?>
exit 0
