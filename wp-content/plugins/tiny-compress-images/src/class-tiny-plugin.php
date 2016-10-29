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

class Tiny_Plugin extends Tiny_WP_Base {
	const VERSION = '2.1.0';
	const MEDIA_COLUMN = self::NAME;
	const DATETIME_FORMAT = 'Y-m-d G:i:s';

	private static $version;

	private $settings;
	private $twig;

	public static function jpeg_quality() {
		return 85;
	}

	public static function version() {
		/* Avoid using get_plugin_data() because it is not loaded early enough
		   in xmlrpc.php. */
		return self::VERSION;
	}

	public function __construct() {
		parent::__construct();

		$this->settings = new Tiny_Settings();
	}

	public function set_compressor( $compressor ) {
		$this->settings->set_compressor( $compressor );
	}

	public function init() {
		add_filter( 'jpeg_quality',
			$this->get_static_method( 'jpeg_quality' )
		);

		add_filter( 'wp_editor_set_quality',
			$this->get_static_method( 'jpeg_quality' )
		);

		add_filter( 'wp_generate_attachment_metadata',
			$this->get_method( 'compress_on_upload' ),
			10, 2
		);

		load_plugin_textdomain( self::NAME, false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	public function admin_init() {
		add_action( 'admin_enqueue_scripts',
			$this->get_method( 'enqueue_scripts' )
		);

		add_action( 'admin_action_tiny_bulk_action',
			$this->get_method( 'media_library_bulk_action' )
		);

		add_action( 'admin_action_-1',
			$this->get_method( 'media_library_bulk_action' )
		);

		add_filter( 'manage_media_columns',
			$this->get_method( 'add_media_columns' )
		);

		add_action( 'manage_media_custom_column',
			$this->get_method( 'render_media_column' ),
			10, 2
		);

		add_action( 'attachment_submitbox_misc_actions',
			$this->get_method( 'show_media_info' )
		);

		add_action( 'wp_ajax_tiny_compress_image_from_library',
			$this->get_method( 'compress_image_from_library' )
		);

		add_action( 'wp_ajax_tiny_compress_image_for_bulk',
			$this->get_method( 'compress_image_for_bulk' )
		);

		add_action( 'wp_ajax_tiny_get_optimization_statistics',
			$this->get_method( 'ajax_optimization_statistics' )
		);

		$plugin = plugin_basename(
			dirname( dirname( __FILE__ ) ) . '/tiny-compress-images.php'
		);

		add_filter( "plugin_action_links_$plugin",
			$this->get_method( 'add_plugin_links' )
		);

		add_action( 'wr2x_retina_file_added',
			$this->get_method( 'compress_retina_image' ),
			10, 3
		);

		add_action( 'wr2x_retina_file_removed',
			$this->get_method( 'remove_retina_image' ),
			10, 2
		);

		add_thickbox();
	}

	public function admin_menu() {
		add_media_page(
			__( 'Bulk Optimization', 'tiny-compress-images' ),
			esc_html__( 'Bulk Optimization', 'tiny-compress-images' ),
			'upload_files',
			'tiny-bulk-optimization',
			$this->get_method( 'render_bulk_optimization_page' )
		);
	}

	public function add_plugin_links( $current_links ) {
		$additional = array(
			'settings' => sprintf(
				'<a href="options-media.php#%s">%s</a>',
				self::NAME,
				esc_html__( 'Settings', 'tiny-compress-images' )
			),
			'bulk' => sprintf(
				'<a href="upload.php?page=tiny-bulk-optimization">%s</a>',
				esc_html__( 'Bulk Optimization', 'tiny-compress-images' )
			),
		);
		return array_merge( $additional, $current_links );
	}

	public function compress_retina_image( $attachment_id, $path, $size_name ) {
		if ( $this->settings->compress_wr2x_images() ) {
			$tiny_image = new Tiny_Image( $this->settings, $attachment_id );
			$tiny_image->compress_retina( $size_name . '_wr2x', $path );
		}
	}

	public function remove_retina_image( $attachment_id, $path ) {
		$tiny_image = new Tiny_Image( $this->settings, $attachment_id );
		$tiny_image->remove_retina_metadata();
	}

	public function enqueue_scripts( $hook ) {
		wp_enqueue_style( self::NAME .'_admin',
			plugins_url( '/css/admin.css', __FILE__ ),
			array(), self::version()
		);

		wp_register_script( self::NAME .'_admin',
			plugins_url( '/js/admin.js', __FILE__ ),
			array(), self::version(), true
		);

		// WordPress < 3.3 does not handle multidimensional arrays
		wp_localize_script( self::NAME .'_admin', 'tinyCompress', array(
			'nonce' => wp_create_nonce( 'tiny-compress' ),
			'wpVersion' => self::wp_version(),
			'pluginVersion' => self::version(),
			'L10nAllDone' => __( 'All images are processed', 'tiny-compress-images' ),
			'L10nNoActionTaken' => __( 'No action taken', 'tiny-compress-images' ),
			'L10nBulkAction' => __( 'Compress Images', 'tiny-compress-images' ),
			'L10nCancelled' => __( 'Cancelled', 'tiny-compress-images' ),
			'L10nCompressing' => __( 'Compressing', 'tiny-compress-images' ),
			'L10nCompressed' => __( 'compressed', 'tiny-compress-images' ),
			'L10nFile' => __( 'File', 'tiny-compress-images' ),
			'L10nSizesOptimized' => __( 'Sizes optimized', 'tiny-compress-images' ),
			'L10nInitialSize' => __( 'Initial size', 'tiny-compress-images' ),
			'L10nCurrentSize' => __( 'Current size', 'tiny-compress-images' ),
			'L10nSavings' => __( 'Savings', 'tiny-compress-images' ),
			'L10nStatus' => __( 'Status', 'tiny-compress-images' ),
			'L10nShowMoreDetails' => __( 'Show more details', 'tiny-compress-images' ),
			'L10nError' => __( 'Error', 'tiny-compress-images' ),
			'L10nLatestError' => __( 'Latest error', 'tiny-compress-images' ),
			'L10nInternalError' => __( 'Internal error', 'tiny-compress-images' ),
			'L10nOutOf' => __( 'out of', 'tiny-compress-images' ),
			'L10nWaiting' => __( 'Waiting', 'tiny-compress-images' ),
		));

		wp_enqueue_script( self::NAME .'_admin' );

		if ( 'media_page_tiny-bulk-optimization' == $hook ) {
			wp_enqueue_style(
				self::NAME . '_tiny_bulk_optimization',
				plugins_url( '/css/bulk-optimization.css', __FILE__ ),
				array(), self::version()
			);

			wp_register_script(
				self::NAME . '_tiny_bulk_optimization',
				plugins_url( '/js/bulk-optimization.js', __FILE__ ),
				array(), self::version(), true
			);

			wp_enqueue_script( self::NAME .'_tiny_bulk_optimization' );
		}

	}

	public function compress_on_upload( $metadata, $attachment_id ) {
		if ( ! empty( $metadata ) ) {
			$tiny_image = new Tiny_Image( $this->settings, $attachment_id, $metadata );
			$result = $tiny_image->compress( $this->settings );
			return $tiny_image->get_wp_metadata();
		} else {
			return $metadata;
		}
	}

	public function compress_image_from_library() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			$message = esc_html__(
				"You don't have permission to upload files.",
				'tiny-compress-images'
			);
			echo $message;
			exit();
		}
		if ( empty( $_POST['id'] ) ) {
			$message = esc_html__(
				'Not a valid media file.',
				'tiny-compress-images'
			);
			echo $message;
			exit();
		}
		$id = intval( $_POST['id'] );
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $metadata ) ) {
			$message = esc_html__(
				'Could not find metadata of media file.',
				'tiny-compress-images'
			);
			echo $message;
			exit;
		}

		$tiny_image = new Tiny_Image( $this->settings, $id, $metadata );
		$result = $tiny_image->compress( $this->settings );

		// The wp_update_attachment_metadata call is thrown because the
		// dimensions of the original image can change. This will then
		// trigger other plugins and can result in unexpected behaviour and
		// further changes to the image. This may require another approach.
		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		echo $this->render_compress_details( $tiny_image );

		exit();
	}

	public function compress_image_for_bulk() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			$message = esc_html__(
				"You don't have permission to upload files.",
				'tiny-compress-images'
			);
			echo json_encode( array( 'error' => $message ) );
			exit();
		}
		if ( empty( $_POST['id'] ) ) {
			$message = esc_html__(
				'Not a valid media file.',
				'tiny-compress-images'
			);
			echo json_encode( array( 'error' => $message ) );
			exit();
		}
		$id = intval( $_POST['id'] );
		$metadata = wp_get_attachment_metadata( $id );
		if ( ! is_array( $metadata ) ) {
			$message = esc_html__(
				'Could not find metadata of media file.',
				'tiny-compress-images'
			);
			echo json_encode( array( 'error' => $message ) );
			exit;
		}

		$tiny_image_before = new Tiny_Image( $this->settings, $id, $metadata );
		$image_statistics_before = $tiny_image_before->get_statistics();
		$size_before = $image_statistics_before['optimized_total_size'];

		$tiny_image = new Tiny_Image( $this->settings, $id, $metadata );
		$result = $tiny_image->compress( $this->settings );
		$image_statistics = $tiny_image->get_statistics();
		wp_update_attachment_metadata( $id, $tiny_image->get_wp_metadata() );

		$current_library_size = intval( $_POST['current_size'] );
		$size_after = $image_statistics['optimized_total_size'];
		$new_library_size = $current_library_size + $size_after - $size_before;

		$result['message'] = $tiny_image->get_latest_error();
		$result['image_sizes_optimized'] = $image_statistics['image_sizes_optimized'];

		$result['initial_total_size'] = size_format(
			$image_statistics['initial_total_size'], 1
		);

		$result['optimized_total_size'] = size_format(
			$image_statistics['optimized_total_size'], 1
		);

		$result['savings'] = $tiny_image->get_savings( $image_statistics );
		$result['status'] = $this->settings->get_status();
		$result['thumbnail'] = wp_get_attachment_image(
			$id, array( '30', '30' ), true, array(
				'class' => 'pinkynail',
				'alt' => '',
			)
		);
		$result['size_change'] = $size_after - $size_before;
		$result['human_readable_library_size'] = size_format( $new_library_size, 2 );

		echo json_encode( $result );

		exit();
	}

	public function ajax_optimization_statistics() {
		if ( ! $this->check_ajax_referer() ) {
			exit();
		}
		$stats = Tiny_Image::get_optimization_statistics( $this->settings );
		echo json_encode( $stats );
		exit();
	}

	public function media_library_bulk_action() {

		if ( empty( $_REQUEST['action'] ) || (
				'tiny_bulk_action' != $_REQUEST['action'] &&
				'tiny_bulk_action' != $_REQUEST['action2'] ) ) {
			return;
		}

		if ( empty( $_REQUEST['media'] ) || ( ! $_REQUEST['media'] ) ) {
			return;
		}

		check_admin_referer( 'bulk-media' );
		$ids = implode( '-', array_map( 'intval', $_REQUEST['media'] ) );
		wp_redirect(add_query_arg(
			'_wpnonce',
			wp_create_nonce( 'tiny-bulk-optimization' ),
			admin_url( "upload.php?page=tiny-bulk-optimization&ids=$ids" )
		));
		exit();
	}

	public function add_media_columns( $columns ) {
		$columns[ self::MEDIA_COLUMN ] = esc_html__( 'Compression', 'tiny-compress-images' );
		return $columns;
	}

	public function render_media_column( $column, $id ) {
		if ( self::MEDIA_COLUMN === $column ) {
			$tiny_image = new Tiny_Image( $this->settings, $id );
			if ( $tiny_image->file_type_allowed() ) {
				echo '<div class="tiny-ajax-container">';
				$this->render_compress_details( $tiny_image );
				echo '</div>';
			}
		}
	}

	public function show_media_info() {
		global $post;
		$tiny_image = new Tiny_Image( $this->settings, $post->ID );
		if ( $tiny_image->file_type_allowed() ) {
			echo '<div class="misc-pub-section tiny-compress-images">';
			echo '<h4>';
			esc_html_e( 'JPEG and PNG optimization', 'tiny-compress-images' );
			echo '</h4>';
			echo '<div class="tiny-ajax-container">';
			$this->render_compress_details( $tiny_image );
			echo '</div>';
			echo '</div>';
		}
	}

	private function render_compress_details( $tiny_image ) {
		$in_progress = $tiny_image->filter_image_sizes( 'in_progress' );
		if ( count( $in_progress ) > 0 ) {
			include( dirname( __FILE__ ) . '/views/compress-details-processing.php' );
		} else {
			include( dirname( __FILE__ ) . '/views/compress-details.php' );
		}
	}

	public function render_bulk_optimization_page() {
		$stats = Tiny_Image::get_optimization_statistics( $this->settings );
		$estimated_costs = Tiny_Compress::estimate_cost(
			$stats['available-unoptimised-sizes'],
			$this->settings->get_compression_count()
		);
		$admin_colors = self::retrieve_admin_colors();

		$active_tinify_sizes = $this->settings->get_active_tinify_sizes();

		$auto_start_bulk = isset( $_REQUEST['ids'] );

		include( dirname( __FILE__ ) . '/views/bulk-optimization.php' );
	}

	private static function retrieve_admin_colors() {
		global $_wp_admin_css_colors;
		$admin_colour_scheme = get_user_option( 'admin_color', get_current_user_id() );
		$admin_colors = array( '#0074aa', '#1685b5', '#78ca44', '#0086ba' ); // default
		if ( isset( $_wp_admin_css_colors[ $admin_colour_scheme ] ) ) {
			if ( isset( $_wp_admin_css_colors[ $admin_colour_scheme ]->colors ) ) {
				$admin_colors = $_wp_admin_css_colors[ $admin_colour_scheme ]->colors;
			}
		}
		if ( '#e5e5e5' == $admin_colors[0] && '#999' == $admin_colors[1] ) {
			$admin_colors[0] = '#bbb';
		}
		if ( '#5589aa' == $admin_colors[0] && '#cfdfe9' == $admin_colors[1] ) {
			$admin_colors[1] = '#85aec5';
		}
		if ( '#7c7976' == $admin_colors[0] && '#c6c6c6' == $admin_colors[1] ) {
			$admin_colors[1] = '#adaba9';
			$admin_colors[2] = '#adaba9';
		}
		if ( self::wp_version() > 3.7 ) {
			if ( 'fresh' == $admin_colour_scheme ) {
				$admin_colors = array( '#0074aa', '#1685b5', '#78ca44', '#0086ba' ); // better
			}
		}
		return $admin_colors;
	}

	function friendly_user_name() {
		$user = wp_get_current_user();
		$name = ucfirst( empty( $user->first_name ) ? $user->display_name : $user->first_name );
		return $name;
	}

	private function get_ids_to_compress() {
		if ( empty( $_REQUEST['ids'] ) ) {
			return array();
		}

		$ids = implode( ',', array_map( 'intval', explode( '-', $_REQUEST['ids'] ) ) );
		$condition = "AND ID IN($ids)";

		global $wpdb;
		return $wpdb->get_results(
			"SELECT ID, post_title FROM $wpdb->posts
             WHERE post_type = 'attachment' $condition
             AND (post_mime_type = 'image/jpeg' OR post_mime_type = 'image/png')
             ORDER BY ID DESC", ARRAY_A);
	}
}
