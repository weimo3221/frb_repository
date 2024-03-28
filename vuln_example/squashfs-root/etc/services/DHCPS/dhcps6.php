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

function commands($inf, $stsp, $phyinf, $dhcpsp)
{
	startcmd('# dhcps6: inf='.$inf.', stsp='.$stsp.', phyinf='.$phyinf.', dhcps='.$dhcpsp);

	/* get the network info. */
	$ifname = PHYINF_getifname($phyinf);

	$dhcps6_mode    = query($dhcpsp.'/mode');
	$dhcps6_network = query($dhcpsp.'/network');
	$dhcps6_prefix  = query($dhcpsp.'/prefix');
	$dhcps6_start   = query($dhcpsp.'/start');
	$dhcps6_stop    = query($dhcpsp.'/stop');
	$dhcps6_domain  = query($dhcpsp.'/domain');

	$inet_network   = query($stsp.'/inet/ipv6/ipaddr');
	$inet_prefix    = query($stsp.'/inet/ipv6/prefix');

	if ($dhcps6_network == '')
	{
		$network = ipv6networkid($inet_network, $inet_prefix);
		$prefix  = $inet_prefix;
	}
	else
	{
		$network = $dhcps6_network;
		$prefix  = $dhcps6_prefix;
	}

	$domain = $dhcps6_domain;
	$mode   = $dhcps6_mode;
	$start  = ipv6ip($network, $prefix, $dhcps6_start);
	$stop   = ipv6ip($network, $prefix, $dhcps6_stop);

	startcmd('# phyifname='.$ifname.', network='.$network.'/'.$prefix.', domain='.$domain);

	startcmd('#xmldbc -X '.$stsp.'/dhcps6');
	startcmd('xmldbc -s '.$stsp.'/dhcps6/mode "'.    $mode.'"');
	startcmd('xmldbc -s '.$stsp.'/dhcps6/network "'. $network.'"');
	startcmd('xmldbc -s '.$stsp.'/dhcps6/prefix "'.  $prefix.'"');
	startcmd('xmldbc -s '.$stsp.'/dhcps6/start "'.   $start.'"');
	startcmd('xmldbc -s '.$stsp.'/dhcps6/stop "'.    $stop.'"');
	startcmd('xmldbc -s '.$stsp.'/dhcps6/domain "'.  $domain.'"');

	$dns = '';
	$cnt = query($dhcpsp.'/dns/count');
	$i = 0;
	while ($i < $cnt)
	{
		$i++;
		$value = query($dhcpsp.'/dns/entry:'.$i);
		if ($value != "")
		{
			startcmd('xmldbc -s '.$stsp.'/dhcps6/dns/entry:'.$i.' '.$value);
			$dns = $dns.' '.$value;
		}
	}

	$cnt = query($stsp.'/inet/ipv6/dns/count');
	$j = 0;
	while ($j < $cnt)
	{
		$j++;
		$value = query($stsp.'/inet/ipv6/dns/entry:'.$j);
		if ($value != "")
		{
			$n = $i + $j;
			startcmd('xmldbc -s '.$stsp.'/dhcps6/dns/entry:'.$n.' '.$value);
			$dns = $dns.' '.$value;
		}
	}

	if ($dns == '')
	{
		$dns = $inet_network;
		startcmd('xmldbc -s '.$stsp.'/dhcps6/dns/count 1');
		startcmd('xmldbc -s '.$stsp.'/dhcps6/dns/entry:1 '.$dns);
	}
	else
	{
		startcmd('xmldbc -s '.$stsp.'/dhcps6/dns/count '.$n);
	}

	if ($mode == 'STATELESS')
	{
		startcmd('# stateless mode!!!');
		$racfg = '/var/run/radvd.'.$inf.'.conf';
		$routerlft = query($stsp.'/inet/ipv6/routerlft');
		$ralft = $routerlft/3;
		if($routerlft != "")
		{
			fwrite(w, $racfg,
				'# radvd config for '.$inf.'\n'.
				'interface '.$ifname.'\n'.
				'{\n'.
				'	AdvSendAdvert on;\n'.
				'	AdvManagedFlag off;\n'.
				'	AdvOtherConfigFlag on;\n'.
				'	MaxRtrAdvInterval '.$ralft.';\n'.
				'	prefix '.$network.'/'.$prefix.'\n'.
				'	{\n'.
				'		AdvOnLink on;\n'.
				'		AdvAutonomous on;\n'.
				'	};\n'.
				'};\n'
			);
		}
		else
		{
			fwrite(w, $racfg,
				'# radvd config for '.$inf.'\n'.
				'interface '.$ifname.'\n'.
				'{\n'.
				'	AdvSendAdvert on;\n'.
				'	AdvManagedFlag off;\n'.
				'	AdvOtherConfigFlag on;\n'.
				'	MinRtrAdvInterval 3;\n'.
				'	MaxRtrAdvInterval 10;\n'.
				'	prefix '.$network.'/'.$prefix.'\n'.
				'	{\n'.
				'		AdvOnLink on;\n'.
				'		AdvAutonomous on;\n'.
				'	};\n'.
				'};\n'
			);
		}
		startcmd('radvd -C '.$racfg);

                /*dns information via dhcpv6*/
		$cfg = '/var/run/dhcps6.'.$inf.'.conf';
		fwrite(w,$cfg,
			'option domain-name-servers '.$dns.';\n'.
			'option domain-name "'.$domain.'";\n'.
			'interface '.$ifname.' {\n'.
			'	allow rapid-commit;\n'.
			'};\n'.
			'\n'.
		);
		startcmd('dhcp6s -c '.$cfg.' '.$ifname);
	}
	else /* STATEFUL */
	{
		startcmd('# stateful mode!!!');
		$racfg = '/var/run/radvd.'.$inf.'.conf';
		$preferlft = query($stsp.'/inet/ipv6/preferlft');
		$validlft = query($stsp.'/inet/ipv6/validlft');
		fwrite(w,$racfg,
			'# radvd config for '.$inf.'\n'.
			'interface '.$ifname.'\n'.
			'{\n'.
			'	AdvSendAdvert on;\n'.
			'	AdvManagedFlag on;\n'.
			'	AdvOtherConfigFlag on;\n'.
			'	MinRtrAdvInterval 3;\n'.
			'	MaxRtrAdvInterval 10;\n'.
			'	prefix '.$network.'/'.$prefix.'\n'.
			'	{\n'.
			'		AdvOnLink on;\n'.
			'		AdvAutonomous off;\n'.
			'	};\n'.
			'};\n'
			);
		startcmd('radvd -C '.$racfg);

		$cfg = '/var/run/dhcps6.'.$inf.'.conf';
		if($preferlft != "")
		{
			$plft = query($stsp.'/inet/ipv6/preferlft');
			$vlft = query($stsp.'/inet/ipv6/validlft');
			fwrite(w,$cfg,
				'option domain-name-servers '.$dns.';\n'.
				'option domain-name "'.$domain.'";\n'.
				'interface '.$ifname.' {\n'.
				'	allow rapid-commit;\n'.
				'	preference 128;\n'.
				'	address-pool dhcpv6pool '.$plft.' '.$vlft.';\n'.
				'};\n'.
				'\n'.
				'pool dhcpv6pool {\n'.
				'	range '.$start.' to '.$stop.';\n'.
				'};\n'
			);
		}
		else
		{
			fwrite(w,$cfg,
				'option domain-name-servers '.$dns.';\n'.
				'option domain-name "'.$domain.'";\n'.
				'interface '.$ifname.' {\n'.
				'	allow rapid-commit;\n'.
				'	preference 128;\n'.
				'	address-pool dhcpv6pool 3600 7200;\n'.
				'};\n'.
				'\n'.
				'pool dhcpv6pool {\n'.
				'	range '.$start.' to '.$stop.';\n'.
				'};\n'
			);
		}
		startcmd('dhcp6s -c '.$cfg.' '.$ifname);
	}

	startcmd('exit 0');

	if ($mode == "STATELESS")
	{
		stopcmd('killall dhcp6s');
		stopcmd('killall radvd');
	}
	else
	{
		stopcmd('killall dhcp6s');
		stopcmd('killall radvd');
	}
	stopcmd('xmldbc -X '.$stsp.'/dhcps6');
}

