<?php
/**
 * Request Handler
 *
 * @since      5.5.4
 * @author     Sudar
 * @package    BulkDelete\Controller
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Bulk Delete Controller.
 *
 * @since 5.5.4
 */
class BD_Controller {
	public function __construct() {
		add_action( 'admin_init', array( $this, 'request_handler' ) );
		add_action( 'bd_pre_bulk_action', array( $this, 'increase_timeout' ), 9 );
		add_action( 'bd_before_scheduler', array( $this, 'increase_timeout' ), 9 );

		add_filter( 'bd_get_action_nonce_check', array( $this, 'verify_get_request_nonce' ), 10, 2 );
	}

	/**
	 * Handle both POST and GET requests.
	 * This method automatically triggers all the actions after checking the nonce.
	 */
	public function request_handler() {
		if ( isset( $_POST['bd_action'] ) ) {
			$bd_action = sanitize_text_field( $_POST['bd_action'] );
			$nonce_valid = false;

			if ( 'delete_pages_' === substr( $bd_action, 0, strlen( 'delete_pages_' ) )
				&& check_admin_referer( 'sm-bulk-delete-pages', 'sm-bulk-delete-pages-nonce' ) ) {
				$nonce_valid = true;
			}

			if ( 'delete_posts_' === substr( $bd_action, 0, strlen( 'delete_posts_' ) )
				&& check_admin_referer( 'sm-bulk-delete-posts', 'sm-bulk-delete-posts-nonce' ) ) {
				$nonce_valid = true;
			}

			if ( 'delete_meta_' === substr( $bd_action, 0, strlen( 'delete_meta_' ) )
				&& check_admin_referer( 'sm-bulk-delete-meta', 'sm-bulk-delete-meta-nonce' ) ) {
				$nonce_valid = true;
			}

			/**
			 * Perform nonce check.
			 *
			 * @since 5.5
			 */
			if ( ! apply_filters( 'bd_action_nonce_check', $nonce_valid, $bd_action ) ) {
				return;
			}

			/**
			 * Before performing a bulk action.
			 * This hook is for doing actions just before performing any bulk operation
			 *
			 * @since 5.4
			 */
			do_action( 'bd_pre_bulk_action', $bd_action );

			/**
			 * Perform the bulk operation.
			 * This hook is for doing the bulk operation. Nonce check has already happened by this point.
			 *
			 * @since 5.4
			 */
			do_action( 'bd_' . $bd_action, $_POST );
		}

		if ( isset( $_GET['bd_action'] ) ) {
			$bd_action = sanitize_text_field( $_GET['bd_action'] );
			$nonce_valid = false;

			/**
			 * Perform nonce check.
			 *
			 * @since 5.5.4
			 */
			if ( ! apply_filters( 'bd_get_action_nonce_check', $nonce_valid, $bd_action ) ) {
				return;
			}

			/**
			 * Perform the bulk operation.
			 * This hook is for doing the bulk operation. Nonce check has already happened by this point.
			 *
			 * @since 5.5.4
			 */
			do_action( 'bd_' . $bd_action, $_GET );
		}
	}

	/**
	 * Verify if GET request has a valid nonce.
	 *
	 * @since  5.5.4
	 * @param  bool   $result Whether nonce is valid.
	 * @param  string $action Action name
	 * @return bool           True if nonce is valid, otherwise return $result.
	 */
	public function verify_get_request_nonce( $result, $action ) {
		if ( check_admin_referer( "bd-{$action}", "bd-{$action}-nonce" ) ) {
			return true;
		}

		return $result;
	}

	/**
	 * Increase PHP timeout.
	 *
	 * This is to prevent bulk operations from timing out
	 *
	 * @since 5.5.4
	 */
	public function increase_timeout() {
		if ( ! ini_get( 'safe_mode' ) ) {
			@set_time_limit( 0 );
		}
	}
}
