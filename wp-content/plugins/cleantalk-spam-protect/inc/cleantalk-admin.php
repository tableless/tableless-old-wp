<?php

$ct_plugin_basename = 'cleantalk-spam-protect/cleantalk.php';
$ct_options=ct_get_options();
$ct_data=ct_get_data();

add_filter( 'activity_box_end', 'cleantalk_custom_glance_items', 10, 1 );
function cleantalk_custom_glance_items( )
{
	global $ct_data;
	$ct_data=ct_get_data();
	if(!isset($ct_data['admin_blocked']))
	{
		$blocked=0;
	}
	else
	{
		$blocked=$ct_data['admin_blocked'];
	}
	if($blocked>0)
	{
		$blocked = number_format($blocked, 0, ',', ' ');
		print "<div style='height:24px;width:100%;display:table-cell; vertical-align:middle;'><img src='" . plugin_dir_url(__FILE__) . "images/logo_color.png' style='margin-right:1em;vertical-align:middle;'/><span><a href='https://cleantalk.org/my/?user_token=".@$ct_data['user_token']."&utm_source=wp-backend&utm_medium=dashboard_widget' target='_blank'>CleanTalk</a> ";
		printf(
			/* translators: %s: Number of spam messages */
			__( 'has blocked %s spam', 'cleantalk' ),
			$blocked
		);
		print "</span></div>";
	}
}


// Timeout to get app server
$ct_server_timeout = 10;


/**
 * Admin action 'wp_ajax_ajax_get_timezone' - Ajax method for getting timezone offset
 */
 
function ct_ajax_get_timezone()
{
	global $ct_data;
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	$ct_data = ct_get_data();
	if(isset($_POST['offset']))
	{
		$ct_data['timezone'] = intval($_POST['offset']);
		update_option('cleantalk_data', $ct_data);
	}
}
 
add_action( 'wp_ajax_ajax_get_timezone', 'ct_ajax_get_timezone' );


/**
 * Admin action 'admin_enqueue_scripts' - Enqueue admin script of reloading admin page after needed AJAX events
 * @param 	string $hook URL of hooked page
 */
function ct_enqueue_scripts($hook) {
	if ($hook == 'edit-comments.php')
		wp_enqueue_script('ct_reload_script', plugins_url('/cleantalk-rel.js', __FILE__));
}

/**
 * Admin action 'admin_menu' - Add the admin options page
 */
function ct_admin_add_page() {
	if(is_network_admin())
	{
		add_submenu_page("settings.php", __('CleanTalk settings', 'cleantalk'), 'CleanTalk', 'manage_options', 'cleantalk', 'ct_settings_page');
	}
	else
	{
		add_options_page(__('CleanTalk settings', 'cleantalk'), 'CleanTalk', 'manage_options', 'cleantalk', 'ct_settings_page');
	}
}

/**
 * Admin action 'admin_init' - Add the admin settings and such
 */
