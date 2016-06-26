<?php
add_action('admin_menu', 'ct_add_users_menu');

function ct_add_users_menu()
{
	if(current_user_can('activate_plugins'))
	{
		add_users_page( __("Check for spam", 'cleantalk'), __("Check for spam", 'cleantalk'), 'read', 'ct_check_users', 'ct_show_users_page');
	}
}

function ct_show_users_page()
{
    global $ct_plugin_name;
	?>
	<div class="wrap">
		<h2><?php echo $ct_plugin_name; ?></h2><br />
		<?php
		global $wpdb;
		$r=$wpdb->get_results("select distinct count($wpdb->users.ID) as cnt from $wpdb->users inner join $wpdb->usermeta on $wpdb->users.ID=$wpdb->usermeta.user_id where $wpdb->usermeta.meta_key='ct_checked' or $wpdb->usermeta.meta_key='ct_hash';");
		$cnt_checked=$r[0]->cnt;
		$r=$wpdb->get_results("select count(ID) as cnt from $wpdb->users;");
		$cnt_all=$r[0]->cnt;
		
		$cnt_unchecked=$cnt_all-$cnt_checked;
		/*$args_spam = array(
			'meta_query' => array(
				Array(
					'key' => 'ct_marked_as_spam',
					'compare' => 'EXISTS'
				)
			)
		);*/
		$r=$wpdb->get_results("select distinct count($wpdb->users.ID) as cnt from $wpdb->users inner join $wpdb->usermeta on $wpdb->users.ID=$wpdb->usermeta.user_id where $wpdb->usermeta.meta_key='ct_marked_as_spam';", ARRAY_A);
$cnt_spam1=$r[0]['cnt'];
?>
		<div id="ct_deleting_message" style="display:none">
			<?php _e("Please wait for a while. CleanTalk is deleting spam users. Users left: ", 'cleantalk'); ?> <span id="cleantalk_users_left">
            <?php echo $cnt_spam1;?>
            </span>
		</div>
		<div id="ct_done_message" <?php if($cnt_unchecked>0) print 'style="display:none"'; ?>>
			<?php //_e("Done. All comments tested via blacklists database, please see result bellow.", 'cleantalk'); 
			?>
		</div>
		<h3 id="ct_checking_users_status" style="text-align:center;width:90%;"></h3>
		<div style="text-align:center;width:100%;display:none;" id="ct_preloader"><img border=0 src="<?php print plugin_dir_url(__FILE__); ?>images/preloader.gif" /></div>
		<div id="ct_working_message" style="margin:auto;padding:3px;width:70%;border:2px dotted gray;display:none;background:#ffff99;margin-top: 1em;">
			<?php _e("Please wait for a while. CleanTalk is checking all users via blacklist database at cleantalk.org. You will have option to delete found spam users after plugin finish.", 'cleantalk'); ?>
		</div>
		<?php
			$page=1;
			if(isset($_GET['spam_page']))
			{
				$page=intval($_GET['spam_page']);
			}
			$args_spam = array(
				'meta_query' => array(
					Array(
						'key' => 'ct_marked_as_spam',
						'value' => '1',
						'compare' => 'NUMERIC'
					)
				),
				'number'=>30,
				'offset'=>($page-1)*30
			);
			
			$c_spam=get_users($args_spam);
			if($cnt_spam1>0)
			{
		?>
		<table class="widefat fixed comments" id="ct_check_users_table">
			<thead>
				<th scope="col" id="cb" class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
					<input id="cb-select-all-1" type="checkbox"/>
				</th>
				<th scope="col" id="author" class="manage-column column-slug"><?php print _e('Username');?></th>
				<th scope="col" id="comment" class="manage-column column-comment"><?php print _x( 'Name', 'column name' );;?></th>
				<th scope="col" id="response" class="manage-column column-comment"><?php print _x( 'E-mail', 'column name' );?></th>
				<th scope="col" id="role" class="manage-column column-response sortable desc"><?php print _x( 'Role', 'column name' );?></th>
				<th scope="col" id="posts" class="manage-column column-response sortable desc"><?php print _x( 'Posts', 'column name' );?></th>
			</thead>
			<tbody id="the-comment-list" data-wp-lists="list:comment">
				<?php
					for($i=0;$i<sizeof($c_spam);$i++)
					{
						?>
						<tr id="comment-<?php print $c_spam[$i]->ID; ?>" class="comment even thread-even depth-1 approved  cleantalk_user" data-id="<?php print $c_spam[$i]->ID; ?>">
						<th scope="row" class="check-column">
							<label class="screen-reader-text" for="cb-select-<?php print $c_spam[$i]->ID; ?>">Select user</label>
							<input id="cb-select-<?php print $c_spam[$i]->ID; ?>" type="checkbox" name="del_comments[]" value="<?php print $c_spam[$i]->comment_ID; ?>"/>
						</th>
						<td class="author column-author" nowrap>
						<strong>
							<?php echo get_avatar( $c_spam[$i]->data->user_email , 32); ?>
							 <?php print $c_spam[$i]->data->user_login; ?>
							</strong>
							<br/>
							<a href="mailto:<?php print $c_spam[$i]->data->user_email; ?>"><?php print $c_spam[$i]->data->user_email; ?></a> <a href="https://cleantalk.org/blacklists/<?php print $c_spam[$i]->data->user_email ; ?>" target="_blank"><img src="https://cleantalk.ru/images/icons/new_window.gif" border="0" style="float:none"/></a>
							<br/>
							<?php
							$user_meta=get_user_meta($c_spam[$i]->ID, 'session_tokens', true);
							if(is_array($user_meta))
							{
								$user_meta=array_values($user_meta);
							}
							$ip='';
							if(@isset($user_meta[0]['ip']))
							{
								$ip=$user_meta[0]['ip'];
								?>
								<a href="user-edit.php?user_id=<?php print $c_spam[$i]->ID ; ?>"><?php print $ip ; ?></a> 
								<a href="https://cleantalk.org/blacklists/<?php print $ip ; ?>" target="_blank"><img src="https://cleantalk.ru/images/icons/new_window.gif" border="0" style="float:none"/></a>
								<?php
							}
								?>
						</td>
						<td class="comment column-comment">
							<div class="submitted-on">
								<?php print $c_spam[$i]->data->display_name; ?>
								<div style="height:16px;">
									<a href="#" class="cleantalk_delete_user_button" id="cleantalk_delete_user_<?php print $c_spam[$i]->ID; ?>" data-id="<?php print $c_spam[$i]->ID; ?>" style="color:#a00;display:none;" onclick="return false;">Delete</a>
								</div>
							</div>
						</td>
						<td class="comment column-comment">
							<?php print $c_spam[$i]->data->user_email; ?>
						</td>
						<td class="comment column-comment">
							<?php
								$info=get_userdata( $c_spam[$i]->ID );
								print implode(', ', $info->roles);
							?>
						</td>
						<td class="comment column-comment">
							<?php
								print count_user_posts($c_spam[$i]->ID);
							?>
						</td>
						</tr>
						<?php
					}
					if($cnt_spam1>30)
					{
				?>
				<tr class="comment even thread-even depth-1 approved">
					<td colspan="4"> 
						<?php
							
							$pages=ceil(intval($cnt_spam1)/30);
							for($i=1;$i<=$pages;$i++)
							{
								if($i==$page)
								{
									print "<a href='users.php?page=ct_check_users&spam_page=$i'><b>$i</b></a> ";
								}
								else
								{
									print "<a href='users.php?page=ct_check_users&spam_page=$i'>$i</a> ";
								}								
							}
						?>
					</td>
				</tr>
				<?php
					}
				?>
			</tbody>
		</table>
        <div id="ct_tools_buttons">
		<button class="button" id="ct_delete_all_users"><?php _e('Delete all users from list'); ?></button> 
		<button class="button" id="ct_delete_checked_users"><?php _e('Delete selected', 'cleantalk'); ?></button>
		<?php
		}
		if($_SERVER['REMOTE_ADDR']=='127.0.0.1')print '<button class="button" id="ct_insert_users">Insert accounts</button><br />';
		?>
        </div>
		<br /><br />
        <table>
            <tr>
                <td>
		            <button class="button" id="ct_check_users_button"><?php _e("Check for spam again", 'cleantalk'); ?></button>
                </td>
                <td style="padding-left: 2em;">
                <div id="ct_info_message" class="wrap"><?php _e("The plugin will check all users against blacklists database and show you senders that have spam activity on other websites. Just click 'Find spam users' to start.", 'cleantalk'); ?>
                </td>
            </tr>
        </table>
		<?php
			if($cnt_spam1>0)
			{
				print "
        <div id=\"ct_search_info\">
        <br />
		There is some differencies between blacklists database and our API mechanisms. Blacklists shows all history of spam activity, but our API (that used in spam checking) used another parameters, too: last day of activity, number of spam attacks during last days etc. This mechanisms help us to reduce number of false positivitie. So, there is nothing strange, if some emails/IPs will be not found by this checking.
        </div>";
			}
		?>
			
	</div>
    
    <div>
		<button class="button" id="ct_stop_deletion" style="display:none;"><?php _e("Stop deletion", 'cleantalk'); ?></button>
    </div>
	<?php
}

