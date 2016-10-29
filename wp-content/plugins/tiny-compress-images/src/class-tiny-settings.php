<?php
/*
* Tiny Compress Images - WordPress plugin.
* Copyright (C) 2015-2016 Voormedia B.V.
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the Free
* Software Foundation; either version 2 of the License, or (at your option)
* any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
* FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
* more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc., 51
* Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
class Tiny_Settings extends Tiny_WP_Base {
	const DUMMY_SIZE = '_tiny_dummy';

	private $sizes;
	private $tinify_sizes;
	private $compressor;
	private $notices;

	public function __construct() {
		parent::__construct();
		$this->notices = new Tiny_Notices();
	}

	private function init_compressor() {
		$this->compressor = Tiny_Compress::create(
			$this->get_api_key(),
			$this->get_method( 'after_compress_callback' )
		);
	}

	public function get_absolute_url() {
		return get_admin_url( null, 'options-media.php#' . self::NAME );
	}

	public function xmlrpc_init() {
		try {
			$this->init_compressor();
		} catch (Tiny_Exception $e) {
		}
	}

	public function admin_init() {
		if ( current_user_can( 'manage_options' ) ) {
			if ( ! $this->get_api_key() ) {
				$notice_class = 'error';
				$notice = esc_html__(
					'Please register or provide an API key to start compressing images',
					'tiny-compress-images'
				);
			} else if ( $this->get_api_key_pending() ) {
				$notice_class = 'notice-warning';
				$notice = esc_html__(
					'Please activate your account to start compressing images',
					'tiny-compress-images'
				);
			}

			if ( isset( $notice ) && $notice ) {
				$link = sprintf(
					'<a href="options-media.php#%s">%s</a>', self::NAME, $notice
				);
				$this->notices->show( 'setting', $link, $notice_class, false );
			}

			if ( ! Tiny_PHP::client_supported() ) {
				$details = 'PHP ' . PHP_VERSION;
				if ( extension_loaded( 'curl' ) ) {
					$curlinfo = curl_version();
					$details .= ' with curl ' . $curlinfo['version'];
				} else {
					$details .= ' without curl';
				}
				$message = esc_html__(
					'You are using an outdated platform (' . $details .
					') – some features are disabled', 'tiny-compress-images'
				);
				$this->notices->show( 'deprecated', $message, 'notice-warning', false );
			}
		}

		try {
			$this->init_compressor();
		} catch (Tiny_Exception $e) {
			$this->notices->show(
				'compressor_exception',
				esc_html__( $e->getMessage(), 'tiny-compress-images' ),
				'error', false
			);
		}

		$section = self::get_prefixed_name( 'settings' );
		add_settings_section( $section,
			esc_html__( 'JPEG and PNG optimization', 'tiny-compress-images' ),
			$this->get_method( 'render_section' ),
			'media'
		);

		$field = self::get_prefixed_name( 'api_key' );
		register_setting( 'media', $field );
		add_settings_field( $field,
			esc_html__( 'TinyPNG account', 'tiny-compress-images' ),
			$this->get_method( 'render_pending_status' ),
			'media',
			$section
		);

		$field = self::get_prefixed_name( 'api_key_pending' );
		register_setting( 'media', $field );

		$field = self::get_prefixed_name( 'sizes' );
		register_setting( 'media', $field );
		add_settings_field( $field,
			esc_html__( 'File compression', 'tiny-compress-images' ),
			$this->get_method( 'render_sizes' ),
			'media',
			$section
		);

		$field = self::get_prefixed_name( 'resize_original' );
		register_setting( 'media', $field );
		add_settings_field( $field,
			esc_html__( 'Original image', 'tiny-compress-images' ),
			$this->get_method( 'render_resize' ),
			'media',
			$section
		);

		$field = self::get_prefixed_name( 'preserve_data' );
		register_setting( 'media', $field );

		add_settings_section( 'section_end', '',
			$this->get_method( 'render_section_end' ),
			'media'
		);

		add_action(
			'wp_ajax_tiny_image_sizes_notice',
			$this->get_method( 'image_sizes_notice' )
		);

		add_action(
			'wp_ajax_tiny_account_status',
			$this->get_method( 'account_status' )
		);

		add_action(
			'wp_ajax_tiny_settings_create_api_key',
			$this->get_method( 'create_api_key' )
		);

		add_action(
			'wp_ajax_tiny_settings_update_api_key',
			$this->get_method( 'update_api_key' )
		);
	}

	public function image_sizes_notice() {
		$this->render_image_sizes_notice(
			$_GET['image_sizes_selected'],
			isset( $_GET['resize_original'] ),
			isset( $_GET['compress_wr2x'] )
		);
		exit();
	}

	public function account_status() {
		$this->render_account_status();
		exit();
	}

	public function get_compressor() {
		return $this->compressor;
	}

	public function set_compressor( $compressor ) {
		$this->compressor = $compressor;
	}

	public function get_status() {
		return intval( get_option( self::get_prefixed_name( 'status' ) ) );
	}

	protected function get_api_key() {
		if ( defined( 'TINY_API_KEY' ) ) {
			return TINY_API_KEY;
		} else {
			return get_option( self::get_prefixed_name( 'api_key' ) );
		}
	}

	protected function get_api_key_pending() {
		if ( defined( 'TINY_API_KEY' ) ) {
			return false;
		} else {
			return get_option( self::get_prefixed_name( 'api_key_pending' ) );
		}
	}

	protected function clear_api_key_pending() {
		delete_option( self::get_prefixed_name( 'api_key_pending' ) );
	}

	protected static function get_intermediate_size( $size ) {
		/* Inspired by
		http://codex.wordpress.org/Function_Reference/get_intermediate_image_sizes */
		global $_wp_additional_image_sizes;

		$width  = get_option( $size . '_size_w' );
		$height = get_option( $size . '_size_h' );
		if ( $width && $height ) {
			return array( $width, $height );
		}

		if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
			$sizes = $_wp_additional_image_sizes[ $size ];
			return array(
				isset( $sizes['width'] ) ? $sizes['width'] : null,
				isset( $sizes['height'] ) ? $sizes['height'] : null,
			);
		}
		return array( null, null );
	}

	public function get_sizes() {
		if ( is_array( $this->sizes ) ) {
			return $this->sizes;
		}

		$setting = get_option( self::get_prefixed_name( 'sizes' ) );

		$size = Tiny_Image::ORIGINAL;
		$this->sizes = array(
			$size => array(
				'width' => null,
				'height' => null,
				'tinify' => ! is_array( $setting ) ||
					( isset( $setting[ $size ] ) && 'on' === $setting[ $size ] ),
			),
		);

		foreach ( get_intermediate_image_sizes() as $size ) {
			if ( self::DUMMY_SIZE === $size ) {
				continue;
			}

			list($width, $height) = self::get_intermediate_size( $size );
			if ( $width || $height ) {
				$this->sizes[ $size ] = array(
					'width' => $width,
					'height' => $height,
					'tinify' => ! is_array( $setting ) ||
						( isset( $setting[ $size ] ) && 'on' === $setting[ $size ] ),
				);
			}
		}

		return $this->sizes;
	}

	public function get_active_tinify_sizes() {
		if ( is_array( $this->tinify_sizes ) ) {
			return $this->tinify_sizes;
		}

		$this->tinify_sizes = array();
		foreach ( $this->get_sizes() as $size => $values ) {
			if ( $values['tinify'] ) {
				$this->tinify_sizes[] = $size;
			}
		}
		return $this->tinify_sizes;
	}

	public function get_resize_enabled() {
		/* This only applies if the original is being resized. */
		$sizes = $this->get_sizes();
		if ( ! $sizes[ Tiny_Image::ORIGINAL ]['tinify'] ) {
			return false;
		}

		$setting = get_option( self::get_prefixed_name( 'resize_original' ) );
		return isset( $setting['enabled'] ) && 'on' === $setting['enabled'];
	}

	public function get_preserve_enabled( $name ) {
		$setting = get_option( self::get_prefixed_name( 'preserve_data' ) );
		return isset( $setting[ $name ] ) && 'on' === $setting[ $name ];
	}

	public function get_preserve_options( $size_name ) {
		if ( ! Tiny_Image::is_original( $size_name ) ) {
			return false;
		}
		$options = array();
		$settings = get_option( self::get_prefixed_name( 'preserve_data' ) );
		if ( $settings ) {
			$keys = array_keys( $settings );
			foreach ( $keys as &$key ) {
				if ( 'on' === $settings[ $key ] ) {
					array_push( $options, $key );
				}
			}
		}
		return $options;
	}

	public function get_resize_options( $size_name ) {
		if ( ! Tiny_Image::is_original( $size_name ) ) {
			return false;
		}
		if ( ! $this->get_resize_enabled() ) {
			return false;
		}
		$setting = get_option( self::get_prefixed_name( 'resize_original' ) );
		$width = intval( $setting['width'] );
		$height = intval( $setting['height'] );
		$method = $width > 0 && $height > 0 ? 'fit' : 'scale';
		$options['method'] = $method;
		if ( $width > 0 ) {
			$options['width'] = $width;
		}
		if ( $height > 0 ) {
			$options['height'] = $height;
		}
		return sizeof( $options ) >= 2 ? $options : false;
	}

	public function render_section_end() {
		echo '</div>';
	}

	public function render_section() {
		echo '<div class="' . self::NAME . '">';
		echo '<span id="' . self::NAME . '"></span>';
	}

	public function render_sizes() {
		echo '<p>';
		esc_html_e(
			'Choose sizes to compress. Remember each selected size counts as a compression.',
			'tiny-compress-images'
		);
		echo '</p>';
		echo '<input type="hidden" name="' .
			self::get_prefixed_name( 'sizes[' . self::DUMMY_SIZE . ']' ) . '" value="on"/>';

		foreach ( $this->get_sizes() as $size => $option ) {
			$this->render_size_checkbox( $size, $option );
		}
		if ( self::wr2x_active() ) {
			$this->render_size_checkbox( 'wr2x', $this->get_wr2x_option() );
		}
		echo '<br>';
		echo '<div id="tiny-image-sizes-notice">';

		$this->render_image_sizes_notice(
			count( self::get_active_tinify_sizes() ),
			self::get_resize_enabled(),
			self::compress_wr2x_images()
		);

		echo '</div>';
	}

	private function render_size_checkbox( $size, $option ) {
		$id = self::get_prefixed_name( "sizes_$size" );
		$name = self::get_prefixed_name( 'sizes[' . $size . ']' );
		$checked = ( $option['tinify'] ? ' checked="checked"' : '' );
		if ( Tiny_Image::is_original( $size ) ) {
			$label = esc_html__( 'Original image', 'tiny-compress-images' ) . ' (' .
				esc_html__( 'overwritten by compressed image', 'tiny-compress-images' ) . ')';
		} else if ( Tiny_Image::is_retina( $size ) ) {
			$label = esc_html__( 'WP Retina 2x sizes', 'tiny-compress-images' );
		} else {
			$label = esc_html__( ucfirst( $size ) )
				. ' - ' . $option['width'] . 'x' . $option['height'];
		}
		echo '<p>';
		echo '<input type="checkbox" id="' . $id . '" name="' . $name .
			'" value="on" ' . $checked . '/>';
		echo '<label for="' . $id . '">' . $label . '</label>';
		echo '</p>';
	}

	public function render_image_sizes_notice(
		$active_sizes_count, $resize_original_enabled, $compress_wr2x ) {
		echo '<p>';
		if ( $resize_original_enabled ) {
			$active_sizes_count++;
		}
		if ( $compress_wr2x ) {
			$active_sizes_count *= 2;
		}

		if ( $active_sizes_count < 1 ) {
			esc_html_e(
				'With these settings no images will be compressed.',
				'tiny-compress-images'
			);
		} else {
			$free_images_per_month = floor(
				Tiny_Config::MONTHLY_FREE_COMPRESSIONS / $active_sizes_count
			);
			printf( wp_kses( __(
				'With these settings you can compress ' .
					'<strong> at least %s images </strong> for free each month.',
				'tiny-compress-images'
			), array( 'strong' => array() ) ), $free_images_per_month );

			if ( self::wr2x_active() ) {
				echo '</p>';
				echo '<p>';
				esc_html_e(
					'If selected, retina sizes will be compressed when generated by WP Retina 2x',
					'tiny-compress-images'
				);
				echo '<br>';
				esc_html_e(
					'Each retina size will count as an additional compression.',
					'tiny-compress-images'
				);
			}
		}
		echo '</p>';
	}

	public function render_resize() {
		echo '<p class="tiny-resize-unavailable" style="display: none">';
		esc_html_e(
			'Enable compression of the original image size for more options.',
			'tiny-compress-images'
		);
		echo '</p>';

		$id = self::get_prefixed_name( 'resize_original_enabled' );
		$name = self::get_prefixed_name( 'resize_original[enabled]' );
		$checked = ( $this->get_resize_enabled() ? ' checked="checked"' : '' );

		$label = esc_html__(
			'Resize and compress the original image',
			'tiny-compress-images'
		);

		echo '<p class="tiny-resize-available">';
		echo '<input  type="checkbox" id="' . $id . '" name="' . $name .
			'" value="on" '. $checked . '/>';
		echo '<label for="' . $id . '">' . $label . '</label>';
		echo '<br>';
		echo '</p>';

		echo '<p class="tiny-resize-available tiny-resize-resolution">';
		printf( '%s ', esc_html__( 'Max Width' ) );
		$this->render_resize_input( 'width' );
		printf( '%s ', esc_html__( 'Max Height' ) );
		$this->render_resize_input( 'height' );
		echo '</p>';

		echo '<p class="tiny-resize-available tiny-resize-resolution">';

		esc_html_e(
			'Resizing takes 1 additional compression for each image that is larger.',
			'tiny-compress-images'
		);

		echo '</p><br>';

		$this->render_preserve_input(
			'creation',
			esc_html__(
				'Preserve creation date and time in the original image',
				'tiny-compress-images'
			) . ' ' .
			esc_html__( '(JPEG only)', 'tiny-compress-images' )
		);

		$this->render_preserve_input(
			'copyright',
			esc_html__(
				'Preserve copyright information in the original image',
				'tiny-compress-images'
			)
		);

		$this->render_preserve_input(
			'location',
			esc_html__(
				'Preserve GPS location in the original image',
				'tiny-compress-images'
			) . ' ' .
			esc_html__( '(JPEG only)', 'tiny-compress-images' )
		);
	}

	public function render_preserve_input( $name, $description ) {
		echo '<p class="tiny-preserve">';
		$id = sprintf( self::get_prefixed_name( 'preserve_data_%s' ), $name );
		$field = sprintf( self::get_prefixed_name( 'preserve_data[%s]' ), $name );
		$checked = ( $this->get_preserve_enabled( $name ) ? ' checked="checked"' : '' );
		$label = esc_html__( $description, 'tiny-compress-images' );
		echo '<input type="checkbox" id="' . $id . '" name="' . $field .
			'" value="on" ' . $checked . '/>';
		echo '<label for="' . $id . '">' . $label . '</label>';
		echo '<br>';
		echo '</p>';
	}

	public function render_resize_input( $name ) {
		$id = sprintf( self::get_prefixed_name( 'resize_original_%s' ), $name );
		$field = sprintf( self::get_prefixed_name( 'resize_original[%s]' ), $name );
		$settings = get_option( self::get_prefixed_name( 'resize_original' ) );
		$value = isset( $settings[ $name ] ) ? $settings[ $name ] : '2048';
		echo '<input type="number" id="'. $id .'" name="' . $field .
			'" value="' . $value . '" size="5" />';
	}

	public function get_compression_count() {
		$field = self::get_prefixed_name( 'status' );
		return get_option( $field );
	}

	public function after_compress_callback( $compressor ) {
		if ( ! is_null( $count = $compressor->get_compression_count() ) ) {
			$field = self::get_prefixed_name( 'status' );
			update_option( $field, $count );
		}
		if ( $compressor->limit_reached() ) {
			$link = '<a href="https://tinypng.com/developers" target="_blank">' .
				esc_html__( 'TinyPNG API account', 'tiny-compress-images' ) . '</a>';

			$this->notices->add('limit-reached',
				sprintf(
					esc_html__(
						'You have reached your limit of %s compressions this month.',
						'tiny-compress-images'
					),
					$count
				) .
				sprintf(
					esc_html__(
						'Upgrade your %s if you like to compress more images.',
						'tiny-compress-images'
					),
					$link
				)
			);
		} else {
			$this->notices->remove( 'limit-reached' );
		}
	}

	public function render_account_status() {
		$key = $this->get_api_key();
		if ( empty( $key ) ) {
			$compressor = $this->get_compressor();
			if ( $compressor->can_create_key() ) {
				include( dirname( __FILE__ ) . '/views/account-status-create-advanced.php' );
			} else {
				include( dirname( __FILE__ ) . '/views/account-status-create-simple.php' );
			}
		} else {
			$status = $this->compressor->get_status();
			$status->pending = false;

			if ( $status->ok ) {
				if ( $this->get_api_key_pending() ) {
					$this->clear_api_key_pending();
				}
			} else {
				if ( $this->get_api_key_pending() ) {
					$status->ok = true;
					$status->pending = true;
					$status->message = (
						'An email has been sent with a link to activate your account'
					);
				}
			}

			include( dirname( __FILE__ ) . '/views/account-status-connected.php' );
		}
	}

	public function render_pending_status() {
		$key = $this->get_api_key();
		if ( empty( $key ) ) {
			$compressor = $this->get_compressor();
			if ( $compressor->can_create_key() ) {
				include( dirname( __FILE__ ) . '/views/account-status-create-advanced.php' );
			} else {
				include( dirname( __FILE__ ) . '/views/account-status-create-simple.php' );
			}
		} else {
			include( dirname( __FILE__ ) . '/views/account-status-loading.php' );
		}
	}

	public function create_api_key() {
		$compressor = $this->get_compressor();
		if ( $compressor->can_create_key() ) {
			if ( ! isset( $_POST['name'] ) || ! $_POST['name'] ) {
				$status = (object) array(
					'ok' => false,
					'message' => __(
						'Please enter your name', 'tiny-compress-images'
					),
				);
				echo json_encode( $status );
				exit();
			}

			if ( ! isset( $_POST['email'] ) || ! $_POST['email'] ) {
				$status = (object) array(
					'ok' => false,
					'message' => __(
						'Please enter your email address', 'tiny-compress-images'
					),
				);
				echo json_encode( $status );
				exit();
			}

			try {
				$site = str_replace( array( 'http://', 'https://' ), '', get_bloginfo( 'url' ) );
				$identifier = 'WordPress plugin for ' . $site;
				$link = $this->get_absolute_url();
				$compressor->create_key( $_POST['email'], array(
					'name' => $_POST['name'],
					'identifier' => $identifier,
					'link' => $link,
				) );

				update_option( self::get_prefixed_name( 'api_key_pending' ), true );
				update_option( self::get_prefixed_name( 'api_key' ), $compressor->get_key() );
				update_option( self::get_prefixed_name( 'status' ), 0 );

				$status = (object) array(
					'ok' => true,
					'message' => null,
				);
			} catch (Tiny_Exception $err) {
				list( $message ) = explode( ' (HTTP', $err->getMessage(), 2 );
				$status = (object) array(
					'ok' => false,
					'message' => $message,
				);
			}
		} else {
			$status = (object) array(
				'ok' => false,
				'message' => 'This feature is not available on your platform',
			);
		}

		$status->message = __( $status->message, 'tiny-compress-images' );
		echo json_encode( $status );
		exit();
	}

	public function update_api_key() {
		$key = $_POST['key'];
		if ( empty( $key ) ) {
			/* Always save if key is blank, so the key can be deleted. */
			$status = (object) array(
				'ok' => true,
				'message' => null,
			);
		} else {
			$status = Tiny_Compress::create( $key )->get_status();
		}
		if ( $status->ok ) {
			update_option( self::get_prefixed_name( 'api_key_pending' ), false );
			update_option( self::get_prefixed_name( 'api_key' ), $key );
		}
		$status->message = __( $status->message, 'tiny-compress-images' );
		echo json_encode( $status );
		exit();
	}

	public static function wr2x_active() {
		return is_plugin_active( 'wp-retina-2x/wp-retina-2x.php' );
	}

	public function get_wr2x_option() {
		$setting = get_option( self::get_prefixed_name( 'sizes' ) );
		return array(
				'width' => null,
				'height' => null,
				'tinify' => ( isset( $setting['wr2x'] ) && 'on' === $setting['wr2x'] ),
			);
	}

	public function compress_wr2x_images() {
		$option = $this->get_wr2x_option();
		return self::wr2x_active() && $option['tinify'];
	}
}
