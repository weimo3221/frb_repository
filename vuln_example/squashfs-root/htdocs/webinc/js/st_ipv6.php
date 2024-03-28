<script type="text/javascript">
function Page() {}
Page.prototype =
{
	services: "<?
		$layout = query("/runtime/device/layout");
	
		echo "RUNTIME.PHYINF,";
		if ($layout=="router")
			echo "INET.WAN-1,INET.WAN-3,INET.WAN-4,INET.LAN-4,RUNTIME.INF.LAN-4,RUNTIME.INF.WAN-3,RUNTIME.INF.WAN-4";
		else
			echo "RUNTIME.INF.BRIDGE-1";
		?>",
	OnLoad: function(){},
	OnUnload: function() {},
	OnSubmitCallback: function ()
	{
	},
	InitValue: function(xml)
	{
		PXML.doc = xml;
		//PXML.doc.dbgdump();
<?
		echo "\t\tif (\"".$layout."\"==\"router\")";
?>
		{
			if (!this.InitWAN()) return false;
			if (!this.InitLAN()) return false;
		}
		else
		{
			OBJ("ipv6_bridge").style.display = "block";
			OBJ("ipv6").style.display 		 = "none";
			OBJ("ipv6_client").style.display = "none";
		}

		return true;
	},
	PreSubmit: function()
	{
	},
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////
	waninetp: null,
	rwanphyp: null,
	rlanphyp: null,
	wanip: null,
	lanip: null,
	inetp: null,
	prefix: null,
	
	InitWAN: function ()
	{
		var wan	= PXML.FindModule("INET.WAN-4");
		var wan1 = PXML.FindModule("INET.WAN-1");
		var wan3 = PXML.FindModule("INET.WAN-3");
		var rphy = PXML.FindModule("RUNTIME.PHYINF");
		var rwan = PXML.FindModule("RUNTIME.INF.WAN-4");
		var is_ppp6 = 0;
		var is_ll = 0;
		var wan3inetuid = XG(wan3+"/inf/inet");
		var wan3inetp = GPBT(wan3+"/inet", "entry", "uid", wan3inetuid, false);
		if(XG(wan3inetp+"/addrtype") == "ppp6")
		{
			wan = PXML.FindModule("INET.WAN-3");
			rwan = PXML.FindModule("RUNTIME.INF.WAN-3");
			is_ppp6 = 1;
		}
		var waninetuid = XG  (wan+"/inf/inet");
		var wanphyuid = XG  (wan+"/inf/phyinf");
		this.waninetp = GPBT(wan+"/inet", "entry", "uid", waninetuid, false);
		this.rwanphyp = GPBT(rphy+"/runtime", "phyinf", "uid", wanphyuid, false);
		if(XG(this.waninetp+"/ipv6/mode") == "")
		{
			wan = PXML.FindModule("INET.WAN-3");
			rwan = PXML.FindModule("RUNTIME.INF.WAN-3");
			is_ll = 1;
		}
		var str_networkstatus = str_Disconnected = "<?echo i18n("Disconnected");?>";
		var str_Connected = "<?echo i18n("Connected");?>";
    		var wancable_status=0;
		if ((!this.waninetp))
		{
			BODY.ShowAlert("InitWAN() ERROR!!!");
			return false;
		}

		if((XG  (this.rwanphyp+"/linkstatus")!="0")&&(XG  (this.rwanphyp+"/linkstatus")!=""))
		{
		   wancable_status=1;
		}
			
		if(!is_ll)
			OBJ("status").innerHTML  = wancable_status==1 ? str_Connected:str_Disconnected;
		else
		{
			OBJ("status").innerHTML  = str_Disconnected;
		   	wancable_status=0;
		}
			

		rwan = rwan+"/runtime/inf/inet";
		if ((XG  (this.waninetp+"/addrtype") == "ipv6")&& wancable_status==1)
		{
			var str_wantype = XG(this.waninetp+"/ipv6/mode");
			var str_wanipaddr = XG(rwan+"/ipv6/ipaddr");
			var str_wanprefix = "/"+XG(rwan+"/ipv6/prefix");
			var str_wangateway = XG(rwan+"/ipv6/gateway");
			if(str_wantype == "STATIC" || str_wantype == "6TO4" || str_wantype == "6IN4")
			{
				var str_wanDNSserver = XG(this.waninetp+"/ipv6/dns/entry:1");
				var str_wanDNSserver2 = XG(this.waninetp+"/ipv6/dns/entry:2");
			}
			else
			{
				var str_wanDNSserver = XG(rwan+"/ipv6/dns/entry:1");
				var str_wanDNSserver2 = XG(rwan+"/ipv6/dns/entry:2");
			}
			if(str_wantype == "6TO4")
			{
				str_wantype = XG  (wan1+"/inf/inf6to4/mode");
			}
		}
		else if (is_ppp6 == 1 && wancable_status==1)
		{
			rwan = PXML.FindModule("RUNTIME.INF.WAN-3");
			rwan = rwan+"/runtime/inf/ppp6";
			var rwan4 = PXML.FindModule("RUNTIME.INF.WAN-4");
			var str_wantype = "PPPoE";
			var str_wanipaddr = XG(rwan+"/ppp6/local");
			var str_wanprefix = "/64";
			var str_wangateway = XG(rwan+"/ppp6/peer");
			var str_wanDNSserver = XG(rwan4+"/runtime/inf/inet/ipv6/dns/entry:1");
			var str_wanDNSserver2 = XG(rwan4+"/runtime/inf/inet/ipv6/dns/entry:2");
		}
		else if (is_ll == 1)
		{
			var str_wantype = "LL";
			var str_wanipaddr = "";
			var str_wanprefix = "";
			var str_wangateway = "";
			var str_wanDNSserver = "";
			var str_wanDNSserver2 = "";
		}

		OBJ("type").innerHTML = str_wantype;
		OBJ("wan_address").innerHTML  = str_wanipaddr;
		OBJ("wan_address_pl").innerHTML  = str_wanprefix;
		OBJ("gateway").innerHTML  =  str_wangateway;
		OBJ("br_dns1").innerHTML  = str_wanDNSserver!="" ? str_wanDNSserver:"0::0";
		OBJ("br_dns2").innerHTML  = str_wanDNSserver2!="" ? str_wanDNSserver2:"0::0";

		return true;
	},
	InitLAN: function()
	{
		var lan	= PXML.FindModule("INET.LAN-4");
		var rlan = PXML.FindModule("RUNTIME.INF.LAN-4");
		var inetuid = XG  (lan+"/inf/inet");
		var phyuid = XG  (lan+"/inf/phyinf");
		var phy = PXML.FindModule("RUNTIME.PHYINF");
		this.inetp = GPBT(lan+"/inet", "entry", "uid", inetuid, false);
		this.rlanphyp = GPBT(phy+"/runtime", "phyinf", "uid", phyuid, false);
		if (!this.inetp)
		{
			BODY.ShowAlert("InitLAN() ERROR!!!");
			return false;
		}

		OBJ("lan_ll_address").innerHTML = XG(this.rlanphyp+"/ipv6ll");
		OBJ("lan_ll_pl").innerHTML = "/64";

		var b = rlan+"/runtime/inf/inet/ipv6";
		this.lanip = XG(b+"/ipaddr");
		this.prefix = XG(b+"/prefix");
		OBJ("lan_address").innerHTML = this.lanip;
		OBJ("lan_pl").innerHTML = "/"+this.prefix;
		
		return true;
	}
}
</script>
