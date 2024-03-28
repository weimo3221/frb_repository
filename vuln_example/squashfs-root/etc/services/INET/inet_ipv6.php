<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";

function startcmd($cmd)	{fwrite(a,$_GLOBALS["START"], $cmd."\n");}
function stopcmd($cmd)	{fwrite(a,$_GLOBALS["STOP"], $cmd."\n");}

function inet_ipv6_ll($inf, $ifname, $rtphyinfp, $inet, $inetp)
{
	startcmd("# inet_ipv6_ll(".$inf.",".$ifname.",".$rtphyinfp.",".$inet.",".$inetp.")");

	$ipaddr	= query($rtphyinfp."/ipv6ll");
	if ($ipaddr == "")
	{
		startcmd("exit 9");
		return;
	}

	/* just write status */
	$stsp	= XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 1);
	startcmd('xmldbc -X '.$stsp.'/inet');
	startcmd('xmldbc -s '.$stsp.'/inet/uid '.$inet);
	startcmd('xmldbc -s '.$stsp.'/inet/addrtype ipv6');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/valid 1');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/mode LL');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/ipaddr "'.$ipaddr.'"');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/prefix 64');
	startcmd('UPTIME=`xmldbc -g /runtime/device/uptime`');
	startcmd('xmldbc -s '.$stsp.'/inet/uptime $UPTIME');
	startcmd('event '.$inf.'.UP');
	startcmd('echo 1 > /var/run/'.$inf.'.UP');

	stopcmd('xmldbc -X '.$stsp.'/inet');
	stopcmd('event '.$inf.'.DOWN');
	stopcmd('rm -f /var/run/'.$inf.'.UP');
}

function inet_ipv6_static($inf, $ifname, $inet, $inetp, $default)
{
	startcmd("# inet_start_ipv6_static(".$inf.",".$ifname.",".$inet.",".$inetp.")");

	anchor($inetp."/ipv6");
	$ipaddr	= query("ipaddr");
	$prefix	= query("prefix");
	$gw     = query("gateway");
	$routerlft	= query("routerlft");
	$preferlft	= query("preferlft");
	$validlft	= query("validlft");

	/* set static ip */
	startcmd('ip -6 addr add '.$ipaddr.'/'.$prefix.' dev '.$ifname);
	/* write status */
	$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 1);
	startcmd('xmldbc -X '.$stsp.'/inet');
	startcmd('xmldbc -s '.$stsp.'/inet/uid '.$inet);
	startcmd('xmldbc -s '.$stsp.'/inet/addrtype ipv6');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/valid 1');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/mode STATIC');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/ipaddr "'.$ipaddr.'"');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/prefix "'.$prefix.'"');
	if($routerlft !="")
		startcmd('xmldbc -s '.$stsp.'/inet/ipv6/routerlft '.$routerlft);
	if($preferlft != "")	
		startcmd('xmldbc -s '.$stsp.'/inet/ipv6/preferlft '.$preferlft);
	if($validlft != "")	
		startcmd('xmldbc -s '.$stsp.'/inet/ipv6/validlft '.$validlft);
	startcmd('UPTIME=`xmldbc -g /runtime/device/uptime`');
	startcmd('xmldbc -s '.$stsp.'/inet/uptime $UPTIME');
