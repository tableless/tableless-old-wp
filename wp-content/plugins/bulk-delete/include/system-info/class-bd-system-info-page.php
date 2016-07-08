<?php
/**
 * Display System Info.
 *
 * Also provide an option to download system info for using in support requests.
 *
 * @since       5.5.4
 * @note        Based on the code from Easy Digital Downloads plugin
 * @author		Sudar
 * @package     BulkDelete\Admin
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Encapsulates System info.
 *
 * @since 5.5.4
 */
class BD_System_Info_page extends BD_Base_Page {

	/**
	 * Make this class a "hybrid Singleton".
	 *
	 * @static
	 * @since 5.5.4
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5.4
	 */
	protected function initialize() {
		$this->page_slug = 'bulk-delete-info';
		$this->menu_action = 'bd_after_all_menus';
		$this->actions = array( 'download_sysinfo' );

		$this->label = array(
			'page_title' => __( 'Bulk Delete - System Info', 'bulk-delete' ),
			'menu_title' => __( 'System Info', 'bulk-delete' ),
		);

		$this->messages = array(
			'info_message' => __( 'Please include this information when posting support requests.', 'bulk-delete' ),
		);

		add_action( 'bd_download_sysinfo', array( $this, 'generate_sysinfo_download' ) );
	}

	/**
	 * Render header.
	 *
	 * @since 5.5.4
	 */
	protected function render_header() {
?>
		<div class="updated">
			<p><strong><?php echo $this->messages['info_message']; ?></strong></p>
		</div>

		<?php if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) { ?>
			<div class="notice notice-warning">
				<p><strong>
					<?php printf( __( 'SAVEQUERIES is <a href="%s" target="_blank">enabled</a>. This puts additional load on the memory and will restrict the number of items that can be deleted.', 'bulk-delete' ), 'https://codex.wordpress.org/Editing_wp-config.php#Save_queries_for_analysis' ); ?>
				</strong></p>
			</div>
		<?php } ?>

		<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) { ?>
			<div class="notice notice-warning">
				<p><strong>
					<?php printf( __( 'DISABLE_WP_CRON is <a href="%s" target="_blank">enabled</a>. This prevents scheduler from running.', 'bulk-delete' ), 'https://codex.wordpress.org/Editing_wp-config.php#Disable_Cron_and_Cron_Timeout' ); ?>
				</strong></p>
			</div>
		<?php } ?>

		<?php bd_render_sidebar_iframe(); ?>
