<?
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";

function startcmd($cmd)	{fwrite("a", $_GLOBALS["START"], $cmd."\n");}
function stopcmd($cmd)	{fwrite("a", $_GLOBALS["STOP"], $cmd."\n");}
function dhcperr($errno)
{
	startcmd('exit '.$errno);
	stopcmd( 'exit '.$errno);
	return $errno;
}

function dhcpc6setup($name)
{
	$inf    = $name;
	$infp   = XNODE_getpathbytarget("", "inf", "uid", $inf, 0);
	$child  = query($infp."/dhcpc6");
	$phyinf = query($infp."/phyinf");
        $inet   = query($infp."/inet");
	$inetp  = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, 0);
	$ifname = PHYINF_getifname($phyinf);

	if ($child != "")
	{
		$child_ifname = PHYINF_getphyinf($child);
		$child_infp   = XNODE_getpathbytarget("", "inf", "uid", $child, 0);
		$child_stsp   = XNODE_getpathbytarget("/runtime", "inf", "uid", $child, 1);
	}
        
	$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 1);

	startcmd('# dhcpc6setup('.$inf.','.$ifname.','.$inetp.')');

	$dhcp6c_helper = '/var/servd/'.$inf.'-dhcp6c.sh';
	$dhcp6c_pid    = '/var/servd/'.$inf.'-dhcp6c.pid';
	$dhcp6c_cfg    = '/var/servd/'.$inf.'-dhcp6c.conf';
	$hlper         = '/etc/services/INET/inet6_dhcpc_helper.php';
	$hostname      = get('s', '/device/hostname');

	fwrite(w, $dhcp6c_cfg,
		'interface '.$ifname.' {\n'.
		'	send ia-pd 0;\n'.
		'	request domain-name-servers;\n'.
		'	request domain-name;\n'.
		'	script "'.$dhcp6c_helper.'";\n'.
		'};\n'.
		'id-assoc pd {\n'.
		//'	prefix-interface '.$child_ifname.' {\n'.
		//'	sla-id 1;\n'.
		//'	};\n'.
		'};\n'
	);	

	if ($child != "")
	{
		$child_stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $child, 1);
		startcmd('xmldbc -P '.$hlper.' -V IFNAME='.$ifname.' -V INF='.$inf.' -V INET='.$inet.' -V INETP='.$inetp.' -V CHILD_STSP='.$child_stsp.' > '.$dhcp6c_helper);
	}
	else
	{
		startcmd('xmldbc -P '.$hlper.' -V IFNAME='.$ifname.' -V INF='.$inf.' -V INET='.$inet.' -V INETP='.$inetp.' > '.$dhcp6c_helper);
	}
	startcmd('chmod +x '.$dhcp6c_helper);
	startcmd('dhcp6c -c '.$dhcp6c_cfg.' -p '.$dhcp6c_pid.' '.$ifname);

	stopcmd('if [ -f '.$dhcp6c_pid.' ]; then');
	stopcmd('	PID=`cat '.$dhcp6c_pid.'`;');
	stopcmd('	if [ "$PID" != 0 ]; then\n');
	stopcmd('		kill -9 $PID;');
	stopcmd('	fi');
	stopcmd('fi');

	if ($child != "")
	{
		startcmd('xmldbc -s '.$child_stsp.'/uid    "'.$child.'"');
		startcmd('xmldbc -s '.$child_stsp.'/parent "'.$inf.'"');
		startcmd('xmldbc -s '.$child_stsp.'/active "1"');
		startcmd('xmldbc -s '.$child_stsp.'/phyinf "'.query($child_infp.'/phyinf').'"');

		startcmd('service INET.CHILD.'.$child.' alias INF.CHILD.'.$child);
		//startcmd('event CHILD.'.$child.'.UP   add "service INFSVCS.'.$child.' start"');
		startcmd('event CHILD.'.$child.'.UP   add "service INFSVCS.'.$child.' restart"');
		startcmd('event CHILD.'.$child.'.DOWN add "service INFSVCS.'.$child.' stop"');

		stopcmd('event CHILD.'.$child.'.DOWN');
		stopcmd('rm -f /var/run/CHILD.'.$child.'.UP');
		stopcmd('service INET.CHILD.'.$child.' stop');
		stopcmd('xmldbc -X '.$child_stsp);
	}

	//startcmd('event '.$inf.'.UP');
	startcmd('echo 1 > /var/run/'.$inf.'.UP');

	//stopcmd('event '.$inf.'.DOWN');
	stopcmd('addr="`ip -f inet6 addr show dev '.$ifname.' scope global | scut -p inet6`"');
	stopcmd('ip -6 addr del $addr dev '.$ifname);

	stopcmd('rm -f /var/run/'.$inf.'.UP');
	stopcmd('xmldbc -X '.$stsp.'/inet');
}
?>

