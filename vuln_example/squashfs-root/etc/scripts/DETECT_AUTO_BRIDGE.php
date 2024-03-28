<?
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/inf.php";
include "/htdocs/phplib/phyinf.php";

if($START=="1")
{
	$static_ip=1;
	$infp = XNODE_getpathbytarget("", "inf", "uid", "WAN-1", 0);
	if ($infp != "")
	{
		$inet = query($infp."/inet");
		$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, 0);
		$addrtype=query($inetp."/addrtype");
		if($addrtype=="ipv4")
		{
			$static_ip=query($inetp."/ipv4/static");
			
		}
	}
	
	$layout	= query("/device/autobridge");
	if($layout	=="1" && $static_ip=="0")
	{
		/*brdg lan and wan port first
		/* Using WAN MAC address during bridge mode. */
		set("/runtime/device/autobridge","1");
		SHELL_info($START, "LAYOUT: Start bridge layout ...");
		$mac = PHYINF_getmacsetting("BRIDGE-1");
		echo "ip link set eth3 addr ".$mac." \n"; 
		echo "ip link set eth3 up \n";
		
		$ifname="eth3";
		$pidf = "/var/autobridge.pid";
		$hlpr = "/etc/scripts/autobridge.sh";
		$hostname = get("s", "/device/hostname");	
		echo "xmldbc -t auto_bdg_stop:8:\"killall udhcpc\" \n";
		echo "udhcpc -i ".$ifname." -H ".$hostname." -p ".$pidf." -s ".$hlpr." -D 1 -R 3 -S 0 -q \n";
		echo "xmldbc -k auto_bdg_stop \n";
		
		echo "/etc/scripts/delpathbytarget.sh /runtime phyinf uid ETH-1\n";
		echo "ip link set eth3 down\n";
	}
}

return 0;
?>