add_action('admin_print_footer_scripts','ct_add_users_button');
function ct_add_users_button()
{
    global $cleantalk_plugin_version;

    $screen = get_current_screen();
    $ajax_nonce = wp_create_nonce( "ct_secret_nonce" );
    ?>
    <script>
    	var ajax_nonce='<?php echo $ajax_nonce; ?>';
    	var spambutton_users_text='<?php _e("Find spam users", 'cleantalk'); ?>';
    </script>
    <?php
    if( $screen->id == 'users' ){
        ?>
            <script src="<?php print plugins_url( 'cleantalk-users-editscreen.js?v=' . $cleantalk_plugin_version, __FILE__ ); ?>"></script>
        <?php
    }
    if($screen->id == 'users_page_ct_check_users')
    {
    	?>
            <script src="<?php print plugins_url( 'cleantalk-users-checkspam.js?v=' . $cleantalk_plugin_version, __FILE__ ); ?>"></script>
        <?php
    }
}


add_action( 'wp_ajax_ajax_check_users', 'ct_ajax_check_users' );

function ct_ajax_check_users()
{
	global $ct_options;

	check_ajax_referer('ct_secret_nonce', 'security');

	$ct_options = ct_get_options();
    
    $skip_roles = array(
        'administrator'
    );

	$args_unchecked = array(
		'meta_query' => array(
			Array(
				'key' => 'ct_checked',
				'value' => '1',
				'compare' => 'NOT EXISTS'
			),
		),
		'number' => 100
	);
	
	$u=get_users($args_unchecked);
    
    if(sizeof($u)>0)
	{
		$data=Array();
		for($i=0;$i<sizeof($u);$i++)
		{
			$user_meta=get_user_meta($u[$i]->ID, 'session_tokens', true);
			if(is_array($user_meta))
			{
				$user_meta=array_values($user_meta);
			}
			if(isset($user_meta[0]['ip']))
			{
				$data[]=$user_meta[0]['ip'];
			    $u[$i]->data->user_ip = $user_meta[0]['ip'];
			} else {
			    $u[$i]->data->user_ip = null;
            }
			$data[]=$u[$i]->data->user_email;
		}
		$data=implode(',',$data);
		
		$request="data=$data";
		
		$opts = array(
		    'http'=>array(
		        'method'=>"POST",
		        'content'=>$request,
		    )
		);
		
		$context = stream_context_create($opts);
	    
        $url = sprintf("https://api.cleantalk.org/?method_name=spam_check&auth_key=%s",
            $ct_options['apikey']
        ); 
		$result = file_get_contents($url, 0, $context);
        
		$result=json_decode($result);
		if(isset($result->error_message))
		{
			print $result->error_message;
		}
		else
		{
			for($i=0;$i<sizeof($u);$i++)
			{
				update_user_meta($u[$i]->ID,'ct_checked',date("Y-m-d H:m:s"),true);
                //
                // Do not display forbidden roles.
                //
                $skip_user = false;
                foreach ($skip_roles as $role) {
                    if (!$skip_user && in_array($role, $u[$i]->roles)) {
					    delete_user_meta($u[$i]->ID, 'ct_marked_as_spam');
                        $skip_user = true;
                        continue;
                    }
                }
                if ($skip_user) {
                    continue;
                }

                $uip = $u[$i]->data->user_ip;
                $uim = $u[$i]->data->user_email;

				if((isset($result->data->$uip) && $result->data->$uip->appears==1) || (isset($result->data->$uim) && $result->data->$uim->appears==1))
				{
					update_user_meta($u[$i]->ID,'ct_marked_as_spam','1',true);
				}
			}
			print 1;
		}
	}
	else
	{
		print 0;
	}

	die;
}

