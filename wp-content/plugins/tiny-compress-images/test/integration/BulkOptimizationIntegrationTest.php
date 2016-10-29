<?php

require_once dirname( __FILE__ ) . '/IntegrationTestCase.php';

class BulkOptimizationIntegrationTest extends IntegrationTestCase {
	public function tear_down() {
		parent::tear_down();
		clear_settings();
		clear_uploads();
	}

	public function test_summary_should_display_correct_values_for_empty_library() {
		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium' ) );

		$this->visit( '/wp-admin/upload.php?page=tiny-bulk-optimization' );

		$this->assertEquals( '0', $this->find( '#uploaded-images' )->getText() );
		$this->assertEquals( '0', $this->find( '#optimizable-image-sizes' )->getText() );
		$this->assertEquals( '$ 0.00', $this->find( '#estimated-cost' )->getText() );
		$this->assertEquals( '0', $this->find( '#optimized-image-sizes' )->getText() );

		$this->assertEquals( '-', $this->find( '#unoptimized-library-size' )->getText() );
		$this->assertEquals( '-', $this->find( '#optimized-library-size' )->getText() );
		$this->assertEquals( '0%', $this->find( '#savings-percentage' )->getText() );

		$this->assertEquals( '0 / 0 (100%)', $this->find( '#compression-progress-bar' )->getText() );
	}

	public function test_summary_should_display_correct_values() {
		$this->set_api_key( 'JPG123' );

		$this->enable_compression_sizes( array() );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->enable_compression_sizes( array( '0' ) );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium' ) );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->visit( '/wp-admin/upload.php?page=tiny-bulk-optimization' );

		$this->assertEquals( '3', $this->find( '#uploaded-images' )->getText() );
		$this->assertEquals( '5', $this->find( '#optimizable-image-sizes' )->getText() );
		$this->assertEquals( '$ 0.00', $this->find( '#estimated-cost' )->getText() );
		$this->assertEquals( '4', $this->find( '#optimized-image-sizes' )->getText() );

		$this->assertRegExp( '/[23](\.\d+)? MB/', $this->find( '#unoptimized-library-size' )->getText() );
		$this->assertRegExp( '/[12](\.\d+)? MB/', $this->find( '#optimized-library-size' )->getText() );
		$this->assertRegExp( '/2\d(\.\d+)?%/', $this->find( '#savings-percentage' )->getText() );

		$this->assertEquals( '4 / 9 (44%)', $this->find( '#compression-progress-bar' )->getText() );
	}

	public function test_start_bulk_optimization_should_optimize_remaining_images() {
		$this->set_api_key( 'JPG123' );

		$this->enable_compression_sizes( array() );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->enable_compression_sizes( array( '0' ) );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->enable_compression_sizes( array( '0', 'thumbnail', 'medium' ) );
		$this->upload_media( 'test/fixtures/input-example.jpg' );

		$this->visit( '/wp-admin/upload.php?page=tiny-bulk-optimization' );

		$this->assertEquals( '5', $this->find( '#optimizable-image-sizes' )->getText() );

		$this->find_button( 'Start Bulk Optimization' )->click();

		$this->wait_for_text(
			'#optimizable-image-sizes',
			'0'
		);

		$this->assertEquals( '9 / 9 (100%)', $this->find( '#compression-progress-bar' )->getText() );
	}

	public function test_should_display_tooltips() {
		$this->assertGreaterThanOrEqual( '1', sizeof($this->find_all( 'div.tip' )));
	}
}