function ct_admin_init()
{
	global $ct_server_timeout, $show_ct_notice_autokey, $ct_notice_autokey_label, $ct_notice_autokey_value, $show_ct_notice_renew, $ct_notice_renew_label, $show_ct_notice_trial, $ct_notice_trial_label, $show_ct_notice_online, $ct_notice_online_label, $renew_notice_showtime, $trial_notice_showtime, $ct_plugin_name, $ct_options, $ct_data, $trial_notice_check_timeout, $account_notice_check_timeout, $ct_user_token_label, $cleantalk_plugin_version, $notice_check_timeout, $renew_notice_check_timeout, $ct_agent_version;

	$ct_options = ct_get_options();
	$ct_data = ct_get_data();
    
	$current_version=@trim($ct_data['current_version']);
	if($current_version!=$cleantalk_plugin_version)
	{
		$ct_data['current_version']=$cleantalk_plugin_version;
		update_option('cleantalk_data', $ct_data);
        ct_send_feedback(
            '0:' . $ct_agent_version // 0 - request_id, agent version.
        );
	}
	if(isset($_POST['option_page'])&&$_POST['option_page']=='cleantalk_settings'&&isset($_POST['cleantalk_settings']['apikey']))
	{
		$ct_options['apikey']=$_POST['cleantalk_settings']['apikey'];
		update_option('cleantalk_settings', $ct_options);
        ct_send_feedback(
            '0:' . $ct_agent_version // 0 - request_id, agent version.
        );
	}

	if(@isset($_POST['cleantalk_settings']['spam_firewall']) && $_POST['cleantalk_settings']['spam_firewall']==1)
	{
		cleantalk_update_sfw();
	}

	/*$show_ct_notice_trial = false;
	if (isset($_COOKIE[$ct_notice_trial_label]))
	{
		if ($_COOKIE[$ct_notice_trial_label] == 1)
		{
			$show_ct_notice_trial = true;
		}
	}
	$show_ct_notice_renew = false;
	if (isset($_COOKIE[$ct_notice_renew_label]))
	{
		if ($_COOKIE[$ct_notice_renew_label] == 1)
		{
			$show_ct_notice_renew = true;
		}
	}*/
	$show_ct_notice_autokey = false;
	if (isset($_COOKIE[$ct_notice_autokey_label]) && !empty($_COOKIE[$ct_notice_autokey_label]))
	{
		if (!empty($_COOKIE[$ct_notice_autokey_label]))
		{
			$show_ct_notice_autokey = true;
			$ct_notice_autokey_value = base64_decode($_COOKIE[$ct_notice_autokey_label]);
			setcookie($ct_notice_autokey_label, '', 1, '/');
		}
	}
	
	if (isset($_POST['get_apikey_auto']))
	{
		$email = get_option('admin_email');
		$website = parse_url(get_option('siteurl'),PHP_URL_HOST);
		$platform = 'wordpress';
		
		if(!function_exists('getAutoKey'))
		{
			require_once('cleantalk.class.php');
		}
		
		$result = getAutoKey($email, $website, $platform);

		if ($result)
		{
			$ct_data['next_account_status_check']=0;
			update_option('cleantalk_data', $ct_data);
			$result = json_decode($result, true);
			if (isset($result['data']) && is_array($result['data']))
			{
				$result = $result['data'];
			}
			if(isset($result['user_token']))
			{
				$ct_data['user_token'] = $result['user_token'];
				update_option('cleantalk_data', $ct_data);
			}
			if (isset($result['auth_key']) && !empty($result['auth_key']))
			{
				$_POST['cleantalk_settings']['apikey'] = $result['auth_key'];
				$ct_options['apikey']=$result['auth_key'];
				update_option('cleantalk_settings', $ct_options);
			}
			else
			{
				setcookie($ct_notice_autokey_label, (string) base64_encode($result['error_message']), 0, '/');
			}
		}
		else
		{
			setcookie($ct_notice_autokey_label, (string) base64_encode(sprintf(__('Unable to connect to %s.', 'cleantalk'),  'api.cleantalk.org')), 0, '/');
		}
	}

	if (time() > $ct_data['next_account_status_check']||
		isset($_POST['cleantalk_settings']['apikey']))
	{
		$result = false;
        $notice_check_timeout = $account_notice_check_timeout; 
		//if (true)
		//{
			if(!function_exists('noticePaidTill'))
			{
				require_once('cleantalk.class.php');
			}
			if(@isset($_POST['cleantalk_settings']['apikey']))
			{
				$result=noticePaidTill($_POST['cleantalk_settings']['apikey']);			
			}
			else
			{
				$result=noticePaidTill($ct_options['apikey']);	
			}
			
			if ($result)
			{
				$result = json_decode($result, true);
				if (isset($result['data']) && is_array($result['data']))
				{
					$result = $result['data'];
				}
				if(isset($result['spam_count']))
				{
					$ct_data['admin_blocked']=$result['spam_count'];
				}

				if (isset($result['show_notice']))
				{
					if ($result['show_notice'] == 1 && isset($result['trial']) && $result['trial'] == 1)
					{
						$notice_check_timeout = $trial_notice_check_timeout;
						$show_ct_notice_trial = true;
						$ct_data['show_ct_notice_trial']=1;
					}
					if ($result['show_notice'] == 1 && isset($result['renew']) && $result['renew'] == 1)
					{
						$notice_check_timeout = $renew_notice_check_timeout;
						$show_ct_notice_renew = true;
						$ct_data['show_ct_notice_renew']=1;
					}
					
					if ($result['show_notice'] == 0)
					{
						$notice_check_timeout = $account_notice_check_timeout;
					}
                    $ct_data['show_ct_notice_trial']=(int) $show_ct_notice_trial;
                    $ct_data['show_ct_notice_renew']= (int) $show_ct_notice_renew;
				}
				
				if (isset($result['moderate_ip']) && $result['moderate_ip'] == 1)
				{
					$ct_data['moderate_ip'] = 1;
					$ct_data['ip_license'] = $result['ip_license'];
				}
				else
				{
					$ct_data['moderate_ip'] = 0;
					$ct_data['ip_license'] = 0;
				}
				
				if (isset($result['user_token']))
				{
					$ct_data['user_token'] = $result['user_token']; 
				}
			}
			// Save next status request time
			$ct_data['next_account_status_check'] = time() + $notice_check_timeout * 3600;
			update_option('cleantalk_data', $ct_data);
		//}
		
		/*if ($result)
		{
			if($show_ct_notice_trial == true)
			{
				setcookie($ct_notice_trial_label, (string) $show_ct_notice_trial, strtotime("+$trial_notice_showtime minutes"), '/');
			}
			if($show_ct_notice_renew == true)
			{
				setcookie($ct_notice_renew_label, (string) $show_ct_notice_renew, strtotime("+$renew_notice_showtime minutes"), '/');
			}
		}*/
	}

	$show_ct_notice_online = '';
	if (isset($_COOKIE[$ct_notice_online_label]))
	{
		if ($_COOKIE[$ct_notice_online_label] === 'BAD_KEY')
		{
			$show_ct_notice_online = 'N';
		}
		else if (time() - $_COOKIE[$ct_notice_online_label] <= 5)
		{
			$show_ct_notice_online = 'Y';
		}
	}

	//ct_init_session();
	
	if(stripos($_SERVER['REQUEST_URI'],'options.php')!==false || stripos($_SERVER['REQUEST_URI'],'options-general.php')!==false || stripos($_SERVER['REQUEST_URI'],'network/settings.php')!==false)
	{
	
		register_setting('cleantalk_settings', 'cleantalk_settings', 'ct_settings_validate');
		add_settings_section('cleantalk_settings_main', __($ct_plugin_name, 'cleantalk'), 'ct_section_settings_main', 'cleantalk');

		add_settings_section('cleantalk_settings_state', "<hr>".__('Protection is active', 'cleantalk'), 'ct_section_settings_state', 'cleantalk');
		//add_settings_section('cleantalk_settings_autodel', "<hr>", 'ct_section_settings_autodel', 'cleantalk');
		add_settings_section('cleantalk_settings_banner', "<hr></h3>", '', 'cleantalk');
		add_settings_section('cleantalk_settings_anti_spam', "<a href='#' style='text-decoration:underline;font-size:10pt;font-weight:400;'>".__('Advanced settings', 'cleantalk')."</a>", 'ct_section_settings_anti_spam', 'cleantalk');
		
		if(!defined('CLEANTALK_ACCESS_KEY'))
		{
			add_settings_field('cleantalk_apikey', __('Access key', 'cleantalk'), 'ct_input_apikey', 'cleantalk', 'cleantalk_settings_main');
		}
		else
		{
			add_settings_field('cleantalk_apikey', '', 'ct_input_apikey', 'cleantalk', 'cleantalk_settings_main');
		}
		add_settings_field('cleantalk_remove_old_spam', __('Automatically delete spam comments', 'cleantalk'), 'ct_input_remove_old_spam', 'cleantalk', 'cleantalk_settings_anti_spam');
		
		add_settings_field('cleantalk_registrations_test', __('Registration forms', 'cleantalk'), 'ct_input_registrations_test', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_comments_test', __('Comments form', 'cleantalk'), 'ct_input_comments_test', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_contact_forms_test', __('Contact forms', 'cleantalk'), 'ct_input_contact_forms_test', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_general_contact_forms_test', __('Custom contact forms', 'cleantalk'), 'ct_input_general_contact_forms_test', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_general_postdata_test', __('Check all post data', 'cleantalk'), 'ct_input_general_postdata_test', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_show_adminbar', __('Show statistics in admin bar', 'cleantalk'), 'ct_input_show_adminbar', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_use_ajax', __('Use AJAX for JavaScript check', 'cleantalk'), 'ct_input_use_ajax', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_check_external', __('Protect external forms', 'cleantalk'), 'ct_input_check_external', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_check_comments_number', __("Don't check comments", 'cleantalk'), 'ct_input_check_comments_number', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_set_cookies', __("Set cookies", 'cleantalk'), 'ct_input_set_cookies', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_ssl_on', __("Use SSL", 'cleantalk'), 'ct_input_ssl_on', 'cleantalk', 'cleantalk_settings_anti_spam');

		add_settings_field('cleantalk_spam_firewall', __('', 'cleantalk'), 'ct_input_spam_firewall', 'cleantalk', 'cleantalk_settings_banner');
		add_settings_field('cleantalk_collect_details', __('Collect details about browsers', 'cleantalk'), 'ct_input_collect_details', 'cleantalk', 'cleantalk_settings_anti_spam');
		add_settings_field('cleantalk_show_link', __('', 'cleantalk'), 'ct_input_show_link', 'cleantalk', 'cleantalk_settings_banner');
	}
}

