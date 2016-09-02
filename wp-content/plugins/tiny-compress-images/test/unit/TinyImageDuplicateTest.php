<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Image_Duplicate_Test extends Tiny_TestCase {
	public function set_up() {
		parent::set_up();

		$this->wp->addImageSize( 'medium-2', array( 'width' => 300, 'height' => 300 ) );
		$this->wp->addImageSize( 'custom-thumbnail', array( 'width' => 175, 'height' => 175 ) );
		$this->wp->addImageSize( 'custom-thumbnail-2', array( 'width' => 175, 'height' => 175 ) );

		$this->wp->addOption( 'tinypng_sizes[medium-2]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[custom-thumbnail]', 'off' );
		$this->wp->addOption( 'tinypng_sizes[custom-thumbnail-2]', 'on' );
		$this->wp->addOption( 'tinypng_sizes[custom-thumbnail-3]', 'on' );

		$this->wp->createImagesFromJSON( $this->json( 'image_filesystem_data' ) );
		$this->wp->setTinyMetadata( 1, $this->json( 'image_database_metadata' ) );
		$this->subject = new Tiny_Image( new Tiny_Settings(), 1, $this->json( '_wp_attachment_metadata_duplicates' ) );
	}

	public function test_get_images_should_return_all_images() {
		$this->assertEquals( array(
			Tiny_Image::ORIGINAL,
			'medium',
			'thumbnail',
			'twentyfourteen-full-width',
			'custom-thumbnail',
			'custom-thumbnail-2',
			'custom-thumbnail-3',
			'failed',
			'large',
			'medium-2',
			'small',
		), array_keys( $this->subject->get_image_sizes() ) );
	}

	public function test_filter_images_should_filter_correctly() {
		$this->assertEquals( array(
			Tiny_Image::ORIGINAL,
			'medium',
		), array_keys( $this->subject->filter_image_sizes( 'compressed' ) ) );
	}

	public function test_filter_images_should_filter_duplicates_correctly() {
		$this->assertEquals( array(
			'medium-2',
			'custom-thumbnail',
			'custom-thumbnail-3',
		), array_keys( $this->subject->filter_image_sizes( 'is_duplicate' ) ) );
	}

	public function test_duplicate_images_should_be_linked_to_primary_size() {
		$this->assertEquals(
			'medium',
			$this->subject->get_image_size( 'medium-2' )->duplicate_of_size()
		);

		$this->assertEquals(
			'custom-thumbnail-2',
			$this->subject->get_image_size( 'custom-thumbnail' )->duplicate_of_size()
		);

		$this->assertEquals(
			'custom-thumbnail-2',
			$this->subject->get_image_size( 'custom-thumbnail-3' )->duplicate_of_size()
		);
	}
}
