//GTM id - originally belongs to index
//var propertyGtmId = 'GTM-{ID}'; exploded from GTM-ID
//var propertyUaId = 'UA-XXXXXX'
//var propertyAwId = 'AW-XXXXXX' 
//var propertyTwitter = script tags
//var propertyFacebookId = xxxxxxxxxxxxxxxx
//var propertyAddThis = script tags
//var propertyAddThisId = ra-xxxxxxxxxxxxxx
//var propertyPingdomId = apiKey;

var ccPerformanceIndex = ['_dc_gtm_UA*', '_ga', '_gid', '__utma', '__utmb', '__utmc', '__utmz'];
var ccFunctionalIndex = ['PREF', 'VISITOR_INFO1_LIVE', 'rl_modals', '__distillery', 'AWSALB', '__atuvc', '__atuvs', 'ct_checkjs', 'ct_fkp_timestamp', 'ct_pointer_data', 'ct_ps_timestamp', 'ct_timezone', '__utmt', '_sdsat_BasketAmountItems', 'affiliate', 'any_affiliate', 'direct_affiliate', 's_cc', 's_cc', 's_sq', '__cfduid'];
var ccAdvertisingIndex = ['OAID', 'IDE', 'ck1', 'drtn*', 'rlas3', 'rtn1-z', 'fr', 'everest_g_v2', 'everest_session_v2', 'gglck', 'NID', 'na_id', 'na_tc', 'id', 'mdata', 'dpm', 'GPS', 'YSC', 'wistia-http2-push-disabled', 'di2', 'loc', 'ouid', 'uid', 'uvc', 'vc', 'TapAd_DID ', 'TapAd_TS ', '1P_JAR', 'd', 'mc', 'KADUSERCOOKIE', 'KTPCACOOKIE', 'AMCVS_', 'AMCV_', 'PP', 'PPP', '_gat_partnerTracker', '_gat_spreadshirtTracker', 'demdex'];

// Works the same for Google Analytics, Advertising or Marketing scripts etc.
var config = {
	apiKey: 'f3c65236ed9682b20dd601125410aa54139c8dfa',
	product: 'PRO_MULTISITE',
	initialState: "notify",
	notifyOnce: false,
	position: "LEFT",
	theme: "LIGHT",
	layout: "slideout",
	branding: {
		fontColor: "#fff",
		fontSizeTitle: "1.1em",
		fontSizeIntro: "1em",
		fontSizeHeaders: "1em",
		fontSize: "0.95em",
		backgroundColor: '#1a3867',
		toggleText: '#000',
		toggleColor: '#ccc',
		toggleBackground: '#fff',
		buttonIcon: null,
		buttonIconWidth: "64px",
		buttonIconHeight: "64px",
		removeIcon: false,
		removeAbout: true
	},
	text: {
		title: 'This website uses cookies to remember users and understand ways to enhance their experience.',
		intro: 'Some cookies are necessary, while other cookies help us improve your experience, while you are navigating through our website. For further information, please visit our Cookie Policy.',
		acceptRecommended: 'Accept Cookies',
		necessaryTitle: 'Manage Cookie Preferences',
		necessaryDescription: '<strong>Strictly Necessary Cookies</strong> are essential for our website to function properly, for instance authenticating logins or serving files, crucial for the core functionality. You can only disable necessary cookies via browser settings but if you do, our website might not be properly functional for your needs.',
		notifyDescription: 'We use cookies to optimize site functionality and give you the best possible experience. Learn more.'
	},
	consentCookieExpiry: 90,
	optionalCookies: [],
	statement: {}
};

