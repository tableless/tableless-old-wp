<?php

$ip=(int)sprintf("%u", ip2long(cleantalk_get_ip()));
$ip_str=cleantalk_get_ip();
//$ip=(int)sprintf("%u", ip2long("2.11.242.8"));
if(isset($_GET['sfw_test_ip']))
{
	$ip=(int)sprintf("%u", ip2long($_GET['sfw_test_ip']));
	$ip_str=$_GET['sfw_test_ip'];
}

global $wpdb;
$r = $wpdb->get_results("select * from `".$wpdb->base_prefix."cleantalk_sfw` where $ip & mask = network & mask;", ARRAY_A);
if(sizeof($r)>0)
{
	global $ct_options, $ct_data;
	$sfw_die_page=file_get_contents(dirname(__FILE__)."/sfw_die_page.html");
	$sfw_die_page=str_replace("{REMOTE_ADDRESS}",$ip_str,$sfw_die_page);
	$sfw_die_page=str_replace("{REQUEST_URI}",$_SERVER['REQUEST_URI'],$sfw_die_page);
	$sfw_die_page=str_replace("{SFW_COOKIE}",md5(cleantalk_get_ip().$ct_options['apikey']),$sfw_die_page);
	if(isset($ct_data['sfw_log']))
	{
		$sfw_log=$ct_data['sfw_log'];
	}
	else
	{
		$sfw_log=array();
	}
	if(isset($sfw_log[$r[0]['network']]))
	{
		$sfw_log[$r[0]['network']]['block']++;
	}
	else
	{
		$sfw_log[$r[0]['network']] = Array('block' => 1, 'allow' => 0);
	}
	$ct_data['sfw_log'] = $sfw_log;
	update_option('cleantalk_data', $ct_data);
	wp_die( $sfw_die_page, "Blacklisted", Array('response'=>403) );
}

?>