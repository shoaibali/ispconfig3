<?php
	session_start();
	include('../../lib/config.inc.php');
	require_once('../../lib/app.inc.php');
	$lang = (isset($_SESSION['s']['language']) && $_SESSION['s']['language'] != '')?$_SESSION['s']['language']:'en';
	include_once(ISPC_ROOT_PATH.'/web/strengthmeter/lib/lang/'.$lang.'_strengthmeter.lng');
	
	$app->uses('ini_parser,getconf');
	$server_config_array = $app->getconf->get_global_config();
?>
var pageFormChanged = false;
var tabChangeWarningTxt = '';
var tabChangeDiscardTxt = '';
var tabChangeWarning = false;
var tabChangeDiscard = false;
var requestsRunning = 0;
var indicatorPaddingH = -1;
var indicatorPaddingW = -1;
var indicatorCompleted = false;
redirect = '';

function reportError(request) {
	/* Error reporting is disabled by default as some browsers like safari 
	   sometimes throw errors when a ajax request is delayed even if the 
	   ajax request worked. */
	   
	/*alert(request);*/
}

function resetFormChanged() {
    pageFormChanged = false;
}

function showLoadIndicator() {
    document.body.style.cursor = 'wait';

<?php
if($server_config_array['misc']['use_loadindicator'] == 'y'){
?>
    requestsRunning += 1;
    
    if(requestsRunning < 2) {
        var indicator = jQuery('#ajaxloader');
        if(indicator.length < 1) {
            indicator = jQuery('<div id="ajaxloader" style="display: none;"></div>');
            indicator.appendTo('body');
        }
        var parent = jQuery('#content');
        if(parent.length < 1) return;
        indicatorCompleted = false;
        
        var atx = parent.offset().left + 150; //((parent.outerWidth(true) - indicator.outerWidth(true)) / 2);
        var aty = parent.offset().top + 150;
        indicator.css( {'left': atx, 'top': aty } ).fadeIn('fast', function() {
            // check if loader should be hidden immediately
            indicatorCompleted = true;
            if(requestsRunning < 1) $(this).fadeOut('fast', function() { $(this).hide();});
        });
    }
<?php
}
?>
}

function hideLoadIndicator() {
    document.body.style.cursor = '';

    requestsRunning -= 1;
    if(requestsRunning < 1) {
        requestsRunning = 0; // just for the case...
        if(indicatorCompleted == true) jQuery('#ajaxloader').fadeOut('fast', function() { jQuery('#ajaxloader').hide(); } );
    }
}

function onAfterContentLoad() {
<?php
if($server_config_array['misc']['use_combobox'] == 'y'){
?>
    $('#pageContent').find("select").combobox();
<?php
}
?>
}

function loadContentRefresh(pagename) {
	
  if(document.getElementById('refreshinterval').value > 0) {
	var pageContentObject2 = jQuery.ajax({	type: "GET", 
											url: pagename,
											data: "refresh="+document.getElementById('refreshinterval').value,
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
                                                hideLoadIndicator();
												jQuery('#pageContent').html(jqXHR.responseText);
                                                onAfterContentLoad();
                                                pageFormChanged = false;
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful.'+pagename);
											}
										});
  	setTimeout( "loadContentRefresh('"+pagename+"&refresh="+document.getElementById('refreshinterval').value+"')", document.getElementById('refreshinterval').value*1000 );
  }
}

function capp(module, redirect) {
	var submitFormObj = jQuery.ajax({		type: "GET", 
											url: "capp.php", 
											data: "mod="+module+((redirect != undefined) ? '&redirect='+redirect : ''),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText != '') {
													if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
														var parts = jqXHR.responseText.split(':');
														loadContent(parts[1]);
													} else if (jqXHR.responseText.indexOf('URL_REDIRECT:') > -1) {
														var newUrl= jqXHR.responseText.substr(jqXHR.responseText.indexOf('URL_REDIRECT:') + "URL_REDIRECT:".length);
														document.location.href = newUrl;
													} else {
														//alert(jqXHR.responseText);
													}
												}
												loadMenus();
                                                hideLoadIndicator();
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful.'+module);
											}
									});
}

