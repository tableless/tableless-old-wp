<?php
/*
Plugin Name: Easy WP SMTP
Version: 1.2.0
Plugin URI: https://wp-ecommerce.net/easy-wordpress-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server-2197
Author: wpecommerce
Author URI: https://wp-ecommerce.net/
Description: Send email via SMTP from your WordPress Blog
*/

/**
* Add menu and submenu.
* @return void
*/

if ( ! function_exists( 'swpsmtp_admin_default_setup' ) ) {
	function swpsmtp_admin_default_setup() {		
		//add_submenu_page( 'options-general.php', __( 'Easy WP SMTP', 'easy_wp_smtp' ), __( 'Easy WP SMTP', 'easy_wp_smtp' ), $capabilities, 'swpsmtp_settings', 'swpsmtp_settings' );
                add_options_page(__('Easy WP SMTP', 'easy_wp_smtp'), __('Easy WP SMTP', 'easy_wp_smtp'), 'manage_options', 'swpsmtp_settings', 'swpsmtp_settings');
	}
}

/**
 * Plugin functions for init
 * @return void
 */
if ( ! function_exists ( 'swpsmtp_admin_init' ) ) {
	function swpsmtp_admin_init() {
		/* Internationalization, first(!) */
		load_plugin_textdomain( 'easy_wp_smtp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		if ( isset( $_REQUEST['page'] ) && 'swpsmtp_settings' == $_REQUEST['page'] ) {
			/* register plugin settings */
			swpsmtp_register_settings();
		}
	}
}

/**
 * Register settings function
 * @return void
 */
if ( ! function_exists( 'swpsmtp_register_settings' ) ) {
	function swpsmtp_register_settings() {
		$swpsmtp_options_default = array(
			'from_email_field' 		=> '',
			'from_name_field'   		=> '',
			'smtp_settings'     		=> array( 
				'host'               	=> 'smtp.example.com',
				'type_encryption'	=> 'none',
				'port'              	=> 25,
				'autentication'		=> 'yes',
				'username'		=> 'yourusername',
				'password'          	=> 'yourpassword'
			)
		);

		/* install the default plugin options */
                if ( ! get_option( 'swpsmtp_options' ) ){
                    add_option( 'swpsmtp_options', $swpsmtp_options_default, '', 'yes' );
                }
	}
}


/**
 * Add action links on plugin page in to Plugin Name block
 * @param $links array() action links
 * @param $file  string  relative path to pugin "easy-wp-smtp/easy-wp-smtp.php"
 * @return $links array() action links
 */
