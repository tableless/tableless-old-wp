<?php
if ( class_exists( 'Bbpp_Animated_Gif' ) ) {
	class EWWWIO_GD_Editor extends Bbpp_Animated_Gif {
		public function save( $filename = null, $mime_type = null ) {
			global $ewww_defer;
			if (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD'))
				ewww_image_optimizer_init();
			$saved = parent::save($filename, $mimetype);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				if ( file_exists( $filename ) ) {
					if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
						ewww_image_optimizer_add_deferred_attachment( "file,$filename" );
						return $saved;
					}
					ewww_image_optimizer($filename);
					ewwwio_debug_message( "image editor (AGR gd) saved: $filename" );
					$image_size = ewww_image_optimizer_filesize( $filename );
					ewwwio_debug_message( "image editor size: $image_size" );;
				}
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
		public function multi_resize( $sizes ) {
			global $ewww_defer;
			if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_CLOUD' ) )
				ewww_image_optimizer_init();
			$metadata = parent::multi_resize( $sizes );
			ewwwio_debug_message( 'image editor (AGR gd) multi resize' );
			ewwwio_debug_message( print_r( $metadata, true ) );
			ewwwio_debug_message( print_r( $this, true ) );
			$info = pathinfo( $this->file );
			$dir = $info['dirname'];
			foreach ( $metadata as $size ) {
				$filename = trailingslashit( $dir ) . $size['file'];
				if ( file_exists( $filename ) ) {
					if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
						ewww_image_optimizer_add_deferred_attachment( "file,$filename" );
						return $saved;
					}
					ewww_image_optimizer( $filename );
					ewwwio_debug_message( "image editor (AGR gd) saved: $filename" );
					$image_size = ewww_image_optimizer_filesize( $filename );
					ewwwio_debug_message( "image editor size: $image_size" );
				}
			}
			ewww_image_optimizer_debug_log();
			ewwwio_memory( __FUNCTION__ );
			return $metadata;
		}
	}
} elseif (class_exists('WP_Thumb_Image_Editor_GD')) {
	class EWWWIO_GD_Editor extends WP_Thumb_Image_Editor_GD {
		protected function _save( $image, $filename = null, $mime_type = null ) {
			global $ewww_defer;
			if (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				if ( file_exists( $filename ) ) {
					if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
						ewww_image_optimizer_add_deferred_attachment( "file,$filename" );
						return $saved;
					}
					ewww_image_optimizer($filename);
					ewwwio_debug_message( "image editor (wpthumb GD) saved: $filename" );
					$image_size = ewww_image_optimizer_filesize( $filename );
					ewwwio_debug_message( "image editor size: $image_size" );
				}
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
} else {
	class EWWWIO_GD_Editor extends WP_Image_Editor_GD {
		protected function _save ($image, $filename = null, $mime_type = null) {
			global $ewww_defer;
			if (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				if ( file_exists( $filename ) ) {
					if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
						ewww_image_optimizer_add_deferred_attachment( "file,$filename" );
						return $saved;
					}
					ewww_image_optimizer($filename);
					ewwwio_debug_message( "image editor (gd) saved: $filename" );
					$image_size = ewww_image_optimizer_filesize( $filename );
					ewwwio_debug_message( "image editor size: $image_size" );
				}
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
}
if (class_exists('WP_Thumb_Image_Editor_Imagick')) {
	class EWWWIO_Imagick_Editor extends WP_Thumb_Image_Editor_Imagick {
		protected function _save( $image, $filename = null, $mime_type = null ) {
			global $ewww_defer;
			if (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				if ( file_exists( $filename ) ) {
					if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
						ewww_image_optimizer_add_deferred_attachment( "file,$filename" );
						return $saved;
					}
					ewww_image_optimizer($filename);
					ewwwio_debug_message( "image editor (wpthumb imagick) saved: $filename" );
					$image_size = ewww_image_optimizer_filesize( $filename );
					ewwwio_debug_message( "image editor size: $image_size" );
				}
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
} else {
	class EWWWIO_Imagick_Editor extends WP_Image_Editor_Imagick {
		protected function _save( $image, $filename = null, $mime_type = null ) {
			global $ewww_defer;
			if (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				if ( file_exists( $filename ) ) {
					if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
						ewww_image_optimizer_add_deferred_attachment( "file,$filename" );
						return $saved;
					}
					ewww_image_optimizer($filename);
					ewwwio_debug_message( "image editor (imagick) saved: $filename" );
					$image_size = ewww_image_optimizer_filesize( $filename );
					ewwwio_debug_message( "image editor size: $image_size" );
				}
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
}
if (class_exists('WP_Image_Editor_Gmagick')) {
	class EWWWIO_Gmagick_Editor extends WP_Image_Editor_Gmagick {
		protected function _save( $image, $filename = null, $mime_type = null ) {
			global $ewww_defer;
			if (!defined('EWWW_IMAGE_OPTIMIZER_CLOUD'))
				ewww_image_optimizer_init();
			$saved = parent::_save($image, $filename, $mime_type);
			if ( ! is_wp_error( $saved ) ) {
				if ( ! $filename ) {
					$filename = $saved['path'];
				}
				if ( file_exists( $filename ) ) {
					if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
						ewww_image_optimizer_add_deferred_attachment( "file,$filename" );
						return $saved;
					}
					ewww_image_optimizer($filename);
					ewwwio_debug_message( "image editor (gmagick) saved: $filename" );
					$image_size = ewww_image_optimizer_filesize( $filename );
					ewwwio_debug_message( "image editor size: $image_size" );
				}
				ewww_image_optimizer_debug_log();
			}
			ewwwio_memory( __FUNCTION__ );
			return $saved;
		}
	}
}
