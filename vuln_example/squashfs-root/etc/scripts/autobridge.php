#!/bin/sh
<?
include "/htdocs/phplib/xnode.php";
include "/htdocs/phplib/trace.php";
include "/htdocs/phplib/phyinf.php";

$MASK	= ipv4mask2int($SUBNET);
$NETID	= ipv4networkid($IPADDR, $MASK);
echo 'echo AUTOBRIDGE: Got IP '.$IPADDR.', Network '.$NETID.'/'.$MASK.' from the WAN port. > /dev/console\n';
echo 'echo AUTOBRIDGE: Got IP '.$IPADDR.', Network '.$NETID.'/'.$MASK.' from the WAN port. > /var/test_auto\n';
$NET = ipv4networkid($NETID, 16);

$in_range="0";
if($NET=="192.168.0.0")
{
	$in_range="1";
}
else
{
	$NET = ipv4networkid($NETID, 12);	
	if($NET=="172.16.0.0")
	{
		$in_range="1";
	}
	else
	{
		$NET = ipv4networkid($NETID, 8);
		if($NET=="10.0.0.0")
		{	
			$in_range="1";
		}
	}
}

if ( $in_range=="1")
{
	echo "echo !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! > /dev/console\n";
	echo "echo !!!      Got it, switch to bridge mode     !!! > /dev/console\n";
	echo "echo !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! > /dev/console\n";

	echo "echo -n bridge > /var/AUTOBRIDGE.result\n";
	set("/runtime/autobridge/ip",$IPADDR);
	set("/runtime/autobridge/subnet",$SUBNET);

}
else
{	
	echo "echo -n router > /var/AUTOBRIDGE.result\n";
}
?>
exit 0