/*
	if(cut($inf, 0, "-") == "LAN")
	{
		if($routerlft != "")	
			startcmd('xmldbc -s '.$stsp.'/inet/ipv6/routerlft '.$routerlft);
		if($preferlft != "")	
			startcmd('xmldbc -s '.$stsp.'/inet/ipv6/preferlft '.$preferlft);
		if($validlft != "")	
			startcmd('xmldbc -s '.$stsp.'/inet/ipv6/validlft '.$validlft);
	}
*/
	if ($gw!="")
	{
		/* set gateway & write status */
		if ($default == "1")
		{
			startcmd('ip -6 route add default via '.$gw.' dev '.$ifname);
		}
		else
		{	/* should we need set routing rules manually for IPv6 ?? */
			//$prefixid = ipv6prefixid($ipaddr,$prefix);
			//fwrite(a,$_GLOBALS["START"],'ip -6 route add '.$prefixid.'/'.$prefix.' dev '.$inf.' metric 256'.'\n');
			startcmd('ip -6 route add '.$ipaddr.'/'.$prefix.' via '.$gw.' dev '.$ifname);
		}
		startcmd('xmldbc -s '.$stsp.'/inet/ipv6/gateway "'.$gw.'"');
	}

	$cnt = query("dns/count");
	if ($cnt=="") $cnt = 0;
	$i = 0;
	while ($i < $cnt)
	{
		$i++;
		$value = query("dns/entry:".$i);
		/* set dns & write status */
		if ($value != "") startcmd('xmldbc -a '.$stsp.'/inet/ipv6/dns "'.$value.'"');
	}
    startcmd('sleep 1');
	startcmd('event '.$inf.'.UP');
	startcmd('echo 1 > /var/run/'.$inf.'.UP');

	/* Stop script */
	stopcmd('xmldbc -X '.$stsp.'/inet');
	stopcmd('ip -6 addr del '.$ipaddr.'/'.$prefix.' dev '.$ifname);
	if ($gw!="")
	{
		/* set gateway & write status */
		if ($default == "1")
		{
			stopcmd('ip -6 route del default via '.$gw.' dev '.$ifname);
		}
		else
		{
			/* should we need set routing rules manually for IPv6 ?? */
			//$prefixid = ipv6prefixid($ipaddr,$prefix);
			//fwrite(a,$_GLOBALS["START"],'ip -6 route add '.$prefixid.'/'.$prefix.' dev '.$inf.' metric 256'.'\n');
			stopcmd('ip -6 route del '.$ipaddr.'/'.$prefix.' via '.$gw.' dev '.$ifname);
		}
	}
	stopcmd('event '.$inf.'.DOWN');
	stopcmd('rm -f /var/run/'.$inf.'.UP');
}

function inet_ipv6_dynamic($inf, $ifname, $inet, $inetp)
{
	$infp = XNODE_getpathbytarget("", "inf", "uid", $inf, 0);
	$child = query($infp."/child");
	if ($child != "")
	{
		$child_ifname = PHYINF_getphyinf($child);
		$child_infp = XNODE_getpathbytarget("", "inf", "uid", $child, 0);
		$child_stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $child, 1);
	}
	$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 1);

	startcmd('# inet_start_ipv6_dynamic('.$inf.','.$ifname.','.$inetp.')');

	$dhcp6c_helper  = '/var/servd/'.$inf.'-dhcp6c.sh';
    $dhcp6c_pid		= '/var/servd/'.$inf.'-dhcp6c.pid';
	$dhcp6c_cfg		= '/var/servd/'.$inf.'-dhcp6c.conf';
	//$event			= '/etc/events/INET6-DHCP.sh';
	$hlper			= '/etc/services/INET/inet6_dhcpc_helper.php';
	//$cfg			= '/etc/services/DHCPV6/inet6_dhcpc_cfg.php';
    $hostname		= get('s', '/device/hostname');

