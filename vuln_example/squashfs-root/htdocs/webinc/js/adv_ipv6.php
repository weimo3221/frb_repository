<script type="text/javascript">

function Page() {}
Page.prototype =
{
	services: "INET.WAN-1,INET.LAN-3,INET.LAN-4,INET.WAN-3,INET.WAN-4, DHCPS6.LAN-4,RUNTIME.PHYINF,RUNTIME.INF.WAN-1,RUNTIME.INF.LAN-4,RUNTIME.INF.WAN-4",
	OnLoad: function()
	{
		if (!this.rgmode)		{BODY.DisableCfgElements(true);}
	},
	OnUnload: function() {},
	OnSubmitCallback: function ()	{},

	InitValue: function(xml)
	{
		PXML.doc = xml;

		//PXML.doc.dbgdump();

		this.ParseAll();

		if (!this.InitWANConnMode()) return false;
		if (!this.InitLANConnMode()) return false;
		if (!this.InitWANInfo()) return false;
		if (!this.InitWANLLInfo()) return false;
		if (!this.InitLANInfo()) return false;
		if (!this.InitLANLLInfo()) return false;
		if (!this.InitLANAutoConf()) return false;
		if (!this.InitDHCPS6()) return false;
		this.OnChangewan_ipv6_mode();	
		this.OnChangelan_auto_type();
		
		return true;
	},
	PreSubmit: function()
	{
		if (!this.PreWAN()) return null;
		if (!this.PreLAN()) return null;
		if (!this.PreLANAutoConf()) return null;
                //if (!this.PreDHCPS6()) return null;
		//PXML.IgnoreModule("INET.WAN-1");
		PXML.IgnoreModule("INET.LAN-3");
		//PXML.IgnoreModule("INET.WAN-3");
		if(!OBJ("enableAuto").checked)
			PXML.IgnoreModule("DHCPS6.LAN-4");

		PXML.DelayActiveModule("INET.LAN-4", "5");
		PXML.IgnoreModule("RUNTIME.PHYINF");
		PXML.IgnoreModule("RUNTIME.INF.WAN-1");
		PXML.IgnoreModule("RUNTIME.INF.WAN-4");
		PXML.IgnoreModule("RUNTIME.INF.LAN-4");
		//PXML.doc.dbgdump();
		return PXML.doc;
	},
	IsDirty: null,
	Synchronize: function() {},

	// The above are MUST HAVE methods ...
	///////////////////////////////////////////////////////////////////////

	/*********************************************************
	    lanact, lanllact, wanact and wanllact:
		this architecture existe at least one drawback:
		1. only accept one active wan, wanll, lan, lanll.
		2. 
	*********************************************************/
	rgmode: <?if (query("/runtime/device/layout")=="bridge") echo "false"; else echo "true";?>,
	laninfo: [  {svcsname: "INET.LAN-3", svcs: null, inetuid: null, inetp: null, phyinf: null, ipv6ll: null },
			{svcsname: "INET.LAN-4", svcs: null, inetuid: null, inetp: null, phyinf: null, ipv6ll: null }
		],
	waninfo: [  {svcsname: "INET.WAN-4", svcs: null, inetuid: null, inetp: null, phyinf: null, ipv6ll: null },
			{svcsname: "INET.WAN-3", svcs: null, inetuid: null, inetp: null, phyinf: null, ipv6ll: null }
		],
	lanact:   { svcsname: null, svcs: null, inetuid: null, inetp: null, phyinf: null, ipv6ll: null },
	lanllact: { svcsname: null, svcs: null, inetuid: null, inetp: null, phyinf: null, ipv6ll: null },
	wanact:   { svcsname: null, svcs: null, inetuid: null, inetp: null, phyinf: null, ipv6ll: null },
	wanllact: { svcsname: null, svcs: null, inetuid: null, inetp: null, phyinf: null, ipv6ll: null },
	rwan: null,
	rlan: null,
        dhcps6: null,
        dhcps6_inet: null,
        dhcps6_inf: null,
        wan1:     { infp: null, inetp: null},

	OnChangewan_ipv6_mode: function()
	{
		OBJ("box_wan_title").style.display			= "none";
		OBJ("box_wan_static_body").style.display	= "none";
		OBJ("box_wan_pppoe").style.display			= "none";
		OBJ("box_wan_pppoe_body").style.display		= "none";
		OBJ("bbox_wan_dns").style.display			= "none";
		OBJ("box_wan_6to4_body").style.display		= "none";
		OBJ("box_wan_tunnel").style.display			= "none";
		OBJ("box_wan_tunnel_body").style.display	= "none";
		OBJ("box_wan_6rd_body").style.display	= "none";
		
		//OBJ("box_wan_ll_body").style.display		= "none";
		OBJ("box_lan").style.display				= "none";
		OBJ("box_lan_body").style.display	= "none";
		OBJ("box_lan_pd_body").style.display		= "none";
		OBJ("box_lan_ll_body").style.display 		= "none";
		OBJ("box_lan_auto").style.display 			= "none";
		OBJ("box_lan_auto_body").style.display 		= "none";
		OBJ("bbox_wan").style.display 	= "none";
		OBJ("bbox_lan_auto").style.display 	= "none";
		OBJ("sp_dli_s").innerHTML = "::";
		OBJ("sp_dli_e").innerHTML = "::";
		OBJ("ra_lifetime").disabled = true;
		OBJ("ip_lifetime").disabled = true;
		//OBJ("ra_lifetime").value = "";
		//OBJ("ip_lifetime").value = "";
		//OBJ("w_dhcp_dns_auto").checked	= true;
		var wan3 = PXML.FindModule("INET.WAN-3");//20100614

        	this.OnClickpd();
		this.OnClickDHCPDNS();
		switch(OBJ("wan_ipv6_mode").value)
		{
			case "STATIC":
				OBJ("box_wan_title").style.display = "";
				OBJ("box_wan_static_body").style.display = "";
				OBJ("box_lan").style.display = "";
				OBJ("span_dsc1").style.display = "";
				OBJ("box_lan_body").style.display = "";
				OBJ("box_lan_ll_body").style.display = "";
				OBJ("box_lan_auto").style.display = "";
				OBJ("box_lan_auto_body").style.display = "";
                		OBJ("bbox_wan").style.display 	= "";
                		OBJ("bbox_lan_auto").style.display 	= "";
                		OBJ("sp_dli_s").innerHTML = ":";
                		OBJ("sp_dli_e").innerHTML = ":";
				OBJ("l_ipaddr").disabled = false;
				OBJ("ra_lifetime").disabled = false;
				OBJ("ip_lifetime").disabled = false;
				break;
	
			case "AUTO":
			case "DHCP":
			case "RA":
				OBJ("bbox_wan_dns").style.display = "";
				OBJ("box_lan").style.display = "";
				OBJ("span_dsc1").style.display = "";
				OBJ("span_dsc2").style.display = "";
				OBJ("box_lan_pd_body").style.display = "";
				OBJ("box_lan_body").style.display = "";
				OBJ("box_lan_ll_body").style.display = "";
				OBJ("box_lan_auto").style.display = "";
				OBJ("box_lan_auto_body").style.display = "";
                		OBJ("bbox_lan_auto").style.display 	= "";
				
				//OBJ("w_dhcp_dns_auto").checked	= true;
				OBJ("w_dhcp_pdns").disabled = OBJ("w_dhcp_sdns").disabled = OBJ("w_dhcp_dns_auto").checked ? true: false;
				OBJ("ra_lifetime").value = "";
				OBJ("ip_lifetime").value = "";
				break;

			case "PPPOE":
				OBJ("box_wan_pppoe").style.display = "";
				OBJ("box_wan_pppoe_body").style.display = "";
				OBJ("bbox_wan_dns").style.display = "";
				OBJ("box_lan").style.display = "";
				OBJ("span_dsc1").style.display = "";
				OBJ("span_dsc2").style.display = "";
				OBJ("box_lan_pd_body").style.display = "";
				OBJ("box_lan_body").style.display = "";
				OBJ("box_lan_ll_body").style.display = "";
				OBJ("box_lan_auto").style.display = "";
				OBJ("box_lan_auto_body").style.display = "";
                		OBJ("bbox_wan").style.display 	= "";
                		OBJ("bbox_lan_auto").style.display 	= "";
				//if (XG(wan3.inetp+"/ppp6/mtu")=="")	OBJ("ppp6_mtu").value = "1492";
                        	if(XG(this.wanllact.inetp+"/ppp6/mtu")=="") OBJ("ppp6_mtu").value = "1492";
				//OBJ("w_dhcp_dns_auto").checked	= true;
				OBJ("w_dhcp_pdns").disabled = OBJ("w_dhcp_sdns").disabled = OBJ("w_dhcp_dns_auto").checked ? true: false;
				OBJ("ra_lifetime").value = "";
				OBJ("ip_lifetime").value = "";
				//this.OnClickPppoeAddrType();
				break;

			case "6IN4":
				OBJ("box_wan_tunnel").style.display			= "";
				OBJ("box_wan_tunnel_body").style.display	= "";
				OBJ("bbox_wan_dns").style.display = "";
				OBJ("box_lan").style.display = "";
				OBJ("span_dsc1").style.display = "";
				OBJ("span_dsc2").style.display = "";
				//OBJ("box_lan_pd_body").style.display = "";
				OBJ("box_lan_body").style.display = "";
				OBJ("box_lan_ll_body").style.display = "";
				OBJ("box_lan_auto").style.display = "";
				OBJ("box_lan_auto_body").style.display = "";
                		OBJ("bbox_wan").style.display 	= "";
                		OBJ("bbox_lan_auto").style.display 	= "";
				OBJ("l_ipaddr").disabled = false;
				OBJ("ra_lifetime").disabled = false;
				OBJ("ip_lifetime").disabled = false;
				//OBJ("w_dhcp_dns_auto").checked	= true;
				OBJ("w_dhcp_pdns").disabled = OBJ("w_dhcp_sdns").disabled = OBJ("w_dhcp_dns_auto").checked ? true: false;
				break;

			case "6RD":
				OBJ("box_wan_title").style.display = "";
				OBJ("box_wan_6rd_body").style.display = "";
				OBJ("box_lan").style.display = "";
				OBJ("span_dsc1").style.display = "";
				OBJ("box_lan_body").style.display = "";
				OBJ("box_lan_ll_body").style.display = "";
				OBJ("box_lan_auto").style.display = "";
				OBJ("box_lan_auto_body").style.display = "";

                		OBJ("bbox_wan").style.display 	= "";
                		OBJ("bbox_lan_auto").style.display 	= "";
				OBJ("ra_lifetime").value = "";
				OBJ("ip_lifetime").value = "";
				break;
      
			case "6TO4":
				OBJ("box_wan_title").style.display = "";
				OBJ("box_wan_6to4_body").style.display = "";
				OBJ("box_lan").style.display = "";
				OBJ("span_dsc1").style.display = "";
				OBJ("box_lan_body").style.display = "";
				OBJ("box_lan_ll_body").style.display = "";
				OBJ("box_lan_auto").style.display = "";
				OBJ("box_lan_auto_body").style.display = "";

                		OBJ("bbox_wan").style.display 	= "";
                		OBJ("bbox_lan_auto").style.display 	= "";
				OBJ("ra_lifetime").value = "";
				OBJ("ip_lifetime").value = "";
				break;
      
			case "LL":
				OBJ("box_lan").style.display = "";
				OBJ("box_lan_ll_body").style.display = "";
				OBJ("span_dsc1").style.display = "none";
				OBJ("span_dsc2").style.display = "none";
                		break;
		}
	},

	
	OnChangelan_auto_type: function()
	{
		OBJ("box_lan_dhcp").style.display  = "none";
        	OBJ("box_lan_stless").style.display = "none";
		switch(OBJ("lan_auto_type").value)
		{
			case "STATELESS":
			      	OBJ("box_lan_dhcp").style.display = "none";
                  	      	OBJ("box_lan_stless").style.display = "";
	              	      	break;
				
			case "STATEFUL":
			      	OBJ("box_lan_dhcp").style.display = "";
                  		OBJ("dhcps_start_ip_prefix").disabled = true;
                  		OBJ("dhcps_stop_ip_prefix").disabled = true;
                  		this.ShowDHCPS6();
	              		break;
		}		
	},

        OnClickpd: function()
        {
             
                if(OBJ("en_dhcp_pd").checked)
                {
                    OBJ("l_ipaddr").disabled = true;
                }
		else
                {
                    OBJ("l_ipaddr").disabled = false;
                }
             
        },

        OnClickAuto: function()
        {
		if(OBJ("enableAuto").checked)
		{
			//OBJ("lan_auto_type").style.display = "";
			OBJ("lan_auto_type").disabled = false;
			if(OBJ("lan_auto_type").value == "STATELESS")
				OBJ("box_lan_stless").style.display = "";
			else
				OBJ("box_lan_dhcp").style.display = "";
		}
		else
		{
			//OBJ("lan_auto_type").style.display = "none";
			OBJ("lan_auto_type").disabled = true;
			if(OBJ("lan_auto_type").value == "STATELESS")
				OBJ("box_lan_stless").style.display = "none";
			else
				OBJ("box_lan_dhcp").style.display = "none";
		}
		
        },
	
	ShowDHCPS6: function()
    	{
        	var str;
        	var inflp = PXML.FindModule("RUNTIME.INF.LAN-4");
        
        	OBJ("dhcps_start_ip_value").value = XG(this.dhcps6+"/start");
        	OBJ("dhcps_stop_ip_value").value = XG(this.dhcps6+"/stop");
        	str = XG(inflp+"/runtime/inf/dhcps6/network");
        	if (str)
        	{
              		index = str.lastIndexOf("::");
              		OBJ("dhcps_start_ip_prefix").value = str.substring(0,index);
              		OBJ("dhcps_stop_ip_prefix").value = str.substring(0,index);
        	}
        	else
        	{
              		OBJ("dhcps_start_ip_prefix").value = "xxxx";
              		OBJ("dhcps_stop_ip_prefix").value = "xxxx";
        	}
        	OBJ("dhcps_start_ip_prefix").disabled = true;
        	OBJ("dhcps_stop_ip_prefix").disabled = true;
    	},
	
	/* Get lanact, lanllact, wanact and wanllact */
	ParseAll: function()
	{
        	var svc = PXML.FindModule("DHCPS6.LAN-4");

		//alert("length"+this.laninfo.length);
		for ( var lidx=0; lidx < this.laninfo.length; lidx++)
		{
			this.laninfo[lidx].svcs		= GPBT("/postxml", "module", "service", this.laninfo[lidx].svcsname, false);
			this.laninfo[lidx].inetuid	= XG(this.laninfo[lidx].svcs+"/inf/inet");
			this.laninfo[lidx].inetp	= GPBT(this.laninfo[lidx].svcs+"/inet", "entry", "uid", this.laninfo[lidx].inetuid, false);
			this.laninfo[lidx].phyinf	= XG(this.laninfo[lidx].svcs+"/inf/phyinf");
			var tRTPHYsvcs			= GPBT("/postxml", "module", "service", "RUNTIME.PHYINF", false);
			var tRTPHYphyinf		= GPBT(tRTPHYsvcs+"/runtime", "phyinf", "uid", this.laninfo[lidx].phyinf);
			this.laninfo[lidx].ipv6ll	= XG(tRTPHYphyinf+"/ipv6ll");

			//this.MyArrayShowAlert("laninfo", this.laninfo[lidx]);

			if ( XG(this.laninfo[lidx].svcs+"/inf/active") == "1" )
			{
				if ( XG(this.laninfo[lidx].inetp+"/ipv6/mode") == "LL" )
					this.FillArrayData( this.lanllact, this.laninfo[lidx] ); 
				else
					this.FillArrayData( this.lanact, this.laninfo[lidx] );
			}
		}

		for ( var widx=0; widx < this.waninfo.length; widx++)
		{
			this.waninfo[widx].svcs		= GPBT("/postxml", "module", "service", this.waninfo[widx].svcsname, false);
			this.waninfo[widx].inetuid	= XG(this.waninfo[widx].svcs+"/inf/inet");
			this.waninfo[widx].inetp	= GPBT(this.waninfo[widx].svcs+"/inet", "entry", "uid", this.waninfo[widx].inetuid, false);
			this.waninfo[widx].phyinf	= XG(this.waninfo[widx].svcs+"/inf/phyinf");
			var tRTPHYsvcs			= GPBT("/postxml", "module", "service", "RUNTIME.PHYINF", false);
			var tRTPHYphyinf		= GPBT(tRTPHYsvcs+"/runtime", "phyinf", "uid", this.waninfo[widx].phyinf);
			this.waninfo[widx].ipv6ll	= XG(tRTPHYphyinf+"/ipv6ll");

			//this.MyArrayShowAlert("laninfo", this.waninfo[widx]);

			if ( XG(this.waninfo[widx].svcs+"/inf/active") == "1" )
			{
				if ( XG(this.waninfo[widx].inetp+"/ipv6/mode") == "LL" )
					this.FillArrayData( this.wanllact, this.waninfo[widx] ); 
				else
					this.FillArrayData( this.wanact, this.waninfo[widx] );
			}
		}

        	this.dhcps6 = GPBT(svc+"/dhcps6", "entry", "uid", "DHCPS6-1", false);
        	this.dhcps6_inf  = GPBT(svc, "inf", "uid", "LAN-4", false);
        	this.dhcps6_inet = svc + "/inet/entry";

                var base = PXML.FindModule("INET.WAN-1");
                this.wan1.infp = GPBT(base, "inf", "uid", "WAN-1", false);
                this.wan1.inetp = GPBT(base+"/inet", "entry", "uid", XG(this.wan1.infp+"/inet"), false);
		//this.MyArrayShowAlert("lanact", this.lanact);
		//this.MyArrayShowAlert("lanllact", this.lanllact);
		//this.MyArrayShowAlert("wanact", this.wanact);
		//this.MyArrayShowAlert("wanllact", this.wanllact);
	},
	FillArrayData: function( to, from)
	{
		to.svcsname	= from.svcsname;
		to.svcs		= from.svcs;
		to.inetuid	= from.inetuid;
		to.inetp	= from.inetp;
		to.phyinf	= from.phyinf;
		to.ipv6ll	= from.ipv6ll;
	},

	InitWANConnMode: function()
	{
		if( this.wanact.svcsname != null )
                {
                        var addrtype = XG(this.wanllact.inetp+"/addrtype");
                        if(addrtype == "ppp6")
			     COMM_SetSelectValue(OBJ("wan_ipv6_mode"), "PPPOE");
                        else
			{
				var wanmode = XG(this.wanact.inetp+"/ipv6/mode");
				if(wanmode != "")
				{			 
					//COMM_SetSelectValue(OBJ("wan_ipv6_mode"), wanmode);
					if(wanmode == "6TO4")
					{
						var mode = XG(this.wan1.infp+"/inf6to4/mode");
						COMM_SetSelectValue(OBJ("wan_ipv6_mode"), mode);
					}
					else	
						COMM_SetSelectValue(OBJ("wan_ipv6_mode"), wanmode);
				}
				else
					COMM_SetSelectValue(OBJ("wan_ipv6_mode"), "LL");
			}
                }
		else 
			COMM_SetSelectValue(OBJ("wan_ipv6_mode"), "LL");
		return true;
	},
	InitWANStaticValue: function()
	{
		var wanmode = XG(this.wanact.inetp+"/ipv6/mode");
		if(wanmode != "STATIC") return true;
		
		OBJ("l_ipaddr").disabled = false;
		//OBJ("l_ipaddr").value	= XG(this.lanact.inetp+"/ipv6/ipaddr");

		OBJ("w_st_ipaddr").value	= XG(this.wanact.inetp+"/ipv6/ipaddr");
		OBJ("w_st_pl").value		= XG(this.wanact.inetp+"/ipv6/prefix");
		OBJ("w_st_gw").value		= XG(this.wanact.inetp+"/ipv6/gateway");
		OBJ("w_st_pdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:1");
		OBJ("w_st_sdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:2");
		return true;
	},
	InitWANDHCPValue: function()
	{
		var wanmode = XG(this.wanact.inetp+"/ipv6/mode");
		this.rlan = PXML.FindModule("RUNTIME.INF.LAN-4");
		if(wanmode != "DHCP") return true;

		SetRadioValue("w_dhcp_dns_rad", "auto")

		//var wan4 = PXML.FindModule("INET.WAN-4");
		if(XG(this.wanact.inetp+"/ipv6/dhcpopt") != "IA-NA")
		{		
			OBJ("en_dhcp_pd").checked = true;
			OBJ("l_ipaddr").disabled = true;
			OBJ("l_ipaddr").value 	= XG(this.rlan+"/runtime/inf/inet/ipv6/ipaddr");
		}
                else
		{
			OBJ("en_dhcp_pd").checked = false;
			OBJ("l_ipaddr").disabled = false;
			OBJ("l_ipaddr").value	= XG(this.lanact.inetp+"/ipv6/ipaddr");
		}

		var count = XG(this.wanact.inetp+"/ipv6/dns/count");
		if(count > 0)
		{
			OBJ("w_dhcp_dns_auto").checked = false;
			SetRadioValue("w_dhcp_dns_rad", "manual")
			OBJ("w_dhcp_pdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:1");
			if(count > 1)
                               OBJ("w_dhcp_sdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:2");
		}
		return true;
	},
	InitWANAUTOValue: function()
	{
		var wanmode = XG(this.wanact.inetp+"/ipv6/mode");
		this.rlan = PXML.FindModule("RUNTIME.INF.LAN-4");
		if(wanmode != "AUTO") return true;

		SetRadioValue("w_dhcp_dns_rad", "auto")

		//var wan4 = PXML.FindModule("INET.WAN-4");
		if(XG(this.wanact.inetp+"/ipv6/dhcpopt") != "")
		{		
			OBJ("en_dhcp_pd").checked = true;
			OBJ("l_ipaddr").disabled = true;
			OBJ("l_ipaddr").value 	= XG(this.rlan+"/runtime/inf/inet/ipv6/ipaddr");
		}
                else
		{
			OBJ("en_dhcp_pd").checked = false;
			OBJ("l_ipaddr").disabled = false;
			OBJ("l_ipaddr").value	= XG(this.lanact.inetp+"/ipv6/ipaddr");
		}

		var count = XG(this.wanact.inetp+"/ipv6/dns/count");
		if(count > 0)
		{
			OBJ("w_dhcp_dns_auto").checked = false;
			SetRadioValue("w_dhcp_dns_rad", "manual")
			OBJ("w_dhcp_pdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:1");
			if(count > 1)
                               OBJ("w_dhcp_sdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:2");
		}
		return true;
	},
	InitWANPPPoEValue: function()
	{
		var addrtype = XG(this.wanllact.inetp+"/addrtype");
		if(addrtype != "ppp6") return true;

                //OBJ("pppoe_dynamic").checked        = true;
                //OBJ("pppoe_ipaddr").value           = "";
                OBJ("pppoe_username").value         = "";
                OBJ("pppoe_password").value         = "";
                OBJ("confirm_pppoe_password").value = "";
                OBJ("pppoe_service_name").value     = "";
                //OBJ("pppoe_ondemand").check         = true;
                //OBJ("pppoe_max_idle_time").value    = "";
                
                //if(XG(this.wanllact.inetp+"/ppp6/static") === "1")  OBJ("pppoe_static").checked = true;
                //else                                                OBJ("pppoe_dynamic").checked = true;
                //OBJ("pppoe_ipaddr").value           = XG(this.wanllact.inetp+"/ppp6/ipaddr");
                OBJ("pppoe_username").value         = XG(this.wanllact.inetp+"/ppp6/username");
                OBJ("pppoe_password").value         = XG(this.wanllact.inetp+"/ppp6/password");
                OBJ("confirm_pppoe_password").value = XG(this.wanllact.inetp+"/ppp6/password");
		OBJ("ppp6_mtu").value = XG(this.wanllact.inetp+"/ppp6/mtu");
		/*
                var dialup   = XG(this.wanllact.inetp+"/ppp6/dialup/mode");
                if(dialup === "auto")             OBJ("pppoe_alwayson").checked = true;
                else if(dialup === "manual")      OBJ("pppoe_manual").checked = true;
                else                              OBJ("pppoe_ondemand").checked = true;
		*/
                //OBJ("pppoe_max_idle_time").value   = XG(this.wanllact.inetp+"/ppp6/dialup/idletimeout");
		if (XG(this.wanllact.inetp+"/ppp6/dns/count") > 0) OBJ("w_dhcp_dns_manual").checked = true;
		else OBJ("w_dhcp_dns_auto").checked = true;
		OBJ("w_dhcp_pdns").value = XG(this.wanllact.inetp+"/ppp6/dns/entry:1");
		if(XG(this.wanllact.inetp+"/ppp6/dns/count")>=2) OBJ("w_dhcp_sdns").value = XG(this.wanllact.inetp+"/ppp6/dns/entry:2"); 
                if(XG(this.wanact.inetp+"/ipv6/mode") != "")
		{		
			OBJ("en_dhcp_pd").checked = true;
			OBJ("l_ipaddr").disabled = true;
			OBJ("l_ipaddr").value 	= XG(this.rlan+"/runtime/inf/inet/ipv6/ipaddr");
		}
                else
		{
			OBJ("en_dhcp_pd").checked = false;
			OBJ("l_ipaddr").disabled = false;
			OBJ("l_ipaddr").value	= XG(this.lanact.inetp+"/ipv6/ipaddr");
		}
		return true;
	},
	InitWANRAValue: function()
	{
		this.rlan = PXML.FindModule("RUNTIME.INF.LAN-4");
		var wanmode = XG(this.wanact.inetp+"/ipv6/mode");
		if(wanmode != "RA") return true;

		var wan4 = PXML.FindModule("INET.WAN-4");
                if(XG(wan4+"/inf/dhcpc6") != "")
		{		
			OBJ("en_dhcp_pd").checked = true;
			OBJ("l_ipaddr").disabled = true;
			OBJ("l_ipaddr").value 	= XG(this.rlan+"/runtime/inf/inet/ipv6/ipaddr");
		}
                else
		{
			OBJ("en_dhcp_pd").checked = false;
			OBJ("l_ipaddr").disabled = false;
			OBJ("l_ipaddr").value	= XG(this.lanact.inetp+"/ipv6/ipaddr");
		}
                //OBJ("w_dhcp_dns_auto").checked        = true;

		SetRadioValue("w_dhcp_dns_rad", "auto")
		var count = XG(this.wanact.inetp+"/ipv6/dns/count");
		if(count > 0)
		{
			OBJ("w_dhcp_dns_auto").checked = false;
			SetRadioValue("w_dhcp_dns_rad", "manual")
			OBJ("w_dhcp_pdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:1");
			if(count > 1)
                               OBJ("w_dhcp_sdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:2");
		}
		return true;
	},
	InitWAN6IN4Value: function()
	{
		var wanmode = XG(this.wanact.inetp+"/ipv6/mode");
		if(wanmode != "6IN4") return true;

		var rwan = PXML.FindModule("RUNTIME.INF.WAN-1");
		OBJ("w_tu_lov6_ipaddr").value	= XG(this.wanact.inetp+"/ipv6/ipaddr");
		OBJ("w_tu_pl").value		= XG(this.wanact.inetp+"/ipv6/prefix");
		OBJ("w_tu_rev6_ipaddr").value	= XG(this.wanact.inetp+"/ipv6/gateway");
		OBJ("w_tu_rev4_ipaddr").value	= XG(this.wanact.inetp+"/ipv6/ipv6in4/remote");
		OBJ("w_st_pdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:1");
		OBJ("w_st_sdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:2");
		
                $addrtype = XG(rwan+"/runtime/inf/inet/addrtype");
                if($addrtype == "")
                {  
                        OBJ("w_tu_lov4_ipaddr").innerHTML = "X.X.X.X";
                }
                else
                {
                        if($addrtype == "ipv4") //static ip or dhcp
                               OBJ("w_tu_lov4_ipaddr").innerHTML	= XG(rwan+"/runtime/inf/inet/ipv4/ipaddr");
                        else if($addrtype == "ppp4") //ppp
                               OBJ("w_tu_lov4_ipaddr").innerHTML	= XG(rwan+"/runtime/inf/inet/ppp4/local");
                        else   return false; 
                }
          
		SetRadioValue("w_dhcp_dns_rad", "auto")
                var count = XG(this.wanact.inetp+"/ipv6/dns/count");
		if(count > 0)
		{
			OBJ("w_dhcp_dns_auto").checked = false;
			SetRadioValue("w_dhcp_dns_rad", "manual")
			OBJ("w_dhcp_pdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:1");
			if(count > 1)
                               OBJ("w_dhcp_sdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:2");
		}

		return true;
	},

	InitWAN6RDValue: function()
	{
		var wanmode = XG(this.wan1.infp+"/inf6to4/mode");
		if(wanmode != "6RD") return true;
		var str;

		/* we get 6RD info from runtime */
		this.rwan = PXML.FindModule("RUNTIME.INF.WAN-4");
		this.rlan = PXML.FindModule("RUNTIME.INF.LAN-4");
		var rwan1 = PXML.FindModule("RUNTIME.INF.WAN-1");
		
                /* check wan is 6RD or not */
		if(XG(this.wan1.infp+"/inf6to4/mode") == "6RD")
		{ 
			OBJ("w_6rd_prefix_1").value 	= XG(this.wan1.infp+"/inf6to4/ipaddr");
			OBJ("w_6rd_prefix_2").value 	= XG(this.wan1.infp+"/inf6to4/prefix");
			OBJ("w_6rd_v4addr_mask").value 	= XG(this.wan1.infp+"/inf6to4/mask");
			OBJ("w_6rd_v4addr").value	= XG(rwan1+"/runtime/inf/inet/ipv4/ipaddr");
			str = XG(this.rlan+"/runtime/inf/dhcps6/network");
			str1 = XG(this.rwan+"/runtime/inf/inet/ipv6/prefix");
			if(str)
			{
				//index = str.lastIndexOf("::");
				OBJ("w_6rd_prefix_3").innerHTML = str+"/"+str1;
			}
			OBJ("w_6rd_relay").value 	= XG(this.wan1.infp+"/inf6to4/relay");
			OBJ("l_ipaddr").value 		= XG(this.rlan+"/runtime/inf/inet/ipv6/ipaddr");
			OBJ("w_6rd_pdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:1");
			OBJ("w_6rd_sdns").value		= XG(this.wanact.inetp+"/ipv6/dns/entry:2");
			OBJ("l_ipaddr").disabled 		= true;
			OBJ("w_6rd_v4addr").disabled 	= true;
		}
		else
		{
			//OBJ("w_6rd_addr").innerHTML = "";
			OBJ("l_ipaddr").value 	= "";
			OBJ("w_6rd_pdns").value	= "";
			OBJ("w_6rd_sdns").value	= "";
			OBJ("l_ipaddr").disabled 	= false;
			OBJ("w_6rd_v4addr").disabled = false;
		}

		/* fill some fixed info */
		/*OBJ("w_6to4_pl").innerHTML = "/16";*/
		return true;
	},

	InitWAN6TO4Value: function()
	{
		var wanmode = XG(this.wan1.infp+"/inf6to4/mode");
		if(wanmode != "6TO4") return true;
		/* we get 6to4 info from runtime */
		this.rwan = PXML.FindModule("RUNTIME.INF.WAN-4");
		this.rlan = PXML.FindModule("RUNTIME.INF.LAN-4");
		
        	/* check wan is 6TO4 or not */
        	if(XG(this.rwan+"/runtime/inf/inet/ipv6/mode") == "6TO4")
        	{	 
			OBJ("w_6to4_ipaddr").innerHTML = XG(this.rwan+"/runtime/inf/inet/ipv6/ipaddr");
			OBJ("l_ipaddr").value 	= XG(this.rlan+"/runtime/inf/inet/ipv6/ipaddr");
                     	OBJ("w_6to4_relay").value	 = XG(this.wan1.infp+"/inf6to4/relay");
			OBJ("w_6to4_pdns").value	= XG(this.wanact.inetp+"/ipv6/dns/entry:1");
			OBJ("w_6to4_sdns").value	= XG(this.wanact.inetp+"/ipv6/dns/entry:2");
			if (this.rgmode)	OBJ("l_ipaddr").disabled 	= true;
		}
        	else
        	{
             		OBJ("w_6to4_ipaddr").innerHTML = "";
		     	OBJ("l_ipaddr").value 	= "";
		     	OBJ("w_6to4_pdns").value	= "";
		     	OBJ("w_6to4_sdns").value	= "";
		     	if (this.rgmode)	OBJ("l_ipaddr").disabled 	= false;
        	}
		/* fill some fixed info */
		/*OBJ("w_6to4_pl").innerHTML = "/16";*/
		return true;
	},
	InitWANInfo: function()
	{
		OBJ("en_dhcp_pd").checked = true;
		OBJ("w_dhcp_dns_auto").checked = true;
		OBJ("l_ipaddr").disabled = true;
		//OBJ("pppoe_dynamic").checked = true; //20100614
		if( this.wanact.svcsname != null )
		{
			/* init value */
			if (!this.InitWANStaticValue()) return false;
			if (!this.InitWANAUTOValue()) return false;
			if (!this.InitWANDHCPValue()) return false;
			if (!this.InitWANPPPoEValue()) return false;
			if (!this.InitWANRAValue()) return false;
			if (!this.InitWAN6IN4Value()) return false;
			if (!this.InitWAN6RDValue()) return false;
			if (!this.InitWAN6TO4Value()) return false;
		}

		return true;
	},

	InitWANLLInfo: function()
	{
		if( this.wanllact.svcsname != null ) /* this should not happen */
		{
			//this.MyArrayShowAlert(this.wanllact);
			OBJ("wan_ll").innerHTML    = this.wanllact.ipv6ll;
			OBJ("wan_ll_pl").innerHTML    = "/64";
		}
		return true;
	},
	InitLANInfo: function()
	{
		//this.MyArrayShowAlert("lanact",this.lanact);
		if( this.lanact.svcsname != null )  /* lan mode has be set -> lanact is not null -> we need fill all lan info*/
		{
			switch (XG(this.lanact.inetp+"/ipv6/mode"))
			{
				case "STATIC":
					OBJ("l_ipaddr").value	= XG(this.lanact.inetp+"/ipv6/ipaddr");
					if(XG(this.wanact.inetp+"/ipv6/mode") == "STATIC")
					{
						OBJ("l_range_start_pl").innerHTML	= "/64";
						OBJ("l_range_end_pl").innerHTML	= "/64";
                        		}
                        		else //6IN4
                        		{;}
					break;

				case "DHCP":
					break;

				case "RA":
					break;

				case "PPPOE":
					break;

				case "6IN4":
					break;

				case "6TO4":
					/* TODO */
					break;
			}
		}

		/* fill some fixed info */
		OBJ("l_pl").innerHTML	= "/64";

		return true;
	},
	InitLANLLInfo: function(addrmode)
	{
		if( this.lanllact.svcsname != null ) /* this should not happen */
		{
			OBJ("lan_ll").innerHTML    = this.lanllact.ipv6ll;
			OBJ("lan_ll_pl").innerHTML    = "/64";
		}
		return true;
	},
	InitLANAutoConf: function()
	{
		var dhcps6 = XG(this.lanact.svcs+"/inf/dhcps6");		
		if(dhcps6 != "")
		{
			OBJ("enableAuto").checked = true;
		}
		else
		{
			OBJ("enableAuto").checked = false;
		}
                return true;
	},
	PreWAN: function()
	{
		if (this.wanact.svcsname == null )
		{
			for ( var widx=0; widx < this.waninfo.length; widx++)
			{
				if ( this.waninfo[widx].svcsname == "INET.WAN-4" )
				{
					this.FillArrayData( this.wanact, this.waninfo[widx] );
					break;
				}
			}
		}
		var wan1 = PXML.FindModule("INET.WAN-1");
		var wan3 = PXML.FindModule("INET.WAN-3");
		var wan4 = PXML.FindModule("INET.WAN-4");
		XS(this.wanact.svcs+"/inf/phyinf", "ETH-2");
		XS(wan3+"/inf/infnext", "");  
		XS(wan4+"/inf/infprevious", ""); 
		// get "wan mode" then set wan info
		switch(OBJ("wan_ipv6_mode").value)
		{
			case "STATIC":
				XS(wan1+"/inf/inf6to4/mode", "");
				XS(wan1+"/inf/infnext", "");
				XS(wan4+"/inf/child", "");
				XS(wan4+"/inf/dhcpc6", "");
           			XS(this.wanllact.inetp+"/addrtype", "ipv6");
				XS(this.wanact.svcs+"/inf/active", "1");
				XS(this.wanact.inetp+"/ipv6/mode", "STATIC");

				/* ipaddr, prefix */
				XS(this.wanact.inetp+"/ipv6/ipaddr", OBJ("w_st_ipaddr").value);
				XS(this.wanact.inetp+"/ipv6/prefix", OBJ("w_st_pl").value);

				/* gateway */
				if (OBJ("w_st_gw").value != "") 
				{	XS(this.wanact.svcs+"/inf/defaultroute", "1");	}
				else
				{	XS(this.wanact.svcs+"/inf/defaultroute", "0");	}
				XS(this.wanact.inetp+"/ipv6/gateway", OBJ("w_st_gw").value);

				/* dns */
				var dnscnt = 0;
				if (OBJ("w_st_pdns").value != "")	dnscnt++;
				if (OBJ("w_st_sdns").value != "")	dnscnt++;
				XS(this.wanact.inetp+"/ipv6/dns/count", dnscnt);
				XS(this.wanact.inetp+"/ipv6/dns/entry:1", OBJ("w_st_pdns").value);
				XS(this.wanact.inetp+"/ipv6/dns/entry:2", OBJ("w_st_sdns").value);
				break;

			case "AUTO":
				XS(wan1+"/inf/inf6to4/mode", "");
				XS(wan1+"/inf/infnext", "");
           			XS(this.wanllact.inetp+"/addrtype", "ipv6");
				XS(this.wanact.svcs+"/inf/active", "1");
				XS(this.wanact.inetp+"/ipv6/mode", "AUTO");
				
				if(OBJ("en_dhcp_pd").checked) 
				{
					XS(wan4+"/inf/child", "LAN-4");
					XS(wan4+"/inf/dhcpc6", "LAN-4");
					XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-NA+IA-PD");//maybe change when service starts
				}
				else 	 
				{
					XS(wan4+"/inf/child", "");
					XS(wan4+"/inf/dhcpc6", "");
					XS(this.wanact.inetp+"/ipv6/dhcpopt", "");
				}
				
				/* dns */
				var dnscnt = 0;
				if (OBJ("w_dhcp_dns_auto").checked)
				{
					XS(this.wanact.inetp+"/ipv6/dns/entry:1","");
					XS(this.wanact.inetp+"/ipv6/dns/entry:2","");
				}
				else
				{
					if(OBJ("w_dhcp_pdns").value != "")
					{
						XS(this.wanact.inetp+"/ipv6/dns/entry:1", OBJ("w_dhcp_pdns").value);
						dnscnt+=1;
					}
					if(OBJ("w_dhcp_sdns").value != "")
					{
						XS(this.wanact.inetp+"/ipv6/dns/entry:2", OBJ("w_dhcp_sdns").value);
						dnscnt+=1;
					}
				}
				XS(this.wanact.inetp+"/ipv6/dns/count", dnscnt);
					
				break;

			case "DHCP":
				XS(wan1+"/inf/inf6to4/mode", "");
				XS(wan1+"/inf/infnext", "");
				//XS(wan4+"/inf/child", "LAN-4");
				XS(wan4+"/inf/dhcpc6", "");
           			XS(this.wanllact.inetp+"/addrtype", "ipv6");
				XS(this.wanact.svcs+"/inf/active", "1");
				XS(this.wanact.inetp+"/ipv6/mode", "DHCP");
                
				if(OBJ("en_dhcp_pd").checked) 
				{
					XS(wan4+"/inf/child", "LAN-4");
					XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-NA+IA-PD");
					//XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-PD");//for test
				}
				else 	 
				{
					XS(wan4+"/inf/child", "");
					XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-NA");
				}
				
				/* dns */
				var dnscnt = 0;
				if (OBJ("w_dhcp_dns_auto").checked)
				{
					XS(this.wanact.inetp+"/ipv6/dns/entry:1","");
					XS(this.wanact.inetp+"/ipv6/dns/entry:2","");
				}
				else
				{
					if(OBJ("w_dhcp_pdns").value != "")
					{
						XS(this.wanact.inetp+"/ipv6/dns/entry:1", OBJ("w_dhcp_pdns").value);
						dnscnt+=1;
					}
					if(OBJ("w_dhcp_sdns").value != "")
					{
						XS(this.wanact.inetp+"/ipv6/dns/entry:2", OBJ("w_dhcp_sdns").value);
						dnscnt+=1;
					}
				}
				XS(this.wanact.inetp+"/ipv6/dns/count", dnscnt);
				break;

			case "RA":
				XS(wan1+"/inf/inf6to4/mode", "");
				XS(wan1+"/inf/infnext", "");
				XS(wan4+"/inf/child", "");
           			XS(this.wanllact.inetp+"/addrtype", "ipv6");
				//XS(wan4+"/inf/dhcpc6", "LAN-4");/* run dhcpc in RA mode */
				XS(this.wanact.svcs+"/inf/active", "1");
				XS(this.wanact.inetp+"/ipv6/mode", "RA");
                                //XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-PD");
				if(OBJ("en_dhcp_pd").checked)
				{  
					XS(wan4+"/inf/dhcpc6", "LAN-4");
					XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-PD");
				}
				else
				{ 	 
					XS(wan4+"/inf/dhcpc6", "");
					XS(this.wanact.inetp+"/ipv6/dhcpopt", "");
				}
				
				/* dns */
				var dnscnt = 0;
				if (OBJ("w_dhcp_dns_auto").checked)
				{
					XS(this.wanact.inetp+"/ipv6/dns/entry:1","");
					XS(this.wanact.inetp+"/ipv6/dns/entry:2","");
				}
				else
				{
					if(OBJ("w_dhcp_pdns").value != "")
					{
						XS(this.wanact.inetp+"/ipv6/dns/entry:1", OBJ("w_dhcp_pdns").value);
						dnscnt+=1;
					}
					if(OBJ("w_dhcp_sdns").value != "")
					{
						XS(this.wanact.inetp+"/ipv6/dns/entry:2", OBJ("w_dhcp_sdns").value);
						dnscnt+=1;
					}
				}
				XS(this.wanact.inetp+"/ipv6/dns/count", dnscnt);
				break;

			case "PPPOE":
				XS(wan1+"/inf/inf6to4/mode", "");
				XS(wan1+"/inf/infnext", "");
				XS(wan3+"/inf/infnext", "WAN-4");  /* if enable dhcp-pd to bring up dhcp6c */
				XS(wan4+"/inf/infprevious", "WAN-3");  /* if enable dhcp-pd to bring up dhcp6c */
				//XS(wan4+"/inf/child", "LAN-4");
				XS(wan4+"/inf/dhcpc6", "");
				XS(this.wanact.svcs+"/inf/active", "1");
				XS(this.wanact.svcs+"/inf/phyinf", "PPP.WAN-3");
				//XS(this.wanact.inetp+"/ipv6/mode", "DHCP");
				if(OBJ("en_dhcp_pd").checked) 
				{
					XS(this.wanact.inetp+"/ipv6/mode", "DHCP");
					XS(wan4+"/inf/child", "LAN-4");
					//XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-NA+IA-PD");
					XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-PD");
				}
				else 	 
				{
					XS(this.wanact.inetp+"/ipv6/mode", "");
					XS(wan4+"/inf/child", "");
					//XS(this.wanact.inetp+"/ipv6/dhcpopt", "IA-NA");
				}
                                if(!this.PrePppoe())  return false; 
				break;

			case "6IN4":
				XS(wan1+"/inf/inf6to4/mode", "");
				XS(wan1+"/inf/infnext", "");
				XS(wan4+"/inf/child", "");
				XS(wan4+"/inf/dhcpc6", "");
           			XS(this.wanllact.inetp+"/addrtype", "ipv6");
				XS(this.wanact.svcs+"/inf/active", "1");
				XS(this.wanact.inetp+"/ipv6/mode", "6IN4");

				/* ipaddr, prefix */
				XS(this.wanact.inetp+"/ipv6/ipaddr", OBJ("w_tu_lov6_ipaddr").value);
				XS(this.wanact.inetp+"/ipv6/prefix", OBJ("w_tu_pl").value);

				/* gateway */
				XS(this.wanact.svcs+"/inf/defaultroute", "1");	
				XS(this.wanact.inetp+"/ipv6/gateway", OBJ("w_tu_rev6_ipaddr").value);

				/* dns */
				var dnscnt = 0;
				if (OBJ("w_dhcp_dns_auto").checked)
				{
					XS(this.wanact.inetp+"/ipv6/dns/entry:1","");
					XS(this.wanact.inetp+"/ipv6/dns/entry:2","");
				}
				else
				{
					if(OBJ("w_dhcp_pdns").value != "")
					{
						XS(this.wanact.inetp+"/ipv6/dns/entry:1", OBJ("w_dhcp_pdns").value);
						dnscnt+=1;
					}
					if(OBJ("w_dhcp_sdns").value != "")
					{
						XS(this.wanact.inetp+"/ipv6/dns/entry:2", OBJ("w_dhcp_sdns").value);
						dnscnt+=1;
					}
				}
				XS(this.wanact.inetp+"/ipv6/dns/count", dnscnt);

                                /* set ipv4 address for server */
				XS(this.wanact.inetp+"/ipv6/ipv6in4/remote", OBJ("w_tu_rev4_ipaddr").value);
                                
				break;

			case "6TO4":
				XS(wan1+"/inf/inf6to4/mode", "6TO4");
				XS(wan1+"/inf/infnext", "WAN-4");
				XS(wan1+"/inf/inf6to4/relay", OBJ("w_6to4_relay").value);
				XS(wan4+"/inf/child", "LAN-4");
				XS(wan4+"/inf/dhcpc6", "");
				XS(wan4+"/inf/infprevious", "WAN-1");
           			XS(this.wanllact.inetp+"/addrtype", "ipv6");
				XS(this.wanact.svcs+"/inf/active", "1");
				XS(this.wanact.inetp+"/ipv6/mode", "6TO4");

				/* dns */
				var dnscnt = 0;
				if (OBJ("w_6to4_pdns").value != "")	dnscnt++;
				if (OBJ("w_6to4_sdns").value != "")	dnscnt++;
				XS(this.wanact.inetp+"/ipv6/dns/count", dnscnt);
				XS(this.wanact.inetp+"/ipv6/dns/entry:1", OBJ("w_6to4_pdns").value);
				XS(this.wanact.inetp+"/ipv6/dns/entry:2", OBJ("w_6to4_sdns").value);
				break;
                          
			case "6RD":
				XS(wan1+"/inf/inf6to4/mode", "6RD");
				XS(wan1+"/inf/infnext", "WAN-4");
				XS(wan1+"/inf/inf6to4/ipaddr", OBJ("w_6rd_prefix_1").value);
				XS(wan1+"/inf/inf6to4/prefix", OBJ("w_6rd_prefix_2").value);
				XS(wan1+"/inf/inf6to4/relay", OBJ("w_6rd_relay").value);
				XS(wan1+"/inf/inf6to4/mask", OBJ("w_6rd_v4addr_mask").value);
				XS(wan4+"/inf/child", "LAN-4");
				XS(wan4+"/inf/dhcpc6", "");
				XS(wan4+"/inf/infprevious", "WAN-1");
           			XS(this.wanllact.inetp+"/addrtype", "ipv6");
				XS(this.wanact.svcs+"/inf/active", "1");
				XS(this.wanact.inetp+"/ipv6/mode", "6TO4");

				/* dns */
				var dnscnt = 0;
				if (OBJ("w_6rd_pdns").value != "")	dnscnt++;
				if (OBJ("w_6rd_sdns").value != "")	dnscnt++;
				XS(this.wanact.inetp+"/ipv6/dns/count", dnscnt);
				XS(this.wanact.inetp+"/ipv6/dns/entry:1", OBJ("w_6rd_pdns").value);
				XS(this.wanact.inetp+"/ipv6/dns/entry:2", OBJ("w_6rd_sdns").value);
				break;

                        case "LL":
				XS(wan1+"/inf/inf6to4/mode", "");
				XS(wan1+"/inf/infnext", "");
				XS(wan4+"/inf/child", "");
				XS(wan4+"/inf/dhcpc6", "");
           			XS(this.wanllact.inetp+"/addrtype", "ipv6");
				XS(this.wanact.inetp+"/ipv6/mode", "");
				//XS(this.wanact.svcs+"/inf/active", "0");
                                break;
                         
		}
		
		return true;
	},

	PreLAN: function()
	{
		if (this.lanact.svcsname == null )
		{
			for ( var lidx=0; lidx < this.laninfo.length; lidx++)
			{
				if ( this.laninfo[lidx].svcsname == "INET.LAN-4" )
				{
					this.FillArrayData( this.lanact, this.laninfo[lidx] );
					break;
				}
			}
		}
		// get "wan mode" then set lan info
		switch(OBJ("wan_ipv6_mode").value)
		{
			case "STATIC":
				XS(this.lanact.svcs+"/inf/active", "1");
				XS(this.lanact.inetp+"/ipv6/mode", "STATIC");

				/* ipaddr, prefix*/
				XS(this.lanact.inetp+"/ipv6/ipaddr", OBJ("l_ipaddr").value);
				var tmp = OBJ("l_pl").innerHTML;
				var tmplstpl = tmp.split("/"); /*cut slash */	
				XS(this.lanact.inetp+"/ipv6/prefix", tmplstpl[1]);
				break;

			case "DHCP":
				//XS(this.lanact.svcs+"/inf/active", "1");
				//XS(this.lanact.inetp+"/ipv6/mode", "");
				//XS(this.lanact.inetp+"/ipv6/ipaddr", "");
				//XS(this.lanact.inetp+"/ipv6/prefix", "");
				if(!OBJ("en_dhcp_pd").checked)
				{
					XS(this.lanact.svcs+"/inf/active", "1");
					XS(this.lanact.inetp+"/ipv6/mode", "STATIC");

					/* ipaddr, prefix*/
					XS(this.lanact.inetp+"/ipv6/ipaddr", OBJ("l_ipaddr").value);
					var tmp = OBJ("l_pl").innerHTML;
					var tmplstpl = tmp.split("/"); /*cut slash */	
					XS(this.lanact.inetp+"/ipv6/prefix", tmplstpl[1]);
				}
				else
				{
					XS(this.lanact.svcs+"/inf/active", "1");
					XS(this.lanact.inetp+"/ipv6/mode", "");

					/* ipaddr, prefix*/
					//OBJ("l_ipaddr").value = "";
					//XS(this.lanact.inetp+"/ipv6/ipaddr", OBJ("l_ipaddr").value);
					//var tmp = OBJ("l_pl").innerHTML;
					//var tmplstpl = tmp.split("/"); /*cut slash */	
					//XS(this.lanact.inetp+"/ipv6/prefix", tmplstpl[1]);
				}
				break;

			case "RA":
				if(!OBJ("en_dhcp_pd").checked)
				{
					XS(this.lanact.svcs+"/inf/active", "1");
					XS(this.lanact.inetp+"/ipv6/mode", "STATIC");

					/* ipaddr, prefix*/
					XS(this.lanact.inetp+"/ipv6/ipaddr", OBJ("l_ipaddr").value);
					var tmp = OBJ("l_pl").innerHTML;
					var tmplstpl = tmp.split("/"); /*cut slash */	
					XS(this.lanact.inetp+"/ipv6/prefix", tmplstpl[1]);
				}
				else
				{
					XS(this.lanact.svcs+"/inf/active", "1");
					XS(this.lanact.inetp+"/ipv6/mode", "");

					/* ipaddr, prefix*/
					//OBJ("l_ipaddr").value = "";
					//XS(this.lanact.inetp+"/ipv6/ipaddr", OBJ("l_ipaddr").value);
					//var tmp = OBJ("l_pl").innerHTML;
					//var tmplstpl = tmp.split("/"); /*cut slash */	
					//XS(this.lanact.inetp+"/ipv6/prefix", tmplstpl[1]);
				}
				break;

			case "PPPOE":
				if(!OBJ("en_dhcp_pd").checked)
				{
					XS(this.lanact.svcs+"/inf/active", "1");
					XS(this.lanact.inetp+"/ipv6/mode", "STATIC");

					/* ipaddr, prefix*/
					XS(this.lanact.inetp+"/ipv6/ipaddr", OBJ("l_ipaddr").value);
					var tmp = OBJ("l_pl").innerHTML;
					var tmplstpl = tmp.split("/"); /*cut slash */	
					XS(this.lanact.inetp+"/ipv6/prefix", tmplstpl[1]);
				}
				else
				{
					XS(this.lanact.svcs+"/inf/active", "1");
					XS(this.lanact.inetp+"/ipv6/mode", "");

					/* ipaddr, prefix*/
					//OBJ("l_ipaddr").value = "";
					//XS(this.lanact.inetp+"/ipv6/ipaddr", OBJ("l_ipaddr").value);
					//var tmp = OBJ("l_pl").innerHTML;
					//var tmplstpl = tmp.split("/"); /*cut slash */	
					//XS(this.lanact.inetp+"/ipv6/prefix", tmplstpl[1]);
				}
				break;

			case "6IN4":
				XS(this.lanact.svcs+"/inf/active", "1");
				XS(this.lanact.inetp+"/ipv6/mode", "STATIC");

				/* ipaddr, prefix*/
				XS(this.lanact.inetp+"/ipv6/ipaddr", OBJ("l_ipaddr").value);
				var tmp = OBJ("l_pl").innerHTML;
				var tmplstpl = tmp.split("/"); /*cut slash */	
				XS(this.lanact.inetp+"/ipv6/prefix", tmplstpl[1]);
				break;

			case "6TO4":
				XS(this.lanact.svcs+"/inf/active", "1");
				XS(this.lanact.inetp+"/ipv6/mode", "");
				break;
 
			case "6RD":
				XS(this.lanact.svcs+"/inf/active", "1");
				XS(this.lanact.inetp+"/ipv6/mode", "");
				break;

                        case "LL":
				XS(this.lanact.svcs+"/inf/active", "1");
				XS(this.lanact.inetp+"/ipv6/mode", "");
				break;
		}
/*		
		switch(OBJ("lan_auto_type").value)
		{
			case "STATELESS":
		                XS(this.dhcps6+"/mode", "STATELESS");
                                break;

			case "STATEFUL":
		                XS(this.dhcps6+"/mode", "STATEFUL");
                                XS(this.dhcps6+"/start", OBJ("dhcps_start_ip_value").value);
                                XS(this.dhcps6+"/stop", OBJ("dhcps_stop_ip_value").value);
                                break;
		}
*/             
                //if(!OBJ("enableAuto").checked)  XS(this.lanact.inetp+"/ipv6/mode","LL");	
		
		if(OBJ("enableAuto").checked)
		{
			XS(this.lanact.svcs+"/inf/dhcps6", "DHCPS6-1");
			XS(this.dhcps6_inf+"/dhcps6", "DHCPS6-1");
			switch(OBJ("lan_auto_type").value)
			{
				case "STATELESS":
		                	XS(this.dhcps6+"/mode", "STATELESS");
					if(OBJ("ra_lifetime").value!="")
						XS(this.lanact.inetp+"/ipv6/routerlft", 60*OBJ("ra_lifetime").value);
                                	break;

				case "STATEFUL":
		                	XS(this.dhcps6+"/mode", "STATEFUL");
                                	XS(this.dhcps6+"/start", OBJ("dhcps_start_ip_value").value);
                                	XS(this.dhcps6+"/stop", OBJ("dhcps_stop_ip_value").value);
					if(OBJ("ip_lifetime").value!="")
					{
						XS(this.lanact.inetp+"/ipv6/preferlft", 60*OBJ("ip_lifetime").value);
						XS(this.lanact.inetp+"/ipv6/validlft", 2*60*OBJ("ip_lifetime").value);
					}
                                	break;
			}
		}
		else
		{
			XS(this.lanact.svcs+"/inf/dhcps6", "");
			XS(this.dhcps6_inf+"/dhcps6", "");
		}
		
		
		return true;
	},

	PreLANAutoConf: function()
	{
		/* need TODO...*/
		return true;
	},

	InitLANConnMode: function()
	{
		if(!this.dhcps6) return false;
		//alert(XG(this.dhcps6+"/mode"));
		COMM_SetSelectValue(OBJ("lan_auto_type"), XG(this.dhcps6+"/mode"));
		//COMM_SetSeletValue(OBJ("lan_auto_type"), "1");
		return true;
	},
	InitDHCPS6: function()
	{
		var svc = PXML.FindModule("DHCPS6.LAN-4");
		var inflp = PXML.FindModule("RUNTIME.INF.LAN-4");
		var str;
		var index;

		//if(XG(this.lanact.inetp+"/ipv6/mode") != "DHCP")
		//    return true;

		str = XG(inflp+"/runtime/inf/dhcps6/network");
		if (!svc || !inflp)
		{
			/*alert("InitDHCPS6() ERROR !");*/
			return false;
		}
		if (!this.dhcps6)   return false;
            
		switch (XG(this.dhcps6+"/mode"))
		{
			case "STATELESS":
                		//COMM_SetSeletValue(OBJ("lan_auto_type"), "STATELESS");
				//OBJ("lan_auto_type").value = "STATELESS";
                		OBJ("box_lan_dhcp").style.display = "none";
                      		OBJ("box_lan_stless").style.display = "";
				if(XG(this.lanact.inetp+"/ipv6/routerlft") != "")
					OBJ("ra_lifetime").value = XG(this.lanact.inetp+"/ipv6/routerlft")/60;
				else
					OBJ("ra_lifetime").value = "";
                      		break;
                     
                 	case "STATEFUL":
                      		//COMM_SetSeletValue(OBJ("lan_auto_type"), "STATEFUL");
                      		//OBJ("lan_aulto_type").value = "STATEFUL";
                      		OBJ("box_lan_dhcp").style.display = "";
                      		OBJ("dhcps_start_ip_value").value = XG(this.dhcps6+"/start");
                      		OBJ("dhcps_stop_ip_value").value = XG(this.dhcps6+"/stop");
				if(XG(this.lanact.inetp+"/ipv6/preferlft") != "")
					OBJ("ip_lifetime").value = XG(this.lanact.inetp+"/ipv6/preferlft")/60;
				else
					OBJ("ip_lifetime").value = "";
              
                      		if (str)
                      		{
                           		index = str.lastIndexOf("::");
                           		OBJ("dhcps_start_ip_prefix").value = str.substring(0,index);
                           		OBJ("dhcps_stop_ip_prefix").value = str.substring(0,index);
                      		}
                      		else
                      		{
                           		OBJ("dhcps_start_ip_prefix").value = "xxxx";
                           		OBJ("dhcps_stop_ip_prefix").value = "xxxx";
                      		}  
                      		//OBJ("dhcps_start_ip_prefix").disabled = true;
                      		//OBJ("dhcps_stop_ip_prefix").disabled = true;
                      		break;
            	}
        	return true;
	},

	PreDHCPS6: function()
	{
        	var lan = PXML.FindModule("DHCPS6.LAN-4");
            	switch (OBJ("lan_auto_type").value)
            	{	
                	case "STATELESS":
                      		//XS(lan+"/inf/dhcps6",    "");
                      		XS(this.lanact.inetp+"/ipv6/mode", "RA");
                      		XS(this.dhcps6+"/mode", OBJ("lan_auto_type").value);
                      		break;

                	case "STATEFUL":
                      		XS(lan+"/inf/dhcps6",    "DHCPS6-1");
                      		XS(this.dhcps6+"/mode", OBJ("lan_auto_type").value);
                      		XS(this.dhcps6+"/start", OBJ("dhcps_start_ip_value").value);
                      		XS(this.dhcps6+"/stop", OBJ("dhcps_stop_ip_value").value);
                      		XS(this.lanact.inetp+"/ipv6/mode", "DHCP");
                      		break;
            	}	
            	return true;
    	},

	PrePppoe: function()
	{
        	var cnt;
           	if(OBJ("pppoe_password").value !== OBJ("confirm_pppoe_password").value)
           	{
                	BODY.ShowAlert("<?echo i18n("The password is mismatched.");?>");
                	return null;
           	}
           	XS(this.wanllact.inetp+"/addrtype", "ppp6");
           	XS(this.wanllact.inetp+"/ppp6/username", OBJ("pppoe_username").value);
           	XS(this.wanllact.inetp+"/ppp6/password", OBJ("pppoe_password").value);    
           
           	/*not implement yet*/
           	//XS(this.wanllact.inetp+"/ppp6/servicename", OBJ("pppoe_service_name").value);
           	
           	//if(OBJ("pppoe_dynamic").checked)
		if(1)
           	{
                 	XS(this.wanllact.inetp+"/ppp6/static", "0");
                	XD(this.wanllact.inetp+"/ppp6/ipaddr");
           	}
           	else
           	{
                 	XS(this.wanllact.inetp+"/ppp6/static", "1");
                 	XS(this.wanllact.inetp+"/ppp6/ipaddr", OBJ("pppoe_ipaddr").value);
                 	if(OBJ("w_dhcp_dns_manual").checked && OBJ("w_dhcp_pdns").value === "")
                 	{
                       		BODY.ShowAlert("<?echo i18n("Invalid Primary DNS address.");?>");
                       		return null;
                 	}
           	}
           	
           	//XS(this.wanllact.inetp+"/ppp6/static", "0");
           	//XD(this.wanllact.inetp+"/ppp6/ipaddr");

		/* dns */
		cnt = 0;
		if (OBJ("w_dhcp_dns_auto").checked)
		{
			XS(this.wanllact.inetp+"/ppp6/dns/entry:1","");
			XS(this.wanllact.inetp+"/ppp6/dns/entry:2","");
		}
		else
		{
			if(OBJ("w_dhcp_pdns").value != "")
			{
				XS(this.wanllact.inetp+"/ppp6/dns/entry", OBJ("w_dhcp_pdns").value);
				cnt+=1;
			}
			if(OBJ("w_dhcp_sdns").value != "")
			{
				XS(this.wanllact.inetp+"/ppp6/dns/entry", OBJ("w_dhcp_sdns").value);
				cnt+=1;
			}
		}
		XS(this.wanllact.inetp+"/ppp6/dns/count", cnt);
		XS(this.wanllact.inetp+"/ppp6/mtu", OBJ("ppp6_mtu").value);
		/* 
           	if(OBJ("pppoe_max_idle_time").value === "") OBJ("pppoe_max_idle_time").value = 0;
           	if(!TEMP_IsDigit(OBJ("pppoe_max_idle_time").value))
           	{
                	BODY.ShowAlert("<?echo i18n("Invalid value for idle timeout.");?>");
                	return null;
           	}
           	XS(this.wanllact.inetp+"/ppp6/dialup/idletimeout", OBJ("pppoe_max_idle_time").value);
		*/
          	/* 
           	var dialup = "ondemand";
           	if(OBJ("pppoe_alwayson").checked)
           	{
                	dialup = "auto";
           	}
           	else if(OBJ("pppoe_manual").checked)    dialup = "manual";
		*/
		var dialup = "auto";
           	XS(this.wanllact.inetp+"/ppp6/dialup/mode", dialup);          

           	/* need to check DHCP-PD is enable or not */
           	//XS(this.wanact.inetp+"/addrtype", "ipv6");
           	//XS(this.wanact.inetp+"/ipv6/mode", "DHCP");

           	return true;
	},
        
        /*PPPoE*/
	/*
	OnClickPppoeAddrType: function()
	{
		OBJ("pppoe_ipaddr").disabled = OBJ("pppoe_dynamic").checked ? true: false;
	},
	*/
        OnClickPppoeReconnect: function()
        {
		/*
                if(OBJ("pppoe_alwayson").checked)
                {
                      OBJ("pppoe_max_idle_time").disabled = true;
                }
                else if(OBJ("pppoe_ondemand").checked)
                {
                      OBJ("pppoe_max_idle_time").disabled = false;
                }
                else
                {
                      OBJ("pppoe_max_idle_time").disabled = true;
                }
		*/
        },

	OnClickDHCPDNS: function()
	{
		OBJ("w_dhcp_pdns").disabled = OBJ("w_dhcp_sdns").disabled = OBJ("w_dhcp_dns_auto").checked ? true: false;
	},
  	
    	MyArrayShowAlert: function( promptInfo, target )
	{
		alert( promptInfo	+ ": " +
			target.svcsname	+ "  " +
			target.svcs		+ "  " +
			target.inetuid	+ "  " +
             			target.inetp	+ "  " +
			target.phyinf	+ "  " +
			target.ipv6ll);	
	}
}

function GetRadioValue(name)
{
	var obj = document.getElementsByName(name);
	for (var i=0; i<obj.length; i++)
	{
		if (obj[i].checked)	return obj[i].value;
	}
}

function SetRadioValue(name, value)
{
	var obj = document.getElementsByName(name);
	for (var i=0; i<obj.length; i++)
	{
		if (obj[i].value==value)
		{
			obj[i].checked = true;
			break;
		}
	}
}  
</script>