window.addEventListener("load", function () {
	if (ccPerformance) {
		config.optionalCookies.push({
			name: 'performance',
			label: 'Performance Cookies',
			description: 'Performance cookies help us to improve our website by collecting and reporting information anonymously. We use Analytics services from Google LLC to help analyze how users use the site. IP anonymization is activated on this website.',
			cookies: ccPerformanceIndex,
			onAccept: function () {
				if ((typeof propertyGtmId !== 'undefined') && (propertyGtmId !== 'undefined')) {
					pushGtmScript(propertyGtmId);
			    	}
			    	if ((typeof propertyUaId !== 'undefined') && (propertyUaId !== 'undefined')) {
					pushUaScript(propertyUaId);
			    	}
			},
			onRevoke: function () {
				ccPerformanceIndex.forEach(element => CookieControl.delete(element));
			},
			recommendedState: true,
			lawfulBasis: 'legitimate interest'
		});
	}

	if (ccFunctional) {
		config.optionalCookies.push({
			name: 'functionality',
			label: 'Functionality Cookies',
			description: 'Functionality cookies are responsible for the partial functionality of this website during your navigation. By deactivating them, you might have limited access on our or third party website\'s features.',
			cookies: ['PREF', 'VISITOR_INFO1_LIVE', 'rl_modals', '__distillery', 'AWSALB', '__atuvc', '__atuvs', 'ct_checkjs', 'ct_fkp_timestamp', 'ct_pointer_data', 'ct_ps_timestamp', 'ct_timezone', '__utmt', '_sdsat_BasketAmountItems', 'affiliate', 'any_affiliate', 'direct_affiliate', 's_cc', 's_cc', 's_sq', '__cfduid'],
			onAccept: function () {
				if ((typeof propertyPingdomId !== 'undefined') && (propertyPingdomId !== 'undefined')) {
					pushPingdomScript(propertyPingdomId);
				}
			},
			onRevoke: function () {
				ccFunctionalIndex.forEach(element => CookieControl.delete(element));
			},
			recommendedState: true
		});
	}

	if (ccAdvertising) {
		config.optionalCookies.push({
			name: 'advertising',
			label: 'Advertising',
			description: 'Advertising cookies help you see some ads based on your preferences. Joomla! serves or hosts ads as they are one of its major financial support.',
			cookies: ['OAID', 'IDE', 'ck1', 'drtn*', 'rlas3', 'rtn1-z', 'fr', 'everest_g_v2', 'everest_session_v2', 'gglck', 'NID', 'na_id', 'na_tc', 'id', 'mdata', 'dpm', 'GPS', 'YSC', 'wistia-http2-push-disabled', 'di2', 'loc', 'ouid', 'uid', 'uvc', 'vc', 'TapAd_DID ', 'TapAd_TS ', '1P_JAR', 'd', 'mc', 'KADUSERCOOKIE', 'KTPCACOOKIE', 'AMCVS_', 'AMCV_', 'PP', 'PPP', '_gat_partnerTracker', '_gat_spreadshirtTracker', 'demdex'],
			onAccept: function () {
				if ((typeof propertyAwId !== 'undefined') && (propertyAwId !== 'undefined')) {
                        		pushAwScript(propertyAwId);
                    		}
                    		if ((typeof propertyTwitter !== 'undefined') && (propertyTwitter !== 'undefined')) {
                        		pushTwScript();
                    		}
                    		if ((typeof propertyFacebookId !== 'undefined') && (propertyFacebookId !== 'undefined')) {
                        		pushFbScript(propertyFacebookId);
                    		}
                    		if ((typeof propertyAddThis !== 'undefined') && (propertyAddThis !== 'undefined')) {
                        		pushAtScript();
                    		}
                    		if ((typeof propertyAddThisId !== 'undefined') && (propertyAddThisId !== 'undefined')) {
                        		pushAtIdScript(propertyAddThisId);
                    		}
			},
			onRevoke: function () {
				ccAdvertisingIndex.forEach(element => CookieControl.delete(element));
			},
			recommendedState: true
		});
	}

	config.optionalCookies.push({
		name: 'accept',
		label: ' ',
		description: '<a href="" onclick="CookieControl.hide();">Continue to site</a>',
		toggleType: 'checkbox'
	});

	CookieControl.load(config);

	var removex = document.getElementById('ccc-notify-dismiss'),
		cookieControlCookie = JSON.parse(CookieControl.getCookie('CookieControl')),
		cookieState = cookieControlCookie.initialState,
		cookieInteraction = cookieControlCookie.interactedWith;

	// Remove the click function if element exists
	if (removex) {
		dismissIcon.remove();
	}

	// If type is closed the cookie notice is not accepted. If it is notify, then it is accepted.
	if (cookieState && cookieState.type === 'closed') {
		if (!cookieInteraction) {
			CookieControl.open();
		}
	}
});

// @TODO: Move these functions into a seperate file (To be discussed)

