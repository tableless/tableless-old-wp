<?php

define( 'ABSPATH', dirname( dirname( __FILE__ ) ) . '/' );
define( 'WPINC', 'wp-includes' );

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\content\LargeFileContent;

class WordPressOptions {
	private $values;

	public function __construct() {
		 $this->values = array(
			'thumbnail_size_w' => 150,
			'thumbnail_size_h' => 150,
			'medium_size_w' => 300,
			'medium_size_h' => 300,
			'large_size_w' => 1024,
			'large_size_h' => 1024,
		 );
	}

	public function set( $key, $value ) {
		if ( preg_match( '#^(.+)\[(.+)\]$#', $key, $match ) ) {
			if ( ! isset( $this->values[ $match[1] ] ) ) {
				$this->values[ $match[1] ] = array();
			}
			$this->values[ $match[1] ][ $match[2] ] = $value;
		} else {
			$this->values[ $key ] = $value;
		}
	}

	public function get( $key, $default = null ) {
		return isset( $this->values[ $key ] ) ? $this->values[ $key ] : $default;
	}
}

class WordPressStubs {
	const UPLOAD_DIR = 'wp-content/uploads';

	private $vfs;
	private $initFunctions;
	private $admin_initFunctions;
	private $options;
	private $metadata;
	private $calls;
	private $stubs;

	public function __construct( $vfs ) {
		$GLOBALS['wp'] = $this;
		$this->vfs = $vfs;
		$this->addMethod( 'add_action' );
		$this->addMethod( 'add_filter' );
		$this->addMethod( 'register_setting' );
		$this->addMethod( 'add_settings_section' );
		$this->addMethod( 'add_settings_field' );
		$this->addMethod( 'get_option' );
		$this->addMethod( 'get_site_option' );
		$this->addMethod( 'update_site_option' );
		$this->addMethod( 'get_post_meta' );
		$this->addMethod( 'update_post_meta' );
		$this->addMethod( 'get_intermediate_image_sizes' );
		$this->addMethod( 'add_image_size' );
		$this->addMethod( 'translate' );
		$this->addMethod( 'load_plugin_textdomain' );
		$this->addMethod( 'get_post_mime_type' );
		$this->addMethod( 'get_plugin_data' );
		$this->addMethod( 'wp_upload_dir' );
		$this->addMethod( 'plugin_basename' );
		$this->addMethod( 'is_multisite' );
		$this->addMethod( 'current_user_can' );
		$this->addMethod( 'wp_get_attachment_metadata' );
		$this->addMethod( 'is_admin' );
		$this->defaults();
		$this->create_filesystem();
	}

	public function create_filesystem() {
		vfsStream::newDirectory( self::UPLOAD_DIR )
			->at( $this->vfs );
	}

	public function defaults() {
		$this->initFunctions = array();
		$this->admin_initFunctions = array();
		$this->options = new WordPressOptions();
		$this->metadata = array();
		$GLOBALS['_wp_additional_image_sizes'] = array();
	}

	public function call( $method, $args ) {
		$this->calls[ $method ][] = $args;
		if ( 'add_action' === $method ) {
			if ( 'init' === $args[0] ) {
				$this->initFunctions[] = $args[1];
			} elseif ( 'admin_init' === $args[0] ) {
				$this->admin_initFunctions[] = $args[1];
			}
		}
		if ( 'translate' === $method ) {
			return $args[0];
		} elseif ( 'get_option' === $method ) {
			return call_user_func_array( array( $this->options, 'get' ), $args );
		} elseif ( 'get_post_meta' === $method ) {
			return call_user_func_array( array( $this, 'getMetadata' ), $args );
		} elseif ( 'add_image_size' === $method ) {
			return call_user_func_array( array( $this, 'addImageSize' ), $args );
		} elseif ( 'update_post_meta' === $method ) {
			return call_user_func_array( array( $this, 'updateMetadata' ), $args );
		} elseif ( 'get_intermediate_image_sizes' === $method ) {
			return array_merge( array( 'thumbnail', 'medium', 'large' ), array_keys( $GLOBALS['_wp_additional_image_sizes'] ) );
		} elseif ( 'get_plugin_data' === $method ) {
			return array( 'Version' => '1.7.2' );
		} elseif ( 'wp_upload_dir' === $method ) {
			return array( 'basedir' => $this->vfs->url() . '/' . self::UPLOAD_DIR, 'baseurl' => '/' . self::UPLOAD_DIR );
		} elseif ( 'is_admin' === $method ) {
			return true;
		} elseif ( $this->stubs[ $method ] ) {
			return call_user_func_array( $this->stubs[ $method ], $args );
		}
	}

