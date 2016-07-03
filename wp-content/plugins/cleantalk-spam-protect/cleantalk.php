<?php
/*
  Plugin Name: Spam Protection by CleanTalk
  Plugin URI: http://cleantalk.org
  Description: Max power, all-in-one, captcha less, premium anti-spam plugin. No comment spam, no registration spam, no contact spam, protects any WordPress forms. Formerly Anti-Spam by CleanTalk. 
  Version: 5.43.2
  Author: СleanTalk <welcome@cleantalk.org>
  Author URI: http://cleantalk.org
 */
$cleantalk_plugin_version='5.43.2';
$ct_agent_version = 'wordpress-5432';
$cleantalk_executed=false;
$ct_sfw_updated = false;

$ct_redirects_label = 'ct_redirects';

if(defined('CLEANTALK_AJAX_USE_BUFFER'))
{
	$cleantalk_use_buffer=CLEANTALK_AJAX_USE_BUFFER;
}
else
{
	$cleantalk_use_buffer=true;
}

if(defined('CLEANTALK_AJAX_USE_FOOTER_HEADER'))
{
	$cleantalk_use_footer_header=CLEANTALK_AJAX_USE_FOOTER_HEADER;
}
else
{
	$cleantalk_use_footer_header=true;
}
if(!defined('CLEANTALK_PLUGIN_DIR')){
    define('CLEANTALK_PLUGIN_DIR', plugin_dir_path(__FILE__));
    global $ct_options, $ct_data, $pagenow;
    
    require_once(CLEANTALK_PLUGIN_DIR . 'inc/cleantalk-common.php');
    require_once(CLEANTALK_PLUGIN_DIR . 'inc/cleantalk-widget.php');
    $ct_options=ct_get_options();
    $ct_data=ct_get_data();
    if(@stripos($_SERVER['REQUEST_URI'],'admin-ajax.php')!==false && sizeof($_POST)>0 && isset($_GET['action']) && $_GET['action']=='ninja_forms_ajax_submit')
    {
    	$_POST['action']='ninja_forms_ajax_submit';
    }
    
    if(isset($ct_options['spam_firewall']))
    {
    	$value = @intval($ct_options['spam_firewall']);
    }
    else
    {
    	$value=0;
    }
    
    /*
        Turn off the SpamFireWall if current url in the exceptions list. 
    */
    if ($value == 1 && isset($cleantalk_url_exclusions) && is_array($cleantalk_url_exclusions)) {
        foreach ($cleantalk_url_exclusions as $v) {
            if (stripos($_SERVER['REQUEST_URI'], $v) !== false) {
                $value = 0;
                break;
            }
        } 
    }

    /*
        Turn off the SpamFireWall for WordPress core pages
    */
    $ct_wordpress_core_pages = array(
        '/wp-admin',
        '/feed'
    );
    if ($value == 1) {
        foreach ($ct_wordpress_core_pages as $v) {
            if (stripos($_SERVER['REQUEST_URI'], $v) !== false) {
                $value = 0;
                break;
            }
        }
    }

    if($value==1 && !is_admin() || $value==1 && defined( 'DOING_AJAX' ) && DOING_AJAX)
    {
	   	$is_sfw_check=true;
	   	$ip=cleantalk_get_ip();

	   	for($i=0;$i<sizeof($ip);$i++)
	   	{
	    	if(isset($_COOKIE['ct_sfw_pass_key']) && $_COOKIE['ct_sfw_pass_key']==md5($ip[$i].$ct_options['apikey']))
	    	{
	    		$is_sfw_check=false;
	    		if(isset($_COOKIE['ct_sfw_passed']))
	    		{
	    			if(isset($ct_data['sfw_log']))
					{
						$sfw_log=$ct_data['sfw_log'];
					}
					else
					{
						$sfw_log=array();
						$sfw_log[$ip[$i]]=Array();
					}
	    			$sfw_log[$ip[$i]]['allow']++;
	    			$ct_data['sfw_log'] = $sfw_log;
	    			update_option('cleantalk_data', $ct_data);
	    			@setcookie ('ct_sfw_passed', '0', 1, "/");
	    		}
	    		//@$ct_data['sfw_log'][cleantalk_get_ip()]['all']++;
	    		//update_option('cleantalk_data', $ct_data);
	    	}
	    }
    	if($is_sfw_check)
    	{
    		//include_once("cleantalk-sfw.php");
    		include_once("inc/cleantalk-sfw.class.php");
    		$sfw = new CleanTalkSFW();
    		$sfw->cleantalk_get_real_ip();
    		$sfw->check_ip();
    		if($sfw->result)
    		{
    			$sfw->sfw_die();
    		}
    	}
    	
    	//cron start
    	if(isset($ct_data['last_sfw_send']))
    	{
    		$last_sfw_send=$ct_data['last_sfw_send'];
    	}
    	else
    	{
    		$last_sfw_send=0;
    	}
    	if(time()-$last_sfw_send>3600)
    	{
    		ct_send_sfw_log();
    		$ct_data['last_sfw_send']=time();
    		update_option('cleantalk_data', $ct_data);
    	}
    	//cron end
    }
    
    if(isset($ct_options['check_external']))
    {
    	if(@intval($ct_options['check_external'])==1)
    	{
    		$test_external_forms=true;
    	}
    	else
    	{
    		$test_external_forms=false;
    	}
    }
    else
    {
    	$test_external_forms=false;
    }

    // Activation/deactivation functions must be in main plugin file.
    // http://codex.wordpress.org/Function_Reference/register_activation_hook
    register_activation_hook( __FILE__, 'ct_activation' );
    register_deactivation_hook( __FILE__, 'ct_deactivation' );

    // 
    // Redirect admin to plugin settings.
    //
    if(!defined('WP_ALLOW_MULTISITE') || defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE == false)
    {
    	add_action('admin_init', 'ct_plugin_redirect');
    }
        
    // After plugin loaded - to load locale as described in manual
    add_action( 'ct_init', 'ct_plugin_loaded' );
    ct_plugin_loaded();
    
    if(isset($ct_options['use_ajax']))
    {
    	$use_ajax = @intval($ct_options['use_ajax']);
    }
    else
    {
    	$use_ajax=1;
    }
    
    if($use_ajax==1 && 
    	stripos($_SERVER['REQUEST_URI'],'.xml')===false && 
    	stripos($_SERVER['REQUEST_URI'],'.xsl')===false)
    {
    	if($cleantalk_use_buffer)
    	{
			add_action('wp_loaded', 'ct_add_nocache_script', 1);
		}
		if($cleantalk_use_footer_header)
		{
			add_action('wp_footer', 'ct_add_nocache_script_footer', 1);
			add_action('wp_head', 'ct_add_nocache_script_header', 1);
		}
		add_action( 'wp_ajax_nopriv_ct_get_cookie', 'ct_get_cookie',1 );
		add_action( 'wp_ajax_ct_get_cookie', 'ct_get_cookie',1 );
	}
    
    
	if(isset($ct_options['show_link']))
    {
    	$value = @intval($ct_options['show_link']);
    }
    else
    {
    	$value=0;
    }
    if($value==1)
    {
    	add_action('comment_form_after', 'ct_show_comment_link');
    }

    if (is_admin()||is_network_admin())
    {
		require_once(CLEANTALK_PLUGIN_DIR . 'inc/cleantalk-admin.php');
		if (!(defined( 'DOING_AJAX' ) && DOING_AJAX))
		{
			add_action('admin_init', 'ct_admin_init', 1);
			add_action('admin_menu', 'ct_admin_add_page');
			if(is_network_admin())
			{
				add_action('network_admin_menu', 'ct_admin_add_page');
			}
			add_action('admin_notices', 'cleantalk_admin_notice_message');
		}
		if (defined( 'DOING_AJAX' ) && DOING_AJAX||isset($_POST['cma-action']))
		{
			$cleantalk_hooked_actions=Array();
			require_once(CLEANTALK_PLUGIN_DIR . 'inc/cleantalk-public.php');
			require_once(CLEANTALK_PLUGIN_DIR . 'inc/cleantalk-ajax.php');
			if(isset($_POST['action'])&&!in_array($_POST['action'],$cleantalk_hooked_actions)&&!isset($_COOKIE[LOGGED_IN_COOKIE]))
			{
				ct_ajax_hook();
			}
            
            //
            // Some of plugins to register a users use AJAX context.
            //
            add_filter('registration_errors', 'ct_registration_errors', 1, 3);
            add_action('user_register', 'ct_user_register');

		}

		add_action('admin_enqueue_scripts', 'ct_enqueue_scripts');
		if($pagenow=='edit-comments.php')
		{
	    	add_action('comment_unapproved_to_approvecomment', 'ct_comment_approved'); // param - comment object
	    	add_action('comment_unapproved_to_approved', 'ct_comment_approved'); // param - comment object
	    	add_action('comment_approved_to_unapproved', 'ct_comment_unapproved'); // param - comment object
	    	add_action('comment_unapproved_to_spam', 'ct_comment_spam');  // param - comment object
	    	add_action('comment_approved_to_spam', 'ct_comment_spam');   // param - comment object
	    	//add_filter('get_comment_text', 'ct_get_comment_text');   // param - current comment text
	    	add_filter('unspam_comment', 'ct_unspam_comment');
	    }
	    if($pagenow=='users.php')
		{
	    	add_action('delete_user', 'ct_delete_user');
	    }
	    if($pagenow=='plugins.php' || @strpos($_SERVER['REQUEST_URI'],'plugins.php')!==false)
		{
	    	add_filter('plugin_row_meta', 'ct_register_plugin_links', 10, 2);
	    	add_filter('plugin_action_links', 'ct_plugin_action_links', 10, 2);
	    }
		add_action('updated_option', 'ct_update_option'); // param - option name, i.e. 'cleantalk_settings'
    }else{
	require_once(CLEANTALK_PLUGIN_DIR . 'inc/cleantalk-public.php');

	// Init action.
	add_action('init', 'ct_init_after_all', 100);
	add_action('plugins_loaded', 'ct_init', 1);

	// Hourly run hook
	add_action('ct_hourly_event_hook', 'ct_do_this_hourly');

	// Comments 
	add_filter('preprocess_comment', 'ct_preprocess_comment', 1, 1);     // param - comment data array
	add_filter('comment_text', 'ct_comment_text' );

	// Registrations
	add_action('register_form','ct_register_form');
	add_filter('registration_errors', 'ct_registration_errors', 1, 3);
	add_action('user_register', 'ct_user_register');

	// Multisite registrations
	add_action('signup_extra_fields','ct_register_form');
	add_filter('wpmu_validate_user_signup', 'ct_registration_errors_wpmu', 10, 3);

	// Login form - for notifications only
	add_filter('login_message', 'ct_login_message');
    }
}

