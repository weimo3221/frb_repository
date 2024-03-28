<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";

function startcmd($cmd)	{fwrite(a,$_GLOBALS["START"], $cmd."\n");}
function stopcmd($cmd)	{fwrite(a,$_GLOBALS["STOP"], $cmd."\n");}
function inet_child_error($errno) { startcmd("exit ".$errno); stopcmd("exit ".$errno); }

function inet_child_dynamic($inf, $ifname, $infp, $inetp)
{
	startcmd("# inf=".$inf.", ifname=".$ifname.", infp=".$infp.", inetp=".$inetp);

	$active = query($inetp.'/active');

	if ($active != 1)
	{
		SHELL_info($_GLOBALS["START"], "inet_child_dynamic: (".$inf.") no active.");
		SHELL_info($_GLOBALS["STOP"],  "inet_child_dynamic: (".$inf.") no active.");
		inet_child_error("1");
		return;
	}

	$pinf = query($infp."/parent");
	$pifname = PHYINF_getphyinf($pinf);

	$infpp = XNODE_getpathbytarget("", "inf", "uid", $pinf, 0);
	$pinet = query($infpp."/inet");
	$inetpp = XNODE_getpathbytarget("/inet", "entry", "uid", $pinet, 0);
	$dhcpopt = query($inetpp."/ipv6/dhcpopt");
	$pmode = query($inetpp."/ipv6/mode");
	$dhcpc6 = query($infpp."/dhcpc6");

	startcmd('prefix="`cat /var/etc/'.$pinf.'.prefix`"');
	startcmd('plen="`cat /var/etc/'.$pinf.'.plen`"');
	// Address wan interface
	//startcmd('wanifname="`cat /var/etc/'.$pinf.'.ifname`"');
	//startcmd('ip -6 addr add `ipv6ip -n $prefix -l $plen -h 1`/$plen dev '.$pifname);
    if(strchr($pifname, "ppp") != "")
    {
       $pinfp = XNODE_getpathbytarget("", "inf", "uid", $pinf, 0);
       $pinfprev  = query($pinfp."/infprevious");
       $pifnameprev = PHYINF_getphyinf($pinfprev);
       startcmd('ip -6 addr add `ipv6ip -n $prefix -l $plen -h 1`/$plen dev '.$pifnameprev);
    }
    else
	{
       //startcmd('ip -6 addr add `ipv6ip -n $prefix -l $plen -h 1`/$plen dev '.$pifname);
		//if($dhcpopt == "IA-PD" && $pmode != "RA")
		if($dhcpopt == "IA-PD" && $dhcpc6 == "")//not RA mode
			startcmd('ip -6 addr add `ipv6ip -n $prefix -l $plen -h 1`/$plen dev '.$pifname);
	}

	// Address lan interface
	//startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 1 -h 1`"');
	//startcmd('ip -6 addr add $addr dev '.$ifname);
	//if($dhcpopt == "IA-PD" && $pmode != "RA")
	if($dhcpopt == "IA-PD" && $dhcpc6 == "")//not RA mode
	{
		//startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 1 -h 1`"');
		//startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 01 -p 64 -h 1`"');
		startcmd('hid0="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f3`"');
		startcmd('hid1="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f4`"');
		startcmd('hid2="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f5`"');
		startcmd('hid3="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f6`"');
		startcmd('if [ $plen -eq 64 ]; then');
		startcmd('addr="`ipv6ip -n $prefix -l $plen -h $hid0:$hid1:$hid2:$hid3`/64"');
		startcmd('else');
		startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 01 -p 64 -h $hid0:$hid1:$hid2:$hid3`"');
		startcmd('fi');
		startcmd('ip -6 addr add $addr dev '.$ifname);
	}
	else if($dhcpopt == "IA-NA+IA-PD")
	{
		//startcmd('addr="`ip -f inet6 addr show dev '.$ifname.' scope global | scut -p inet6`"');
		startcmd('hid0="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f3`"');
		startcmd('hid1="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f4`"');
		startcmd('hid2="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f5`"');
		startcmd('hid3="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f6`"');
		//startcmd('addr="`ipv6ip -n $prefix -l $plen -h $hid0:$hid1:$hid2:$hid3`/64"');
		//Select the first /64 subnet if a network is larger than /64
		//startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 01 -p 64 -h $hid0:$hid1:$hid2:$hid3`"');
		startcmd('if [ $plen -eq 64 ]; then');
		startcmd('addr="`ipv6ip -n $prefix -l $plen -h $hid0:$hid1:$hid2:$hid3`/64"');
		startcmd('else');
		startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 01 -p 64 -h $hid0:$hid1:$hid2:$hid3`"');
		startcmd('fi');
		startcmd('ip -6 addr add $addr dev '.$ifname);
	}
	else if($dhcpopt == "IA-PD" && $pmode == "RA")
	{
	//	startcmd('addr="`ip -f inet6 addr show dev '.$ifname.' scope global | scut -p inet6`"');

		startcmd('hid0="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f3`"');
		startcmd('hid1="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f4`"');
		startcmd('hid2="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f5`"');
		startcmd('hid3="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f6`"');
		//startcmd('addr="`ipv6ip -n $prefix -l $plen -h $hid0:$hid1:$hid2:$hid3`/64"');
		//startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 01 -p 64 -h $hid0:$hid1:$hid2:$hid3`"');
		startcmd('if [ $plen -eq 64 ]; then');
		startcmd('addr="`ipv6ip -n $prefix -l $plen -h $hid0:$hid1:$hid2:$hid3`/64"');
		startcmd('else');
		startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 01 -p 64 -h $hid0:$hid1:$hid2:$hid3`"');
		startcmd('fi');
		startcmd('ip -6 addr add $addr dev '.$ifname);
	}
	else if($dhcpopt == "IA-PD" && $pmode == "AUTO")
	{
	//	startcmd('addr="`ip -f inet6 addr show dev '.$ifname.' scope global | scut -p inet6`"');

		startcmd('hid0="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f3`"');
		startcmd('hid1="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f4`"');
		startcmd('hid2="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f5`"');
		startcmd('hid3="`ip -f inet6 addr show dev '.$ifname.' scope link | scut -p inet6 | cut -d\'/\' -f1 | cut -d: -f6`"');
		//startcmd('addr="`ipv6ip -n $prefix -l $plen -h $hid0:$hid1:$hid2:$hid3`/64"');
		//startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 01 -p 64 -h $hid0:$hid1:$hid2:$hid3`"');
		startcmd('if [ $plen -eq 64 ]; then');
		startcmd('addr="`ipv6ip -n $prefix -l $plen -h $hid0:$hid1:$hid2:$hid3`/64"');
		startcmd('else');
		startcmd('addr="`ipv6pdip -n $prefix -l $plen -s 01 -p 64 -h $hid0:$hid1:$hid2:$hid3`"');
		startcmd('fi');
		startcmd('ip -6 addr add $addr dev '.$ifname);
	}

	//blackhole issue
	startcmd('ip -6 route add to blackhole $prefix/$plen dev lo');

	// Fill up runtime nodes
	startcmd('prefix="`echo $addr | cut -d\'/\' -f1`"');
	startcmd('plen="`echo $addr | cut -d\'/\' -f2`"');
	startcmd('xmldbc -s '.$inetp.'/ipv6/ipaddr "$prefix"');
	startcmd('xmldbc -s '.$inetp.'/ipv6/prefix "$plen"');

	//stopcmd('xmldbc -X '.$infp.'/inet');
	// Remove the address of wan interface
	stopcmd('prefix="`cat /var/etc/'.$pinf.'.prefix`"');
	stopcmd('plen="`cat /var/etc/'.$pinf.'.plen`"');
	//stopcmd('wanifname="`cat /var/etc/'.$pinf.'.ifname`"');
	// Remove the address of lan interface
	//stopcmd('ip -6 addr del `ipv6ip -n $prefix -l $plen -h 1`/$plen dev '.$pifname.' > /dev/console');
	if($dhcpopt == "IA-PD" && $pmode != "RA")
	{
		if(strchr($pifname, "ppp") != "")
			stopcmd('ip -6 addr del `ipv6ip -n $prefix -l $plen -h 1`/$plen dev '.$pifnameprev.' > /dev/console');
		else
		{
			if($dhcpc6 == "")//not RA mode
				stopcmd('ip -6 addr del `ipv6ip -n $prefix -l $plen -h 1`/$plen dev '.$pifname.' > /dev/console');
		}
	}
	else
	{
		//stopcmd('ip -6 addr del `ip -f inet6 addr show dev '.$pifname.' scope global | scut -p inet6` dev '.$pifname.' > /dev/console');
	}
	stopcmd('addr="`ip -f inet6 addr show dev '.$ifname.' scope global | scut -p inet6`"');

	stopcmd('ip -6 addr del $addr dev '.$ifname.' > /dev/console');
	stopcmd('ip -6 route del to blackhole $prefix/$plen dev lo');
}

fwrite(a,$START, "# CHILD_INFNAME = [".$CHILD_INFNAME."]\n");
fwrite(a,$STOP,  "# CHILD_INFNAME = [".$CHILD_INFNAME."]\n");

/* These parameter should be valid. */
$inf    = $CHILD_INFNAME;
$infp   = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 0);
$phyinf = query($infp."/phyinf");
$default= query($infp."/defaultroute");
$inetp  = $infp."/inet";
$ifname = PHYINF_getifname($phyinf);

$mode = query($inetp."/ipv6/mode");

if		($mode=="DHCP")		inet_child_dynamic($inf, $ifname, $infp, $inetp);

?>