if ( ! function_exists ( 'swpsmtp_plugin_action_links' ) ) {
	function swpsmtp_plugin_action_links( $links, $file ) {
		/* Static so we don't call plugin_basename on every plugin row. */
		static $this_plugin;
		if ( ! $this_plugin ) {
			$this_plugin = plugin_basename( __FILE__ );
		}
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="options-general.php?page=swpsmtp_settings">' . __( 'Settings', 'easy_wp_smtp' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

/**
 * Add action links on plugin page in to Plugin Description block
 * @param $links array() action links
 * @param $file  string  relative path to pugin "easy-wp-smtp/easy-wp-smtp.php"
 * @return $links array() action links
 */
if ( ! function_exists ( 'swpsmtp_register_plugin_links' ) ) {
	function swpsmtp_register_plugin_links( $links, $file ) {
		$base = plugin_basename( __FILE__ );
		if ( $file == $base ) {
			$links[] = '<a href="options-general.php?page=swpsmtp_settings">' . __( 'Settings', 'easy_wp_smtp' ) . '</a>';
		}
		return $links;
	}
}


/**
 * Function to add plugin scripts
 * @return void
 */
if ( ! function_exists ( 'swpsmtp_admin_head' ) ) {
	function swpsmtp_admin_head() {
		wp_enqueue_style( 'swpsmtp_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );

		if ( isset( $_REQUEST['page'] ) && 'swpsmtp_settings' == $_REQUEST['page'] ) {
			wp_enqueue_script( 'swpsmtp_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ) );
		}
	}
}

/**
 * Function to add smtp options in the phpmailer_init
 * @return void
 */
if ( ! function_exists ( 'swpsmtp_init_smtp' ) ) {
	function swpsmtp_init_smtp( $phpmailer ) {              
		$swpsmtp_options = get_option( 'swpsmtp_options' );
		/* Set the mailer type as per config above, this overrides the already called isMail method */
		$phpmailer->IsSMTP();
		$from_email = $swpsmtp_options['from_email_field'];
                $phpmailer->From = $from_email;
                $from_name  = $swpsmtp_options['from_name_field'];
                $phpmailer->FromName = $from_name;
                $phpmailer->SetFrom($phpmailer->From, $phpmailer->FromName);
		/* Set the SMTPSecure value */
		if ( $swpsmtp_options['smtp_settings']['type_encryption'] !== 'none' ) {
			$phpmailer->SMTPSecure = $swpsmtp_options['smtp_settings']['type_encryption'];
		}
		
		/* Set the other options */
		$phpmailer->Host = $swpsmtp_options['smtp_settings']['host'];
		$phpmailer->Port = $swpsmtp_options['smtp_settings']['port']; 

		/* If we're using smtp auth, set the username & password */
		if( 'yes' == $swpsmtp_options['smtp_settings']['autentication'] ){
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $swpsmtp_options['smtp_settings']['username'];
			$phpmailer->Password = swpsmtp_get_password();
		}
	}
}

/**
 * View function the settings to send messages.
 * @return void
 */
if ( ! function_exists( 'swpsmtp_settings' ) ) {
	function swpsmtp_settings() {
		$display_add_options = $message = $error = $result = '';

		$swpsmtp_options = get_option( 'swpsmtp_options' );
                
		if ( isset( $_POST['swpsmtp_form_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'swpsmtp_nonce_name' ) ) {	
			/* Update settings */
			$swpsmtp_options['from_name_field'] = isset( $_POST['swpsmtp_from_name'] ) ? sanitize_text_field(wp_unslash($_POST['swpsmtp_from_name'])) : '';
			if( isset( $_POST['swpsmtp_from_email'] ) ){
				if( is_email( $_POST['swpsmtp_from_email'] ) ){
					$swpsmtp_options['from_email_field'] = $_POST['swpsmtp_from_email'];
				}
				else{
					$error .= " " . __( "Please enter a valid email address in the 'FROM' field.", 'easy_wp_smtp' );
				}
			}
					
			$swpsmtp_options['smtp_settings']['host']     				= sanitize_text_field($_POST['swpsmtp_smtp_host']);
			$swpsmtp_options['smtp_settings']['type_encryption'] = ( isset( $_POST['swpsmtp_smtp_type_encryption'] ) ) ? $_POST['swpsmtp_smtp_type_encryption'] : 'none' ;
			$swpsmtp_options['smtp_settings']['autentication']   = ( isset( $_POST['swpsmtp_smtp_autentication'] ) ) ? $_POST['swpsmtp_smtp_autentication'] : 'yes' ;
			$swpsmtp_options['smtp_settings']['username']  			= sanitize_text_field($_POST['swpsmtp_smtp_username']);
                        $smtp_password = trim($_POST['swpsmtp_smtp_password']);
			$swpsmtp_options['smtp_settings']['password'] 				= base64_encode($smtp_password);

			/* Check value from "SMTP port" option */
			if ( isset( $_POST['swpsmtp_smtp_port'] ) ) {
				if ( empty( $_POST['swpsmtp_smtp_port'] ) || 1 > intval( $_POST['swpsmtp_smtp_port'] ) || ( ! preg_match( '/^\d+$/', $_POST['swpsmtp_smtp_port'] ) ) ) {
					$swpsmtp_options['smtp_settings']['port'] = '25';
					$error .= " " . __( "Please enter a valid port in the 'SMTP Port' field.", 'easy_wp_smtp' );
				} else {
					$swpsmtp_options['smtp_settings']['port'] = $_POST['swpsmtp_smtp_port'];
				}
			}

			/* Update settings in the database */
			if ( empty( $error ) ) {
				update_option( 'swpsmtp_options', $swpsmtp_options );
				$message .= __( "Settings saved.", 'easy_wp_smtp' );	
			}
			else{
				$error .= " " . __( "Settings are not saved.", 'easy_wp_smtp' );
			}
		} 
		
		/* Send test letter */
		if ( isset( $_POST['swpsmtp_test_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'swpsmtp_nonce_name' ) ) {	
			if( isset( $_POST['swpsmtp_to'] ) ){
				if( is_email( $_POST['swpsmtp_to'] ) ){
					$swpsmtp_to =$_POST['swpsmtp_to'];
				}
				else{
					$error .= " " . __( "Please enter a valid email address in the 'FROM' field.", 'easy_wp_smtp' );
				}
			}
			$swpsmtp_subject = isset( $_POST['swpsmtp_subject'] ) ? $_POST['swpsmtp_subject'] : '';
			$swpsmtp_message = isset( $_POST['swpsmtp_message'] ) ? $_POST['swpsmtp_message'] : '';
			if( ! empty( $swpsmtp_to ) )
				$result = swpsmtp_test_mail( $swpsmtp_to, $swpsmtp_subject, $swpsmtp_message );
		} ?>
		<div class="swpsmtp-mail wrap" id="swpsmtp-mail">
			<div id="icon-options-general" class="icon32 icon32-bws"></div>
			<h2><?php _e( "Easy WP SMTP Settings", 'easy_wp_smtp' ); ?></h2>
                        <div class="update-nag">Please visit the <a target="_blank" href="https://wp-ecommerce.net/easy-wordpress-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server-2197">Easy WP SMTP</a> documentation page for usage instructions.</div>
			<div class="updated fade" <?php if( empty( $message ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $message; ?></strong></p>
			</div>
			<div class="error" <?php if ( empty( $error ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $error; ?></strong></p>
			</div>
			<div id="swpsmtp-settings-notice" class="updated fade" style="display:none">
				<p><strong><?php _e( "Notice:", 'easy_wp_smtp' ); ?></strong> <?php _e( "The plugin's settings have been changed. In order to save them please don't forget to click the 'Save Changes' button.", 'easy_wp_smtp' ); ?></p>
			</div>
			<h3><?php _e( 'General Settings', 'easy_wp_smtp' ); ?></h3>
			<form id="swpsmtp_settings_form" method="post" action="">					
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( "From Email Address", 'easy_wp_smtp' ); ?></th>
						<td>
							<input type="text" name="swpsmtp_from_email" value="<?php echo esc_attr( $swpsmtp_options['from_email_field'] ); ?>"/><br />
							<span class="swpsmtp_info"><?php _e( "This email address will be used in the 'From' field.", 'easy_wp_smtp' ); ?></span>
					</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( "From Name", 'easy_wp_smtp' ); ?></th>
						<td>
							<input type="text" name="swpsmtp_from_name" value="<?php echo esc_attr($swpsmtp_options['from_name_field']); ?>"/><br />
							<span  class="swpsmtp_info"><?php _e( "This text will be used in the 'FROM' field", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>			
					<tr class="ad_opt swpsmtp_smtp_options">
						<th><?php _e( 'SMTP Host', 'easy_wp_smtp' ); ?></th>
						<td>
							<input type='text' name='swpsmtp_smtp_host' value='<?php echo esc_attr($swpsmtp_options['smtp_settings']['host']); ?>' /><br />
							<span class="swpsmtp_info"><?php _e( "Your mail server", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>
					<tr class="ad_opt swpsmtp_smtp_options">
						<th><?php _e( 'Type of Encription', 'easy_wp_smtp' ); ?></th>
						<td>
							<label for="swpsmtp_smtp_type_encryption_1"><input type="radio" id="swpsmtp_smtp_type_encryption_1" name="swpsmtp_smtp_type_encryption" value='none' <?php if( 'none' == $swpsmtp_options['smtp_settings']['type_encryption'] ) echo 'checked="checked"'; ?> /> <?php _e( 'None', 'easy_wp_smtp' ); ?></label>
							<label for="swpsmtp_smtp_type_encryption_2"><input type="radio" id="swpsmtp_smtp_type_encryption_2" name="swpsmtp_smtp_type_encryption" value='ssl' <?php if( 'ssl' == $swpsmtp_options['smtp_settings']['type_encryption'] ) echo 'checked="checked"'; ?> /> <?php _e( 'SSL', 'easy_wp_smtp' ); ?></label>
							<label for="swpsmtp_smtp_type_encryption_3"><input type="radio" id="swpsmtp_smtp_type_encryption_3" name="swpsmtp_smtp_type_encryption" value='tls' <?php if( 'tls' == $swpsmtp_options['smtp_settings']['type_encryption'] ) echo 'checked="checked"'; ?> /> <?php _e( 'TLS', 'easy_wp_smtp' ); ?></label><br />
							<span class="swpsmtp_info"><?php _e( "For most servers SSL is the recommended option", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>
					<tr class="ad_opt swpsmtp_smtp_options">
						<th><?php _e( 'SMTP Port', 'easy_wp_smtp' ); ?></th>
						<td>
							<input type='text' name='swpsmtp_smtp_port' value='<?php echo esc_attr($swpsmtp_options['smtp_settings']['port']); ?>' /><br />
							<span class="swpsmtp_info"><?php _e( "The port to your mail server", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>
					<tr class="ad_opt swpsmtp_smtp_options">
						<th><?php _e( 'SMTP Authentication', 'easy_wp_smtp' ); ?></th>
						<td>
							<label for="swpsmtp_smtp_autentication"><input type="radio" id="swpsmtp_smtp_autentication" name="swpsmtp_smtp_autentication" value='no' <?php if( 'no' == $swpsmtp_options['smtp_settings']['autentication'] ) echo 'checked="checked"'; ?> /> <?php _e( 'No', 'easy_wp_smtp' ); ?></label>
							<label for="swpsmtp_smtp_autentication"><input type="radio" id="swpsmtp_smtp_autentication" name="swpsmtp_smtp_autentication" value='yes' <?php if( 'yes' == $swpsmtp_options['smtp_settings']['autentication'] ) echo 'checked="checked"'; ?> /> <?php _e( 'Yes', 'easy_wp_smtp' ); ?></label><br />
							<span class="swpsmtp_info"><?php _e( "This options should always be checked 'Yes'", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>
					<tr class="ad_opt swpsmtp_smtp_options">
						<th><?php _e( 'SMTP username', 'easy_wp_smtp' ); ?></th>
						<td>
							<input type='text' name='swpsmtp_smtp_username' value='<?php echo esc_attr($swpsmtp_options['smtp_settings']['username']); ?>' /><br />
							<span class="swpsmtp_info"><?php _e( "The username to login to your mail server", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>
					<tr class="ad_opt swpsmtp_smtp_options">
						<th><?php _e( 'SMTP Password', 'easy_wp_smtp' ); ?></th>
						<td>
							<input type='password' name='swpsmtp_smtp_password' value='<?php echo swpsmtp_get_password(); ?>' /><br />
							<span class="swpsmtp_info"><?php _e( "The password to login to your mail server", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" id="settings-form-submit" class="button-primary" value="<?php _e( 'Save Changes', 'easy_wp_smtp' ) ?>" />
					<input type="hidden" name="swpsmtp_form_submit" value="submit" />
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'swpsmtp_nonce_name' ); ?>
				</p>				
			</form>
			
			<div class="updated fade" <?php if( empty( $result ) ) echo "style=\"display:none\""; ?>>
				<p><strong><?php echo $result; ?></strong></p>
			</div>
			<h3><?php _e( 'Testing And Debugging Settings', 'easy_wp_smtp' ); ?></h3>
			<form id="swpsmtp_settings_form" method="post" action="">					
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( "To", 'easy_wp_smtp' ); ?>:</th>
						<td>
							<input type="text" name="swpsmtp_to" value=""/><br />
							<span class="swpsmtp_info"><?php _e( "Enter the email address to recipient", 'easy_wp_smtp' ); ?></span>
					</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( "Subject", 'easy_wp_smtp' ); ?>:</th>
						<td>
							<input type="text" name="swpsmtp_subject" value=""/><br />
							<span  class="swpsmtp_info"><?php _e( "Enter a subject for your message", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( "Message", 'easy_wp_smtp' ); ?>:</th>
						<td>
							<textarea name="swpsmtp_message" id="swpsmtp_message" rows="5"></textarea><br />
							<span  class="swpsmtp_info"><?php _e( "Write your message", 'easy_wp_smtp' ); ?></span>
						</td>
					</tr>				
				</table>
				<p class="submit">
					<input type="submit" id="settings-form-submit" class="button-primary" value="<?php _e( 'Send Test Email', 'easy_wp_smtp' ) ?>" />
					<input type="hidden" name="swpsmtp_test_submit" value="submit" />
					<?php wp_nonce_field( plugin_basename( __FILE__ ), 'swpsmtp_nonce_name' ); ?>
				</p>				
			</form>
		</div><!--  #swpsmtp-mail .swpsmtp-mail -->
	<?php }
}
	
/**
 * Function to test mail sending
 * @return text or errors
 */
if ( ! function_exists( 'swpsmtp_test_mail' ) ) {
	function swpsmtp_test_mail( $to_email, $subject, $message ) {
		$errors = '';

		$swpsmtp_options = get_option( 'swpsmtp_options' );

		require_once( ABSPATH . WPINC . '/class-phpmailer.php' );
		$mail = new PHPMailer();
                
                $charset = get_bloginfo( 'charset' );
		$mail->CharSet = $charset;
                
		$from_name  = $swpsmtp_options['from_name_field'];
		$from_email = $swpsmtp_options['from_email_field']; 
		
		$mail->IsSMTP();
		
		/* If using smtp auth, set the username & password */
		if( 'yes' == $swpsmtp_options['smtp_settings']['autentication'] ){
			$mail->SMTPAuth = true;
			$mail->Username = $swpsmtp_options['smtp_settings']['username'];
			$mail->Password = swpsmtp_get_password();
		}
		
		/* Set the SMTPSecure value, if set to none, leave this blank */
		if ( $swpsmtp_options['smtp_settings']['type_encryption'] !== 'none' ) {
			$mail->SMTPSecure = $swpsmtp_options['smtp_settings']['type_encryption'];
		}
		
		/* Set the other options */
		$mail->Host = $swpsmtp_options['smtp_settings']['host'];
		$mail->Port = $swpsmtp_options['smtp_settings']['port']; 
		$mail->SetFrom( $from_email, $from_name );
		$mail->isHTML( true );
		$mail->Subject = $subject;
		$mail->MsgHTML( $message );
		$mail->AddAddress( $to_email );
		$mail->SMTPDebug = 0;

		/* Send mail and return result */
		if ( ! $mail->Send() )
			$errors = $mail->ErrorInfo;
		
		$mail->ClearAddresses();
		$mail->ClearAllRecipients();
			
		if ( ! empty( $errors ) ) {
			return $errors;
		}
		else{
			return 'Test mail was sent';
		}
	}
}

/**
 * Performed at uninstal.
 * @return void
 */
if ( ! function_exists( 'swpsmtp_send_uninstall' ) ) {
	function swpsmtp_send_uninstall() {
		/* delete plugin options */
		delete_site_option( 'swpsmtp_options' );
		delete_option( 'swpsmtp_options' );
	}
}

if ( ! function_exists( 'swpsmtp_get_password' ) ) {
	function swpsmtp_get_password() {
            $swpsmtp_options = get_option( 'swpsmtp_options' );
            $temp_password = $swpsmtp_options['smtp_settings']['password'];
            $password = "";
            $decoded_pass = base64_decode($temp_password);
            /* no additional checks for servers that aren't configured with mbstring enabled */
            if ( ! function_exists( 'mb_detect_encoding' ) ){
                return $decoded_pass;
            }
            /* end of mbstring check */
            if (base64_encode($decoded_pass) === $temp_password) {  //it might be encoded
                if(false === mb_detect_encoding($decoded_pass)){  //could not find character encoding.
                    $password = $temp_password;
                }
                else{
                    $password = base64_decode($temp_password); 
                }               
            }
            else{ //not encoded
                $password = $temp_password;
            }
            return $password;
	}
}


/**
 * Add all hooks
 */

add_filter( 'plugin_action_links', 'swpsmtp_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'swpsmtp_register_plugin_links', 10, 2 );

add_action( 'phpmailer_init','swpsmtp_init_smtp');

add_action( 'admin_menu', 'swpsmtp_admin_default_setup' );

add_action( 'admin_init', 'swpsmtp_admin_init' );
add_action( 'admin_enqueue_scripts', 'swpsmtp_admin_head' );

register_uninstall_hook( plugin_basename( __FILE__ ), 'swpsmtp_send_uninstall' );