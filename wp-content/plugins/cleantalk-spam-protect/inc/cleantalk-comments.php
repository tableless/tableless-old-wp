<?php

add_action('admin_menu', 'ct_add_comments_menu');

function ct_add_comments_menu()
{
	if(current_user_can('activate_plugins'))
	{
		add_comments_page( __("Check for spam", 'cleantalk'), __("Check for spam", 'cleantalk'), 'read', 'ct_check_spam', 'ct_show_checkspam_page');
	}
}

function ct_show_checkspam_page()
{
	?>
	<div class="wrap">
		<h2><?php _e("Anti-spam by CleanTalk", 'cleantalk'); ?></h2><br />
		
		<h3 id="ct_checking_status" style="text-align:center;width:90%;"></h3>
		<div style="text-align:center;width:100%;display:none;" id="ct_preloader"><img border=0 src="<?php print plugin_dir_url(__FILE__); ?>images/preloader.gif" /></div>
		<?php
			$args_spam = array(
				'meta_query' => array(
					Array(
						'key' => 'ct_marked_as_spam',
						'compare' => 'EXISTS'
					)
				),
				'count'=>true
			);
			$cnt_spam=get_comments($args_spam);
			
			
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
			
			$c_spam=get_comments($args_spam);
			if($cnt_spam>0)
			{
		?>
		<table class="widefat fixed comments" id="ct_check_comments_table">
			<thead>
				<th scope="col" id="cb" class="manage-column column-cb check-column">
					<label class="screen-reader-text" for="cb-select-all-1">Select All</label>
					<input id="cb-select-all-1" type="checkbox" style="margin-top:0;"/>
				</th>
				<th scope="col" id="author" class="manage-column column-slug"><?php print _e('Author');?></th>
				<th scope="col" id="comment" class="manage-column column-comment"><?php print _x( 'Comment', 'column name' );;?></th>
				<th scope="col" id="response" class="manage-column column-response sortable desc"><?php print _x( 'In Response To', 'column name' );?></th>
			</thead>
			<tbody id="the-comment-list" data-wp-lists="list:comment">
				<?php
					for($i=0;$i<sizeof($c_spam);$i++)
					{
						?>
						<tr id="comment-<?php print $c_spam[$i]->comment_ID; ?>" class="comment even thread-even depth-1 approved  cleantalk_comment" data-id="<?php print $c_spam[$i]->comment_ID; ?>">
						<th scope="row" class="check-column">
							<label class="screen-reader-text" for="cb-select-<?php print $c_spam[$i]->comment_ID; ?>">Select comment</label>
							<input id="cb-select-<?php print $c_spam[$i]->comment_ID; ?>" type="checkbox" name="del_comments[]" value="<?php print $c_spam[$i]->comment_ID; ?>"/>
						</th>
						<td class="author column-author" nowrap>
						<strong>
							<?php echo get_avatar( $c_spam[$i]->comment_author_email , 32); ?>
							 <?php print $c_spam[$i]->comment_author; ?>
							</strong>
							<br/>
							<a href="mailto:<?php print $c_spam[$i]->comment_author_email; ?>"><?php print $c_spam[$i]->comment_author_email; ?></a> <a href="https://cleantalk.org/blacklists/<?php print $c_spam[$i]->comment_author_email ; ?>" target="_blank"><img src="https://cleantalk.ru/images/icons/new_window.gif" border="0" style="float:none"/></a>
							<br/>
							<a href="edit-comments.php?s=<?php print $c_spam[$i]->comment_author_IP ; ?>&mode=detail"><?php print $c_spam[$i]->comment_author_IP ; ?></a> 
							<a href="https://cleantalk.org/blacklists/<?php print $c_spam[$i]->comment_author_IP ; ?>" target="_blank"><img src="https://cleantalk.ru/images/icons/new_window.gif" border="0" style="float:none"/></a>
						</td>
						<td class="comment column-comment">
							<div class="submitted-on">
								<?php printf( __( 'Submitted on <a href="%1$s">%2$s at %3$s</a>' ), get_comment_link($c_spam[$i]->comment_ID),
									/* translators: comment date format. See http://php.net/date */
									get_comment_date( __( 'Y/m/d' ),$c_spam[$i]->comment_ID ),
									get_comment_date( get_option( 'time_format' ),$c_spam[$i]->comment_ID )
									); 
								?>
									
							</div>
							<p>
							<?php print $c_spam[$i]->comment_content; ?>
							</p>
							<div style="height:16px;">
							<a href="#" class="cleantalk_delete_button" id="cleantalk_delete_<?php print $c_spam[$i]->comment_ID; ?>" data-id="<?php print $c_spam[$i]->comment_ID; ?>" style="color:#a00;display:none;" onclick="return false;">Delete</a>
							</div>
						</td>
						<td class="response column-response">
							<div>
								<span>
									<a href="http://ct_wp/wp-admin/post.php?post=<?php print $c_spam[$i]->comment_post_ID; ?>&action=edit"><?php print get_the_title($c_spam[$i]->comment_post_ID); ?></a>
									<br/>
									<a href="http://ct_wp/wp-admin/edit-comments.php?p=<?php print $c_spam[$i]->comment_post_ID; ?>" class="post-com-count">
										<span class="comment-count"><?php
											$p_cnt=wp_count_comments();
											print $p_cnt->total_comments;
										?></span>
									</a>
								</span>
								<a href="<?php print get_permalink($c_spam[$i]->comment_post_ID); ?>"><?php print _e('View Post');?></a>
							</div>
						</td>
						</tr>
						<?php
					}
					$args_spam = array(
						'meta_query' => array(
							Array(
								'key' => 'ct_marked_as_spam',
								'value' => '1',
								'compare' => 'NUMERIC'
							)
							
						),
						'count'=>true
					);
					$cnt_spam=get_comments($args_spam);
					if($cnt_spam>30)
					{
				?>
				<tr class="comment even thread-even depth-1 approved">
					<td colspan="4"> 
						<?php
							
							$pages=ceil(intval($cnt_spam)/30);
							for($i=1;$i<=$pages;$i++)
							{
								if($i==$page)
								{
									print "<a href='edit-comments.php?page=ct_check_spam&spam_page=$i'><b>$i</b></a> ";
								}
								else
								{
									print "<a href='edit-comments.php?page=ct_check_spam&spam_page=$i'>$i</a> ";
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
		<button class="button" id="ct_delete_all"><?php _e('Delete all comments from the list'); ?></button> 
		<button class="button" id="ct_delete_checked"><?php _e('Delete selected', 'cleantalk'); ?></button><br /><br />
		<?php
		}
		?>
		<?php
		$args_unchecked = array(
			'meta_query' => array(
				'relation' => 'AND',
				Array(
					'key' => 'ct_checked',
					'value' => '1',
					'compare' => 'NOT EXISTS'
				),
				Array(
					'key' => 'ct_hash',
					'value' => '1',
					'compare' => 'NOT EXISTS'
				)
			),
			'count'=>true
		);
		$cnt_unchecked=get_comments($args_unchecked);
		
		$args_spam = array(
			'meta_query' => array(
				Array(
					'key' => 'ct_marked_as_spam',
					'compare' => 'EXISTS'
				)
			),
			'count'=>true
		);
		$cnt_spam=get_comments($args_spam);
		//if($cnt_unchecked>0)
		{
		?>
			<div id="ct_info_message"><?php _e("Anti-spam by CleanTalk checks all not spam comments against blacklists database and show you senders that have spam activity on other websites.", 'cleantalk'); ?>
			<?php
				if($cnt_spam>0)
				{
					print "<br />
			There is some differencies between blacklists database and our API mechanisms. Blacklists shows all history of spam activity, but our API (that used in spam checking) used another parameters, too: last day of activity, number of spam attacks during last days etc. This mechanisms help us to reduce number of false positivitie. So, there is nothing strange, if some emails/IPs will be not found by this checking.";
				}
			?></div>
		<?php
		}
		?>
<?php
		if($_SERVER['REMOTE_ADDR']=='127.0.0.1')print '<button class="button" id="ct_insert_comments">Insert comments</button><br />';
?>

		<div id="ct_working_message" style="margin:auto;padding:3px;width:70%;border:2px dotted gray;display:none;background:#ffff99;">
			<?php _e("Please wait for a while. CleanTalk is checking all approved and pending comments via blacklist database at cleantalk.org. You will have option to delete found spam comments after plugin finish.", 'cleantalk'); ?>
		</div>
		<div id="ct_deleting_message" style="display:none;">
			<?php _e("Please wait for a while. CleanTalk is deleting spam comments. Comments left: ", 'cleantalk'); ?> <span id="cleantalk_comments_left"></span>
		</div>
		<div id="ct_done_message" <?php if($cnt_unchecked>0) print 'style="display:none"'; ?>>
			<?php //_e("Done. All comments tested via blacklists database, please see result bellow.", 'cleantalk'); 
			?>
		</div><br />
		<button class="button" id="ct_check_spam_button"><?php _e("Check for spam again", 'cleantalk'); ?></button><br /><br />
	</div>
	<br /><a href="options-general.php?page=cleantalk">&laquo;<?php print __('Back to CleanTalk settings', 'cleantalk'); ?></a>
	<?php
}

add_action('admin_print_footer_scripts','ct_add_checkspam_button');
function ct_add_checkspam_button()
{
    $screen = get_current_screen();
    $ajax_nonce = wp_create_nonce( "ct_secret_nonce" );
    ?>
    <script>
    	var ajax_nonce='<?php echo $ajax_nonce; ?>';
    	var spambutton_text='<?php _e("Find spam comments", 'cleantalk'); ?>';
    </script>
    <?php
    if( $screen->id == 'edit-comments' ){
        ?>
            <script src="<?php print plugins_url( 'cleantalk-comments-editscreen.js', __FILE__ ); ?>"></script>
        <?php
    }
    if($screen->id == 'comments_page_ct_check_spam')
    {
    	?>
            <script src="<?php print plugins_url( 'cleantalk-comments-checkspam.js', __FILE__ ); ?>"></script>
        <?php
    }
}


add_action( 'wp_ajax_ajax_check_comments', 'ct_ajax_check_comments' );

function ct_ajax_check_comments()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	global $ct_options;
	$ct_options = ct_get_options();
	
	$args_unchecked = array(
		'meta_query' => array(
			//'relation' => 'AND',
			Array(
				'key' => 'ct_checked',
				'value' => '1',
				'compare' => 'NOT EXISTS'
			),
			/*Array(
				'key' => 'ct_hash',
				'value' => '1',
				'compare' => 'NOT EXISTS'
			)*/
		),
		'number'=>500,
		'status' => 'all'
	);
	
	$u=get_comments($args_unchecked);
	$u=array_slice($u,0,500);
	$u=array_values($u);
	if(sizeof($u)>0)
	{
		//print_r($u);
		//die();
		$data=Array();
		for($i=0;$i<sizeof($u);$i++)
		{
			$data[]=$u[$i]->comment_author_IP;
			$data[]=$u[$i]->comment_author_email;
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
		$result = @file_get_contents("https://api.cleantalk.org/?method_name=spam_check&auth_key=".$ct_options['apikey'], 0, $context);
		$result=json_decode($result);
		if(isset($result->error_message))
		{
			print $result->error_message;
		}
		else
		{
			for($i=0;$i<sizeof($u);$i++)
			{
				add_comment_meta($u[$i]->comment_ID,'ct_checked',date("Y-m-d H:m:s"),true);
				$uip=$u[$i]->comment_author_IP;
				if(empty($uip))continue;
				$uim=$u[$i]->comment_author_email;
				if(empty($uim))continue;
				if(isset($result->data->$uip) && $result->data->$uip->appears==1 || isset($result->data->$uim) && $result->data->$uim->appears==1)
				{
					add_comment_meta($u[$i]->comment_ID,'ct_marked_as_spam','1',true);
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

add_action( 'wp_ajax_ajax_info_comments', 'ct_ajax_info_comments' );
function ct_ajax_info_comments()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	$cnt=get_comments(Array('count'=>true));
	
	$args_spam = array(
		'meta_query' => array(
			Array(
				'key' => 'ct_marked_as_spam',
				'value' => '1',
				'compare' => 'NUMERIC'
			)
		),
		'count'=>true
	);
	
	$cnt_spam=get_comments($args_spam);
	
	$args_checked1=array(
		'meta_query' => array(
			Array(
				'key' => 'ct_hash',
				//'value'=>'1',
				'compare' => 'EXISTS'
			)
		),
		'count'=>true
	);
	$args_checked2=array(
		'meta_query' => array(
			Array(
				'key' => 'ct_checked',
				//'value'=>'1',
				'compare' => 'EXISTS'
			)
		),
		'count'=>true
	);
	
	$cnt_checked1=get_comments($args_checked1);
	$cnt_checked2=get_comments($args_checked2);
	$cnt_checked=$cnt_checked1+$cnt_checked2;
	
	printf (__("Total comments %s, checked %s, found %s spam comments.", 'cleantalk'), $cnt, $cnt_checked, $cnt_spam);
	die();
}

add_action( 'wp_ajax_ajax_insert_comments', 'ct_ajax_insert_comments' );
function ct_ajax_insert_comments()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	$time = current_time('mysql');
	
	for($i=0;$i<500;$i++)
	{
		$rnd=mt_rand(1,100);
		if($rnd<20)
		{
			$email="stop_email@example.com";
		}
		else
		{
			$email="stop_email_$rnd@example.com";
		}
		$data = array(
			'comment_post_ID' => 1,
			'comment_author' => "author_$rnd",
			'comment_author_email' => $email,
			'comment_author_url' => 'http://',
			'comment_content' => "comment content ".mt_rand(1,10000)." ".mt_rand(1,10000)." ".mt_rand(1,10000),
			'comment_type' => '',
			'comment_parent' => 0,
			'user_id' => 1,
			'comment_author_IP' => '127.0.0.1',
			'comment_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10 (.NET CLR 3.5.30729)',
			'comment_date' => $time,
			'comment_approved' => 1,
		);
		
		wp_insert_comment($data);
	}
	print "ok";
	die();
}

add_action( 'wp_ajax_ajax_delete_checked', 'ct_ajax_delete_checked' );
function ct_ajax_delete_checked()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	foreach($_POST['ids'] as $key=>$value)
	{
		wp_delete_comment($value, false);
	}
	die();
}

add_action( 'wp_ajax_ajax_delete_all', 'ct_ajax_delete_all' );
function ct_ajax_delete_all()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	$args_spam = array(
		'number'=>100,
		'meta_query' => array(
			Array(
				'key' => 'ct_marked_as_spam',
				'value' => '1',
				'compare' => 'NUMERIC'
			)
		)
	);	
	$c_spam=get_comments($args_spam);
	$cnt=sizeof($c_spam);
	
	$args_spam = array(
		'count'=>true,
		'meta_query' => array(
			Array(
				'key' => 'ct_marked_as_spam',
				'value' => '1',
				'compare' => 'NUMERIC'
			)
		)
	);
	$cnt_all=get_comments($args_spam);
	for($i=0;$i<sizeof($c_spam);$i++)
	{
		wp_delete_comment($c_spam[$i]->comment_ID, false);
		usleep(10000);
	}
	print $cnt_all;
	die();
}

add_action( 'wp_ajax_ajax_clear_comments', 'ct_ajax_clear_comments' );
function ct_ajax_clear_comments()
{
	check_ajax_referer( 'ct_secret_nonce', 'security' );
	global $wpdb;
	$wpdb->query("delete from $wpdb->commentmeta where meta_key='ct_hash' or meta_key='ct_checked' or meta_key='ct_marked_as_spam';");
	die();
}
?>