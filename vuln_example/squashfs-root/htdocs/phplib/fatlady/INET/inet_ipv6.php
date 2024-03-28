<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/inet.php";
include "/htdocs/phplib/inf.php";

function set_result($result, $node, $message)
{
	$_GLOBALS["FATLADY_result"]	= $result;
	$_GLOBALS["FATLADY_node"]	= $node;
	$_GLOBALS["FATLADY_message"]= $message;
}

function check_ipv6($path, $needgw)
{
	anchor($path);
	$mode = query("mode");

	TRACE_debug("FATLADY: INET_IPV6: mode = ".$mode);
	if ($mode == "STATIC")
	{
		$ip = query("ipaddr");
		$prefix = query("prefix");

		TRACE_debug("FATLADY: INET_IPV6: ip = ".$ip);
		TRACE_debug("FATLADY: INET_IPV6: prefix = ".$prefix);
		if (INET_validv6addr($ip) == 0)		
		{
			set_result("FAILED", $path."/ipaddr", i18n("Invalid IPv6 address"));
			return;
		}
		$type = INET_v6addrtype($ip);
		if($type == "") 
		{
			set_result("FAILED", $path."/ipaddr", i18n("Invalid IPv6 address"));
			return;
		}
		if($type != "UNICAST" && $type != "LINKLOCAL" && $type != "SITELOCAL") 
		{
			set_result("FAILED", $path."/ipaddr", i18n("Not a global/linklocal/sitelocal IPv6 address"));
			return;
		}

		if ($prefix=="")
		{
			set_result("FAILED", $path."/prefix", i18n("No prefix value"));
			return;
		}
		if (isdigit($prefix)=="0")
		{
			set_result("FAILED", $path."/prefix", i18n("Prefix value must be digit number"));
			return;
		}
		if ($prefix <= 0 || $prefix > 128)
		{
			set_result("FAILED", $path."/prefix", i18n("Invalid prefix value"));
			return;
		}

		$routerlft = query("routerlft");
		TRACE_debug("FATLADY: INET_IPV6: routerlft = ".$routerlft);
		if ($routerlft!="")
		{
			if (isdigit($routerlft)=="0")
			{
				set_result("FAILED", $path."/routerlft", i18n("Lifetime value must be digit number"));
				return;
			}
			if ($routerlft < 12 || $routerlft > 5400)
			{
				set_result("FAILED", $path."/routerlft", i18n("Invalid router lifetime value"));
				return;
			}
		}
	
		$preferlft = query("preferlft");
		TRACE_debug("FATLADY: INET_IPV6: preferlft = ".$preferlft);
		if ($preferlft!="")
		{
			if (isdigit($preferlft)=="0")
			{
				set_result("FAILED", $path."/preferlft", i18n("Lifetime value must be digit number"));
				return;
			}
			if ($preferlft < 0)
			{
				set_result("FAILED", $path."/preferlft", i18n("Invalid preferred lifetime value"));
				return;
			}
		}

		$validlft = query("validlft");
		TRACE_debug("FATLADY: INET_IPV6: validlft = ".$validlft);
		if ($validlft!="")
		{
			if (isdigit($validlft)=="0")
			{
				set_result("FAILED", $path."/validlft", i18n("Lifetime value must be digit number"));
				return;
			}
			if ($validlft < 0)
			{
				set_result("FAILED", $path."/validlft", i18n("Invalid valid lifetime value"));
				return;
			}
		}

		if ($preferlft!="" && $validlft=="")
		{
				set_result("FAILED", $path."/validlft", i18n("Don't leave valid lifetime blank"));
				return;
		}
		
		if ($preferlft=="" && $validlft!="")
		{
				set_result("FAILED", $path."/preferlft", i18n("Don't leave preferred lifetime blank"));
				return;
		}

		if ($preferlft!="" && $validlft!="")
		{
			if ($preferlft > $validlft)
			{
				set_result("FAILED", $path."/preferlft", i18n("Preferred lifetime has larger than valid lifetime"));
				return;
			}
		}

		$gw = query("gateway");
		TRACE_debug("FATLADY: INET_IPV6: gw=".$gw);
		if ($gw != "")
		{
			if (INET_validv6addr($gw) == 0)		
			{
				set_result("FAILED", $path."/gateway", i18n("Invalid gateway IPv6 address"));
				return;
			}
		}
	}
	else if ($mode == "6IN4")
	{
		$lip6 = query("ipaddr");
		$rip4 = query($path."/ipv6in4/remote");
		$prefix = query("prefix");

		TRACE_debug("FATLADY: INET_IPV6: local ip6 = ".$lip6);
		TRACE_debug("FATLADY: INET_IPV6: remote ip4 = ".$rip4);
		TRACE_debug("FATLADY: INET_IPV6: prefix = ".$prefix);
		if (INET_validv6addr($lip6) == 0)		
		{
			set_result("FAILED", $path."/ipaddr", i18n("Invalid IPv6 address"));
			return;
		}
		if (INET_validv4addr($rip4) == 0)		
		{
			set_result("FAILED", $path."/ipaddr", i18n("Invalid IPv4 address"));
			return;
		}
		if ($prefix=="")
		{
			set_result("FAILED", $path."/prefix", i18n("No prefix value"));
			return;
		}
		if (isdigit($prefix)=="0")
		{
			set_result("FAILED", $path."/prefix", i18n("Prefix value must be digit number"));
			return;
		}
		if ($prefix <= 0 || $prefix > 128)
		{
			set_result("FAILED", $path."/prefix", i18n("Invalid prefix value"));
			return;
		}
	
		$gw = query("gateway");
		TRACE_debug("FATLADY: INET_IPV6: remote ip6=".$gw);
		if ($gw != "")
		{
			if (INET_validv6addr($gw) == 0)		
			{
				set_result("FAILED", $path."/gateway", i18n("Invalid gateway IPv6 address"));
				return;
			}
		}
	}
	else if ($mode == "TSP")
	{
		$tspc_remote   = query("ipv6in4/remote");
		$tspc_username = query("ipv6in4/tsp/username");
		$tspc_password = query("ipv6in4/tsp/password");
		$tspc_prefix   = query("ipv6in4/tsp/prefix");

		TRACE_debug("FATLADY: INET_IPV6: tspc remote = ".  $tspc_remote);
		TRACE_debug("FATLADY: INET_IPV6: tspc username = ".$tspc_username);
		TRACE_debug("FATLADY: INET_IPV6: tspc password = ".$tspc_password);
		TRACE_debug("FATLADY: INET_IPV6: tspc prefix = ".  $tspc_prefix);

		if (INET_validv4addr($tspc_remote) == 0)		
		{
			set_result("FAILED", $path."/ipv6in4/remote", i18n("Invalid IPv4 address"));
			return;
		}
	}

	$cnt = query("dns/count");
	$i = 0;
	while ($i < $cnt)
	{
		$i++;
		$value = query("dns/entry:".$i);
		TRACE_debug("FATLADY: INET_IPV6: dns".$i."=".$value);
		if (INET_validv6addr($value)==0)
		{
			set_result("FAILED", $path."/dns/entry:".$i, i18n("Invalid DNS IPv6 address"));
			return;
		}
	}

	$mtu = query("mtu");
	TRACE_debug("FATLADY: INET_IPV6: mtu=".$mtu);
	if ($mtu!="")
	{
		if (isdigit($mtu)=="0")
		{
			set_result("FAILED", $path."/mtu",
				i18n("The MTU value is invalid."));
			return;
		}
		if ($mtu<1280) /* RFC 2460 */
		{
			set_result("FAILED", $path."/mtu",
				i18n("The MTU value is too small, the valid value is 1280 ~ 1500."));
			return;
		}
		if ($mtu>1500)
		{
			set_result("FAILED", $path."/mtu",
				i18n("The MTU value is too large, the valid value is 1280 ~ 1500."));
			return;
		}
	}

	set_result("OK","","");
}

TRACE_debug("FATLADY: INET: inetentry=[".$_GLOBALS["FATLADY_INET_ENTRY"]."]");
set_result("FAILED","","");
if ($_GLOBALS["FATLADY_INET_ENTRY"]=="") set_result("FAILED","","No XML document");
else check_ipv6($_GLOBALS["FATLADY_INET_ENTRY"]."/ipv6", $_GLOBALS["FATLADY_INET_NEED_GW"]);
?>