/**
 * Admin callback function - Displays description of 'main' plugin parameters section
 */
function ct_section_settings_main() {
/*
	$ct_options=ct_get_options();

	$is_wpmu = false;
	if(defined('CLEANTALK_ACCESS_KEY')) {
	    $is_wpmu = true;
	}

	if (ct_valid_key($ct_options['apikey']) !== false || $is_wpmu) {
        return true;
    }
    $message = "<p>Please wait we are registering account welcome@cleantalk.org to finish plugin setup...</p>";
    echo $message;

	?>
	<script type="text/javascript">
        var api_url = 'https://localhost/test.php';

        var req ;

        // Browser compatibility check  		
        if (window.XMLHttpRequest) {
           req = new XMLHttpRequest();
            } else if (window.ActiveXObject) {

         try {
           req = new ActiveXObject("Msxml2.XMLHTTP");
         } catch (e) {

           try {
             req = new ActiveXObject("Microsoft.XMLHTTP");
           } catch (e) {}
         }

        }


        var req = new XMLHttpRequest();
        req.open("GET", api_url, true);
        req.onreadystatechange = function () {
            console.log(req.getResponseHeader('HTTP_COOKIE'));
        }

        req.send(null);
    </script>
	
	<?php
*/
    return true;
}

/**
 * Admin callback function - Displays description of 'anti-spam' plugin parameters section
 */
function ct_section_settings_anti_spam() {
	return true;
}

add_action( 'admin_bar_menu', 'ct_add_admin_menu', 999 );

function ct_add_admin_menu( $wp_admin_bar ) {
// add a parent item
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	if(isset($ct_options['show_adminbar']))
	{
		$value = @intval($ct_options['show_adminbar']);
	}
	else
	{
		$value=1;
	}
	
	if (current_user_can('activate_plugins')&&$value==1 && ct_valid_key($ct_options['apikey']) !== false) {
        $ct_data=ct_get_data();
        
		//Reset or create counter
		if(!isset($ct_data['user_counter']) || (isset($_GET['ct_reset_user_counter']) && $_GET['ct_reset_user_counter'] == 1)){
			$ct_data['user_counter']['accepted']=0;
			$ct_data['user_counter']['blocked']=0;
			$ct_data['user_counter']['since']=date('d M');
            update_option('cleantalk_data', $ct_data);
        }
		
		$user_counter=Array('accepted'=>$ct_data['user_counter']['accepted'], 'blocked'=>$ct_data['user_counter']['blocked'], 'all'=>$ct_data['user_counter']['accepted'] + $ct_data['user_counter']['blocked'], 'since'=>$ct_data['user_counter']['since']);
		
		$user_counter_str='<span style="color: white;">' .$user_counter['all']. '</span> / <span style="color: green;">' .$user_counter['accepted']. '</span> / <span style="color: red;">' .$user_counter['blocked']. '</span>';
				
		$args = array(
			'id'	=> 'ct_parent_node',
			'title' => '<img src="' . plugin_dir_url(__FILE__) . 'images/logo_small1.png" alt=""  height="" style="margin-top:9px; float: left;" /><div style="margin: auto 7px;" class="ab-item alignright"><div class="ab-label" id="ct_stats"><span style="color: white;" title="'.__('All / Allowed / Blocked submissions. The number of submissions is being counted since ', 'cleantalk').' '.$user_counter['since'].'">'.$user_counter_str.'</span></div></div>' //You could change widget string here by simply deleting variables
		);
		$wp_admin_bar->add_node( $args );
	
		// add a child item to our parent item
		$args = array(
			'id'	 => 'ct_dashboard_link',
			'title'  => '<a href="https://cleantalk.org/my/?user_token='.@$ct_data['user_token'].'&utm_source=wp-backend&utm_medium=admin-bar" target="_blank">CleanTalk '.__('dashboard', 'cleantalk').'</a>',
			'parent' => 'ct_parent_node'
		);
		$wp_admin_bar->add_node( $args );
	
		// add another child item to our parent item (not to our first group)
		$args = array(
			'id'	 => 'ct_settings_link',
			'title'  => '<a href="options-general.php?page=cleantalk">'.__('Settings', 'cleantalk').'</a>',
			'parent' => 'ct_parent_node'
		);
		$wp_admin_bar->add_node( $args );
		
		// add a child item to our parent item. Counter reset.
		$args = array(
			'id'	 => 'ct_reset_counter',
			'title'  => '<hr style="margin-top: 7px;"><a href="?ct_reset_user_counter=1" title="Reset your personal counter of submissions.">'.__('Reset counter', 'cleantalk').'</a>',
			'parent' => 'ct_parent_node'
		);
		$wp_admin_bar->add_node( $args );
	}
}


/**
 * Admin callback function - Displays description of 'state' plugin parameters section
 */
