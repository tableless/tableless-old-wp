<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\content\LargeFileContent;

class Tiny_Plugin_Test extends Tiny_TestCase {
	public function set_up() {
		parent::set_up();
		$this->subject = new Tiny_Plugin();
		$this->subject->init();
		$this->compressor = $this->getMockBuilder( 'TestCompressor' )
								 ->setMethods( array( 'compress_file' ) )
								 ->getMock();
		$this->subject->set_compressor( $this->compressor );

		$this->wp->addOption( 'tinypng_api_key', 'test123' );
		$this->wp->addOption( 'tinypng_sizes[0]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[large]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[post-thumbnail]', 'on' );

		$this->wp->addImageSize( 'post-thumbnail', array( 'width' => 825, 'height' => 510 ) );
		$this->wp->createImages();
	}

	public function success_compress( $file ) {
		if ( preg_match( '#[^-]+-([^.]+)[.](png|jpe?g)$#', basename( $file ), $match ) ) {
			$key = $match[1];
		} else {
			$key = null;
		}

		$input = filesize( $file );
		switch ( $key ) {
			case 'thumbnail':
				$output = 81;
				$width = '150';
				$height = '150';
				break;
			case 'medium':
				$output = 768;
				$width = '300';
				$height = '300';
				break;
			case 'large':
				$output = 6789;
				$width = '1024';
				$height = '1024';
				break;
			case 'post-thumbnail':
				$output = 1000;
				$width = '800';
				$height = '500';
				break;
			default:
				$output = 10000;
				$width = '4000';
				$height = '3000';
		}
		$this->vfs->getChild( vfsStream::path( $file ) )->truncate( $output );
		return array( 'input' => array( 'size' => $input ), 'output' => array( 'size' => $output, 'width' => $width, 'height' => $height ) );
	}

	public function test_init_should_add_filters() {
		$this->assertEquals(array(
			array( 'jpeg_quality', array( 'Tiny_Plugin', 'jpeg_quality' ) ),
			array( 'wp_editor_set_quality', array( 'Tiny_Plugin', 'jpeg_quality' ) ),
			array( 'wp_generate_attachment_metadata', array( $this->subject, 'compress_on_upload' ), 10, 2 ),
		), $this->wp->getCalls( 'add_filter' ));
	}

	public function test_compress_should_respect_settings() {
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "image/png";' ) );
		$this->compressor->expects( $this->exactly( 3 ) )->method( 'compress_file' )->withConsecutive(
			array( $this->equalTo( 'vfs://root/wp-content/uploads/14/01/test.png' ) ),
			array( $this->equalTo( 'vfs://root/wp-content/uploads/14/01/test-large.png' ) ),
			array( $this->equalTo( 'vfs://root/wp-content/uploads/14/01/test-post-thumbnail.png' ) )
		)->will( $this->returnCallback( array( $this, 'success_compress' ) ) );
		$this->subject->compress_on_upload( $this->wp->getTestMetadata(), 1 );
	}

	public function test_compress_should_not_compress_twice() {
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "image/png";' ) );

		$testmeta = $this->wp->getTestMetadata();
		$tiny_image = new Tiny_Image( new Tiny_Settings(), 1, $testmeta );
		$tiny_image->get_image_size()->add_tiny_meta_start();
		$tiny_image->get_image_size()->add_tiny_meta( self::success_compress( 'vfs://root/wp-content/uploads/14/01/test.png' ) );
		$tiny_image->get_image_size( 'large' )->add_tiny_meta_start();
		$tiny_image->get_image_size( 'large' )->add_tiny_meta( self::success_compress( 'vfs://root/wp-content/uploads/14/01/test-large.png' ) );
		$tiny_image->update_tiny_post_meta();

		$this->compressor->expects( $this->once() )->method( 'compress_file' )->withConsecutive(
			array( $this->equalTo( 'vfs://root/wp-content/uploads/14/01/test-post-thumbnail.png' ) )
		)->will( $this->returnCallback( array( $this, 'success_compress' ) ) );
		$this->subject->compress_on_upload( $testmeta, 1 );
	}

	public function test_compress_when_file_changed() {
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "image/png";' ) );

		$testmeta = $this->wp->getTestMetadata();
		$tiny_image = new Tiny_Image( new Tiny_Settings(), 1, $testmeta );
		$tiny_image->get_image_size()->add_tiny_meta_start();
		$tiny_image->get_image_size()->add_tiny_meta( self::success_compress( 'vfs://root/wp-content/uploads/14/01/test.png' ) );
		$tiny_image->get_image_size( 'large' )->add_tiny_meta_start();
		$tiny_image->get_image_size( 'large' )->add_tiny_meta( self::success_compress( 'vfs://root/wp-content/uploads/14/01/test-large.png' ) );
		$tiny_image->get_image_size( 'post-thumbnail' )->add_tiny_meta_start();
		$tiny_image->get_image_size( 'post-thumbnail' )->add_tiny_meta( self::success_compress( 'vfs://root/wp-content/uploads/14/01/test-post-thumbnail.png' ) );
		$tiny_image->update_tiny_post_meta();

		$this->vfs->getChild( 'wp-content/uploads/14/01/test-large.png' )->truncate( 100000 );

		$this->compressor->expects( $this->once() )->method( 'compress_file' )->withConsecutive(
			array( $this->equalTo( 'vfs://root/wp-content/uploads/14/01/test-large.png' ) )
		)->will( $this->returnCallback( array( $this, 'success_compress' ) ) );
		$this->subject->compress_on_upload( $testmeta, 1 );
	}