function dhcps6setup($name)
{
	/* Get the interface */
	$stsp = XNODE_getpathbytarget("/runtime", "inf", "uid", $name, 0);
	$infp = XNODE_getpathbytarget("", "inf", "uid", $name, 0);
	startcmd('# '.$name.': stsp='.$stsp.' infp='.$infp);
	if ($stsp=="" || $infp=="")
	{
		startcmd('# dhcps6setup: ('.$name.') no interface.');
		return dhcperr('1');
	}
	/* Is this interface active ? */
	$inet = query($infp.'/inet');
	$inetp = XNODE_getpathbytarget("/inet", "entry", "uid", $inet, 0);
	$inet_uid = query($inetp.'/uid');

	if ($inet_uid != "")
	{
		$active	= query($infp."/active");
		$dhcps	= query($infp."/dhcps6");
	}
	else
	{
		$active	= query($stsp."/active");
		$dhcps	= query($infp."/dhcps6");
	}

	if ($active!="1" || $dhcps=="")
	{
		startcmd('# dhcps6setup: ('.$name.') not active.');
		return dhcperr('2');
	}

	/* Check runtime status */
	if (query($stsp."/inet/addrtype")!="ipv6" || query($stsp."/inet/ipv6/valid")!="1")
	{
		startcmd('# dhcps6setup: ('.$name.') invalid IPv6.');
		return dhcperr('3');
	}

	/* Get the physical interface */
	$phyinf = query($infp."/phyinf");
	if ($phyinf=="")
	{
		startcmd('# dhcps6setup: ('.$name.') no phyinf.');
		return dhcperr('4');
	}

	/* Get the profile */
	$dhcpsp = XNODE_getpathbytarget("/dhcps6", "entry", "uid", $dhcps, 0);
	if ($dhcpsp=="")
	{
		startcmd('# dhcps6setup: ('.$name.') no profile.');
		return dhcperr('5');
	}

	/* */
	commands($name, $stsp, $phyinf, $dhcpsp);
}
?>

