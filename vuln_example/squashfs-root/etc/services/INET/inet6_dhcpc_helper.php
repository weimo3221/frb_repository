#!/bin/sh
<? /* vi: set sw=4 ts=4: */
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/phyinf.php";

$stsp = XNODE_getpathbytarget("/runtime",  "inf", "uid", $INF, 1);

echo '# inf='.$INF.' inet='.$INET.' inetp='.$INETP.' stsp='.$stsp.'\n';

$cnt = query($INETP.'/ipv6/dns/count');
$mode = query($INETP.'/ipv6/mode');
$dhcpopt = query($INETP.'/ipv6/dhcpopt');
if ($cnt=="") $cnt = 0;
$i = 0;
$servers='';
while ($i < $cnt)
{
	$i++;
	$servers = $servers.query($INETP.'/ipv6/dns/entry:'.$i).' ';
}
echo 'servers='.$servers.';';
?>
xmldbc -X <?=$stsp?>/inet;
xmldbc -s <?=$stsp?>/inet/uid         "<?=$INET?>";
xmldbc -s <?=$stsp?>/inet/addrtype    "ipv6";
xmldbc -s <?=$stsp?>/inet/ipv6/valid  "1";
#xmldbc -s <?=$stsp?>/inet/ipv6/mode   "DHCP";
xmldbc -s <?=$stsp?>/inet/ipv6/mode   "<?=$mode?>";

new_resolv_conf=/var/etc/new_resolv.conf;
echo "" > $new_resolv_conf;

dns_count=`xmldbc -g <?=$INETP?>/ipv6/dns/count`;

echo "# $0" >> $new_resolv_conf;
i=0;
for dns in $servers $new_domain_name_servers; do
	echo "nameserver \"$dns\"" >> $new_resolv_conf;
	i=`expr $i + 1`;
	xmldbc -s <?=$stsp?>/inet/ipv6/dns/entry:$i $dns;
<?
if ($CHILD_STSP != "")
{
	echo '	xmldbc -s '.$CHILD_STSP.'/inet/ipv6/dns/entry:\$i \$dns;';
}
?>
done
xmldbc -s <?=$stsp?>/inet/ipv6/dns/count "$i";
<?
if ($CHILD_STSP != "")
{
	echo 'xmldbc -s '.$CHILD_STSP.'/inet/ipv6/dns/count "\$i";';
}
?>

cat $new_resolv_conf >> /etc/resolv.conf;
rm -f $new_resolv_conf;

echo "========================================" > /dev/console;
#echo "new_addr: $new_addr"                      > /dev/console;
echo "new_pd_prefix: $new_pd_prefix"            > /dev/console;
echo "new_pd_plen: $new_pd_plen"                > /dev/console;
echo "========================================" > /dev/console;

prefix=$new_pd_prefix;
plen=$new_pd_plen;