/**
 * On activation, set a time, frequency and name of an action hook to be scheduled.
 */
if (!function_exists ( 'ct_activation')) {
    function ct_activation() {
        wp_schedule_event(time(), 'hourly', 'ct_hourly_event_hook' );
        //wp_schedule_event(time(), 'hourly', 'ct_send_sfw_log' );
        wp_schedule_event(time(), 'daily', 'cleantalk_update_sfw' );
        
        cleantalk_update_sfw();
        add_option('ct_plugin_do_activation_redirect', true);
    }
}
/**
 * On deactivation, clear schedule.
 */
if (!function_exists ( 'ct_deactivation')) {
    function ct_deactivation() {
	wp_clear_scheduled_hook( 'ct_hourly_event_hook' );
	@wp_clear_scheduled_hook( 'ct_send_sfw_log' );
	wp_clear_scheduled_hook( 'cleantalk_update_sfw' );
    }
}

/**
 * Redirects admin to plugin settings after activation. 
 */
function ct_plugin_redirect()
{
    global $ct_redirects_label;
	if (get_option('ct_plugin_do_activation_redirect', false))
	{
		delete_option('ct_plugin_do_activation_redirect');
		if(!isset($_GET['activate-multi']) && !isset($_COOKIE[$ct_redirects_label]))
		{
		    setcookie($ct_redirects_label, 1, null, '/'); 
			wp_redirect("options-general.php?page=cleantalk");
		}
	}
}