/*
	fwrite(w, $dhcp6c_cfg,
		'interface '.$ifname.' {\n'.
		'	send ia-na 0;\n'.
		'	request domain-name-servers;\n'.
		'	request domain-name;\n'.
		'	script  "'.$dhcp6c_helper.'";\n'.
		'};\n'.
		'id-assoc na {\n'.
		'};\n'
	);
*/
/*
	fwrite(w, $dhcp6c_cfg,
		'interface '.$ifname.' {\n'.
		'	send ia-pd 0;\n'.
		'	request domain-name-servers;\n'.
		'	request domain-name;\n'.
		'	script  "'.$dhcp6c_helper.'";\n'.
		'};\n'.
		'id-assoc pd {\n'.
		//'	prefix-interface '.$child_ifname.' {\n'.
		//'		sla-id 1;\n'.
		//'		sla-len 1;\n'.
		//'	};\n'.
		'};\n'
	);
*/
	$dhcpopt = query($inetp."/ipv6/dhcpopt");
	if($dhcpopt == "IA-PD")
	{
		fwrite(w, $dhcp6c_cfg,
			'interface '.$ifname.' {\n'.
			'	send ia-pd 0;\n'.
			'	request domain-name-servers;\n'.
			'	request domain-name;\n'.
			'	script  "'.$dhcp6c_helper.'";\n'.
			'};\n'.
			'id-assoc pd {\n'.
			//'	prefix-interface '.$child_ifname.' {\n'.
			//'		sla-id 1;\n'.
			//'		sla-len 1;\n'.
			//'	};\n'.
			'};\n'
		);
	}
	else if($dhcpopt == "IA-NA+IA-PD")
	{
		fwrite(w, $dhcp6c_cfg,
			'interface '.$ifname.' {\n'.
			'	send ia-na 0;\n'.
			'	send ia-pd 0;\n'.
			'	request domain-name-servers;\n'.
			'	request domain-name;\n'.
			'	script  "'.$dhcp6c_helper.'";\n'.
			'};\n'.
			'id-assoc na {\n'.
			'};\n'.
			'id-assoc pd {\n'.
				//'	prefix-interface '.$child_ifname.' {\n'.
				//'		sla-id 1;\n'.
				//'		sla-len 1;\n'.
				//'	};\n'.
			'};\n'
		);
	}
	else if($dhcpopt == "IA-NA")
	{
		fwrite(w, $dhcp6c_cfg,
			'interface '.$ifname.' {\n'.
			'	send ia-na 0;\n'.
			'	request domain-name-servers;\n'.
			'	request domain-name;\n'.
			'	script  "'.$dhcp6c_helper.'";\n'.
			'};\n'.
			'id-assoc na {\n'.
			'};\n'
		);
	}

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
	//startcmd('dhcp6c -c '.$dhcp6c_cfg.' -p '.$dhcp6c_pid.' '.$ifname);
	$phyinf = query($infp."/phyinf");
	//startcmd("phyinf is ".$phyinf);
	if(cut($phyinf, 0, ".") != "PPP")
		startcmd('dhcp6c -c '.$dhcp6c_cfg.' -p '.$dhcp6c_pid.' -t LL '.$ifname);//DUID-TYPE: LL
	else
	{
		$pifname = cut($phyinf, 1, ".");//PPP.WAN-3
		$pinfp = XNODE_getpathbytarget("", "inf", "uid", $pifname, 0);
		$pphyinf = query($pinfp."/phyinf");
		$pifname = PHYINF_getifname($pphyinf);
		startcmd('dhcp6c -c '.$dhcp6c_cfg.' -p '.$dhcp6c_pid.' -t LL -o '.$pifname.' '.$ifname);//DUID-TYPE: LL
	}

	if($dhcpopt != "IA-PD")
		stopcmd('ip -6 addr del `ip -f inet6 addr show dev '.$ifname.' scope global | scut -p inet6` dev '.$ifname);

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

	startcmd('event '.$inf.'.UP');
	startcmd('echo 1 > /var/run/'.$inf.'.UP');

	//stopcmd('ip -6 route del default dev '.$ifname);
	if(cut($phyinf, 0, ".") != "PPP")
	{
		stopcmd('ip -6 route del default dev '.$ifname);
	}
	stopcmd('event '.$inf.'.DOWN');
	stopcmd('rm -f /var/run/'.$inf.'.UP');
	stopcmd('xmldbc -X '.$stsp.'/inet');
}