	public function test_compress_should_update_metadata() {
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "image/png";' ) );
		$this->compressor->expects( $this->exactly( 3 ) )->method( 'compress_file' )->will(
			$this->returnCallback( array( $this, 'success_compress' ) )
		);

		$this->subject->compress_on_upload( $this->wp->getTestMetadata(), 1 );

		$tiny_metadata = $this->wp->getMetadata( 1, Tiny_Image::META_KEY, true );
		foreach ( $tiny_metadata as $key => $values ) {
			if ( ! empty( $values ) ) {
				$this->assertBetween( -1, + 1, $values['end'] - time() );
				unset( $tiny_metadata[ $key ]['end'] );
				unset( $tiny_metadata[ $key ]['start'] );
			}
		}
		$this->assertEquals(array(
			0 => array( 'input' => array( 'size' => 12345 ), 'output' => array( 'size' => 10000, 'width' => 4000, 'height' => 3000 ) ),
			'thumbnail' => array(),
			'medium' => array(),
			'large' => array( 'input' => array( 'size' => 10000 ), 'output' => array( 'size' => 6789, 'width' => 1024, 'height' => 1024 ) ),
			'post-thumbnail' => array( 'input' => array( 'size' => 1234 ), 'output' => array( 'size' => 1000, 'width' => 800, 'height' => 500 ) ),
		), $tiny_metadata);
	}

	public function test_should_handle_compress_exceptions() {
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "image/jpeg";' ) );

		$this->compressor->expects( $this->exactly( 3 ) )->method( 'compress_file' )->will(
			$this->throwException( new Tiny_Exception( 'Does not appear to be a PNG or JPEG file', 'BadSignature' ) )
		);

		$this->subject->compress_on_upload( $this->wp->getTestMetadata(), 1 );

		$tiny_metadata = $this->wp->getMetadata( 1, Tiny_Image::META_KEY, true );
		foreach ( $tiny_metadata as $key => $values ) {
			if ( ! empty( $values ) ) {
				$this->assertEquals( time(), $values['timestamp'], 2 );
				unset( $tiny_metadata[ $key ]['timestamp'] );
			}
		}
		$this->assertEquals(array(
			0 => array( 'error' => 'BadSignature', 'message' => 'Does not appear to be a PNG or JPEG file' ),
			'thumbnail' => array(),
			'medium' => array(),
			'large' => array( 'error' => 'BadSignature', 'message' => 'Does not appear to be a PNG or JPEG file' ),
			'post-thumbnail' => array( 'error' => 'BadSignature', 'message' => 'Does not appear to be a PNG or JPEG file' ),
		), $tiny_metadata);
	}

	public function test_should_return_if_no_compressor() {
		$this->subject->set_compressor( null );
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "image/png";' ) );
		$this->compressor->expects( $this->never() )->method( 'compress_file' );

		$this->subject->compress_on_upload( $this->wp->getTestMetadata(), 1 );
	}

	public function test_should_return_if_no_image() {
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "video/webm";' ) );
		$this->compressor->expects( $this->never() )->method( 'compress_file' );

		$this->subject->compress_on_upload( $this->wp->getTestMetadata(), 1 );
	}

	public function test_wrong_metadata_should_not_show_warnings() {
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "image/png";' ) );
		$this->compressor->expects( $this->exactly( 1 ) )->method( 'compress_file' )->will(
			$this->returnCallback( array( $this, 'success_compress' ) )
		);

		$testmeta = $this->wp->getTestMetadata();
		$testmeta['sizes'] = 0;

		$this->subject->compress_on_upload( $testmeta, 1 );
	}

	public function test_wrong_metadata_should_save_tiny_metadata() {
		$this->wp->stub( 'get_post_mime_type', create_function( '$i', 'return "image/png";' ) );
		$this->compressor->expects( $this->exactly( 1 ) )->method( 'compress_file' )->will(
			$this->returnCallback( array( $this, 'success_compress' ) )
		);

		$testmeta = $this->wp->getTestMetadata();
		$testmeta['sizes'] = 0;

		$this->subject->compress_on_upload( $testmeta, 1 );
		$this->assertEquals( 2, count( $this->wp->getCalls( 'update_post_meta' ) ) );
	}
}