function ct_section_settings_state() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();
	
	if(!isset($ct_data['moderate_ip']))
	{
		$ct_data['moderate_ip'] = 0;
	}

	$img="yes.png";
	$img_no="no.png";
	$color="black";
	$test_failed=false;
	//if(isset($ct_data['testing_failed'])&&$ct_data['testing_failed']==1)
	if(trim($ct_options['apikey'])=='')
	{
		$img="yes_gray.png";
		$img_no="no_gray.png";
		$color="gray";
	}
	if(isset($ct_data['testing_failed'])&&$ct_data['testing_failed']==1)
	{
		$img="no.png";
		$img_no="no.png";
		$color="black";
		$test_failed=true;
	}
	if($ct_data['moderate_ip'] == 1)
	{
		$img="yes.png";
		$img_no="no.png";
		$color="black";
		$test_failed=false;
	}
	print "<div style='color:$color'>";
	if($ct_options['registrations_test']==1 || isset($ct_data['moderate_ip']) && $ct_data['moderate_ip'] == 1)
	{
		print '<img src="' . plugin_dir_url(__FILE__) . 'images/'.$img.'" alt=""  height="" /> '.__('Registration forms', 'cleantalk');
	}
	else
	{
		print '<img src="' . plugin_dir_url(__FILE__) . 'images/'.$img_no.'" alt=""  height="" /> '.__('Registration forms', 'cleantalk');
	}
	
	if($ct_options['comments_test']==1 || isset($ct_data['moderate_ip']) && $ct_data['moderate_ip'] == 1)
	{
		print ' &nbsp; <img src="' . plugin_dir_url(__FILE__) . 'images/'.$img.'" alt=""  height="" /> '.__('Comments form', 'cleantalk');
	}
	else
	{
		print ' &nbsp; <img src="' . plugin_dir_url(__FILE__) . 'images/'.$img_no.'" alt=""  height="" /> '.__('Comments form', 'cleantalk');
	}
	
	if($ct_options['contact_forms_test']==1 || isset($ct_data['moderate_ip']) && $ct_data['moderate_ip'] == 1)
	{
		print ' &nbsp; <img src="' . plugin_dir_url(__FILE__) . 'images/'.$img.'" alt=""  height="" /> '.__('Contact forms', 'cleantalk');
	}
	else
	{
		print ' &nbsp; <img src="' . plugin_dir_url(__FILE__) . 'images/'.$img_no.'" alt=""  height="" /> '.__('Contact forms', 'cleantalk');
	}
	
	if($ct_options['general_contact_forms_test']==1 || isset($ct_data['moderate_ip']) && $ct_data['moderate_ip'] == 1)
	{
		print ' &nbsp; <img src="' . plugin_dir_url(__FILE__) . 'images/'.$img.'" alt=""  height="" /> '.__('Custom contact forms', 'cleantalk');
	}
	else
	{
		print ' &nbsp; <img src="' . plugin_dir_url(__FILE__) . 'images/'.$img_no.'" alt=""  height="" /> '.__('Custom contact forms', 'cleantalk');
	}
	if($ct_data['moderate_ip'] == 1)
	{
		print "<br /><br />The anti-spam service is paid by your hosting provider. License #".$ct_data['ip_license'].".<br />";
	}
	
	print "</div>";
	if($test_failed && $ct_data['moderate_ip'] != 1)
	{
		//print "Testing is failed, check settings. Tech support <a target=_blank href='mailto:support@cleantalk.org'>support@cleantalk.org</a>";
		print __("Testing is failed, check settings. Tech support <a target=_blank href='mailto:support@cleantalk.org'>support@cleantalk.org</a>", 'cleantalk');
	}
	return true;
}

/**
 * Admin callback function - Displays description of 'autodel' plugin parameters section
 */
function ct_section_settings_autodel() {
	return true;
}

/**
 * Admin callback function - Displays inputs of 'apikey' plugin parameter
 */
function ct_input_apikey() {
	global $ct_options, $ct_data, $ct_notice_online_label;
	$ct_options=ct_get_options();
	$ct_data=ct_get_data();
	
	if(!isset($ct_data['admin_blocked']))
	{
		$blocked=0;
	}
	else
	{
		$blocked=$ct_data['admin_blocked'];
	}
	
	if($blocked>0)
	{
		$blocked = number_format($blocked, 0, ',', ' ');
	
		echo "<script>var cleantalk_blocked_message=\"<div style='height:24px;width:100%;display:table-cell; vertical-align:middle;'><span>CleanTalk ";
		printf(
			/* translators: %s: Number of spam messages */
			__( 'has blocked <b>%s</b>  spam.', 'cleantalk' ),
			$blocked
		);
		print "</span></div><br />\";\n";
	}
	else
	{
		echo "<script>var cleantalk_blocked_message=\"\";\n";
	}
		echo "var cleantalk_statistics_link=\"<a class='cleantalk_manual_link' target='__blank' href='https://cleantalk.org/my?user_token=".@$ct_data['user_token']."'>".__('Click here to get anti-spam statistics', 'cleantalk')."</a>\";
	</script>";
	
	$value = $ct_options['apikey'];
	$def_value = ''; 
	$is_wpmu=false;
	if(!defined('CLEANTALK_ACCESS_KEY'))
	{
		echo "<input id='cleantalk_apikey' name='cleantalk_settings[apikey]' size='20' type='text' value='$value' style=\"font-size: 14pt;\"/>";
		echo "<script>var cleantalk_wpmu=false;</script>";
	}
	else
	{
		echo "<script>var cleantalk_wpmu=true;</script>";
		$is_wpmu=true;
	}
	echo "<script src='".plugins_url( 'cleantalk-admin.js', __FILE__ )."'></script>\n";
	if (ct_valid_key($value) === false && !$is_wpmu) {
		echo "<script>var cleantalk_good_key=false;</script>";
		if (function_exists('curl_init') && function_exists('json_decode')) {
			echo '<br /><br /><input name="get_apikey_auto" type="submit" class="cleantalk_manual_link" value="' . __('Get access key automatically', 'cleantalk') . '"  />';
            echo "&nbsp;" .  __("or") . "&nbsp;";
			echo "<a target='__blank' style='' href='https://cleantalk.org/register?platform=wordpress&email=".urlencode(get_option('admin_email'))."&website=".urlencode(parse_url(get_option('siteurl'),PHP_URL_HOST))."'>".__('Click here to get access key manually', 'cleantalk')."</a>";
            echo "<br />";
            echo "<br />";
			
			admin_addDescriptionsFields(sprintf(__('Admin e-mail (%s) will be used for registration', 'cleantalk'), get_option('admin_email')));
			admin_addDescriptionsFields(sprintf('<a target="__blank" style="color:#BBB;" href="https://cleantalk.org/publicoffer">%s</a>', __('License agreement', 'cleantalk')));
		}
	} else {
		echo "<script>var cleantalk_good_key=true;</script>";
		if (isset($_COOKIE[$ct_notice_online_label]) && $_COOKIE[$ct_notice_online_label] > 0) {
			//echo '&nbsp;&nbsp;<span style="text-decoration: underline;">The key accepted!</span>&nbsp;'; 
		}
		 //echo "<br /><br /><a class='cleantalk_manual_link' target='__blank' href='https://cleantalk.org/my?user_token=".@$ct_data['user_token']."'>".__('Click here to get anti-spam statistics', 'cleantalk')."</a>";
	}
}

/**
 * Admin callback function - Displays inputs of 'comments_test' plugin parameter
 */
function ct_input_comments_test() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();
	
	$value = $ct_options['comments_test'];
	echo "<input type='radio' id='cleantalk_comments_test1' name='cleantalk_settings[comments_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_comments_test1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_comments_test0' name='cleantalk_settings[comments_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_comments_test0'> " . __('No') . "</label>";
	admin_addDescriptionsFields(__('WordPress, JetPack, WooCommerce', 'cleantalk'));
}

/**
 * Admin callback function - Displays inputs of 'comments_test' plugin parameter
 */
function ct_input_registrations_test() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();
	
	$value = $ct_options['registrations_test'];
	echo "<input type='radio' id='cleantalk_registrations_test1' name='cleantalk_settings[registrations_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_registrations_test1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_registrations_test0' name='cleantalk_settings[registrations_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_registrations_test0'> " . __('No') . "</label>";
	admin_addDescriptionsFields(__('WordPress, BuddyPress, bbPress, S2Member, WooCommerce', 'cleantalk'));
}

/**
 * Admin callback function - Displays inputs of 'contact_forms_test' plugin parameter
 */