function submitLoginForm(formname) {
    //* Validate form. TODO: username and password with strip();
    var frm = document.getElementById(formname);
    var userNameObj = frm.username;
    if(userNameObj.value == ''){
        userNameObj.focus();
        return;
    }
    var passwordObj = frm.passwort;
    if(passwordObj.value == ''){
        passwordObj.focus();
        return;
    }
	
	$('#dummy_username').val(userNameObj.value);
	$('#dummy_passwort').val(passwordObj.value);
	$('#dummy_login_form').submit();

	var submitFormObj = jQuery.ajax({		type: "POST", 
											url: "content.php",
											data: jQuery('#'+formname).serialize(),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(':');
													//alert(parts[1]);
													loadContent(parts[1]);
													//redirect = parts[1];
													//window.setTimeout('loadContent(redirect)', 1000);
												} else if (jqXHR.responseText.indexOf('LOGIN_REDIRECT:') > -1) {
													// Go to the login page
													document.location.href = 'index.php';
												} else {
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad();
                                                    pageFormChanged = false;
												}
												loadMenus();
                                                hideLoadIndicator();
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful.110');
											}
									});
	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	document.getElementById('footer').innerHTML = 'Powered by <a href="http://www.ispconfig.org" target="_blank">ISPConfig</a>';
	*/
	
}

function submitForm(formname,target) {
	var submitFormObj = jQuery.ajax({		type: "POST", 
											url: target,
											data: jQuery('#'+formname).serialize(),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(':');
													//alert(parts[1]);
													loadContent(parts[1]);
													//redirect = parts[1];
													//window.setTimeout('loadContent(redirect)', 1000);
												} else {
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad();
                                                    pageFormChanged = false;
												}
                                                hideLoadIndicator();
											},
											error: function(jqXHR, textStatus, errorThrown) {
                                                hideLoadIndicator();
												var parts = jqXHR.responseText.split(':');
												reportError('Ajax Request was not successful. 111');
											}
									});
	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	*/
}

function submitFormConfirm(formname,target,confirmation) {
	var successMessage = arguments[3];
	if(window.confirm(confirmation)) {
		var submitFormObj = jQuery.ajax({	type: "POST", 
											url: target,
											data: jQuery('#'+formname).serialize(),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(successMessage) alert(successMessage);
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(':');
													//alert(parts[1]);
													loadContent(parts[1]);
													//redirect = parts[1];
													//window.setTimeout('loadContent(redirect)', 1000);
												} else {
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad();
                                                    pageFormChanged = false;
												}
                                                hideLoadIndicator();
											},
											error: function(jqXHR, textStatus, errorThrown) {
                                                hideLoadIndicator();
												var parts = jqXHR.responseText.split(':');
												reportError('Ajax Request was not successful. 111');
											}
									});
	}
}

function submitUploadForm(formname,target) {		
	var handleResponse = function(loadedFrame) {
		var response, responseStr = loadedFrame.contentWindow.document.body.innerHTML;
		
		try {
			response = JSON.parse(responseStr);
		} catch(e) {
			response = responseStr;
		}
		var msg = '';
		var okmsg = jQuery('#OKMsg',response).html();
		if(okmsg){
			msg = '<div id="OKMsg">'+okmsg+'</div>';
		}
		var errormsg = jQuery('#errorMsg',response).html();
		if(errormsg){
			msg = msg+'<div id="errorMsg">'+errormsg+'</div>';
		}
		return msg;
		
    };
	
	var frame_id = 'ajaxUploader-iframe-' + Math.round(new Date().getTime() / 1000);
	jQuery('body').after('<iframe width="0" height="0" style="display:none;" name="'+frame_id+'" id="'+frame_id+'"/>');
	jQuery('input[type="file"]').closest("form").attr({target: frame_id, action: target}).submit();
	jQuery('#'+frame_id).load(function() {
        var msg = handleResponse(this);
		jQuery('#errorMsg').remove();
		jQuery('#OKMsg').remove();
		jQuery('input[name="id"]').before(msg);
		jQuery(this).remove();
      });

	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	*/
}

