<?include "/htdocs/phplib/inet.php";?>
<?include "/htdocs/phplib/inf.php";?>
<?
	$inet = INF_getinfinfo("LAN-1", "inet");
	$ipaddr = INET_getinetinfo($inet, "ipv4/ipaddr");
?>
<script type="text/javascript">
var mac_clone_changed = 0;
function Page() {}
Page.prototype =
{
	services: "DEVICE.LAYOUT,DEVICE.HOSTNAME,PHYINF.WAN-1,INET.BRIDGE-1,INET.INF,WAN",
	OnLoad: function() {},
	OnUnload: function() {},
	OnSubmitCallback: function (code, result)
	{
		BODY.ShowContent();
		switch (code)
		{
		case "OK":
			if ( mac_clone_changed==1 || COMM_Equal(OBJ("rtmode").getAttribute("modified"), true) || COMM_Equal(OBJ("apmode").getAttribute("modified"), true) 
				|| COMM_Equal(OBJ("atmode").getAttribute("modified"), true))
			{
				if (mac_clone_changed==1)
				{
					/* change to router mode. */
					var msgArray =
					[
						'<?echo i18n("You may need to change the IP address of your computer to access the device.");?>',
						'<?echo i18n("You can access the device by clicking the link below.");?>',
						'<a href="http://<?echo $ipaddr;?>" style="color:#0000ff;">http://<?echo $ipaddr;?></a>'
					];
				}
				else if (OBJ("apmode").checked)
				{
					/* change to bridge mode */
					var msgArray =
					[
						'<?echo i18n("The device is changing to AP mode.");?>',
						'<?echo i18n("You may need to change the IP address of your computer to access the device.");?>',
						'<?echo i18n("You can access the device by clicking the link below.");?>',
						'<a href="http://192.168.0.50" style="color:#0000ff;">http://192.168.0.50</a>'
					];
				}
				else if	(OBJ("rtmode").checked)
				{
					/* change to router mode. */
					var msgArray =
					[
						'<?echo i18n("The device is changing to router mode.");?>',
						'<?echo i18n("You may need to change the IP address of your computer to access the device.");?>',
						'<?echo i18n("You can access the device by clicking the link below.");?>',
						'<a href="http://<?echo $ipaddr;?>" style="color:#0000ff;">http://<?echo $ipaddr;?></a>'
					];
				}
				else
				{
					/* change to auto mode. */
					var msgArray =
					[
						'<?echo i18n("The device is changing to auto mode.");?>',
						'<?echo i18n("You may need to change the IP address of your computer to access the device.");?>',
						'<?echo i18n("For Router mode.");?>',
						'<?echo i18n("You can access the device by clicking the link below.");?>',
						'<a href="http://<?echo $ipaddr;?>" style="color:#0000ff;">http://<?echo $ipaddr;?></a>',
						'<?echo i18n("For AP mode.");?>',		
						'<?echo i18n("You can access the device by clicking the link below.");?>',
						'<a href="http://192.168.0.50" style="color:#0000ff;">http://192.168.0.50</a>'
					];
				}		
				BODY.ShowCountdown('<?echo i18n("Device Mode");?>', msgArray, this.bootuptime, null);
			}
			else
			{
				BODY.OnReload();
			}
			break;
		case "BUSY":
			BODY.ShowAlert("<?echo i18n("Someone is configuring the device, please try again later.");?>");
			break;
		case "HEDWIG":
			BODY.ShowAlert(result.Get("/hedwig/message"));
			break;
		case "PIGWIDGEON":
			if (result.Get("/pigwidgeon/message")==="no power")
			{
				BODY.NoPower();
			}
			else
			{
				BODY.ShowAlert(result.Get("/pigwidgeon/message"));
			}
			break;
		}
		return true;
	},
	InitValue: function(xml)
	{
		mac_clone_changed = 0;
		this.defaultCFGXML = xml;
		PXML.doc = xml;

		/* init the WAN-# & br-# obj */
		var base = PXML.FindModule("INET.INF");
		this.wan1.infp	= GPBT(base, "inf", "uid", "WAN-1", false);
		this.wan1.inetp	= GPBT(base+"/inet", "entry", "uid", XG(this.wan1.infp+"/inet"), false);
		var b = PXML.FindModule("PHYINF.WAN-1");
		this.wan1.phyinfp = GPBT(b, "phyinf", "uid", XG(b+"/inf/phyinf"), false);
		
		this.wan2.infp	= GPBT(base, "inf", "uid", "WAN-2", false);
		this.wan2.inetp	= GPBT(base+"/inet", "entry", "uid", XG(this.wan2.infp+"/inet"), false);

		this.br1.infp	= GPBT(base, "inf", "uid", "BRIDGE-1", false);
		this.br1.inetp	= GPBT(base+"/inet", "entry", "uid", XG(this.br1.infp+"/inet"), false);

		if (!base) { alert("InitValue ERROR!"); return false; }

		var layout = PXML.FindModule("DEVICE.LAYOUT");
		if (!layout) { alert("InitLayout ERROR !"); return false; }

		this.device_host = PXML.FindModule("DEVICE.HOSTNAME");
		if (!this.device_host) { alert("Init Device Host ERROR !"); return false; }

		if(XG(layout+"/device/autobridge")==="1")	OBJ("atmode").checked = true;
		else if(XG(layout+"/device/layout")==="bridge")	OBJ("apmode").checked = true;	
		else	OBJ("rtmode").checked = true;		

		/* init wan type */
		var wan1addrtype = XG(this.wan1.inetp+"/addrtype");
		if (wan1addrtype === "ipv4")
		{
			if (XG(this.wan1.inetp+"/ipv4/static")==="1")	COMM_SetSelectValue(OBJ("wan_ip_mode"), "static");
			else
			{
				OBJ("dhcpplus_username").value = XG(this.wan1.inetp+"/ipv4/dhcpplus/username");
				OBJ("dhcpplus_password").value = XG(this.wan1.inetp+"/ipv4/dhcpplus/password");
				if (XG(this.wan1.inetp+"/ipv4/dhcpplus/enable")==="1")
					COMM_SetSelectValue(OBJ("wan_ip_mode"), "dhcpplus");
				else
					COMM_SetSelectValue(OBJ("wan_ip_mode"), "dhcp");
			}
		}
		else if (wan1addrtype === "ppp4")
		{
			var over = XG(this.wan1.inetp+"/ppp4/over");
			if (over === "eth")
			{
				if (XG(this.wan2.infp+"/nat") === "NAT-1" && XG(this.wan2.infp+"/active")==="1")
					COMM_SetSelectValue(OBJ("wan_ip_mode"), "r_pppoe");
				else
					COMM_SetSelectValue(OBJ("wan_ip_mode"), "pppoe");
			}
			else if (over === "pptp")
			{
				if (XG(this.wan2.infp+"/nat") === "NAT-1" && XG(this.wan2.infp+"/active")==="1")
					COMM_SetSelectValue(OBJ("wan_ip_mode"), "r_pptp");
				else
					COMM_SetSelectValue(OBJ("wan_ip_mode"), "pptp");
			}
			else if (over === "l2tp")
			{
				COMM_SetSelectValue(OBJ("wan_ip_mode"), "l2tp");
			}
		}
		/* init ip setting */
		if (!this.InitIpv4Value()) return false;
		if (!this.InitPpp4Value()) return false;
		this.OnClickRgmode("InitValue");
		return true;
	},
	PreSubmit: function()
	{
		/* disable all modules */
		PXML.IgnoreModule("DEVICE.LAYOUT");
		PXML.IgnoreModule("DEVICE.HOSTNAME");
		PXML.IgnoreModule("PHYINF.WAN-1");
		PXML.IgnoreModule("INET.BRIDGE-1");
		PXML.IgnoreModule("WAN");
		/* router/bridge mode setting */
		if (COMM_Equal(OBJ("rtmode").getAttribute("modified"), true) || COMM_Equal(OBJ("apmode").getAttribute("modified"), true) 
			|| COMM_Equal(OBJ("atmode").getAttribute("modified"), true))
		{
			var layout = PXML.FindModule("DEVICE.LAYOUT")+"/device/layout";
			var autobridge = PXML.FindModule("DEVICE.LAYOUT")+"/device/autobridge";

			PXML.ActiveModule("DEVICE.LAYOUT");
			PXML.CheckModule("INET.BRIDGE-1", "ignore", "ignore", null);

			if (OBJ("apmode").checked)
			{
				/* router or auto -> bridge mode */
				XS(layout, "bridge");
				XS(autobridge, "0");

				/* ignore other services */
				return PXML.doc;
			}
			else if	(OBJ("rtmode").checked)
			{
				/* bridge or auto -> router */
				XS(layout, "router");
				XS(autobridge, "0");
			}
			else
			{
				/* router or bridge -> auto mode */
				XS(autobridge, "1");
			}		
		}

		/* clear WAN-2 & clone mac */
		var russia = false;
		XS(this.wan1.infp+"/schedule","");
		XS(this.wan1.infp+"/lowerlayer","");
		XS(this.wan1.infp+"/upperlayer","");
		XS(this.wan2.infp+"/schedule","");
		XS(this.wan2.infp+"/lowerlayer","");
		XS(this.wan2.infp+"/upperlayer","");
		XS(this.wan2.infp+"/active", "0");
		XS(this.wan2.infp+"/nat","");
	
		if (COMM_Equal(OBJ("dhcp_host_name").getAttribute("modified"), "true"))
			PXML.ActiveModule("DEVICE.HOSTNAME");
		else
			PXML.IgnoreModule("DEVICE.HOSTNAME");

		var mtu_obj = "ipv4_mtu";
		var mac_obj = "ipv4_macaddr";
		switch(OBJ("wan_ip_mode").value)
		{
		case "static":
			if (!this.PreStatic()) return null;
			break;
		case "dhcp":
		case "dhcpplus":
			if (!this.PreDhcp()) return null;
			break;
		case "r_pppoe":
			if (!this.PreRPppoe()) return null;
		case "pppoe":
			if (!this.PrePppoe()) return null;
			mtu_obj = "ppp4_mtu";
			mac_obj = "ppp4_macaddr";
			break;
		case "r_pptp":
			if (!this.PrePptp("russia")) return null;
			mtu_obj = "ppp4_mtu";
			mac_obj = "ppp4_macaddr";
			break;
		case "pptp":
			if (!this.PrePptp()) return null;
			mtu_obj = "ppp4_mtu";
			mac_obj = "ppp4_macaddr";
			break;
		case "l2tp":
			if (!this.PreL2tp()) return null;
			mtu_obj = "ppp4_mtu";
			mac_obj = "ppp4_macaddr";
			break;		
		}
		if (!TEMP_IsDigit(OBJ(mtu_obj).value))
		{
			BODY.ShowAlert("<?echo i18n("The MTU value is invalid.");?>");
			return null;
		}

		/* If mac is changed, restart PHYINF.WAN-1, else restart WAN. */
		if (COMM_Equal(OBJ(mac_obj).getAttribute("modified"), true))
		{
			var p = PXML.FindModule("PHYINF.WAN-1");
			var b = GPBT(p, "phyinf", "uid", XG(p+"/inf/phyinf"), false);
			XS(b+"/macaddr", OBJ(mac_obj).value);
			PXML.ActiveModule("PHYINF.WAN-1");
			PXML.DelayActiveModule("PHYINF.WAN-1", "3");
			mac_clone_changed = 1;
		}
		else
		{
			PXML.CheckModule("WAN", null, "ignore", null);
		}
		PXML.CheckModule("INET.INF", null, null, "ignore");

		return PXML.doc;
	},
	IsDirty: null,
	Synchronize: function() {},
	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////
	bootuptime: <?

		$bt=query("/runtime/device/bootuptime");
		if ($bt=="")	$bt=30;
		else			$bt=$bt+10;
		echo $bt;

	?>,
	defaultCFGXML: null,
	device_host: null,
	wan1:	{infp: null, inetp:null, phyinfp:null},
	wan2:	{infp: null, inetp:null},
	br1:	{infp: null, inetp:null},
	InitIpv4Value: function()
	{
		/* static ip */
		OBJ("st_ipaddr").value	= XG(this.wan1.inetp+"/ipv4/ipaddr");
		OBJ("st_mask").value	= COMM_IPv4INT2MASK(XG(this.wan1.inetp+"/ipv4/mask"));
		OBJ("st_gw").value		= XG(this.wan1.inetp+"/ipv4/gateway");
		/* dns server */
		var cnt = XG(this.wan1.inetp+"/ipv4/dns/count");
		OBJ("ipv4_dns1").value	= cnt > 0 ? XG(this.wan1.inetp+"/ipv4/dns/entry:1") : "";
		OBJ("ipv4_dns2").value	= cnt > 1 ? XG(this.wan1.inetp+"/ipv4/dns/entry:2") : "";
		OBJ("ipv4_mtu").value			= XG(this.wan1.inetp+"/ipv4/mtu");
		/* dhcp & dhcp plus */
		OBJ("dhcp_host_name").value	= XG(this.device_host+"/device/hostname");
		OBJ("dhcpplus_username").vlaue	= XG(this.ipv4+"/dhcpplus/username");
		OBJ("dhcpplus_password").vlaue	= XG(this.ipv4+"/dhcpplus/password");
		/* mac addr */
		OBJ("ipv4_macaddr").value = XG(this.wan1.phyinfp+"/macaddr");
		return true;
	},
	InitPpp4Value: function()
	{

		/* set/clear to default */
		/* pppoe */
		OBJ("pppoe_dynamic").checked		= true;
		OBJ("pppoe_ipaddr").value			= "";
		OBJ("pppoe_username").value			= "";
		OBJ("pppoe_mppe").checked			= false;
		OBJ("pppoe_password").value			= "";
		OBJ("confirm_pppoe_password").value = "";
		OBJ("pppoe_service_name").value		= "";
		OBJ("pppoe_ondemand").checked		= true;
		OBJ("pppoe_max_idle_time").value	= "";
		OBJ("dns_isp").checked				= true;
		OBJ("pppoe_dns1").value				= "";
		OBJ("pppoe_dns2").value				= "";
		OBJ("en_fakeos").checked			= false;
		COMM_SetSelectValue(OBJ("pppoe_schedule"), "");
		/* pptp */
		OBJ("pptp_dynamic").checked			= true;
		OBJ("pptp_ipaddr").value			= "";
		OBJ("pptp_mask").value				= "";
		OBJ("pptp_gw").value				= "";
		OBJ("pptp_username").value			= "";
		OBJ("pptp_mppe").checked			= false;
		OBJ("pptp_password").value			= "";
		OBJ("confirm_pptp_password").value	= "";
		OBJ("pptp_ondemand").checked		= true;
		OBJ("pptp_max_idle_time").value		= "";
		OBJ("pptp_dns1").value				= "";
		OBJ("pptp_dns2").value				= "";
		COMM_SetSelectValue(OBJ("pptp_schedule"), "");
		/* l2tp */
		OBJ("l2tp_dynamic").checked			= true;
		OBJ("l2tp_ipaddr").value			= "";
		OBJ("l2tp_mask").value				= "";
		OBJ("l2tp_gw").value				= "";
		OBJ("l2tp_server").value			= "";
		OBJ("l2tp_username").value			= "";
		OBJ("l2tp_password").value			= "";
		OBJ("confirm_l2tp_password").value	= "";
		OBJ("l2tp_ondemand").checked		= true;
		OBJ("l2tp_max_idle_time").value		= "";
		OBJ("l2tp_dns1").value				= "";
		OBJ("l2tp_dns2").value				= "";
		COMM_SetSelectValue(OBJ("l2tp_schedule"), "");
		/* common */
		OBJ("ppp4_mtu").value = XG(this.wan1.inetp+"/ppp4/mtu");
		OBJ("ppp4_macaddr").value = XG(this.wan1.phyinfp+"/macaddr");

		/* init */
		/* rpppoe */
		if (XG(this.wan2.inetp+"/ipv4/static")==="1")	OBJ("rpppoe_static").checked = true;
		else											OBJ("rpppoe_dynamic").checked = true;
		var cnt = XG(this.wan2.inetp+"/ipv4/dns/count");
		OBJ("rpppoe_ipaddr").value = XG(this.wan2.inetp+"/ipv4/ipaddr");
		OBJ("rpppoe_mask").value = COMM_IPv4INT2MASK(XG(this.wan2.inetp+"/ipv4/mask"));
		OBJ("rpppoe_gw").value = XG(this.wan2.inetp+"/ipv4/gateway");
		OBJ("rpppoe_dns1").value = (cnt>0)? XG(this.wan2.inetp+"/ipv4/dns/entry:1") : "";
		OBJ("rpppoe_dns2").value = (cnt>1)? XG(this.wan2.inetp+"/ipv4/dns/entry:2") : "";

		var over = XG(this.wan1.inetp+"/ppp4/over");
		switch (over)
		{
		case "eth":
			if (XG(this.wan1.inetp+"/ppp4/static")==="1")	OBJ("pppoe_static").checked = true;
			else											OBJ("pppoe_dynamic").checked = true;
			OBJ("pppoe_ipaddr").value		= XG(this.wan1.inetp+"/ppp4/ipaddr");
			OBJ("pppoe_username").value		= XG(this.wan1.inetp+"/ppp4/username");
			OBJ("pppoe_mppe").checked		= XG(this.wan1.inetp+"/ppp4/mppe/enable")==="1" ? true : false;
			OBJ("pppoe_password").value		= XG(this.wan1.inetp+"/ppp4/password");
			OBJ("confirm_pppoe_password").value	= XG(this.wan1.inetp+"/ppp4/password");
			OBJ("pppoe_service_name").value	= XG(this.wan1.inetp+"/ppp4/pppoe/servicename");
			var dialup = XG(this.wan1.inetp+"/ppp4/dialup/mode");
			if		(dialup === "auto")		OBJ("pppoe_alwayson").checked = true;
			else if	(dialup === "manual")	OBJ("pppoe_manual").checked = true;
			else							OBJ("pppoe_ondemand").checked = true;
			OBJ("pppoe_max_idle_time").value = XG(this.wan1.inetp+"/ppp4/dialup/idletimeout");
			if (XG(this.wan1.inetp+"/ppp4/dns/count") > 0)	OBJ("dns_manual").checked = true;
			else OBJ("dns_isp").checked = true;
			OBJ("pppoe_dns1").value = XG(this.wan1.inetp+"/ppp4/dns/entry:1");
			if (XG(this.wan1.inetp+"/ppp4/dns/count")>=2) OBJ("pppoe_dns2").value = XG(this.wan1.inetp+"/ppp4/dns/entry:2");
			COMM_SetSelectValue(OBJ("pppoe_schedule"), XG(this.wan1.infp+"/schedule"));
			OBJ("en_fakeos").checked = XG(this.wan1.inetp+"/ppp4/pppoe/fakeos/enable")==="1" ? true : false;
			break;
		case "pptp":
			if (XG(this.wan2.inetp+"/ipv4/static")==="1")	OBJ("pptp_static").checked = true;
			else											OBJ("pptp_dynamic").checked = true;
			OBJ("pptp_ipaddr").value	= XG(this.wan2.inetp+"/ipv4/ipaddr");
			OBJ("pptp_mask").value		= COMM_IPv4INT2MASK(XG(this.wan2.inetp+"/ipv4/mask"));
			OBJ("pptp_gw").value		= XG(this.wan2.inetp+"/ipv4/gateway");
			OBJ("pptp_server").value	= XG(this.wan1.inetp+"/ppp4/pptp/server");
			OBJ("pptp_username").value	= XG(this.wan1.inetp+"/ppp4/username");
			OBJ("pptp_mppe").checked	= XG(this.wan1.inetp+"/ppp4/mppe/enable")==="1" ? true : false;
			OBJ("pptp_password").value	= XG(this.wan1.inetp+"/ppp4/password");
			OBJ("confirm_pptp_password").value	= XG(this.wan1.inetp+"/ppp4/password");
			var dialup = XG(this.wan1.inetp+"/ppp4/dialup/mode");
			if		(dialup === "auto")		OBJ("pptp_alwayson").checked= true;
			else if	(dialup === "manual")	OBJ("pptp_manual").checked = true;
			else							OBJ("pptp_ondemand").checked = true;
			OBJ("pptp_max_idle_time").value	= XG(this.wan1.inetp+"/ppp4/dialup/idletimeout");
			var dnscount = XG(this.wan2.inetp+"/ipv4/dns/count");
			if (dnscount > 0)	OBJ("pptp_dns1").value	= XG(this.wan2.inetp+"/ipv4/dns/entry:1");
			if (dnscount > 1)	OBJ("pptp_dns2").value	= XG(this.wan2.inetp+"/ipv4/dns/entry:2");
			COMM_SetSelectValue(OBJ("pptp_schedule"), XG(this.wan1.infp+"/schedule"));
			break;
		case "l2tp":
			if (XG(this.wan2.inetp+"/ipv4/static")==="1")	OBJ("l2tp_static").checked = true;
			else											OBJ("l2tp_dynamic").checked = true;
			OBJ("l2tp_ipaddr").value	= XG(this.wan2.inetp+"/ipv4/ipaddr");
			OBJ("l2tp_mask").value		= COMM_IPv4INT2MASK(XG(this.wan2.inetp+"/ipv4/mask"));
			OBJ("l2tp_gw").value		= XG(this.wan2.inetp+"/ipv4/gateway");
			OBJ("l2tp_server").value	= XG(this.wan1.inetp+"/ppp4/l2tp/server");
			OBJ("l2tp_username").value	= XG(this.wan1.inetp+"/ppp4/username");
			OBJ("l2tp_password").value	= XG(this.wan1.inetp+"/ppp4/password");
			OBJ("confirm_l2tp_password").value	= XG(this.wan1.inetp+"/ppp4/password");
			var dialup = XG(this.wan1.inetp+"/ppp4/dialup/mode");
			if		(dialup === "auto")		OBJ("l2tp_alwayson").checked= true;
			else if	(dialup === "manual")	OBJ("l2tp_manual").checked = true;
			else							OBJ("l2tp_ondemand").checked = true;
			OBJ("l2tp_max_idle_time").value	= XG(this.wan1.inetp+"/ppp4/dialup/idletimeout");
			var dnscount = XG(this.wan2.inetp+"/ipv4/dns/count");
			if (dnscount > 0)	OBJ("l2tp_dns1").value	= XG(this.wan2.inetp+"/ipv4/dns/entry:1");
			if (dnscount > 1)	OBJ("l2tp_dns2").value	= XG(this.wan2.inetp+"/ipv4/dns/entry:2");
			COMM_SetSelectValue(OBJ("l2tp_schedule"), XG(this.wan1.infp+"/schedule"));
			break;
		}
		return true;
	},
	/* for Pre-Submit */
	PreStatic: function()
	{
		var cnt;
		XS(this.wan1.inetp+"/addrtype",		"ipv4");
		XS(this.wan1.inetp+"/ipv4/static",	"1");
		XS(this.wan1.inetp+"/ipv4/ipaddr",	OBJ("st_ipaddr").value);
		XS(this.wan1.inetp+"/ipv4/mask",	COMM_IPv4MASK2INT(OBJ("st_mask").value));
		XS(this.wan1.inetp+"/ipv4/gateway",	OBJ("st_gw").value);
		XS(this.wan1.inetp+"/ipv4/mtu",		OBJ("ipv4_mtu").value);

		var st_ip = OBJ("st_ipaddr").value;
		if(!check_ip_validity(st_ip))
		{
			BODY.ShowAlert("Invalid IP address");
			OBJ("st_ipaddr").focus();
			return null;
		}

		cnt = 0;
		if(OBJ("ipv4_dns1").value === "")
		{
			BODY.ShowAlert("<?echo i18n("Invalid Primary DNS address .");?>");
			return null;
		}
		XS(this.wan1.inetp+"/ipv4/dns/entry", OBJ("ipv4_dns1").value);
		cnt+=1;
		if (OBJ("ipv4_dns2").value !== "")
		{
			XS(this.wan1.inetp+"/ipv4/dns/entry:2", OBJ("ipv4_dns2").value);
			cnt+=1;
		}
		XS(this.wan1.inetp+"/ipv4/dns/count", cnt);
		return true;
	},
	PreDhcp: function()
	{
		var cnt;
		XS(this.wan1.inetp+"/addrtype",			"ipv4");
		XS(this.wan1.inetp+"/ipv4/static",		"0");
		XS(this.device_host+"/device/hostname",	OBJ("dhcp_host_name").value);
		
		cnt = 0;
		if(OBJ("ipv4_dns1").value !== "")
		{
			XS(this.wan1.inetp+"/ipv4/dns/entry", OBJ("ipv4_dns1").value);
			cnt+=1;
		}
		if (OBJ("ipv4_dns2").value !== "")
		{
			XS(this.wan1.inetp+"/ipv4/dns/entry:2", OBJ("ipv4_dns2").value);
			cnt+=1;
		}
		XS(this.wan1.inetp+"/ipv4/dns/count", cnt);
		XS(this.wan1.inetp+"/ipv4/mtu", OBJ("ipv4_mtu").value);
		if (OBJ("wan_ip_mode").value === "dhcpplus")
		{
			XS(this.wan1.inetp+"/ipv4/dhcpplus/enable", "1");
			XS(this.wan1.inetp+"/ipv4/dhcpplus/username", OBJ("dhcpplus_username").value);
			XS(this.wan1.inetp+"/ipv4/dhcpplus/password", OBJ("dhcpplus_password").value);
		}
		else
		{
			XS(this.wan1.inetp+"/ipv4/dhcpplus/enable", "0");
		}
		return true;
	},
	PrePppoe: function()
	{
		var temp_value="";
		var cnt;
		if (OBJ("pppoe_password").value !== OBJ("confirm_pppoe_password").value)
		{
			BODY.ShowAlert("<?echo i18n("The password is mismatched.");?>");
			return null;
		}
		XS(this.wan1.inetp+"/addrtype", "ppp4");
		XS(this.wan1.inetp+"/ppp4/over", "eth");
		XS(this.wan1.inetp+"/ppp4/username", OBJ("pppoe_username").value);
		var mppe = 0;
		if(OBJ("pppoe_mppe").checked && OBJ("wan_ip_mode").value==="r_pppoe")mppe = "1";
		XS(this.wan1.inetp+"/ppp4/mppe/enable", mppe);
		XS(this.wan1.inetp+"/ppp4/password", OBJ("pppoe_password").value);
		XS(this.wan1.inetp+"/ppp4/pppoe/servicename", OBJ("pppoe_service_name").value);
		if (OBJ("pppoe_dynamic").checked)
		{
			XS(this.wan1.inetp+"/ppp4/static", "0");
			XD(this.wan1.inetp+"/ppp4/ipaddr");
		}
		else
		{
			XS(this.wan1.inetp+"/ppp4/static", "1");
			XS(this.wan1.inetp+"/ppp4/ipaddr", OBJ("pppoe_ipaddr").value);
			if (OBJ("dns_manual").checked && OBJ("pppoe_dns1").value === "")
			{
				BODY.ShowAlert("<?echo i18n("Invalid Primary DNS address .");?>");
				return null;
			}
		}
		/* star fakeos */
		XS(this.wan1.inetp+"/ppp4/pppoe/fakeos/enable", OBJ("en_fakeos").checked ? "1" : "0");

		/* dns */
		cnt = 0;
		if (OBJ("dns_isp").checked)
		{
			XS(this.wan1.inetp+"/ppp4/dns/entry:1","");
			XS(this.wan1.inetp+"/ppp4/dns/entry:2","");
		}
		else
		{
			if (OBJ("pppoe_dns1").value !== "")
			{
				XS(this.wan1.inetp+"/ppp4/dns/entry", OBJ("pppoe_dns1").value);
				cnt+=1;
			}
			if (OBJ("pppoe_dns2").value !== "")
			{
				XS(this.wan1.inetp+"/ppp4/dns/entry:2", OBJ("pppoe_dns2").value);
				cnt+=1;
			}
		}
		XS(this.wan1.inetp+"/ppp4/dns/count", cnt);
		XS(this.wan1.inetp+"/ppp4/mtu", OBJ("ppp4_mtu").value);
		if (OBJ("pppoe_max_idle_time").value==="") OBJ("pppoe_max_idle_time").value = 0;
		if (!TEMP_IsDigit(OBJ("pppoe_max_idle_time").value))
		{
			BODY.ShowAlert("<?echo i18n("Invalid value for idle timeout.");?>");
			return null;
		}
		XS(this.wan1.inetp+"/ppp4/dialup/idletimeout", OBJ("pppoe_max_idle_time").value);
		var dialup = "ondemand";
		if(OBJ("pppoe_alwayson").checked)
		{	
			dialup = "auto";
			XS(this.wan1.infp+"/schedule", OBJ("pppoe_schedule").value);
		}
		else if	(OBJ("pppoe_manual").checked)	dialup = "manual";
		XS(this.wan1.inetp+"/ppp4/dialup/mode", dialup);

		return true;
	},
	PreRPppoe: function()
	{
		var rpppoe_static = OBJ("rpppoe_static").checked;
		var cnt = 0;
		XS(this.wan2.infp+"/active", "1");
		XS(this.wan2.infp+"/nat","NAT-1");
		XS(this.wan2.inetp+"/ipv4/static",	(rpppoe_static)? "1":"0");
		XS(this.wan2.inetp+"/ipv4/ipaddr",	(rpppoe_static)? OBJ("rpppoe_ipaddr").value:"");
		XS(this.wan2.inetp+"/ipv4/mask",	(rpppoe_static)? COMM_IPv4MASK2INT(OBJ("rpppoe_mask").value):"");
		XS(this.wan2.inetp+"/ipv4/gateway",	(rpppoe_static)? OBJ("rpppoe_gw").value:"");
		if (rpppoe_static)
		{
			if (OBJ("rpppoe_dns1").value!=="")
			{
				XS(this.wan2.inetp+"/ipv4/dns/entry", OBJ("rpppoe_dns1").value);
				cnt+=1;
			}
			else
			{
				BODY.ShowAlert("<?echo i18n("Invalid Primary DNS address .");?>");
				return null;
			}
			if (OBJ("rpppoe_dns2").value!=="")
			{
				XS(this.wan2.inetp+"/ipv4/dns/entry:2", OBJ("rpppoe_dns2").value);
				cnt+=1;
			}
		}
		XS(this.wan2.inetp+"/ipv4/dns/count", cnt);

		return true;
	},
	PrePptp: function(type)
	{
		if (OBJ("pptp_password").value !== OBJ("confirm_pptp_password").value)
		{
			BODY.ShowAlert("<?echo i18n("The password is mismatched.");?>");
			return null;
		}
		/* Note : Russia mode need two WANs to be active simultaneously. So we remove the lowerlayer connection. 
		For normal pptp, the lowerlayer/upperlayer connection still remains. */
		
		if(type == "russia")	//normal pptp
		{
			/* defaultroute value will become metric value.
			As for Russia, physical WAN (wan2) priority should be lower than 
			ppp WAN (wan1) */
			XS(this.wan1.infp+"/defaultroute", "100");
			XS(this.wan2.infp+"/defaultroute", "200");
		}
		else
		{
			XS(this.wan1.infp+"/defaultroute", "100");
			XS(this.wan2.infp+"/defaultroute", "");

			XS(this.wan1.infp+"/lowerlayer", "WAN-2");
			XS(this.wan2.infp+"/upperlayer", "WAN-1");
		}
		
		XS(this.wan2.infp+"/active", "1");
		XS(this.wan2.infp+"/nat", (OBJ("wan_ip_mode").value==="r_pptp") ? "NAT-1" : "");
		XS(this.wan1.inetp+"/addrtype", "ppp4");
		XS(this.wan1.inetp+"/ppp4/over", "pptp");
		XS(this.wan1.inetp+"/ppp4/static", "0");
		XS(this.wan1.inetp+"/ppp4/username", OBJ("pptp_username").value);
		XS(this.wan1.inetp+"/ppp4/password", OBJ("pptp_password").value);
		XS(this.wan1.inetp+"/ppp4/pptp/server", OBJ("pptp_server").value);
		var cnt = 0;
		if (OBJ("pptp_static").checked)
		{
			XS(this.wan2.inetp+"/ipv4/static",	"1");
			XS(this.wan2.inetp+"/ipv4/ipaddr",	OBJ("pptp_ipaddr").value);
			XS(this.wan2.inetp+"/ipv4/mask",	COMM_IPv4MASK2INT(OBJ("pptp_mask").value));
			XS(this.wan2.inetp+"/ipv4/gateway",	OBJ("pptp_gw").value);
			if (OBJ("pptp_dns1").value === "")
			{
				BODY.ShowAlert("<?echo i18n("Invalid Primary DNS address .");?>");
				return null;
			}
			else
			{
				XS(this.wan2.inetp+"/ipv4/dns/entry:1", OBJ("pptp_dns1").value);
				cnt++;
			}
			if (OBJ("pptp_dns2").value !== "") { XS(this.wan2.inetp+"/ipv4/dns/entry:2", OBJ("pptp_dns2").value); cnt++; }
		}
		else
		{
			XS(this.wan2.inetp+"/ipv4/static", "0");
		}
		XS(this.wan1.inetp+"/ppp4/dns/count", "0");
		XS(this.wan2.inetp+"/ipv4/dns/count", cnt);
		var mppe = "0";
		if(OBJ("pptp_mppe").checked && OBJ("wan_ip_mode").value=="r_pptp")	mppe ="1";
		XS(this.wan1.inetp+"/ppp4/mppe/enable", mppe);
		XS(this.wan1.inetp+"/ppp4/mtu", OBJ("ppp4_mtu").value);
		if (OBJ("pptp_max_idle_time").value==="") OBJ("pptp_max_idle_time").value = 0;
		if (!TEMP_IsDigit(OBJ("pptp_max_idle_time").value))
		{
			BODY.ShowAlert("<?echo i18n("Invalid value for idle timeout.");?>");
			return null;
		}
		XS(this.wan1.inetp+"/ppp4/dialup/idletimeout", OBJ("pptp_max_idle_time").value);
		var dialup = "ondemand";
		if(OBJ("pptp_alwayson").checked)
		{
			dialup = "auto";
			XS(this.wan1.infp+"/schedule", OBJ("pptp_schedule").value);
		}
		else if	(OBJ("pptp_manual").checked)	dialup = "manual";
		XS(this.wan1.inetp+"/ppp4/dialup/mode", dialup);
		return true;
	},
	PreL2tp: function()
	{
		var cnt;
		if (OBJ("l2tp_password").value !== OBJ("confirm_l2tp_password").value)
		{
			BODY.ShowAlert("<?echo i18n("The password is mismatched.");?>");
			return null;
		}
		XS(this.wan1.infp+"/lowerlayer", "WAN-2");
		XS(this.wan2.infp+"/upperlayer", "WAN-1");
		XS(this.wan2.infp+"/active", "1");
		XS(this.wan1.inetp+"/addrtype", "ppp4");
		XS(this.wan1.inetp+"/ppp4/over", "l2tp");
		XS(this.wan1.inetp+"/ppp4/static", "0");
		XS(this.wan1.inetp+"/ppp4/username", OBJ("l2tp_username").value);
		XS(this.wan1.inetp+"/ppp4/password", OBJ("l2tp_password").value);
		XS(this.wan1.inetp+"/ppp4/l2tp/server", OBJ("l2tp_server").value);
		cnt = 0;
		if (OBJ("l2tp_static").checked)
		{
			XS(this.wan2.inetp+"/ipv4/static", "1");
			XS(this.wan2.inetp+"/ipv4/ipaddr",	OBJ("l2tp_ipaddr").value);
			XS(this.wan2.inetp+"/ipv4/mask",	COMM_IPv4MASK2INT(OBJ("l2tp_mask").value));
			XS(this.wan2.inetp+"/ipv4/gateway",	OBJ("l2tp_gw").value);
			if (OBJ("l2tp_dns1").value != "")	{ XS(this.wan2.inetp+"/ipv4/dns/entry:1", OBJ("l2tp_dns1").value); cnt++; }
			else								{ BODY.ShowAlert("<?echo i18n("Invalid Primary DNS address .");?>"); return null; }
			if (OBJ("l2tp_dns2").value != "")	{ XS(this.wan2.inetp+"/ipv4/dns/entry:2", OBJ("l2tp_dns2").value); cnt++; }
		}
		else
		{
			XS(this.wan2.inetp+"/ipv4/static", "0");
		}
		if (OBJ("l2tp_max_idle_time").value === "") OBJ("l2tp_max_idle_time").value = 0;
		if (!TEMP_IsDigit(OBJ("l2tp_max_idle_time").value))
		{
			BODY.ShowAlert("<?echo i18n("Invalid value for idle timeout.");?>");
			return null;
		}
		XS(this.wan1.inetp+"/ppp4/dns/count", "0");
		XS(this.wan2.inetp+"/ipv4/dns/count", cnt);
		XS(this.wan1.inetp+"/ppp4/mtu", OBJ("ppp4_mtu").value);
		XS(this.wan1.inetp+"/ppp4/dialup/idletimeout", OBJ("l2tp_max_idle_time").value);
		var dialup = "ondemand";
		if(OBJ("l2tp_alwayson").checked)
		{
			dialup = "auto";
			XS(this.wan1.infp+"/schedule", OBJ("l2tp_schedule").value);
		}
		else if	(OBJ("l2tp_manual").checked)	dialup = "manual";
		XS(this.wan1.inetp+"/ppp4/dialup/mode", dialup);
		return true;
	},
	OnChangeWanIpMode: function()
	{
		OBJ("ipv4_setting").style.display		= "none";
		OBJ("ppp4_setting").style.display		= "none";

		OBJ("box_wan_static").style.display		= "none";
		OBJ("box_wan_dhcp").style.display		= "none";
		OBJ("box_wan_dhcpplus").style.display	= "none";
		OBJ("box_wan_static_body").style.display= "none";
		OBJ("box_wan_dhcp_body").style.display	= "none";
		OBJ("box_wan_ipv4_common_body").style.display = "none";

		OBJ("box_wan_pppoe").style.display		= "none";
		OBJ("box_wan_pptp").style.display		= "none";
		OBJ("box_wan_l2tp").style.display		= "none";
		OBJ("box_wan_ru_pppoe").style.display	= "none";
		OBJ("box_wan_ru_pptp").style.display	= "none";
		OBJ("show_pppoe_mppe").style.display	= "none";
		OBJ("show_pptp_mppe").style.display		= "none";
		OBJ("box_wan_pppoe_body").style.display	= "none";
		OBJ("box_wan_pptp_body").style.display	= "none";
		OBJ("box_wan_l2tp_body").style.display	= "none";
		OBJ("box_wan_ppp4_comm_body").style.display = "none";
		OBJ("R_PPPoE").style.display			= "none";

		var over = XG(this.wan1.inetp+"/ppp4/over");
		switch(OBJ("wan_ip_mode").value)
		{
		case "static":
			OBJ("ipv4_setting").style.display				= "block";
			OBJ("box_wan_static").style.display				= "block"; 
			OBJ("box_wan_static_body").style.display		= "block";
			OBJ("box_wan_ipv4_common_body").style.display	= "block";
			break;
		case "dhcpplus":
		case "dhcp":
			OBJ("ipv4_setting").style.display				= "block";
			OBJ("box_wan_dhcp").style.display				= (OBJ("wan_ip_mode").value === "dhcpplus") ? "none"  : "block";
			OBJ("box_wan_dhcpplus").style.display			= (OBJ("wan_ip_mode").value === "dhcpplus") ? "block" : "none";
			OBJ("box_wan_dhcp_body").style.display			= "block";
			OBJ("dhcpplus").style.display					= (OBJ("wan_ip_mode").value === "dhcpplus") ? "block" : "none";
			OBJ("box_wan_ipv4_common_body").style.display	= "block";
			break;
		case "r_pppoe":
			OBJ("show_pppoe_mppe").style.display			= "inline";
			OBJ("R_PPPoE").style.display					= "block";
			this.OnClickRPppoeAddrType();
		case "pppoe":
			OBJ("ppp4_setting").style.display				= "block";
			OBJ("box_wan_pppoe_body").style.display			= "block";
			OBJ("box_wan_pppoe").style.display				= "block";
			OBJ("box_wan_ppp4_comm_body").style.display		= "block";
			if (XG(this.wan1.inetp+"/ppp4/mtu")=="")		OBJ("ppp4_mtu").value = "1492";
			this.OnClickPppoeAddrType();
			this.OnClickPppoeReconnect();
			this.OnClickDnsMode();
			break;
		case "r_pptp":
			OBJ("ppp4_setting").style.display				= "block";
			OBJ("box_wan_ru_pptp").style.display			= "block";
			OBJ("box_wan_pptp_body").style.display			= "block";
			OBJ("box_wan_ppp4_comm_body").style.display 	= "block";
			OBJ("show_pptp_mppe").style.display				= "inline";
			if (XG(this.wan1.inetp+"/ppp4/mtu")=="" || 
			   (over!="r_pptp" && parseInt(XG(this.wan1.inetp+"/ppp4/mtu"))>1400))		OBJ("ppp4_mtu").value = "1400";
			this.OnClickPptpAddrType();
			this.OnClickPptpReconnect();
			break;
		case "pptp":
			OBJ("ppp4_setting").style.display				= "block";
			OBJ("box_wan_pptp").style.display				= "block";
			OBJ("box_wan_pptp_body").style.display			= "block";
			OBJ("box_wan_ppp4_comm_body").style.display 	= "block";
			if (XG(this.wan1.inetp+"/ppp4/mtu")=="" || 
			   (over!="pptp" && parseInt(XG(this.wan1.inetp+"/ppp4/mtu"))>1400))		OBJ("ppp4_mtu").value = "1400";
			this.OnClickPptpAddrType();
			this.OnClickPptpReconnect();
			break;
		case "l2tp":		
			OBJ("ppp4_setting").style.display				= "block";
			OBJ("box_wan_l2tp").style.display				= "block";
			OBJ("box_wan_l2tp_body").style.display			= "block";
			OBJ("box_wan_ppp4_comm_body").style.display		= "block";
			if (XG(this.wan1.inetp+"/ppp4/mtu")=="" || 
			   (over!="l2tp" && parseInt(XG(this.wan1.inetp+"/ppp4/mtu"))>1400))		OBJ("ppp4_mtu").value = "1400";
			this.OnClickL2tpAddrType();
			this.OnClickL2tpReconnect();
			break;
		}
	},
	/* PPPoE */
	OnClickPppoeAddrType: function()
	{
		OBJ("pppoe_ipaddr").disabled = OBJ("pppoe_dynamic").checked ? true: false;
	},
	OnClickPppoeReconnect: function()
	{
		if(OBJ("pppoe_alwayson").checked)
		{
			OBJ("pppoe_schedule").disabled = false;
			OBJ("pppoe_schedule_button").disabled = false;
			OBJ("pppoe_max_idle_time").disabled = true;
		}
		else if(OBJ("pppoe_ondemand").checked)
		{
			OBJ("pppoe_schedule").disabled = true;
			OBJ("pppoe_schedule_button").disabled = true;
			OBJ("pppoe_max_idle_time").disabled = false;
		}
		else
		{
			OBJ("pppoe_schedule").disabled = true;
			OBJ("pppoe_schedule_button").disabled = true;
			OBJ("pppoe_max_idle_time").disabled = true;
		}
	},
	OnClickDnsMode: function()
	{
		var dis = OBJ("dns_isp").checked;
		OBJ("pppoe_dns1").disabled = dis;
		OBJ("pppoe_dns2").disabled = dis;
	},
	/* pptp */
	OnClickPptpAddrType: function()
	{
		var dis = OBJ("pptp_dynamic").checked ? true: false;
		OBJ("pptp_ipaddr").disabled	= dis;
		OBJ("pptp_mask").disabled	= dis;
		OBJ("pptp_gw").disabled		= dis;
		OBJ("pptp_dns1").disabled	= dis;
		OBJ("pptp_dns2").disabled	= dis;
	},
	OnClickPptpReconnect: function()
	{
		if(OBJ("pptp_alwayson").checked)
		{
			OBJ("pptp_schedule").disabled = false;
			OBJ("pptp_schedule_button").disabled = false;
			OBJ("pptp_max_idle_time").disabled = true;
		}
		else if(OBJ("pptp_ondemand").checked)
		{
			OBJ("pptp_schedule").disabled = true;
			OBJ("pptp_schedule_button").disabled = true;
			OBJ("pptp_max_idle_time").disabled = false;
		}
		else
		{
			OBJ("pptp_schedule").disabled = true;
			OBJ("pptp_schedule_button").disabled = true;
			OBJ("pptp_max_idle_time").disabled = true;
		}
	},
	/* l2tp */
	OnClickL2tpAddrType: function()
	{
		var dis = OBJ("l2tp_dynamic").checked ? true: false;
		OBJ("l2tp_ipaddr").disabled	= dis;
		OBJ("l2tp_mask").disabled	= dis;
		OBJ("l2tp_gw").disabled		= dis;
		OBJ("l2tp_dns1").disabled	= dis;
		OBJ("l2tp_dns2").disabled	= dis;
	},
	OnClickL2tpReconnect: function()
	{
		if(OBJ("l2tp_alwayson").checked)
		{
			OBJ("l2tp_schedule").disabled = false;
			OBJ("l2tp_schedule_button").disabled = false;
			OBJ("l2tp_max_idle_time").disabled = true;
		}
		else if(OBJ("l2tp_ondemand").checked)
		{
			OBJ("l2tp_schedule").disabled = true;
			OBJ("l2tp_schedule_button").disabled = true;
			OBJ("l2tp_max_idle_time").disabled = false;
		}
		else
		{
			OBJ("l2tp_schedule").disabled = true;
			OBJ("l2tp_schedule_button").disabled = true;
			OBJ("l2tp_max_idle_time").disabled = true;
		}
	},
	/* RPPPoE*/
	OnClickRPppoeAddrType: function()
	{
		var dis = OBJ("rpppoe_dynamic").checked ? true: false;
		OBJ("rpppoe_ipaddr").disabled	= dis;
		OBJ("rpppoe_mask").disabled	= dis;
		OBJ("rpppoe_gw").disabled		= dis;
		OBJ("rpppoe_dns1").disabled	= dis;
		OBJ("rpppoe_dns2").disabled	= dis;
	},
	OnClickMacButton: function(objname)
	{
		OBJ(objname).value="<?echo INET_ARP($_SERVER["REMOTE_ADDR"]);?>";
	},
	OnClickRgmode: function(from)
	{
		if (OBJ("apmode").checked)
		{
			/* reinit the all setting. */
			if (from !== "InitValue")	this.InitValue(this.defaultCFGXML);
			if (from === "apcheckbox")	OBJ("apmode").checked = true;
			this.OnChangeWanIpMode();
			BODY.DisableCfgElements(true);
			if (AUTH.AuthorizedGroup < 100)
			{
				OBJ("rtmode").disabled = false;
				OBJ("apmode").disabled = false;
				OBJ("atmode").disabled = false;
				OBJ("topsave").disabled = false;
				OBJ("topcancel").disabled = false;
				OBJ("bottomsave").disabled = false;
				OBJ("bottomcancel").disabled = false;
			}
		}
		else
		{
			BODY.DisableCfgElements(false);
			this.OnChangeWanIpMode();
		}
	}
}


function IdleTime(value)
{
	if (value=="")
		return "0";
	else
		return parseInt(value, 10);
}

function check_ip_validity(ipstr)
{
	var vals = ipstr.split(".");
	if (vals.length!=4) 
		return false;
	
	for (var i=0; i<4; i++)
	{
		if (!TEMP_IsDigit(vals[i]) || vals[i]>255)
			return false;
	}
	return true;
}
</script>