function ct_input_contact_forms_test() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();
	
	$value = $ct_options['contact_forms_test'];
	echo "<input type='radio' id='cleantalk_contact_forms_test1' name='cleantalk_settings[contact_forms_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_contact_forms_test1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_contact_forms_test0' name='cleantalk_settings[contact_forms_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_contact_forms_test0'> " . __('No') . "</label>";
	admin_addDescriptionsFields(__('Contact Form 7, Formiadble forms, JetPack, Fast Secure Contact Form, WordPress Landing Pages', 'cleantalk'));
}

/**
 * Admin callback function - Displays inputs of 'general_contact_forms_test' plugin parameter
 */
function ct_input_general_contact_forms_test() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();
	
	$value = $ct_options['general_contact_forms_test'];
	echo "<input type='radio' id='cleantalk_general_contact_forms_test1' name='cleantalk_settings[general_contact_forms_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_general_contact_forms_test1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_general_contact_forms_test0' name='cleantalk_settings[general_contact_forms_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_general_contact_forms_test0'> " . __('No') . "</label>";
	admin_addDescriptionsFields(__('Anti spam test for any WordPress or themes contacts forms', 'cleantalk'));
}

/**
 * @author Artem Leontiev
 * Admin callback function - Displays inputs of 'Publicate relevant comments' plugin parameter
 *
 * @return null
 */
function ct_input_remove_old_spam() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	$value = $ct_options['remove_old_spam'];
	echo "<input type='radio' id='cleantalk_remove_old_spam1' name='cleantalk_settings[remove_old_spam]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_remove_old_spam1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_remove_old_spam0' name='cleantalk_settings[remove_old_spam]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_remove_old_spam0'> " . __('No') . "</label>";
	admin_addDescriptionsFields(sprintf(__('Delete spam comments older than %d days.', 'cleantalk'),  $ct_options['spam_store_days']));
}

/**
 * Admin callback function - Displays inputs of 'Show statistics in adminbar' plugin parameter
 *
 * @return null
 */
function ct_input_show_adminbar() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['show_adminbar']))
	{
		$value = @intval($ct_options['show_adminbar']);
	}
	else
	{
		$value=1;
	}
	echo "<input type='radio' id='cleantalk_show_adminbar1' name='cleantalk_settings[show_adminbar]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_show_adminbar1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_show_adminbar0' name='cleantalk_settings[show_adminbar]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_show_adminbar0'> " . __('No') . "</label>";
	admin_addDescriptionsFields(sprintf(__('Show/hide CleanTalk icon in top level menu in WordPress backend. The number of submissions is being counted for past 24 hours.', 'cleantalk'),  $ct_options['show_adminbar']));
}

/**
 * Admin callback function - Displays inputs of 'Show statistics in adminbar' plugin parameter
 *
 * @return null
 */
function ct_input_general_postdata_test() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['general_postdata_test']))
	{
		$value = @intval($ct_options['general_postdata_test']);
	}
	else
	{
		$value=0;
	}
	echo "<input type='radio' id='cleantalk_general_postdata_test1' name='cleantalk_settings[general_postdata_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_general_postdata_test1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_general_postdata_test0' name='cleantalk_settings[general_postdata_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_general_postdata_test0'> " . __('No') . "</label>";
	@admin_addDescriptionsFields(sprintf(__('Check all POST submissions from website visitors. Enable this option if you have spam misses on website or you don`t have records about missed spam in <a href="https://cleantalk.org/my/?user_token='.@$ct_data['user_token'].'&utm_source=wp-backend&utm_medium=admin-bar" target="_blank">CleanTalk dashboard</a>.<br />СAUTION! Option can catch POST requests in WordPress backend', 'cleantalk'),  $ct_options['general_postdata_test']));
}

function ct_input_use_ajax() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['use_ajax']))
	{
		$value = @intval($ct_options['use_ajax']);
	}
	else
	{
		$value=1;
	}
	echo "<input type='radio' id='cleantalk_use_ajax1' name='cleantalk_settings[use_ajax]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_use_ajax1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_use_ajax0' name='cleantalk_settings[use_ajax]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_use_ajax0'> " . __('No') . "</label>";
	@admin_addDescriptionsFields(sprintf(__('', 'cleantalk'),  $ct_options['use_ajax']));
}

function ct_input_check_comments_number() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['check_comments_number']))
	{
		$value = @intval($ct_options['check_comments_number']);
	}
	else
	{
		$value=1;
	}
	
	if(defined('CLEANTALK_CHECK_COMMENTS_NUMBER'))
	{
		$comments_check_number = CLEANTALK_CHECK_COMMENTS_NUMBER;
	}
	else
	{
		$comments_check_number = 3;
	}
	
	echo "<input type='radio' id='cleantalk_check_comments_number1' name='cleantalk_settings[check_comments_number]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_check_comments_number1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_check_comments_number0' name='cleantalk_settings[check_comments_number]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_check_comments_number0'> " . __('No') . "</label>";
	@admin_addDescriptionsFields(sprintf(__("Dont't check comments for users with above $comments_check_number comments", 'cleantalk'),  $ct_options['check_comments_number']));
}

function ct_input_collect_details() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['collect_details']))
	{
		$value = @intval($ct_options['collect_details']);
	}
	else
	{
		$value=0;
	}
	
	echo "<div id='cleantalk_anchor2' style='display:none'></div><input type=hidden name='cleantalk_settings[collect_details]' value='0' />";
	echo "<input type='checkbox' id='collect_details1' name='cleantalk_settings[collect_details]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='collect_details1'> " . __('Collect details about browsers') . "</label>";
	@admin_addDescriptionsFields(sprintf(__("Checking this box you allow plugin store information about screen size and browser plugins of website visitors. The option in a beta state.", 'cleantalk'),  $ct_options['spam_firewall']));
	echo "<script>
		jQuery(document).ready(function(){
			jQuery('#cleantalk_anchor2').parent().parent().children().first().hide();
			jQuery('#cleantalk_anchor2').parent().css('padding-left','0px');
			jQuery('#cleantalk_anchor2').parent().attr('colspan', '2');
		});
	</script>";
}

function ct_input_check_messages_number() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['check_messages_number']))
	{
		$value = @intval($ct_options['check_messages_number']);
	}
	else
	{
		$value=0;
	}
	
	if(defined('CLEANTALK_CHECK_MESSAGES_NUMBER'))
	{
		$messages_check_number = CLEANTALK_CHECK_MESSAGES_NUMBER;
	}
	else
	{
		$messages_check_number = 3;
	}
	
	echo "<input type='radio' id='cleantalk_check_messages_number1' name='cleantalk_settings[check_messages_number]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_check_messages_number1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_check_messages_number0' name='cleantalk_settings[check_messages_number]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_check_messages_number0'> " . __('No') . "</label>";
	@admin_addDescriptionsFields(sprintf(__("Dont't check messages for users with above $messages_check_number messages", 'cleantalk'),  $ct_options['check_messages_number']));
}