echo $prefix > /var/etc/<?=$INF?>.prefix;
echo $plen > /var/etc/<?=$INF?>.plen;
#echo <?=$IFNAME?> > /var/etc/<?=$INF?>.ifname;
#prefix="`ipv6ip -n $prefix -l $plen -h 1`";
<?
$infp = XNODE_getpathbytarget("",  "inf", "uid", $INF, 0);
$dhcpc6 = query($infp."/dhcpc6");
if($dhcpopt == "IA-NA" || $dhcpopt == "IA-NA+IA-PD") 
{
	//echo 'prefix="`ip -f inet6 addr show dev '.$IFNAME.' scope global | scut -p inet6 | cut -d\'/\' -f0`";\n';
	//echo 'sleep 2;\n';
	echo 'addr="`ip -f inet6 addr show dev '.$IFNAME.' scope global | scut -p inet6`";\n';
	echo 'prefix="`echo $addr | cut -d\'/\' -f1`";\n';
	echo 'plen="`echo $addr | cut -d\'/\' -f2`";\n';

	echo 'gw="`cat /proc/sys/net/ipv6/conf/'.$IFNAME.'/ra_saddr`";\n';
	echo 'ip -6 route add default via $gw dev '.$IFNAME.';\n';
	echo 'xmldbc -s '.$stsp.'/inet/ipv6/gateway     "$gw";\n';
	echo 'xmldbc -s '.$stsp.'/defaultroute     "1";\n';
}
else if($dhcpopt == "IA-PD" && $mode == "RA") 
{
	//echo 'prefix="`ip -f inet6 addr show dev '.$IFNAME.' scope global | scut -p inet6 | cut -d\'/\' -f0`";\n';
	//echo 'sleep 2;\n';
	echo 'addr="`ip -f inet6 addr show dev '.$IFNAME.' scope global | scut -p inet6`";\n';
	echo 'prefix="`echo $addr | cut -d\'/\' -f1`";\n';
	echo 'plen="`echo $addr | cut -d\'/\' -f2`";\n';
	
	echo 'gw="`cat /proc/sys/net/ipv6/conf/'.$IFNAME.'/ra_saddr`";\n';
	echo 'xmldbc -s '.$stsp.'/inet/ipv6/gateway     "$gw";\n';
	echo 'xmldbc -s '.$stsp.'/defaultroute     "1";\n';
}
else if($dhcpopt == "IA-PD" && $mode == "AUTO") 
{
	echo 'addr="`ip -f inet6 addr show dev '.$IFNAME.' scope global | scut -p inet6`";\n';
	echo 'prefix="`echo $addr | cut -d\'/\' -f1`";\n';
	echo 'plen="`echo $addr | cut -d\'/\' -f2`";\n';
	
	if($dhcpc6 != "")//auto mode,but don't do RA
	{
		echo 'gw="`cat /proc/sys/net/ipv6/conf/'.$IFNAME.'/ra_saddr`";\n';
		echo 'xmldbc -s '.$stsp.'/inet/ipv6/gateway     "$gw";\n';
		echo 'xmldbc -s '.$stsp.'/defaultroute     "1";\n';
	}
}
else
{
	echo 'prefix="ipv6ip -n '.$prefix.' -l '.$plen.' -h 1";\n';
	echo 'gw="`cat /proc/sys/net/ipv6/conf/'.$IFNAME.'/ra_saddr`";\n';
	echo 'ip -6 route add default via $gw dev '.$IFNAME.';\n';
	echo 'xmldbc -s '.$stsp.'/inet/ipv6/gateway     "$gw";\n';
	echo 'xmldbc -s '.$stsp.'/defaultroute     "1";\n';
}
?>

xmldbc -s <?=$stsp?>/inet/ipv6/ipaddr "$prefix";
xmldbc -s <?=$stsp?>/inet/ipv6/prefix "$plen";
UPTIME=`xmldbc -g /runtime/device/uptime`;
xmldbc -s <?=$stsp?>/inet/uptime $UPTIME;


prefixorg=$new_pd_prefix;
if [ -z $prefixorg ]; then
exit 0;
fi

<?
if ($CHILD_STSP != "")
{
	echo 'xmldbc -s '.$CHILD_STSP.'/inet/active     "1";\n';
	echo 'xmldbc -s '.$CHILD_STSP.'/inet/addrtype   "ipv6";\n';
	echo 'xmldbc -s '.$CHILD_STSP.'/inet/ipv6/valid "1";\n';
	echo 'xmldbc -s '.$CHILD_STSP.'/inet/ipv6/mode  "DHCP";\n';
	echo 'xmldbc -s '.$CHILD_STSP.'/inet/uptime $UPTIME;\n';

	echo 'child=`xmldbc -g '.$CHILD_STSP.'/uid`;\n';
	echo 'service INET.CHILD.$child restart;\n';
	echo 'event CHILD.$child.UP;\n';
	echo 'echo 1 > /var/run/CHILD.$child.UP;\n';
}
?>

exit 0;