function inet_ipv6_ra($inf, $ifname, $inet, $inetp) 
{
	startcmd('# inet_start_ipv6_ra('.$inf.','.$ifname.','.$inetp.')');
    //config as host to accept ra and not send rs
	startcmd('echo 0 > /proc/sys/net/ipv6/conf/'.$ifname.'/forwarding');
	startcmd('echo 0 > /proc/sys/net/ipv6/conf/'.$ifname.'/router_solicitations');

	//move to service of inf
    //start dhcp-pd if necessary
	//inet_ipv6_dynamic($inf, $ifname, $inet, $inetp);

	startcmd('event '.$inf.'.UP');
	startcmd('echo 1 > /var/run/'.$inf.'.UP');

	stopcmd('event '.$inf.'.DOWN');
	stopcmd('rm -f /var/run/'.$inf.'.UP');
	stopcmd('echo 1 > /proc/sys/net/ipv6/conf/'.$ifname.'/forwarding');
	stopcmd('echo 3 > /proc/sys/net/ipv6/conf/'.$ifname.'/router_solicitations');
}


function inet_ipv6_6in4($inf, $inet, $inetp, $default)
{
	startcmd("# inet_start_ipv6_6in4(".$inf.",".$inet.",".$inetp.")");

	anchor($inetp."/ipv6");
	$ipaddr	= query("ipaddr");
	$prefix	= query("prefix");
	$gw     = query($inetp."/ipv6/ipv6in4/remote");

	/* set 6in4 tunnel */
    startcmd('ip tunnel add Tun6in4 mode sit remote '.$gw.' ttl 255');
    startcmd('ip link set Tun6in4 up');
    startcmd('echo 0 > /proc/sys/net/ipv6/conf/Tun6in4/disable_ipv6');
	startcmd('ip -6 addr add '.$ipaddr.'/'.$prefix.' dev Tun6in4');
	/* write status */
	$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 1);
	startcmd('xmldbc -X '.$stsp.'/inet');
	startcmd('xmldbc -s '.$stsp.'/inet/uid '.$inet);
	startcmd('xmldbc -s '.$stsp.'/inet/addrtype ipv6');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/valid 1');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/mode 6IN4');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/ipaddr "'.$ipaddr.'"');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/prefix "'.$prefix.'"');
	startcmd('UPTIME=`xmldbc -g /runtime/device/uptime`');
	startcmd('xmldbc -s '.$stsp.'/inet/uptime $UPTIME');

	if ($gw!="")
	{
		/* set gateway & write status */
		if ($default == "1")
		{
			startcmd('ip -6 route add ::/0 dev Tun6in4');
		}
		else
		{	/* should we need set routing rules manually for IPv6 ?? */
			//$prefixid = ipv6prefixid($ipaddr,$prefix);
			//fwrite(a,$_GLOBALS["START"],'ip -6 route add '.$prefixid.'/'.$prefix.' dev '.$inf.' metric 256'.'\n');
			//startcmd('ip -6 route add '.$ipaddr.'/'.$prefix.' via '.$gw.' dev '.$ifname);
		}
		startcmd('xmldbc -s '.$stsp.'/inet/ipv6/gateway "'.$gw.'"');
	}

	$cnt = query("dns/count");
	if ($cnt=="") $cnt = 0;
	$i = 0;
	while ($i < $cnt)
	{
		$i++;
		$value = query("dns/entry:".$i);
		/* set dns & write status */
		if ($value != "") startcmd('xmldbc -a '.$stsp.'/inet/ipv6/dns "'.$value.'"');
	}
	startcmd('event '.$inf.'.UP');
	startcmd('echo 1 > /var/run/'.$inf.'.UP');

	/* Stop script */
	stopcmd('xmldbc -X '.$stsp.'/inet');
	stopcmd('ip -6 addr del '.$ipaddr.'/'.$prefix.' dev Tun6in4');
	if ($gw!="")
	{
		/* set gateway & write status */
		if ($default == "1")
		{
			stopcmd('ip -6 route del ::/0 dev Tun6in4');
            stopcmd('ip link set Tun6in4 down');
            stopcmd('ip tunnel del Tun6in4');
		}
		else
		{
			/* should we need set routing rules manually for IPv6 ?? */
			//$prefixid = ipv6prefixid($ipaddr,$prefix);
			//fwrite(a,$_GLOBALS["START"],'ip -6 route add '.$prefixid.'/'.$prefix.' dev '.$inf.' metric 256'.'\n');
			//stopcmd('ip -6 route del '.$ipaddr.'/'.$prefix.' via '.$gw.' dev '.$ifname);
		}
	}
	stopcmd('event '.$inf.'.DOWN');
	stopcmd('rm -f /var/run/'.$inf.'.UP');
}