function ct_add_event($event_type)
{
	global $ct_data,$cleantalk_executed;
   
    //
    // To migrate on the new version of ct_add_event(). 
    //
    switch ($event_type) {
        case '0': $event_type = 'no';break;
        case '1': $event_type = 'yes';break;
    }

	$ct_data = ct_get_data();
	$current_date=date('d M');
	
	//User counter
	if(!isset($ct_data['user_counter'])){
		$ct_data['user_counter']['accepted']=0;
		$ct_data['user_counter']['blocked']=0;
		$ct_data['user_counter']['since']=$current_date;
	}
	
	//Add 1 to counters
	if($event_type=='yes'){
		@$ct_data['user_counter']['accepted']++;
	}
	if($event_type=='no'){
		@$ct_data['user_counter']['blocked']++;
	}

	update_option('cleantalk_data', $ct_data);
	$cleantalk_executed=true;
}

/**
 * return new cookie value
 */
function ct_get_cookie()
{
	global $ct_checkjs_def;
	$ct_checkjs_key = ct_get_checkjs_value(true); 
	print $ct_checkjs_key;
	die();
}

/**
 * adds nocache script
 */
function ct_add_nocache_script()
{
	ob_start('ct_inject_nocache_script');
}

function ct_add_nocache_script_footer()
{
	if(strpos($_SERVER['REQUEST_URI'],'jm-ajax')===false)
	{
		global $test_external_forms, $cleantalk_plugin_version;
		print "<script async type='text/javascript' src='".plugins_url( '/inc/cleantalk_nocache.js' , __FILE__ )."?random=".$cleantalk_plugin_version."'></script>\n";
		if($test_external_forms)
		{
			print "\n<script type='text/javascript'>var ct_blog_home = '".get_home_url()."';</script>\n";
			print "<script async type='text/javascript' src='".plugins_url( '/inc/cleantalk_external.js' , __FILE__ )."?random=".$cleantalk_plugin_version."'></script>\n";
		}
		//print "<script async type='text/javascript' src='".plugins_url( '/inc/cleantalk-info.js' , __FILE__ )."?random=".$cleantalk_plugin_version."'></script>\n";
	}
}