function loadContent(pagename) {
  var params = arguments[1];
  var pageContentObject2 = jQuery.ajax({	type: "GET", 
											url: pagename,
                                            data: (params ? params : null),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(':');
													loadContent(parts[1]);
												} else if (jqXHR.responseText.indexOf('URL_REDIRECT:') > -1) {
													var newUrl= jqXHR.responseText.substr(jqXHR.responseText.indexOf('URL_REDIRECT:') + "URL_REDIRECT:".length);
													document.location.href = newUrl;
												} else {
													//document.getElementById('pageContent').innerHTML = jqXHR.responseText;
													//var reponse = jQuery(jqXHR.responseText);
													//var reponseScript = reponse.filter("script");
													//jQuery.each(reponseScript, function(idx, val) { eval(val.text); } );
													
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad();
                                                    pageFormChanged = false;
												}
                                                hideLoadIndicator();
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 113');
											}
									});
}


function loadInitContent() {
	var pageContentObject = jQuery.ajax({	type: "GET", 
											url: "content.php",
											data: "s_mod=login&s_pg=index",
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(":");
													loadContent(parts[1]);
												} else {
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad();
                                                    pageFormChanged = false;
												}
                                                hideLoadIndicator();
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 114');
											}
										});
  
  loadMenus();
  keepalive();
  setTimeout("setFocus()",1000);

}

function setFocus() {
	try {
		jQuery('form#pageForm').find('input[name="username"]').focus();
	} catch (e) {
	}
}


function loadMenus() {
  var sideNavObject = jQuery.ajax({			type: "GET", 
											url: "nav.php",
											data: "nav=side",
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
                                                hideLoadIndicator();
												jQuery('#sideNav').html(jqXHR.responseText);
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 115');
											}
									});
	
  var topNavObject = jQuery.ajax({			type: "GET", 
											url: "nav.php",
											data: "nav=top",
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
                                                hideLoadIndicator();
												jQuery('#topNav').html(jqXHR.responseText);
											},
											error: function(o) {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 116');
											}
								});

}

function changeTab(tab,target,force) {
	//document.forms[0].next_tab.value = tab;
	document.pageForm.next_tab.value = tab;
    
    var idel = jQuery('form#pageForm').find('[name="id"]');
    var id = null;
    if(idel.length > 0) id = idel.val();
    if(tabChangeDiscard == 'y' && !force) {
        if((idel.length < 1 || id) && (pageFormChanged == false || window.confirm(tabChangeDiscardTxt))) {
            var next_tab = tab;
            if(id) loadContent(target, {'next_tab': next_tab, 'id': id});
            else loadContent(target, {'next_tab': next_tab});
        } else {
            return false;
        }
    } else {
        if(id && tabChangeWarning == 'y' && pageFormChanged == true) {
            if(window.confirm(tabChangeWarningTxt)) {
                submitForm('pageForm', target);
            } else {
                var next_tab = tab;
                if(id) loadContent(target, {'next_tab': next_tab, 'id': id});
                else loadContent(target, {'next_tab': next_tab});
            }
        } else {
            submitForm('pageForm',target);
        }
    }
}
	
function del_record(link,confirmation) {
  if(window.confirm(confirmation)) {
          loadContent(link);
  }
}

function confirm_action(link,confirmation) {
  if(window.confirm(confirmation)) {
          loadContent(link);
  }
}

function loadContentInto(elementid,pagename) {
  var pageContentObject2 = jQuery.ajax({	type: "GET", 
											url: pagename,
											dataType: "html",
											beforeSend: function() {
//												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
//                                                hideLoadIndicator();
												jQuery('#'+elementid).html(jqXHR.responseText);
											},
											error: function() {
//                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 118');
											}
										});
}

function loadOptionInto(elementid,pagename) {
	var pageContentObject2 = jQuery.ajax({	type: "GET", 
											url: pagename,
											dataType: "html",
											beforeSend: function() {
//												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
//                                                hideLoadIndicator();
												var teste = jqXHR.responseText;
												var elemente = teste.split('#');
												el=document.getElementById(elementid);
												el.innerHTML='';
												for (var i = 0; i < elemente.length; ++i){

													var foo2 = document.createElement("option");
													foo2.appendChild(document.createTextNode(elemente[i]));
													foo2.value=elemente[i];
													el.appendChild(foo2);
												}
											},
											error: function() {
//                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 119');
											}
										});
}

function keepalive() {
	var pageContentObject3 = jQuery.ajax({	type: "GET", 
											url: "keepalive.php",
											dataType: "html",
											success: function(data, textStatus, jqXHR) {
												setTimeout( keepalive, 1000000 );
											},
											error: function() {
												reportError('Session expired. Please login again.');
											}
										});
  	//setTimeout( keepalive, 1000000 );
}