add_action( 'wp_ajax_ajax_info_users', 'ct_ajax_info_users' );
function ct_ajax_info_users()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
    global $wpdb;
		$r=$wpdb->get_results("select distinct count($wpdb->users.ID) as cnt from $wpdb->users inner join $wpdb->usermeta on $wpdb->users.ID=$wpdb->usermeta.user_id where $wpdb->usermeta.meta_key='ct_checked' or $wpdb->usermeta.meta_key='ct_hash';");
		$cnt_checked=$r[0]->cnt;
		$r=$wpdb->get_results("select count(ID) as cnt from $wpdb->users;");
		$cnt=$r[0]->cnt;
		
		$cnt_unchecked=$cnt_all-$cnt_checked;

		$r=$wpdb->get_results("select distinct count($wpdb->users.ID) as cnt from $wpdb->users inner join $wpdb->usermeta on $wpdb->users.ID=$wpdb->usermeta.user_id where $wpdb->usermeta.meta_key='ct_marked_as_spam';", ARRAY_A);
$cnt_spam1=$r[0]['cnt'];
	
	printf (__("Total users %s, checked %s, found %s spam users", 'cleantalk'), $cnt, $cnt_checked, $cnt_spam1);
    $backup_notice = '&nbsp;';
    if ($cnt_spam1 > 0) {
        $backup_notice = __("Please do backup of WordPress database before delete any accounts!", 'cleantalk');
    }
	print "<p>$backup_notice</p>";

	die();
}