function inet_ipv6_tspc($inf, $inet, $inetp, $default)
{
	$ipaddr = "";
	$prefix = "";
	anchor($inetp."/ipv6/ipv6in4");
	$remote	= query("remote");
	$userid	= query("tsp/username");
	$passwd = query("tsp/password");
	$prelen = query("tsp/prefix");
	$tspc_dir  = '/var/etc/tspc';
	$tspc_conf = 'tspc-'.$inf.'.conf';
	$tspc_sh   = 'tspc_helper-'.$inf;

	/* write status */
	$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 1);
	startcmd('xmldbc -X '.$stsp.'/inet');
	startcmd('xmldbc -s '.$stsp.'/inet/uid '.$inet);
	startcmd('xmldbc -s '.$stsp.'/inet/addrtype ipv6');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/valid 1');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/mode TSP');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/ipaddr "'.$ipaddr.'"');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/prefix "'.$prefix.'"');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/ipv6in4/remote "'.$remote.'"');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/ipv6in4/tsp/username "'.$userid.'"');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/ipv6in4/tsp/password "'.$passwd.'"');
	startcmd('xmldbc -s '.$stsp.'/inet/ipv6/ipv6in4/tsp/prefix "'.$prelen.'"');
	startcmd('UPTIME=`xmldbc -g /runtime/device/uptime`');
	startcmd('xmldbc -s '.$stsp.'/inet/uptime $UPTIME');
	

	$laninfp = XNODE_getpathbytarget("", "inf", "uid", "LAN-1", 0);
	$lanphyinf = query($laninfp."/phyinf");
	$lanifname = PHYINF_getifname($lanphyinf);
	$lanstsp = XNODE_getpathbytarget("/runtime", "inf", "uid", "LAN-1", 0);

	/* Start script */
	startcmd("# inet_ipv6_tspc(".$inf.",".$inet.",".$inetp.")");
	startcmd('mkdir -p '.$tspc_dir.'/template'); 
	startcmd('xmldbc -P /etc/services/INET/tspc_conf.php -V TSPC_DIR="'.$tspc_dir.'"'
		.' -V REMOTE="'.$remote.'"'
		.' -V USERID="'.$userid.'"'
		.' -V PASSWD="'.$passwd.'"'
		.' -V HELPER="'.$tspc_sh.'"'
		.' -V HOMEIF="'.$lanifname.'"'
		.' -V PRELEN="'.$prelen.'"'
		.' > '.$tspc_dir.'/'.$tspc_conf);
	startcmd('xmldbc -P /etc/services/INET/tspc_helper.php'
		.' -V STSP="'.$stsp.'"'
		.' -V LANSTSP="'.$lanstsp.'"'
		.' > '.$tspc_dir.'/template/'.$tspc_sh.'.sh');
	startcmd('chmod 777 '.$tspc_dir.'/template/'.$tspc_sh.'.sh');
	startcmd('/usr/sbin/ip tuntap add dev tun mode tun');
	startcmd('tspc -vvvv -f '.$tspc_dir.'/'.$tspc_conf);
	startcmd('event '.$inf.'.UP');
	startcmd('echo 1 > /var/run/'.$inf.'.UP');

	
	/* Stop script */
	stopcmd('killall tspc');
	stopcmd('/usr/sbin/ip link set tun down');
	stopcmd('/usr/sbin/ip tuntap del dev tun mode tun');
	stopcmd('rm -rf '.$tspc_dir);
	stopcmd('event '.$inf.'.DOWN');
	stopcmd('rm -f /var/run/'.$inf.'.UP');
	stopcmd('xmldbc -X '.$stsp.'/inet');
}