var pass_minimum_length = 5;
var pass_messages = new Array();

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_0_txt']?>";
pass_message['color'] = "#d0d0d0";
pass_messages[0] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_1_txt']?>";
pass_message['color'] = "red";
pass_messages[1] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_2_txt']?>";
pass_message['color'] = "yellow";
pass_messages[2] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_3_txt']?>";
pass_message['color'] = "#00ff00";
pass_messages[3] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_4_txt']?>";
pass_message['color'] = "green";
pass_messages[4] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_5_txt']?>";
pass_message['color'] = "green";
pass_messages[5] = pass_message;

function pass_check(password) {
	var length = password.length;
	var points = 0;
	if (length < pass_minimum_length) {
		pass_result(0);
		return;
	}
	
	if (length < 5) {
		pass_result(1);
		return;
	}
	
	if (pass_contains(password, "ABCDEFGHIJKLNMOPQRSTUVWXYZ")) {
		points += 1;
	}
	
	if (pass_contains(password, "0123456789")) {
		points += 1;
	}
	
	if (pass_contains(password, "`~!@#$%^&*()_+|\=-[]}{';:/?.>,<\" ")) {
		points += 1;
	}
	
	if (points == 0) {
		if (length >= 5 && length <=6) {
			pass_result(1);
		} else if (length >= 7 && length <=8) {
			pass_result(2);
		} else {
			pass_result(3);
		}
	} else if (points == 1) {
		if (length >= 5 && length <=6) {
			pass_result(2);
		} else if (length >= 7 && length <=10) {
			pass_result(3);
		} else {
			pass_result(4);
		}
	} else if (points == 2) {
		if (length >= 5 && length <=8) {
			pass_result(3);
		} else if (length >= 9 && length <=10) {
			pass_result(4);
		} else {
			pass_result(5);
		}
	} else if (points == 3) {
		if (length >= 5 && length <=6) {
			pass_result(3);
		} else if (length >= 7 && length <=8) {
			pass_result(4);
		} else {
			pass_result(5);
		}
	} else if (points >= 4) {
		if (length >= 5 && length <=6) {
			pass_result(4);
		} else {
			pass_result(5);
		}
	}
}



function pass_result(points, message) {
	if (points == 0) {
		width = 10;
	} else {
		width = points*20;
	}
	document.getElementById("passBar").innerHTML = '<div style="float:left; height: 10px; padding:0px; background-color: ' + pass_messages[points]['color'] + '; width: ' + width + 'px;" />';
	document.getElementById("passText").innerHTML = pass_messages[points]['text'];
}
function pass_contains(pass, check) {
	for (i = 0; i < pass.length; i++) {
		if (check.indexOf(pass.charAt(i)) > -1) {
			return true;
		}
	}
	return false;
}

function addAdditionalTemplate(){
	var tpl_add = document.getElementById('template_additional').value;
	
	  var tpl_list = document.getElementById('template_additional_list').innerHTML;
	  var addTemplate = document.getElementById('tpl_add_select').value.split('|',2);
	  var addTplId = addTemplate[0];
	  var addTplText = addTemplate[1];
	if(addTplId > 0) {
	  var newVal = tpl_add + '/' + addTplId + '/';
	  newVal = newVal.replace('//', '/');
	  var newList = tpl_list + '<br>' + addTplText;
	  newList = newList.replace('<br><br>', '<br>');
	  document.getElementById('template_additional').value = newVal;
	  document.getElementById('template_additional_list').innerHTML = newList;
	  alert('additional template ' + addTplText + ' added to customer');
	} else {
	  alert('no additional template selcted');
	}
}

function delAdditionalTemplate(){
	var tpl_add = document.getElementById('template_additional').value;
	if(tpl_add != '') {
		var tpl_list = document.getElementById('template_additional_list').innerHTML;
		var addTemplate = document.getElementById('tpl_add_select').value.split('|',2);
		var addTplId = addTemplate[0];
		var addTplText = addTemplate[1];
		var newVal = tpl_add;
		newVal = newVal.replace(addTplId, '');
		newVal = newVal.replace('//', '/');
		var newList = tpl_list.replace(addTplText, '');
		newList = newList.replace('<br><br>', '<br>');
		document.getElementById('template_additional').value = newVal;
		document.getElementById('template_additional_list').innerHTML = newList;
		alert('additional template ' + addTplText + ' deleted from customer');
  } else {
  	alert('no additional template selcted');
  }
  
}