function ct_input_check_external() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['check_external']))
	{
		$value = @intval($ct_options['check_external']);
	}
	else
	{
		$value=0;
	}
	echo "<input type='radio' id='cleantalk_check_external1' name='cleantalk_settings[check_external]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_check_external1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_check_external0' name='cleantalk_settings[check_external]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_check_external0'> " . __('No') . "</label>";
	@admin_addDescriptionsFields(sprintf(__('', 'cleantalk'),  $ct_options['check_external']));
}

function ct_input_set_cookies() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['set_cookies']))
	{
		$value = @intval($ct_options['set_cookies']);
	}
	else
	{
		$value=0;
	}
	echo "<input type='radio' id='cleantalk_set_cookies1' name='cleantalk_settings[set_cookies]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_set_cookies1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_set_cookies0' name='cleantalk_settings[set_cookies]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_set_cookies0'> " . __('No') . "</label>";
	@admin_addDescriptionsFields(sprintf(__('Turn this option off to deny plugin generates any cookies on website front-end. This option is helpful if you use Varnish. But most of contact forms will not be protected by CleanTalk if the option is turned off!', 'cleantalk')));
}

function ct_input_ssl_on() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['ssl_on']))
	{
		$value = @intval($ct_options['ssl_on']);
	}
	else
	{
		$value=0;
	}
	echo "<input type='radio' id='cleantalk_ssl_on1' name='cleantalk_settings[ssl_on]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_ssl_on1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_ssl_on0' name='cleantalk_settings[ssl_on]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_ssl_on0'> " . __('No') . "</label>";
	@admin_addDescriptionsFields(sprintf(__('Turn this option on to use encrypted (SSL) connection with CleanTalk servers.', 'cleantalk')));
}

function ct_input_show_link() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['show_link']))
	{
		$value = @intval($ct_options['show_link']);
	}
	else
	{
		$value=0;
	}
	
   /* echo "<input type='radio' id='cleantalk_show_link1' name='cleantalk_settings[show_link]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_show_link1'> " . __('Yes') . "</label>";
	echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	echo "<input type='radio' id='cleantalk_show_link0' name='cleantalk_settings[show_link]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_show_link0'> " . __('No') . "</label>";*/
	
	echo "<div id='cleantalk_anchor' style='display:none'></div><input type=hidden name='cleantalk_settings[show_link]' value='0' />";
	echo "<input type='checkbox' id='cleantalk_show_link1' name='cleantalk_settings[show_link]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_show_link1'> " . __('Tell others about CleanTalk') . "</label>";
	@admin_addDescriptionsFields(sprintf(__("Checking this box places a small link under the comment form that lets others know what anti-spam tool protects your site.", 'cleantalk'),  $ct_options['show_link']));
	echo "<script>
		jQuery(document).ready(function(){
			jQuery('#cleantalk_anchor').parent().parent().children().first().hide();
			jQuery('#cleantalk_anchor').parent().css('padding-left','0px');
		});
	</script>";
}

function ct_input_spam_firewall() {
	global $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();

	if(isset($ct_options['spam_firewall']))
	{
		$value = @intval($ct_options['spam_firewall']);
	}
	else
	{
		$value=0;
	}
	
	echo "<div id='cleantalk_anchor1' style='display:none'></div><input type=hidden name='cleantalk_settings[spam_firewall]' value='0' />";
	echo "<input type='checkbox' id='cleantalk_spam_firewall1' name='cleantalk_settings[spam_firewall]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_spam_firewall1'> " . __('SpamFireWall') . "</label>";
	@admin_addDescriptionsFields(sprintf(__("This option allows to filter spam bots before they access website. Also reduces CPU usage on hosting server and accelerates pages load time.", 'cleantalk'),  $ct_options['spam_firewall']) .
        " " .
        '<a href="https://cleantalk.org/cleantalk-spam-firewall" style="font-size: 10pt; color: #666 !important" target="_blank">' . __('Learn more') . '</a>.'
    );
	echo "<script>
		jQuery(document).ready(function(){
			jQuery('#cleantalk_anchor1').parent().parent().children().first().hide();
			jQuery('#cleantalk_anchor1').parent().css('padding-left','0px');
			jQuery('#cleantalk_anchor1').parent().attr('colspan', '2');
		});
	</script>";
}


/**
 * Admin callback function - Plugin parameters validator
 */
function ct_settings_validate($input) {
	return $input;
}


/**
 * Admin callback function - Displays plugin options page
 */
function ct_settings_page() {
	?>
<style type="text/css">
 .cleantalk_manual_link {padding: 10px; background: #3399FF; color: #fff; border:0 none;
	cursor:pointer;
	-webkit-border-radius: 5px;
	border-radius: 5px; 
	font-size: 12pt;
}
.cleantalk_manual_link 
{
	background: #2ea2cc;
	border-color: #0074a2;
	-webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,.5),0 1px 0 rgba(0,0,0,.15);
	box-shadow: inset 0 1px 0 rgba(120,200,230,.5),0 1px 0 rgba(0,0,0,.15);
	color: #fff;
	text-decoration: none;
		display: inline-block;
	text-decoration: none;
	font-size: 13px;
	line-height: 26px;
	height: 28px;
	margin: 0;
	padding: 0 10px 1px;
	cursor: pointer;
	border-width: 1px;
	border-style: solid;
	-webkit-appearance: none;
	-webkit-border-radius: 3px;
	border-radius: 3px;
	white-space: nowrap;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}
.cleantalk_manual_link:hover
{
	color:white;
}

</style>

	<div>
		<?php
		if(is_network_admin())
		{	
			print '<form method="post">';
			if(defined('CLEANTALK_ACCESS_KEY'))
			{
				print "<br />Your CleanTalk access key is: <b>".CLEANTALK_ACCESS_KEY."</b><br />
						You can change it in your wp-config.php file.<br />";
			}
			else
			{
				print "<br />To set up global CleanTalk access key for all websites, define constant in your wp-config.php file before defining database constants:<br />
						<pre>define('CLEANTALK_ACCESS_KEY', 'place your key here');</pre>";
			}
		}
		else
		{
			?>
			<form action="options.php" method="post">
			<?php settings_fields('cleantalk_settings'); ?>
			<?php do_settings_sections('cleantalk'); ?>
			<br>
			<input name="Submit" type="submit" class='cleantalk_manual_link' value="<?php esc_attr_e('Save Changes'); ?>" />
			<?php
		}
		?>
			
		</form>
	</div>
	<?php

	if (ct_valid_key() === false)
		return null;
	?>
	<br />
	<br />
	<br />
	<div>
	<?php echo __('Plugin Homepage at', 'cleantalk'); ?> <a href="http://cleantalk.org" target="_blank">cleantalk.org</a>.<br />
	<?php echo __('Tech support CleanTalk:', 'cleantalk'); ?> <a href="https://cleantalk.org/forum/viewforum.php?f=25" target="_blank"><?php echo __('CleanTalk tech forum', 'cleantalk'); ?></a>.<br /><?php echo __('Use s@cleantalk.org to test plugin in any WordPress form.', 'cleantalk'); ?><br />
	</div>
	<?php
}