/**
*   Function prepares values to manage JavaScript code  
*   @return string 
*/
function ct_set_info_flag () {
    global $ct_options;
    
    $ct_options=ct_get_options();

    $result = 'false';
    if(@intval($ct_options['collect_details'])==1
        && @intval($ct_options['set_cookies']) == 1
        ) {
        $result = 'true';
    }
    
	$ct_info_flag = "var ct_info_flag=$result;\n";

    $result = 'true';
    if (@intval($ct_options['set_cookies']) == 0) {
        $result = 'false';
    }
	
    $ct_set_cookies_flag = "var ct_set_cookies_flag=$result;\n";

    return $ct_info_flag . $ct_set_cookies_flag;
}

function ct_add_nocache_script_header()
{
	if(strpos($_SERVER['REQUEST_URI'],'jm-ajax')===false)
	{
        $ct_info_flag = ct_set_info_flag();
		print "\n<script type='text/javascript'>\nvar ct_ajaxurl = '".admin_url('admin-ajax.php')."';\n $ct_info_flag </script>\n";
	}
}

function ct_inject_nocache_script($html)
{
	if(strpos($_SERVER['REQUEST_URI'],'jm-ajax')===false)
	{
		global $test_external_forms, $cleantalk_plugin_version, $ct_options;
        
        $ct_info_flag = ct_set_info_flag();
		
        if(!is_admin()&&stripos($html,"</body")!==false)
		{
			//$ct_replace.="\n<script type='text/javascript'>var ajaxurl = '".admin_url('admin-ajax.php')."';\n  $ct_info_flag </script>\n";
			$ct_replace="<script async type='text/javascript' src='".plugins_url( '/inc/cleantalk_nocache.js' , __FILE__ )."?random=".$cleantalk_plugin_version."'></script>\n";
			if($test_external_forms)
			{
				$ct_replace.="\n<script type='text/javascript'>var ct_blog_home = '".get_home_url()."';</script>\n";
				$ct_replace.="<script async type='text/javascript' src='".plugins_url( '/inc/cleantalk_external.js' , __FILE__ )."?random=".$cleantalk_plugin_version."'></script>\n";
			}
			
			//$html=str_ireplace("</body",$ct_replace."</body",$html);
			$html=substr_replace($html,$ct_replace."</body",strripos($html,"</body"),6);
		}
	}
	return $html;
}
if(is_admin())
{
	require_once(CLEANTALK_PLUGIN_DIR . 'inc/cleantalk-comments.php');
	require_once(CLEANTALK_PLUGIN_DIR . 'inc/cleantalk-users.php');
}
if(isset($_GET['ait-action'])&&$_GET['ait-action']=='register')
{
	$tmp=$_POST['redirect_to'];
	unset($_POST['redirect_to']);
	ct_contact_form_validate();
	$_POST['redirect_to']=$tmp;
}