	public function addMethod( $method ) {
		$this->calls[ $method ] = array();
		$this->stubs[ $method ] = array();
		if ( ! function_exists( $method ) ) {
			eval( "function $method() { return \$GLOBALS['wp']->call('$method', func_get_args()); }" );
		}
	}

	public function addOption( $key, $value ) {
		$this->options->set( $key, $value );
	}

	public function addImageSize( $size, $values ) {
		$GLOBALS['_wp_additional_image_sizes'][ $size ] = $values;
	}

	public function getMetadata( $id, $key, $single = false ) {
		$values = isset( $this->metadata[ $id ] ) ? $this->metadata[ $id ] : array();
		$value = isset( $values[ $key ] ) ? $values[ $key ] : '';
		return $single ? $value : array( $value );
	}

	public function updateMetadata( $id, $key, $values ) {
		$this->metadata[ $id ][ $key ] = $values;
	}

	public function setTinyMetadata( $id, $values ) {
		$this->metadata[ $id ] = array( Tiny_Image::META_KEY => $values );
	}

	public function getCalls( $method ) {
		return $this->calls[ $method ];
	}

	public function init() {
		foreach ( $this->initFunctions as $func ) {
			call_user_func( $func );
		}
	}

	public function admin_init() {
		foreach ( $this->admin_initFunctions as $func ) {
			call_user_func( $func );
		}
	}

	public function stub( $method, $func ) {
		$this->stubs[ $method ] = $func;
	}

	public function createImage( $file_size, $path, $name ) {
		if ( ! $this->vfs->hasChild( self::UPLOAD_DIR . "/$path" ) ) {
			vfsStream::newDirectory( self::UPLOAD_DIR . "/$path" )->at( $this->vfs );
		}
		$dir = $this->vfs->getChild( self::UPLOAD_DIR . "/$path" );

		vfsStream::newFile( $name )
			->withContent( new LargeFileContent( $file_size ) )
			->at( $dir );
	}

	public function createImages( $sizes = null, $original_size = 12345, $path = '14/01', $name = 'test' ) {
		vfsStream::newDirectory( self::UPLOAD_DIR . "/$path" )->at( $this->vfs );
		$dir = $this->vfs->getChild( self::UPLOAD_DIR . '/' . $path );

		vfsStream::newFile( "$name.png" )
			->withContent( new LargeFileContent( $original_size ) )
			->at( $dir );

		if ( is_null( $sizes ) ) {
			$sizes = array( 'thumbnail' => 100, 'medium' => 1000 , 'large' => 10000, 'post-thumbnail' => 1234 );
		}

		foreach ( $sizes as $key => $size ) {
			vfsStream::newFile( "$name-$key.png" )
				->withContent( new LargeFileContent( $size ) )
				->at( $dir );
		}
	}

	public function createImagesFromJSON( $virtual_images ) {
		foreach ( $virtual_images['images'] as $image ) {
			self::createImage( $image['size'], $virtual_images['path'], $image['file'] );
		}
	}

	public function getTestMetadata( $path = '14/01', $name = 'test' ) {
		$metadata = array(
			'file' => "$path/$name.png",
			'width' => 4000,
			'height' => 3000,
			'sizes' => array(),
		);

		$regex = '#^' . preg_quote( $name ) .'-([^.]+)[.](png|jpe?g)$#';
		$dir = $this->vfs->getChild( self::UPLOAD_DIR . "/$path" );
		foreach ( $dir->getChildren() as $child ) {
			$file = $child->getName();
			if ( preg_match( $regex, $file, $match ) ) {
				$metadata['sizes'][ $match[1] ] = array( 'file' => $file );
			}
		}

		return $metadata;
	}
}

class WP_HTTP_Proxy {
	public function is_enabled() {
		return false;
	}
}

function __( $text, $domain = 'default' ) {
	return translate( $text, $domain );
}

function esc_html__( $text, $domain = 'default' ) {
	return translate( $text, $domain );
}