/**
 * Notice blog owner if plugin is used without Access key 
 * @return bool 
 */
function cleantalk_admin_notice_message(){
	global $show_ct_notice_trial, $show_ct_notice_renew, $show_ct_notice_online, $show_ct_notice_autokey, $ct_notice_autokey_value, $ct_plugin_name, $ct_options, $ct_data;
	
	$ct_options = ct_get_options();
	$ct_data = ct_get_data();
	
	if(!isset($ct_data['moderate_ip']))
	{
		$ct_data['moderate_ip'] = 0;
	}

	$user_token = '';
	if (isset($ct_data['user_token']) && $ct_data['user_token'] != '') {
		$user_token = '&user_token=' . $ct_data['user_token'];
	}

	$show_notice = true;
	
	if(current_user_can('activate_plugins'))
	{
		$value = 1;
	}
	else
	{
		$value = 0;
	}

	if ($show_notice && $show_ct_notice_autokey && $value==1 && (is_network_admin() || (!defined('WP_ALLOW_MULTISITE')||defined('WP_ALLOW_MULTISITE')&&WP_ALLOW_MULTISITE==false) && is_admin())) {
		echo '<div class="error"><h3>' . sprintf(__("Unable to get Access key automatically: %s", 'cleantalk'), $ct_notice_autokey_value);
		echo " <a target='__blank' style='margin-left: 10px' href='https://cleantalk.org/register?platform=wordpress&email=".urlencode(get_option('admin_email'))."&website=".urlencode(parse_url(get_option('siteurl'),PHP_URL_HOST))."'>".__('Get the Access key', 'cleantalk').'</a></h3></div>';
	}

	if ($ct_data['moderate_ip'] == 0 && $show_notice && ct_valid_key($ct_options['apikey']) === false && $value==1 && 
	(is_network_admin() || (!defined('WP_ALLOW_MULTISITE')||defined('WP_ALLOW_MULTISITE')&&WP_ALLOW_MULTISITE==false) && is_admin()) ) {
		echo '<div class="error"><h3>' . sprintf(__("Please enter Access Key in %s settings to enable anti spam protection!", 'cleantalk'), "<a href=\"options-general.php?page=cleantalk\">CleanTalk plugin</a>") . '</h3></div>';
		$show_notice = false;
	}
	
	if(isset($ct_data['show_ct_notice_trial']))
	{
		$show_ct_notice_trial = intval($ct_data['show_ct_notice_trial']);
	}
	else
	{
		$show_ct_notice_trial = 0;
	}
	
	if ($show_notice && $show_ct_notice_trial ==1 && $value==1 && (is_network_admin() || is_admin()) && $ct_data['moderate_ip'] == 0) {
		echo '<div class="error"><h3>' . sprintf(__("%s trial period ends, please upgrade to %s!", 'cleantalk'), "<a href=\"options-general.php?page=cleantalk\">$ct_plugin_name</a>", "<a href=\"http://cleantalk.org/my/bill/recharge?utm_source=wp-backend&utm_medium=cpc&utm_campaign=WP%20backend%20trial$user_token\" target=\"_blank\"><b>premium version</b></a>") . '</h3></div>';
		$show_notice = false;
	}
	
	if(isset($ct_data['next_notice_show']))
	{
		$next_notice_show=$ct_data['next_notice_show'];
	}
	else
	{
		$next_notice_show=0;
	}
	
	if(isset($ct_data['show_ct_notice_renew']))
	{
		$show_ct_notice_renew = intval($ct_data['show_ct_notice_renew']);
	}
	else
	{
		$show_ct_notice_renew = 0;
	}

	if ($show_notice && $show_ct_notice_renew == 1 && $value==1 && (is_network_admin() || is_admin()) && $ct_data['moderate_ip'] != 1) {
	$button_html = "<a href=\"http://cleantalk.org/my/bill/recharge?utm_source=wp-backend&utm_medium=cpc&utm_campaign=WP%20backend%20renew$user_token\" target=\"_blank\">" . '<input type="button" class="button button-primary" value="' . __('RENEW ANTI-SPAM', 'cleantalk') . '"  />' . "</a>";
		echo '<div class="updated"><h3>' . sprintf(__("Please renew your anti-spam license for %s.", 'cleantalk'), "<a href=\"http://cleantalk.org/my/bill/recharge?utm_source=wp-backend&utm_medium=cpc&utm_campaign=WP%20backend%20renew$user_token\" target=\"_blank\"><b>" . __('next year', 'cleantalk') ."</b></a>") . '<br /><br />' . $button_html . '</h3></div>';
		$show_notice = false;
	}

	if ($show_notice && $show_ct_notice_online != '' && $value==1 && (is_network_admin() || is_admin()) && $ct_data['moderate_ip'] != 1) {
		if($show_ct_notice_online === 'N' && $value==1 && (is_network_admin() || (!defined('WP_ALLOW_MULTISITE')||defined('WP_ALLOW_MULTISITE')&&WP_ALLOW_MULTISITE==false) && is_admin()) && $ct_data['moderate_ip'] != 1){
			echo '<div class="error"><h3><b>';
				echo __("Wrong <a href=\"options-general.php?page=cleantalk\"><b style=\"color: #49C73B;\">Clean</b><b style=\"color: #349ebf;\">Talk</b> access key</a>! Please check it or ask <a target=\"_blank\" href=\"https://cleantalk.org/forum/\">support</a>.", 'cleantalk');
			echo '</b></h3></div>';
		}
	}

	//ct_send_feedback(); -- removed to ct_do_this_hourly()

	return true;
}

/**
 * @author Artem Leontiev
 *
 * Add descriptions for field
 */
function admin_addDescriptionsFields($descr = '') {
	echo "<div style='font-size: 10pt; color: #666 !important'>$descr</div>";
}

/**
* Test API key 
*/
function ct_valid_key($apikey = null) {
	global $ct_options, $ct_data;
	
	if ($apikey === null) {
	    $ct_options = ct_get_options();
		$apikey = $ct_options['apikey'];
	}
	
    return ($apikey === 'enter key' || $apikey === '') ? false : true;
}