<?php
	}

	/**
	 * Shows the system info panel which contains version data and debug info.
	 *
	 * @since 5.5.4
	 * @global $wpdb Global object $wpdb Used to query the database using the WordPress Database API
	 */
	protected function render_body() {
		global $wpdb;
?>
		<textarea wrap="off" style="width:100%;height:500px;font-family:Menlo,Monaco,monospace;white-space:pre;" readonly="readonly" onclick="this.focus();this.select()" id="system-info-textarea" name="bulk-delete-sysinfo" title="<?php _e( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'bulk-delete' ); ?>">
### Begin System Info ###
<?php
		/**
		 * Runs before displaying system info.
		 *
		 * This action is primarily for adding extra content in System Info.
		 */
		do_action( 'bd_system_info_before' );
?>

Multisite:                <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>

SITE_URL:                 <?php echo site_url() . "\n"; ?>
HOME_URL:                 <?php echo home_url() . "\n"; ?>
Browser:                  <?php echo esc_html( $_SERVER['HTTP_USER_AGENT'] ), "\n"; ?>

Permalink Structure:      <?php echo get_option( 'permalink_structure' ) . "\n"; ?>
Active Theme:             <?php echo bd_get_current_theme_name() . "\n"; ?>
<?php
		$host = bd_identify_host();
		if ( '' !== $host ) : ?>
Host:                     <?php echo $host . "\n\n"; ?>
<?php
		endif;

		$post_types = get_post_types();
?>
Registered Post types:    <?php echo implode( ', ', $post_types ) . "\n"; ?>
<?php
		foreach ( $post_types as $post_type ) {
			echo $post_type;
			if ( strlen( $post_type ) < 26 ) {
				echo str_repeat( ' ', 26 - strlen( $post_type ) );
			}
			$post_count = wp_count_posts( $post_type );
			foreach ( $post_count as $key => $value ) {
				echo $key, '=', $value, ', ';
			}
			echo "\n";
		}
?>

Bulk Delete Version:      <?php echo Bulk_Delete::VERSION . "\n"; ?>
WordPress Version:        <?php echo get_bloginfo( 'version' ) . "\n"; ?>
PHP Version:              <?php echo PHP_VERSION . "\n"; ?>
MySQL Version:            <?php echo $wpdb->db_version() . "\n"; ?>
Web Server Info:          <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>

WordPress Memory Limit:   <?php echo WP_MEMORY_LIMIT; ?><?php echo "\n"; ?>
WordPress Max Limit:      <?php echo WP_MAX_MEMORY_LIMIT; ?><?php echo "\n"; ?>
PHP Memory Limit:         <?php echo ini_get( 'memory_limit' ) . "\n"; ?>

SAVEQUERIES:              <?php echo defined( 'SAVEQUERIES' ) ? SAVEQUERIES ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>
WP_DEBUG:                 <?php echo defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>
WP_SCRIPT_DEBUG:          <?php echo defined( 'WP_SCRIPT_DEBUG' ) ? WP_SCRIPT_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>

GMT Offset:               <?php echo esc_html( get_option( 'gmt_offset' ) ), "\n\n"; ?>
DISABLE_WP_CRON:          <?php echo defined( 'DISABLE_WP_CRON' ) ? DISABLE_WP_CRON ? 'Yes' . "\n" : 'No' . "\n" : 'Not set' . "\n" ?>
WP_CRON_LOCK_TIMEOUT:     <?php echo defined( 'WP_CRON_LOCK_TIMEOUT' ) ? WP_CRON_LOCK_TIMEOUT : 'Not set', "\n" ?>
EMPTY_TRASH_DAYS:         <?php echo defined( 'EMPTY_TRASH_DAYS' ) ? EMPTY_TRASH_DAYS : 'Not set', "\n" ?>

PHP Safe Mode:            <?php echo ini_get( 'safe_mode' ) ? 'Yes' : 'No', "\n"; ?>
PHP Upload Max Size:      <?php echo ini_get( 'upload_max_filesize' ) . "\n"; ?>
PHP Post Max Size:        <?php echo ini_get( 'post_max_size' ) . "\n"; ?>
PHP Upload Max Filesize:  <?php echo ini_get( 'upload_max_filesize' ) . "\n"; ?>
PHP Time Limit:           <?php echo ini_get( 'max_execution_time' ) . "\n"; ?>
PHP Max Input Vars:       <?php echo ini_get( 'max_input_vars' ) . "\n"; ?>
PHP Arg Separator:        <?php echo ini_get( 'arg_separator.output' ) . "\n"; ?>
PHP Allow URL File Open:  <?php echo ini_get( 'allow_url_fopen' ) ? 'Yes' : 'No', "\n"; ?>

WP Table Prefix:          <?php echo $wpdb->prefix, "\n";?>

Session:                  <?php echo isset( $_SESSION ) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
Session Name:             <?php echo esc_html( ini_get( 'session.name' ) ); ?><?php echo "\n"; ?>
Cookie Path:              <?php echo esc_html( ini_get( 'session.cookie_path' ) ); ?><?php echo "\n"; ?>
Save Path:                <?php echo esc_html( ini_get( 'session.save_path' ) ); ?><?php echo "\n"; ?>
Use Cookies:              <?php echo ini_get( 'session.use_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>
Use Only Cookies:         <?php echo ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off'; ?><?php echo "\n"; ?>

DISPLAY ERRORS:           <?php echo ( ini_get( 'display_errors' ) ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A'; ?><?php echo "\n"; ?>
FSOCKOPEN:                <?php echo ( function_exists( 'fsockopen' ) ) ? 'Your server supports fsockopen.' : 'Your server does not support fsockopen.'; ?><?php echo "\n"; ?>
cURL:                     <?php echo ( function_exists( 'curl_init' ) ) ? 'Your server supports cURL.' : 'Your server does not support cURL.'; ?><?php echo "\n"; ?>
SOAP Client:              <?php echo ( class_exists( 'SoapClient' ) ) ? 'Your server has the SOAP Client enabled.' : 'Your server does not have the SOAP Client enabled.'; ?><?php echo "\n"; ?>
SUHOSIN:                  <?php echo ( extension_loaded( 'suhosin' ) ) ? 'Your server has SUHOSIN installed.' : 'Your server does not have SUHOSIN installed.'; ?><?php echo "\n"; ?>

ACTIVE PLUGINS:

<?php bd_print_current_plugins(); ?>

<?php
		if ( is_multisite() ) : ?>
NETWORK ACTIVE PLUGINS:

<?php
			bd_print_network_active_plugins();
		endif;
?>

<?php do_action( 'bd_system_info_after' );?>
### End System Info ###</textarea>

		<p class="submit">
			<input type="hidden" name="bd_action" value="download_sysinfo">
			<?php submit_button( 'Download System Info File', 'primary', 'bulk-delete-download-sysinfo', false ); ?>
		</p>
<?php
	}

	/**
	 * Generates the System Info Download File.
	 *
	 * @since 5.0
	 * @return void
	 */
	public function generate_sysinfo_download() {
		nocache_headers();

		header( 'Content-type: text/plain' );
		header( 'Content-Disposition: attachment; filename="bulk-delete-system-info.txt"' );

		echo wp_strip_all_tags( $_POST['bulk-delete-sysinfo'] );
		die();
	}
}

BD_System_Info_page::factory();
