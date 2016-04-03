<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Metadata_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $this->wp->addOption("tinypng_api_key", "test123");
        $this->wp->addOption("tinypng_sizes[0]", "on");

        $meta = array(Tiny_Metadata::META_KEY => array(
            Tiny_Metadata::ORIGINAL => array(
                "input" => array("size" => 146480),
                "output" => array("size" => 137856)),
            "thumbnail" => array(
                "input" => array("size" => 46480),
                "output" => array("size" => 37856)),
            "medium" => array(
                "input" => array("size" => 66480),
                "output" => array("size" => 57856))
        ));
        $this->wp->setMetadata(1, $meta);
        $this->wp->createImagesFromMeta($this->json("wp_meta_default_sizes"), $meta, 137856);
        $this->subject = new Tiny_Metadata(1, $this->json("wp_meta_default_sizes"));
    }

    public function testUpdateWpMetadataShouldNotUpdateWithNoResizedOriginal() {
        $tiny_meta = new Tiny_Metadata(150, $this->json("wp_meta_sizes_with_same_files"));
        $wp_metadata = array(
            'width' => 2000,
            'height' => 1000
        );
        $this->assertEquals(array('width' => 2000, 'height' => 1000), $tiny_meta->update_wp_metadata($wp_metadata));
    }

    public function testUpdateWpMetadataShouldUpdateWithResizedOriginal() {
        $tiny_meta = new Tiny_Metadata(150, $this->json("wp_meta_sizes_with_same_files"));
        $wp_metadata = array(
            'width' => 2000,
            'height' => 1000
        );
        $tiny_meta->add_request();
        $tiny_meta->add_response(array('output' => array('width' => 200, 'height' => 100)));
        $this->assertEquals(array('width' => 200, 'height' => 100), $tiny_meta->update_wp_metadata($wp_metadata));
    }

    public function testAddRequestShouldIncreaseProccessingCount() {
        $processing_count = $this->subject->get_in_progress_count();
        $this->subject->add_request("large");
        $this->assertEquals($processing_count + 1, $this->subject->get_in_progress_count());
    }

    public function testAddRequestShouldIncreaseProccessingCountEvenIfAlreadyCompressed() {
        $processing_count = $this->subject->get_in_progress_count();
        $this->subject->add_request("thumbnail");
        $this->assertEquals($processing_count + 1, $this->subject->get_in_progress_count());
    }

    public function testAddRequestShouldNotIncreaseSuccessCount() {
        $success_count = $this->subject->get_success_count();
        $this->subject->add_request("large");
        $this->assertEquals($success_count, $this->subject->get_success_count());
    }

    public function testAddResponseShouldIncreaseSuccessCountForCompression() {
        $success_count = $this->subject->get_success_count();
        $this->subject->add_request("large");
        $this->subject->add_response(array("output" => array("size" => 137857)), "large");
        $this->wp->createImage(137857, "2015/09", "tinypng_gravatar-600x600.png");
        $this->assertEquals($success_count + 1, $this->subject->get_success_count());
    }

    public function testAddResponseShouldNotIncreaseSuccessCountIfPhysicalFileIsMissing() {
        $success_count = $this->subject->get_success_count();
        $this->subject->add_request("large");
        $this->subject->add_response(array("output" => array("size" => 137857)), "large");
        $this->assertEquals($success_count, $this->subject->get_success_count());
    }

    public function testAddResponseShouldIncreaseCompressedCount() {
        $compressed_count = count($this->subject->get_compressed_sizes());
        $this->subject->add_request("large");
        $this->subject->add_response(array("output" => array("size" => 137857)), "large");
        $this->wp->createImage(137857, "2015/09", "tinypng_gravatar-600x600.png");
        $this->assertEquals($compressed_count + 1, count($this->subject->get_compressed_sizes()));
    }

    public function testAddResponseShouldIncreaseCompressedCountIfPhysicalFileIsMissing() {
        $compressed_count = count($this->subject->get_compressed_sizes());
        $this->subject->add_request("large");
        $this->subject->add_response(array("output" => array("size" => 137857)), "large");
        $this->assertEquals($compressed_count + 1, count($this->subject->get_compressed_sizes()));
    }

    public function testAddResponseShouldNotIncreaseProcessingCount() {
        $processing_count = $this->subject->get_in_progress_count();
        $this->subject->add_request("large");
        $this->subject->add_response(array("output" => array("size" => 137857)), "large");
        $this->wp->createImage(137857, "2015/09", "tinypng_gravatar-600x600.png");
        $this->assertEquals($processing_count, $this->subject->get_in_progress_count());
    }

    public function testAddExceptionShouldNotIncreaseSuccessCount() {
        $success_count = $this->subject->get_success_count();
        $this->subject->add_request("large");
        $this->subject->add_exception(new Tiny_Exception('Could not download output', 'OutputError'), "large");
        $this->assertEquals($success_count, $this->subject->get_success_count());
    }

    public function testAddExceptionShouldNotIncreaseProcessingCount() {
        $processing_count = $this->subject->get_in_progress_count();
        $this->subject->add_request("large");
        $this->subject->add_exception(new Tiny_Exception('Could not download output', 'OutputError'), "large");
        $this->assertEquals($processing_count, $this->subject->get_in_progress_count());
    }

    public function testIsCompressedShouldReturnTrueForOriginal() {
        $this->assertTrue($this->subject->is_compressed(Tiny_Metadata::ORIGINAL));
    }

    public function testIsCompressedShouldReturnTrueForCompressedSize() {
        $this->assertTrue($this->subject->is_compressed("thumbnail"));
    }

    public function testIsCompressedShouldReturnFalseForUncompressedSize() {
        $this->assertFalse($this->subject->is_compressed("large"));
    }

    public function testIsCompressedShouldReturnFalseWhenFilesizeOnFilesystemDoesNotMatchMeta() {
        $meta = array(Tiny_Metadata::META_KEY => array(
            "thumbnail" => array(
                "input" => array("size" => 46480),
                "output" => array("size" => 37856))
        ));
        $this->wp->setMetadata(2, $meta);
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $tiny_meta = new Tiny_Metadata(2, $this->json("wp_meta_default_sizes"));
        $this->assertFalse($tiny_meta->is_compressed("thumbnail"));
    }

    public function testIsCompressedShouldReturnFalseWhenFileDoesNotExist() {
        $meta = array(Tiny_Metadata::META_KEY => array(
            "no_file" => array(
                "input" => array("size" => 46480),
                "output" => array("size" => 37856))
        ));
        $this->wp->setMetadata(3, $meta);
        $tiny_meta = new Tiny_Metadata(3, $this->json("wp_meta_default_sizes"));
        $this->assertFalse($tiny_meta->is_compressed("no_file"));
    }

    public function testIsCompressedShouldReturnFalseWhenNoMetadata() {
        $this->wp->createImage(37856, "2015/09", "tinypng_gravatar-150x150.png");
        $tiny_meta = new Tiny_Metadata(4, $this->json("wp_meta_default_sizes"));
        $this->assertFalse($tiny_meta->is_compressed("thumbnail"));
    }

    public function testIsCompressingShouldReturnTrue() {
        $meta = array(Tiny_Metadata::META_KEY => array(
            "thumbnail" => array(
                "start" => 1447925134,
                "input" => array("size" => 46480))
        ));
        $this->wp->setMetadata(5, $meta);
        $this->wp->createImage(46480, "2015/09", "tinypng_gravatar-150x150.png");
        $tiny_meta = new Tiny_Metadata(5, $this->json("wp_meta_default_sizes"));
        $this->assertTrue($tiny_meta->is_compressing("thumbnail"));
    }

    public function testIsCompressingShouldReturnFalse() {
        $this->assertFalse($this->subject->is_compressing("thumbnail"));
    }

    public function testIsResizedShouldReturnTrueForResizedImage() {
        $meta = array(Tiny_Metadata::META_KEY => array(
            Tiny_Metadata::ORIGINAL => array(
                "output" => array("size" => 46480, "resized" => true))
        ));
        $this->wp->setMetadata(7, $meta);
        $tiny_meta = new Tiny_Metadata(7, $this->json("wp_meta_default_sizes"));
        $this->assertTrue($tiny_meta->is_resized());
    }

    public function testIsResizedShouldReturnFalseForNotResizedSize() {
        $this->assertFalse($this->subject->is_resized("thumbnail"));
    }

    public function testGetSizes() {
        $this->assertEquals(
            array(Tiny_Metadata::ORIGINAL, "thumbnail", "medium", "large"),
            $this->subject->get_sizes());
    }

    public function testGetSizesWithDuplicates() {
        $tiny_meta = new Tiny_Metadata(150, $this->json("wp_meta_sizes_with_same_files"));
        $this->assertEquals(
            array(Tiny_Metadata::ORIGINAL, "custom-size"),
            $tiny_meta->get_sizes());
    }

    public function testGetSuccessSizes() {
        $this->assertEquals(array(0, "thumbnail", "medium"), $this->subject->get_success_sizes());
    }

    public function testGetSuccessSizesShouldNotIncludeSizeIfNotOnFileSystem() {
        $meta = array(Tiny_Metadata::META_KEY => array(
            "additional_size" => array(
                "start" => 1447925134,
                "end" => 1447925138,
                "input" => array("size" => 46480),
                "output" => array("size" => 37856))
        ));
        $this->wp->setMetadata(6, $meta);
        $tiny_meta = new Tiny_Metadata(6, $this->json("wp_meta_default_sizes"));
        $this->assertEquals(array(), $tiny_meta->get_success_sizes());
    }

    public function testGetCompressedSizes() {
        $this->assertEquals(array(0, "thumbnail", "medium"), $this->subject->get_compressed_sizes());
    }

    public function testGetCompressedSizesShouldIncludeSizeEvenIfNotOnFileSystem() {
        $meta = array(Tiny_Metadata::META_KEY => array(
            "additional_size" => array(
                "start" => 1447925134,
                "end" => 1447925138,
                "input" => array("size" => 46480),
                "output" => array("size" => 37856))
        ));
        $this->wp->setMetadata(6, $meta);
        $tiny_meta = new Tiny_Metadata(6, $this->json("wp_meta_default_sizes"));
        $this->assertEquals(array("additional_size"), $tiny_meta->get_compressed_sizes());
    }

    public function testGetUncompressedSizes() {
        $this->wp->createImage(137857, "2015/09", "tinypng_gravatar-600x600.png");
        $tinify_sizes = array(Tiny_Metadata::ORIGINAL, "thumbnail", "medium", "large");
        $this->assertEquals(
            array_values(array("large")),
            array_values($this->subject->get_uncompressed_sizes($tinify_sizes)));
    }

    public function testGetUncompressedSizesShouldReturnOnlyUniqueSizes() {
        $this->wp->addOption("tinypng_sizes[custom-size]", "on");
        $this->wp->addOption("tinypng_sizes[custom-size-2]", "on");
        $this->wp->addImageSize('custom-size', array('width' => 150, 'height' => 150));
        $this->wp->addImageSize('custom-size-2', array('width' => 150, 'height' => 150));
        $this->wp->createImages(array("150x150" => 37856), 146480, "2015/09", "panda");

        $tiny_meta = new Tiny_Metadata(155, $this->json("wp_meta_sizes_with_same_files"));

        $tinify_sizes = array(Tiny_Metadata::ORIGINAL, "custom-size", "custom-size-2");
        $uncompressed_sizes = array(Tiny_Metadata::ORIGINAL, "custom-size");
        $this->assertEquals($uncompressed_sizes, $tiny_meta->get_uncompressed_sizes($tinify_sizes));
    }

    public function testGetInProgressSizesShouldReturnEmptyArray() {
        $this->assertEquals(array(), $this->subject->get_in_progress_sizes());
    }

    public function testGetInProgressSizesShouldReturnSizeBeingCompressed() {
        $meta = array(Tiny_Metadata::META_KEY => array(
            "thumbnail" => array(
                "start" => 1447925134,
                "input" => array("size" => 46480))
        ));
        $this->wp->setMetadata(5, $meta);
        $tiny_meta = new Tiny_Metadata(5, $this->json("wp_meta_default_sizes"));
        $this->assertEquals(array("thumbnail"), $tiny_meta->get_in_progress_sizes());
    }

    public function testGetLatestErrorShouldReturnMessage() {
        $processing_count = $this->subject->get_in_progress_count();
        $this->subject->add_request("large");
        $this->subject->add_exception(new Tiny_Exception('Could not download output', 'OutputError'), "large");
        $this->assertEquals("Could not download output", $this->subject->get_latest_error());
    }
}