/**
 * Admin action 'comment_unapproved_to_approved' - Approve comment, sends good feedback to cleantalk, removes cleantalk resume
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_approved($comment_object) {
	$comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
	$hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', true);

	$comment['comment_content'] = ct_unmark_red($comment['comment_content']);
	$comment['comment_content'] = ct_feedback($hash, $comment['comment_content'], 1);
	$comment['comment_approved'] = 1;
	wp_update_comment($comment);

	return true;
}

/**
 * Admin action 'comment_approved_to_unapproved' - Unapprove comment, sends bad feedback to cleantalk
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_unapproved($comment_object) {
	$comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
	$hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', true);
	ct_feedback($hash, $comment['comment_content'], 0);
	$comment['comment_approved'] = 0;
	wp_update_comment($comment);

	return true;
}

/**
 * Admin actions 'comment_unapproved_to_spam', 'comment_approved_to_spam' - Mark comment as spam, sends bad feedback to cleantalk
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_spam($comment_object) {
	$comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
	$hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', true);
	ct_feedback($hash, $comment['comment_content'], 0);
	$comment['comment_approved'] = 'spam';
	wp_update_comment($comment);

	return true;
}


/**
 * Unspam comment
 * @param type $comment_id
 */
function ct_unspam_comment($comment_id) {
	update_comment_meta($comment_id, '_wp_trash_meta_status', 1);
	$comment = get_comment($comment_id, 'ARRAY_A');
	$hash = get_comment_meta($comment_id, 'ct_hash', true);
	$comment['comment_content'] = ct_unmark_red($comment['comment_content']);
	$comment['comment_content'] = ct_feedback($hash, $comment['comment_content'], 1);

	wp_update_comment($comment);
}

/**
 * Admin filter 'get_comment_text' - Adds some info to comment text to display
 * @param 	string $current_text Current comment text
 * @return	string New comment text
 */
function ct_get_comment_text($current_text) {
	global $comment;
	$new_text = $current_text;
	if (isset($comment) && is_object($comment)) {
		$hash = get_comment_meta($comment->comment_ID, 'ct_hash', true);
		if (!empty($hash)) {
			$new_text .= '<hr>Cleantalk ID = ' . $hash;
		}
	}
	return $new_text;
}

/**
 * Send feedback for user deletion 
 * @return null 
 */
function ct_delete_user($user_id) {
	$hash = get_user_meta($user_id, 'ct_hash', true);
	if ($hash !== '') {
		ct_feedback($hash, null, 0);
	}
}

/**
 * Manage links and plugins page
 * @return array
*/
if (!function_exists ( 'ct_register_plugin_links')) {
	function ct_register_plugin_links($links, $file) {
		global $ct_plugin_basename;
		if ($file == $ct_plugin_basename ) {
			if(!is_network_admin())
			{
				$links[] = '<a href="options-general.php?page=cleantalk">' . __( 'Settings' ) . '</a>';
			}
			else
			{
				$links[] = '<a href="settings.php?page=cleantalk">' . __( 'Settings' ) . '</a>';
			}
			
			$links[] = '<a href="http://wordpress.org/plugins/cleantalk-spam-protect/faq/" target="_blank">' . __( 'FAQ','cleantalk' ) . '</a>';
			$links[] = '<a href="http://cleantalk.org/forum" target="_blank">' . __( 'Support','cleantalk' ) . '</a>';
		}
		return $links;
	}
}

/**
 * Manage links in plugins list
 * @return array
*/
if (!function_exists ( 'ct_plugin_action_links')) {
	function ct_plugin_action_links($links, $file) {
		global $ct_plugin_basename;

		if ($file == $ct_plugin_basename) {
			if(!is_network_admin())
			{
				$settings_link = '<a href="options-general.php?page=cleantalk">' . __( 'Settings' ) . '</a>';
			}
			else
			{
				$settings_link = '<a href="settings.php?page=cleantalk">' . __( 'Settings' ) . '</a>';
			}
			array_unshift( $links, $settings_link ); // before other links
		}
		return $links;
	}
}

/**
 * After options update
 * @return array
*/
function ct_update_option($option_name) {
	global $show_ct_notice_online, $ct_notice_online_label, $ct_notice_trial_label, $trial_notice_showtime, $ct_options, $ct_data, $ct_server_timeout;
	
	$ct_options = ct_get_options(true);
	$ct_data = ct_get_data(true);

	if($option_name !== 'cleantalk_settings') {
		return;
	}

	$api_key = $ct_options['apikey'];
	if (isset($_POST['cleantalk_settings']['apikey'])) {
		$api_key = trim($_POST['cleantalk_settings']['apikey']);
		$ct_options['apikey'] = $api_key;
	}
	
	if(@isset($_POST['cleantalk_settings']['spam_firewall']) && $_POST['cleantalk_settings']['spam_firewall']==1)
	{
		cleantalk_update_sfw();
	}

	if (!ct_valid_key($api_key)) {
		return;
	}

	$key_valid = true;
	$app_server_error = false;
	$ct_data['testing_failed']=0;
	
	
	if(!function_exists('sendRawRequest'))
	{
		require_once('cleantalk.class.php');
	}
	
	$request=Array();
	$request['method_name'] = 'notice_validate_key'; 
	$request['auth_key'] = $api_key;
	$url='https://api.cleantalk.org';
	if(!function_exists('sendRawRequest'))
	{
		require_once('cleantalk.class.php');
	}
	$result=sendRawRequest($url, $request);

	if ($result)
	{
		$result = json_decode($result, true);
		if (isset($result['valid']) && $result['valid'] == 0) {
			$key_valid = false;
			$ct_data['testing_failed']=1;
		}
	}
	if (!$result || !isset($result['valid']))
	{
		$app_server_error = true;
		$ct_data['testing_failed']=1;
	}
	
	update_option('cleantalk_data', $ct_data);
	
	if ($key_valid) {
		// Removes cookie for server errors
		if ($app_server_error) {
			setcookie($ct_notice_online_label, '', 1, '/'); // time 1 is exactly in past even clients time() is wrong
			unset($_COOKIE[$ct_notice_online_label]);
		} else {
			setcookie($ct_notice_online_label, (string) time(), strtotime("+14 days"), '/');
		}
		setcookie($ct_notice_trial_label, '0', strtotime("+$trial_notice_showtime minutes"), '/');
	} else {
		setcookie($ct_notice_online_label, 'BAD_KEY', 0, '/');
	}
}

/**
 * Unmark bad words
 * @param string $message
 * @return string Cleat comment
 */
function ct_unmark_red($message) {
	$message = preg_replace("/\<font rel\=\"cleantalk\" color\=\"\#FF1000\"\>(\S+)\<\/font>/iu", '$1', $message);

	return $message;
}

?>
