<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Metadata_Image_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();

        $meta = array(Tiny_Metadata::META_KEY => array(
            Tiny_Metadata::ORIGINAL => array(
                "input" => array("size" => 146480),
                "output" => array("size" => 137856, "resized" => true),
                'end' => 1447925138,
                'start' => 1447925134),
            "thumbnail" => array(
                "input" => array("size" => 46480),
                "output" => array("size" => 37856),
                'timestamp' => 1447925244),
            "medium" => array(
                "input" => array("size" => 66480),
                "output" => array("size" => 57856)),
            "small" => array(
                "input" => array("size" => 66480),
                'start' => 1447925134),
        ));
        $this->wp->setMetadata(1, $meta);
        $this->wp->createImagesFromMeta($this->json("wp_meta_default_sizes"), $meta, 137856);
        $metadata = new Tiny_Metadata(1, $this->json("wp_meta_default_sizes"));
        $this->original = $metadata->get_image();
        $this->thumbnail = $metadata->get_image('thumbnail');
        $this->small = $metadata->get_image('small');
        $this->medium = $metadata->get_image('medium');
        $this->large = $metadata->get_image('large');
    }

    public function testEndTimeShouldReturnEndFromMeta() {
        $this->assertEquals(1447925138, $this->original->end_time());
    }

    public function testEndTimeShouldReturnEndFromTimestampIfEndIsUnavailable() {
        $this->assertEquals(1447925244, $this->thumbnail->end_time());
    }

    public function testEndTimeShouldReturnNullIfUnavailable() {
        $this->assertEquals(null, $this->medium->end_time());
    }

    public function testAddRequestShouldAddStartTime() {
        $this->large->add_request();
        $this->assertEqualWithinDelta(time(), $this->large->meta['start'], 2);
    }

    public function testAddRequestShouldUnsetPreviousResponse() {
        $this->medium->add_request();
        $this->assertEqualWithinDelta(time(), $this->medium->meta['start'], 2);
    }

    public function testAddResponseShouldAddEndTime() {
        $this->large->add_request();
        $this->large->add_response(array('input' => array('size' => 1024), 'output' => array('size' => 1024)));
        $this->assertEqualWithinDelta(time(), $this->large->meta['end'], 2);
    }

    public function testAddResponseShouldResponse() {
        $this->large->add_request();
        $this->large->add_response(array('input' => array('size' => 1024), 'output' => array('size' => 1024)));
        $actual = $this->large->meta;
        unset($actual['end']);
        $this->assertEquals(array('input' => array('size' => 1024), 'output' => array('size' => 1024)), $actual);
    }

    public function testAddResponseShouldNotAddIfNoRequestWasMade() {
        $this->large->add_response(array('input' => array('size' => 1024), 'output' => array('size' => 1024)));
        $this->assertEquals(null, $this->large->meta);
    }

    public function testAddExceptionShouldAddMessageAndError() {
        $this->large->add_request();
        $this->large->add_exception(new Tiny_Exception("Image could not be found", "Not found"));
        unset($this->large->meta['timestamp']);
        $this->assertEquals(array('error' => 'Not found', 'message' => 'Image could not be found'),  $this->large->meta);
    }

    public function testAddExceptionShouldAddTimestamp() {
        $this->large->add_request();
        $this->large->add_exception(new Tiny_Exception("Image could not be found", "Not found"));
        $this->assertEqualWithinDelta(time(), $this->large->meta['timestamp'], 2);
    }

    public function testAddExceptionShouldNotAddIfNoRequestWasMade() {
        $this->large->add_exception(new Tiny_Exception("Image could not be found", "Not found"));
        unset($this->large->meta['timestamp']);
        $this->assertEquals(null,  $this->large->meta);
    }

    public function testImageHasBeenCompressedIfMetaHasOutput() {
        $this->assertTrue($this->original->has_been_compressed());
    }

    public function testImageHasNotBeenCompressedIfMetaDoesNotHaveOutput() {
        $this->assertFalse($this->large->has_been_compressed());
    }

    public function testImageDoesNotStillExistIfFileDoesNotExist() {
        $image = new Tiny_Metadata_Image('does_not_exist');
        $this->assertFalse($image->still_exists());
    }

    public function testImageStillExistsIfFileExists() {
        $this->assertTrue($this->original->still_exists());
    }

    public function testImageCompressedShouldReturnTrueIfFileExistsAndSizeIsSame() {
        $this->assertTrue($this->original->compressed());
    }

    public function testImageCompressedShouldReturnFalseIfSizeIsInequalToMeta() {
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $this->assertFalse($this->thumbnail->compressed());
    }

    public function testImageModifiedShouldReturnTrueIfSizeIsInequalToMeta() {
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $this->assertTrue($this->thumbnail->modified());
    }

    public function testImageModifiedShouldReturnFalseIfCompressedCorrectly() {
        $this->assertFalse($this->original->modified());
    }

    public function testUncompressedShouldReturnTrueIfImageExistAndIsUncompressed() {
        $this->wp->createImage(37857, "2015/09", "tinypng_gravatar-150x150.png");
        $this->assertTrue($this->thumbnail->uncompressed());
    }

    public function testUncompressedShouldReturnFalseIfImageExistAndIsCompressed() {
        $this->assertFalse($this->original->uncompressed());
    }

    public function testInProgressShouldReturnTrueIfMetaHaveStartAndNotOutput() {
        $this->assertTrue($this->small->in_progress());
    }

    public function testInProgressShouldReturnFalseIfMetaHaveStartAndOutput() {
        $this->assertFalse($this->original->in_progress());
    }

    public function testResizedShouldReturnTrueIfMetaHaveOutputAndResized() {
        $this->assertTrue($this->original->resized());
    }

    public function testResizedShouldReturnFalseIfMetaHaveOutputAndNotResized() {
        $this->assertFalse($this->thumbnail->resized());
    }
}