function ct_show_comment_link()
{
	print "<div style='font-size:10pt;'><a href='https://cleantalk.org/wordpress-anti-spam-plugin' target='_blank'>".__( 'WordPress spam', 'cleantalk' )."</a> ".__( 'blocked by', 'cleantalk' )." CleanTalk.</div>";
}

add_action( 'right_now_content_table_end', 'my_add_counts_to_dashboard' );

function cleantalk_update_sfw()
{
	global $wpdb, $ct_sfw_updated;

	if(!function_exists('sendRawRequest'))
	{
		require_once('inc/cleantalk.class.php');
	}
	global $ct_options, $ct_data;
	if(isset($ct_options['spam_firewall']))
    {
    	$value = @intval($ct_options['spam_firewall']);
    }
    else
    {
    	$value=0;
    }

    if($value==1 && $ct_sfw_updated === false)
    {
		$data = Array(	'auth_key' => $ct_options['apikey'],
						'method_name' => '2s_blacklists_db'
			 	);
		
		$result=sendRawRequest('https://api.cleantalk.org', $data);
		$result=json_decode($result, true);
		if(isset($result['data']))
		{
			$wpdb->query("drop table if exists `".$wpdb->base_prefix."cleantalk_sfw`;");
			$wpdb->query("CREATE TABLE IF NOT EXISTS `".$wpdb->base_prefix."cleantalk_sfw` (
`network` int(11) unsigned NOT NULL,
`mask` int(11) unsigned NOT NULL,
INDEX (  `network` ,  `mask` )
) ENGINE = MYISAM ;");
			$result=$result['data'];
			$query="INSERT INTO `".$wpdb->base_prefix."cleantalk_sfw` VALUES ";
			//$wpdb->query("TRUNCATE TABLE `".$wpdb->base_prefix."cleantalk_sfw`;");
			for($i=0;$i<sizeof($result);$i++)
			{
				if($i==sizeof($result)-1)
				{
					$query.="(".$result[$i][0].",".$result[$i][1].");";
				}
				else
				{
					$query.="(".$result[$i][0].",".$result[$i][1]."), ";
				}
			}
			$wpdb->query($query);
            $ct_sfw_updated = true;    
		}
	}
}

function cleantalk_get_ip()
{
	$result=Array();
	if ( function_exists( 'apache_request_headers' ) )
	{
		$headers = apache_request_headers();
	}
	else
	{
		$headers = $_SERVER;
	}
	if ( array_key_exists( 'X-Forwarded-For', $headers ) )
	{
		$the_ip=explode(",", trim($headers['X-Forwarded-For']));
		$result[] = trim($the_ip[0]);
	}
	if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ))
	{
		$the_ip=explode(",", trim($headers['HTTP_X_FORWARDED_FOR']));
		$result[] = trim($the_ip[0]);
	}
	$result[] = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );

	if(isset($_GET['sfw_test_ip']))
	{
		$result[]=$_GET['sfw_test_ip'];
	}
	return $result;
}

function ct_send_sfw_log()
{
    include_once("inc/cleantalk-sfw.class.php");
    $sfw = new CleanTalkSFW();
    $sfw->send_logs();
}
?>