// Push GAnalytics Script into <head></head>
function pushUaScript(id)
{
    var UaScript = document.createElement("script");
    var UaGtag = document.createElement("script");
    var UaGtagContent = document.createTextNode("window.dataLayer = window.dataLayer || [];function gtag() {dataLayer.push(arguments);}gtag('js', new Date());	gtag('config', '" + id + "', {'anonymize_ip': true});");
    
    UaGtag.appendChild(UaGtagContent);
    UaScript.src = "//www.googletagmanager.com/gtag/js?id=" + id;

    document.head.appendChild(UaScript);
    document.head.appendChild(UaGtag);
}

// Push Google Tag Manager Script into <head></head>
function pushGtmScript(id)
{
    var GtmScript = document.createElement("script");
    var GtmContent = document.createTextNode("(function (w, d, s, l, i) { w[l] = w[l] || [];w[l].push({'gtm.start':new Date().getTime(), event: 'gtm.js'});var f = d.getElementsByTagName(s)[0],j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';j.async = true;j.src ='//www.googletagmanager.com/gtm.js?id=' + i + dl;f.parentNode.insertBefore(j, f);})(window, document, 'script', 'dataLayer', '" + id + "');");
    
    GtmScript.appendChild(GtmContent);
    
    document.head.appendChild(GtmScript);
}

// Push Pingdom Script into <head></head>
function pushPingdomScript(id)
{
    var PingdomScript = document.createElement("script");
    var PingdomContent = document.createTextNode("var _prum = [['id', '" + id + "'],	['mark', 'firstbyte', (new Date()).getTime()]];(function () {var s = document.getElementsByTagName('script')[0]	, p = document.createElement('script');	p.async = 'async';	p.src = 'https://rum-static.pingdom.net/prum.min.js';	s.parentNode.insertBefore(p, s);})();");
    
    PingdomScript.appendChild(PingdomContent);
    
    document.head.appendChild(PingdomScript);
}

// Push Google Adwords Script into <head></head>
function pushAwScript(id)
{
	var AwScript = document.createElement("script");
    var AwGtag = document.createElement("script");
	var AwGtagContent = document.createTextNode("window.dataLayer = window.dataLayer || [];function gtag() { dataLayer.push(arguments); }gtag('js', new Date());gtag('config', '" + id + "');");
	
	AwGtag.appendChild(AwGtagContent);
	AwScript.src = "//www.googletagmanager.com/gtag/js?id=" + id;
	
	document.head.appendChild(AwScript);
    document.head.appendChild(AwGtag);
}

// Push Twitter widget into <body></body>
function pushTwScript()
{
	var TwScript = document.createElement("script");
	
	TwScript.src = "//platform.twitter.com/widgets.js";
	
	document.body.appendChild(TwScript);					
}

// Push Facebook script and pixel into <body></body> (src value must be checked)
function pushFbScript(id)
{
	var FbImg = document.createElement("img");
	var FbScript = document.createElement("script");
	var FbContent = document.createTextNode("!function (f, b, e, v, n, t, s) {if (f.fbq) return;n = f.fbq = function () {n.callMethod ?n.callMethod.apply(n, arguments) : n.queue.push(arguments)};if (!f._fbq) f._fbq = n;n.push = n;n.loaded = !0;n.version = '2.0';n.queue = [];t = b.createElement(e);t.async = !0;t.src = v;s = b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t, s)}(window, document, 'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init', '" + id + "');fbq('track', 'PageView');");

	FbScript.appendChild(FbContent);
	FbImg.src = "https://www.facebook.com/tr?id=1490208684611957&ev=PageView&noscript=1";
	FbImg.style = "display:none";

	document.body.appendChild(FbScript);
	document.body.appendChild(FbImg);
}

// Push AddThis script into <body></body> (src value must be checked)
function pushAtScript()
{
	var AtScript = document.createElement("script");
	
	AtScript.src = "//cdn.carbonads.com/carbon.js?zoneid=1673&serve=C6AILKT&placement=joomlaorg";
	AtScript.id = "_carbonads_js";
	
	document.body.appendChild(AtScript);					
}

// Push AddThisId script into <body></body> (src value must be checked)
function pushAtIdScript(id)
{
	var AtIdScript = document.createElement("script");
	
	AtIdScript.src = "//s7.addthis.com/js/300/addthis_widget.js#pubid=" + id;
	
	document.body.appendChild(AtIdScript);		
}