function getInternetExplorerVersion() {
    var rv = -1; // Return value assumes failure.
    if (navigator.appName == 'Microsoft Internet Explorer') {
        var ua = navigator.userAgent;
        var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null)
            rv = parseFloat(RegExp.$1);
    }
    return rv;
}

function password(minLength, special){
	var iteration = 0;
	var password = "";
	var randomNumber;
	minLength = minLength || 10;
	var maxLength = minLength + 5;
	var length = getRandomInt(minLength, maxLength);
	if(special == undefined){
		var special = false;
	}
	while(iteration < length){
		randomNumber = (Math.floor((Math.random() * 100)) % 94) + 33;
		if(!special){
			if ((randomNumber >=33) && (randomNumber <=47)) { continue; }
			if ((randomNumber >=58) && (randomNumber <=64)) { continue; }
			if ((randomNumber >=91) && (randomNumber <=96)) { continue; }
			if ((randomNumber >=123) && (randomNumber <=126)) { continue; }
		}
		iteration++;
		password += String.fromCharCode(randomNumber);
	}
	return password;
}

function generatePassword(passwordFieldID, repeatPasswordFieldID){
	var oldPWField = jQuery('#'+passwordFieldID);
	var newPWField = oldPWField.clone();
	newPWField.attr('type', 'text').attr('id', 'tmp'+passwordFieldID).insertBefore(oldPWField);
	oldPWField.remove();
	var pword = password(10, false);
	jQuery('#'+repeatPasswordFieldID).val(pword);
	newPWField.attr('id', passwordFieldID).val(pword).trigger('keyup');
}

var funcDisableClick = function(e) { e.preventDefault(); return false; };

function checkPassMatch(pwField1,pwField2){
    var rpass = jQuery('#'+pwField2).val();
    var npass = jQuery('#'+pwField1).val();
    if(npass!= rpass) {
		jQuery('#confirmpasswordOK').hide();
        jQuery('#confirmpasswordError').show();
		jQuery('button.positive').attr('disabled','disabled');
        jQuery('.tabbox_tabs ul li a').each(function() {
            var $this = $(this);
            $this.data('saved_onclick', $this.attr('onclick'));
            $this.removeAttr('onclick');
            $this.click(funcDisableClick);
        });
        return false;
    } else {
		jQuery('#confirmpasswordError').hide();
        jQuery('#confirmpasswordOK').show();
		jQuery('button.positive').removeAttr('disabled');
		jQuery('.tabbox_tabs ul li a').each(function() {
            var $this = $(this);
            $this.unbind('click', funcDisableClick);
            if($this.data('saved_onclick') && !$this.attr('onclick')) $this.attr('onclick', $this.data('saved_onclick'));
        });
    }
}

function getRandomInt(min, max){
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

jQuery('.addPlaceholder').live("click", function(){
	var placeholderText = jQuery(this).text();
	var template = jQuery(this).siblings(':input');
	template.insertAtCaret(placeholderText);
});

jQuery('.addPlaceholderContent').live("click", function(){
	var placeholderContentText = jQuery(this).find('.addPlaceholderContent').text();
	var template2 = jQuery(this).siblings(':input');
	template2.insertAtCaret(placeholderContentText);
});
		
jQuery.fn.extend({
	insertAtCaret: function(myValue){
		return this.each(function(i) {
			if (document.selection) {
				//For browsers like Internet Explorer
				this.focus();
				sel = document.selection.createRange();
				sel.text = myValue;
				this.focus();
			} else if (this.selectionStart || this.selectionStart == '0') {
				//For browsers like Firefox and Webkit based
				var startPos = this.selectionStart;
				var endPos = this.selectionEnd;
				var scrollTop = this.scrollTop;
				this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
				this.focus();
				this.selectionStart = startPos + myValue.length;
				this.selectionEnd = startPos + myValue.length;
				this.scrollTop = scrollTop;
			} else {
				this.value += myValue;
				this.focus();
			}
		})
	}
});