add_action( 'wp_ajax_ajax_insert_users', 'ct_ajax_insert_users' );
function ct_ajax_insert_users()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
    
    $inserted = 0;
    $use_id = 0;
	for($i=0; $i<500 ;$i++)
	{
		$rnd=mt_rand(1,10000000);
        
        $user_name = "user_$rnd";
		$email="stop_email_$rnd@example.com";
        
        $user_id = wp_create_user(
            $user_name,
            $email,
            rand()
        );

        if (is_int($user_id)) {
            $inserted++;
        } else {
            error_log(print_r($user_id, true));
        }
	}
	
    print "$inserted";
	die();
}

add_action( 'wp_ajax_ajax_delete_checked_users', 'ct_ajax_delete_checked_users' );
function ct_ajax_delete_checked_users()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	foreach($_POST['ids'] as $key=>$value)
	{
		wp_delete_user($value);
	}
	die();
}

add_action( 'wp_ajax_ajax_delete_all_users', 'ct_ajax_delete_all_users' );
function ct_ajax_delete_all_users()
{
    global $wpdb;
    
    $r=$wpdb->get_results("select distinct $wpdb->users.ID from $wpdb->users inner join $wpdb->usermeta on $wpdb->users.ID=$wpdb->usermeta.user_id where $wpdb->usermeta.meta_key='ct_marked_as_spam';", ARRAY_A);
    $cnt_all = 0; 
    if ($r) {
        $cnt_all = count($r);
    }
    
    $r=$wpdb->get_results("select distinct $wpdb->users.ID from $wpdb->users inner join $wpdb->usermeta on $wpdb->users.ID=$wpdb->usermeta.user_id where $wpdb->usermeta.meta_key='ct_marked_as_spam' limit 50;", ARRAY_A);
    if ($r) {
        for($i = 0; $i < count($r); $i++) {
            wp_delete_user($r[$i]['ID']);
            usleep(5000);
        }
    }
	print $cnt_all;
	die();
}

add_action( 'wp_ajax_ajax_clear_users', 'ct_ajax_clear_users' );
function ct_ajax_clear_users()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	global $wpdb;
	$wpdb->query("delete from $wpdb->usermeta where meta_key='ct_hash' or meta_key='ct_checked' or meta_key='ct_marked_as_spam';");
	die();
}
?>
