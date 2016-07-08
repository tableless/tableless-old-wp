<?php
/**
 * Wrapper for EDD API
 *
 * @since      5.0
 * @author     Sudar
 * @package    BulkDelete\License
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class BD_EDD_API_Wrapper {

	/**
	 * Store url
	 *
	 * @since 5.0
	 */
	const STORE_URL = 'http://bulkwp.com';

	/**
	 * Check license
	 *
	 * @since  5.0
	 * @static
	 * @param string  $addon   Addon name
	 * @param string  $license The license code
	 * @return array|false     False if request fails, API response otherwise
	 */
	public static function check_license( $addon, $license ) {
		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => trim( $license ),
			'item_name'  => urlencode( $addon ),
			'url'        => home_url(),
		);

		$license_data = array(
			'license'   => $license,
			'item_name' => $addon,
			'validity'  => 'invalid',
		);

		$response = self::call_edd_api( $api_params );

		if ( $response && isset( $response->license ) ) {
			if ( 'valid' == $response->license ) {
				$license_data['license']    = $license;
				$license_data['validity']   = 'valid';
				$license_data['expires']    = $response->expires;
				$license_data['addon-name'] = $response->item_name;
			} elseif ( 'invalid' == $response->license ) {
				$license_data['validity']   = 'invalid';
			} elseif ( 'site_inactive' == $response->license ) {
				$license_data['validity']   = 'invalid';
			}

			return $license_data;
		}

		return false;
	}

	/**
	 * Activate license
	 *
	 * @since  5.0
	 * @static
	 * @param string  $addon   The addon that needs to be activated
	 * @param string  $license The license code
	 * @return array|false     False if request fails, License info otherwise
	 */
	public static function activate_license( $addon, $license ) {
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => trim( $license ),
			'item_name'  => urlencode( $addon ),
			'url'        => home_url(),
		);

		$response = self::call_edd_api( $api_params );

		if ( $response && isset( $response->success ) ) {
			if ( 'true' == $response->success ) {
				return array(
					'license'    => $license,
					'validity'   => 'valid',
					'expires'    => $response->expires,
					'addon-name' => $response->item_name,
				);
			} else {
				$err_response = array(
					'validity'   => 'invalid',
				);

				if ( isset( $response->error ) ) {
					$err_response['error'] = $response->error;
				}

				return $err_response;
			}
		}

		return false;
	}

	/**
	 * Deactivate License
	 *
	 * @since  5.0
	 * @static
	 * @param string  $addon   The addon that needs to be deactivated
	 * @param string  $license The license code
	 * @return bool            True if deactivated, False otherwise
	 */
	public static function deactivate_license( $addon, $license ) {
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => trim( $license ),
			'item_name'  => urlencode( $addon ),
			'url'        => home_url(),
		);

		$response = self::call_edd_api( $api_params );

		if ( $response && isset( $response->license ) ) {
			if ( 'deactivated' == $response->license ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Call the EDD API
	 *
	 * @since  5.0
	 * @static
	 * @access private
	 * @param  array      $api_params   Parameters for API
	 * @return bool|array $license_data False if request fails, API response otherwise
	 */
	private static function call_edd_api( $api_params ) {
		$response = wp_remote_get( add_query_arg( $api_params, self::STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$license_object = json_decode( wp_remote_retrieve_body( $response ) );
		return $license_object;
	}
}
?>