function inet_ipv6_auto($inf, $infp, $ifname, $inet, $inetp) 
{
	startcmd('# inet_start_ipv6_auto('.$inf.','.$infp.','.$ifname.','.$inetp.')');

	$ra_mflag	= '/proc/sys/net/ipv6/conf/'.$ifname.'/ra_mflag';
	$ra_oflag	= '/proc/sys/net/ipv6/conf/'.$ifname.'/ra_oflag';
	$dhcpoptp	= $inetp.'/ipv6/dhcpopt';
	$dhcpopt	= query($inetp."/ipv6/dhcpopt"); 
	if(isfile($ra_mflag)==1)
	{
		//startcmd('echo Found the file > /dev/console');
		$mflag = fread("r",$ra_mflag);
		//startcmd('echo mflag is '.$mflag.' > /dev/console');
		if(strtoul($mflag,10) == 0)//Stateless
		{
			//startcmd('echo mflag is 0 > /dev/console');
			if($dhcpopt != "")
			{
				startcmd('xmldbc -s '.$infp.'/child ""');
				startcmd('xmldbc -s '.$dhcpoptp.' IA-PD');
			}
		 	inet_ipv6_ra($inf, $ifname, $inet, $inetp);
		}
		else if(strtoul($mflag,10) == "1")//DHCP
		{
			if($dhcpopt != "")
			{
				startcmd('xmldbc -s '.$infp.'/dhcpc6 ""');
				startcmd('xmldbc -s '.$dhcpoptp.' IA-NA+IA-PD');
			}
			inet_ipv6_dynamic($inf, $ifname, $inet, $inetp);
		}
		else	startcmd('xmldbc -t reconn.'.$inf.':5:"service INET.'.$inf.' restart"');//re-schedule
	}
}

/* IPv6 *********************************************************/
fwrite(a,$START, "# INFNAME = [".$INET_INFNAME."]\n");
fwrite(a,$STOP,  "# INFNAME = [".$INET_INFNAME."]\n");

/* These parameter should be valid. */
$inf    = $INET_INFNAME;
$infp   = XNODE_getpathbytarget("", "inf", "uid", $inf, 0);
$phyinf = query($infp."/phyinf");
$default= query($infp."/defaultroute");
$inet   = query($infp."/inet");
$inetp  = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, 0);
$ifname = PHYINF_getifname($phyinf);
$rtphyinfp	= XNODE_getpathbytarget("/runtime", "phyinf", "uid", $phyinf, 0);

/* Create the runtime inf. Set phyinf. */
$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $inf, 1);
set($stsp."/phyinf", $phyinf);
set($stsp."/defaultroute", $default);

$mode = query($inetp."/ipv6/mode");
if		($mode=="LL")				inet_ipv6_ll($inf, $ifname, $rtphyinfp, $inet, $inetp);
else if	($mode=="STATIC")			inet_ipv6_static($inf, $ifname, $inet, $inetp, $default);
else if	($mode=="AUTO")				inet_ipv6_auto($inf, $infp, $ifname, $inet, $inetp, $default);
else if	($mode=="DHCP")				inet_ipv6_dynamic($inf, $ifname, $inet, $inetp);
else if	($mode=="RA")				inet_ipv6_ra($inf, $ifname, $inet, $inetp);
else if	($mode=="6IN4")	    		inet_ipv6_6in4($inf, $inet, $inetp, $default);
else if	($mode=="TSP")				inet_ipv6_tspc($inf, $inet, $inetp, $default);
?>

