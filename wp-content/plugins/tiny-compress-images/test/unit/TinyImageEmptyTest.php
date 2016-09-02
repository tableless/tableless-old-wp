<?php

require_once dirname( __FILE__ ) . '/TinyTestCase.php';

class Tiny_Image_Empty_Test extends Tiny_TestCase {
	public function set_up() {
		parent::set_up();

		$this->wp->createImagesFromJSON( $this->json( 'image_filesystem_data' ) );
		$this->wp->setTinyMetadata( 1, '' );
		$this->subject = new Tiny_Image( new Tiny_Settings(), 1, $this->json( '_wp_attachment_metadata' ) );
	}

	public function test_get_savings() {
		$this->assertEquals( 0, $this->subject->get_savings( $this->subject->get_statistics() ) );
	}

	public function test_get_statistics() {
		$this->assertEquals( array(
			'initial_total_size' => 328670,
			'optimized_total_size' => 328670,
			'image_sizes_optimized' => 0,
			'available_unoptimized_sizes' => 4,
		), $this->subject->get_statistics() );
	}
}
