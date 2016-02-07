function sendRequest(url,callback,postData) {
    var req = createXMLHTTPObject();
    if (!req) return;
    var method = (postData) ? "POST" : "GET";
    req.open(method,url,true);
    if (postData)
        req.setRequestHeader('Content-type','application/x-www-form-urlencoded');
    req.onreadystatechange = function () {
        if (req.readyState != 4) return;
        if (req.status != 200 && req.status != 304) {
//          alert('HTTP error ' + req.status);
            return;
        }
        callback(req);
    };
    if (req.readyState == 4) return;
    req.send(postData);
}

var XMLHttpFactories = [
    function () {return new XMLHttpRequest()},
    function () {return new ActiveXObject("Msxml2.XMLHTTP")},
    function () {return new ActiveXObject("Msxml3.XMLHTTP")},
    function () {return new ActiveXObject("Microsoft.XMLHTTP")}
];

function createXMLHTTPObject() {
    var xmlhttp = false;
    for (var i=0;i<XMLHttpFactories.length;i++) {
        try {
            xmlhttp = XMLHttpFactories[i]();
        }
        catch (e) {
            continue;
        }
        break;
    }
    return xmlhttp;
}

function ct_getCookie(name) {
  var matches = document.cookie.match(new RegExp(
    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
  ));
  return matches ? decodeURIComponent(matches[1]) : undefined;
}

function ct_setCookie(name, value)
{
	/*var domain=location.hostname;
	tmp=domain.split('.');
	if(tmp[0].toLowerCase()=='www')
	{
		tmp[0]='';
	}
	else
	{
		tmp[0]='.'+tmp[0];
	}
	domain=tmp.join('.');*/
	
	document.cookie = name+" =; expires=Thu, 01 Jan 1970 00:00:01 GMT; path = /";
	document.cookie = name+" =; expires=Thu, 01 Jan 1970 00:00:01 GMT";
	//document.cookie = name+" =; expires=Thu, 01 Jan 1970 00:00:01 GMT; path = /; domain = " +  domain;
	
	var date = new Date;
	date.setDate(date.getDate() + 1);
	//setTimeout(function() { document.cookie = name+"=" + value + "; expires=" + date.toUTCString() + "; path = /; domain = " + domain}, 500)
	setTimeout(function() { document.cookie = name+"=" + value + "; expires=" + date.toUTCString() + "; path = /;"}, 500);
	//document.cookie = name+"=" + value + "; expires=" + date.toUTCString() + "; path = /;";
}

function ct_callback(req)
{
	ct_cookie=req.responseText.trim();
	//alert('Key value: ' + ct_cookie);
	
	ct_setCookie('ct_checkjs', ct_cookie);
	
	for(i=0;i<document.forms.length;i++)
	{
		f=document.forms[i];
		for(j=0;j<f.elements.length;j++)
		{
			e=f.elements[j];
			if(e.name!==undefined&&e.name.indexOf('ct_checkjs')!=-1)
			{
				e.value=ct_cookie;
				//alert('Form #' + i + ', field ' + e.name + ' = ' + ct_cookie);
			}
		}
	}

	//alert('Set cookie: \n' + document.cookie);
}

if (!Date.now) {
	Date.now = function() { return new Date().getTime(); }
}

if(ct_nocache_executed==undefined)
{
	var ct_nocache_executed=true;
	var new_timestamp=Math.floor(Date.now() / 1000);
	
	var old_timestamp=ct_getCookie('ct_timestamp');
	
	var checkjs_cookie=ct_getCookie('ct_checkjs');
	
	if(checkjs_cookie!=undefined)
	{
		for(i=0;i<document.forms.length;i++)
		{
			f=document.forms[i];
			for(j=0;j<f.elements.length;j++)
			{
				e=f.elements[j];
				if(e.name!==undefined&&e.name.indexOf('ct_checkjs')!=-1)
				{
					e.value=checkjs_cookie;
					//alert('Form #' + i + ', field ' + e.name + ' = ' + ct_cookie);
				}
			}
		}
	}	
	
	if(old_timestamp==undefined||new_timestamp-old_timestamp>86400||checkjs_cookie==undefined) //86400 is 24 hours
	{
		ct_setCookie('ct_timestamp', new_timestamp);
		//alert('set!');
		sendRequest(ct_ajaxurl+'?'+Math.random(),ct_callback,'action=ct_get_cookie');
	}
}