<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\content\LargeFileContent;

class Tiny_Plugin_Test extends TinyTestCase {
    public function setUp() {
        parent::setUp();
        $this->subject = new Tiny_Plugin();
        $this->subject->init();
        $this->compressor = $this->getMockBuilder('TestCompressor')
                                 ->setMethods(array('compress_file'))
                                 ->getMock();
        $this->subject->set_compressor($this->compressor);

        $this->wp->addOption("tinypng_api_key", "test123");
        $this->wp->addOption("tinypng_sizes[0]", "on");
        $this->wp->addOption("tinypng_sizes[large]", "on");
        $this->wp->addOption("tinypng_sizes[post-thumbnail]", "on");

        $this->wp->addImageSize('post-thumbnail', array('width' => 825, 'height' => 510));
        $this->wp->createImages();
    }

    public function successCompress($file) {
        if (preg_match('#[^-]+-([^.]+)[.](png|jpe?g)$#', basename($file), $match)) {
            $key = $match[1];
        } else {
            $key = null;
        }

        $input = filesize($file);
        switch ($key) {
            case "thumbnail":
                $output = 81;
                $width = '150';
                $height = '150';
                break;
            case "medium":
                $output = 768;
                $width = '300';
                $height = '300';
                break;
            case "large":
                $output = 6789;
                $width = '1024';
                $height = '1024';
                break;
            case "post-thumbnail":
                $output = 1000;
                $width = '800';
                $height = '500';
                break;
            default:
                $output = 10000;
                $width = '4000';
                $height = '3000';
        }
        $this->vfs->getChild(vfsStream::path($file))->truncate($output);
        return array('input' => array('size' => $input), 'output' => array('size' => $output, 'width' => $width, 'height' => $height));
    }

    public function testInitShouldAddFilters() {
        $this->assertEquals(array(
            array('jpeg_quality', array('Tiny_Plugin', 'jpeg_quality')),
            array('wp_editor_set_quality', array('Tiny_Plugin', 'jpeg_quality')),
            array('wp_generate_attachment_metadata', array($this->subject, 'compress_attachment'), 10, 2),
        ), $this->wp->getCalls('add_filter'));
    }

    public function testCompressShouldRespectSettings() {
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "image/png";'));
        $this->compressor->expects($this->exactly(3))->method('compress_file')->withConsecutive(
            array($this->equalTo('vfs://root/wp-content/uploads/14/01/test.png')),
            array($this->equalTo('vfs://root/wp-content/uploads/14/01/test-large.png')),
            array($this->equalTo('vfs://root/wp-content/uploads/14/01/test-post-thumbnail.png'))
        )->will($this->returnCallback(array($this, 'successCompress')));
        $this->subject->compress_attachment($this->wp->getTestMetadata(), 1);
    }

    public function testCompressShouldNotCompressTwice() {
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "image/png";'));

        $testmeta = $this->wp->getTestMetadata();
        $meta = new Tiny_Metadata(1, $testmeta);
        $meta->add_request();
        $meta->add_response(self::successCompress('vfs://root/wp-content/uploads/14/01/test.png'));
        $meta->add_request('large');
        $meta->add_response(self::successCompress('vfs://root/wp-content/uploads/14/01/test-large.png'), 'large');
        $meta->update();

        $this->compressor->expects($this->once())->method('compress_file')->withConsecutive(
            array($this->equalTo('vfs://root/wp-content/uploads/14/01/test-post-thumbnail.png'))
        )->will($this->returnCallback(array($this, 'successCompress')));
        $this->subject->compress_attachment($testmeta, 1);
    }

    public function testCompressWhenFileChanged() {
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "image/png";'));

        $testmeta = $this->wp->getTestMetadata();
        $meta = new Tiny_Metadata(1, $testmeta);
        $meta->add_request();
        $meta->add_response(self::successCompress('vfs://root/wp-content/uploads/14/01/test.png'));
        $meta->add_request('large');
        $meta->add_response(self::successCompress('vfs://root/wp-content/uploads/14/01/test-large.png'), 'large');
        $meta->add_request('post-thumbnail');
        $meta->add_response(self::successCompress('vfs://root/wp-content/uploads/14/01/test-post-thumbnail.png'), 'post-thumbnail');
        $meta->update();

        $this->vfs->getChild('wp-content/uploads/14/01/test-large.png')->truncate(100000);

        $this->compressor->expects($this->once())->method('compress_file')->withConsecutive(
            array($this->equalTo('vfs://root/wp-content/uploads/14/01/test-large.png'))
        )->will($this->returnCallback(array($this, 'successCompress')));
        $this->subject->compress_attachment($testmeta, 1);
    }

    public function testCompressShouldUpdateMetadata() {
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "image/png";'));
        $this->compressor->expects($this->exactly(3))->method('compress_file')->will(
            $this->returnCallback(array($this, 'successCompress'))
        );

        $this->subject->compress_attachment($this->wp->getTestMetadata(), 1);

        $metadata = $this->wp->getMetadata(1, 'tiny_compress_images', true);
        foreach ($metadata as $key => $values) {
            $this->assertBetween(-1, +1, $values['end'] - time());
            unset($metadata[$key]['end']);
            unset($metadata[$key]['start']);
        }
        $this->assertEquals(array(
            0 => array('input' => array('size' => 12345), 'output' => array('size' => 10000, 'width' => 4000, 'height' => 3000)),
            'large' => array('input' => array('size' => 10000), 'output' => array('size' => 6789, 'width' => 1024, 'height' => 1024)),
            'post-thumbnail' => array('input' => array('size' => 1234), 'output' => array('size' => 1000, 'width' => 800, 'height' => 500)),
        ), $metadata);
    }

    public function testShouldHandleCompressExceptions() {
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "image/jpeg";'));

        $this->compressor->expects($this->exactly(3))->method('compress_file')->will(
            $this->throwException(new Tiny_Exception('Does not appear to be a PNG or JPEG file', 'BadSignature'))
        );

        $this->subject->compress_attachment($this->wp->getTestMetadata(), 1);

        $metadata = $this->wp->getMetadata(1, 'tiny_compress_images', true);
        foreach ($metadata as $key => $values) {
            $this->assertEquals(time(), $values['timestamp'], 2);
            unset($metadata[$key]['timestamp']);
        }
        $this->assertEquals(array(
            0 => array('error' => 'BadSignature', 'message' => 'Does not appear to be a PNG or JPEG file'),
            'large' => array('error' => 'BadSignature', 'message' => 'Does not appear to be a PNG or JPEG file'),
            'post-thumbnail' => array('error' => 'BadSignature', 'message' => 'Does not appear to be a PNG or JPEG file'),
        ), $metadata);
    }

    public function testShouldReturnIfNoCompressor() {
        $this->subject->set_compressor(null);
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "image/png";'));
        $this->compressor->expects($this->never())->method('compress_file');

        $this->subject->compress_attachment($this->wp->getTestMetadata(), 1);
    }

    public function testShouldReturnIfNoImage() {
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "video/webm";'));
        $this->compressor->expects($this->never())->method('compress_file');

        $this->subject->compress_attachment($this->wp->getTestMetadata(), 1);
    }

    public function testWrongMetadataShouldNotShowWarnings() {
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "image/png";'));
        $this->compressor->expects($this->exactly(1))->method('compress_file')->will(
            $this->returnCallback(array($this, 'successCompress'))
        );

        $testmeta = $this->wp->getTestMetadata();
        $testmeta['sizes'] = 0;

        $this->subject->compress_attachment($testmeta, 1);
    }

    public function testWrongMetadataShouldSaveTinyMetadata() {
        $this->wp->stub('get_post_mime_type', create_function('$i', 'return "image/png";'));
        $this->compressor->expects($this->exactly(1))->method('compress_file')->will(
            $this->returnCallback(array($this, 'successCompress'))
        );

        $testmeta = $this->wp->getTestMetadata();
        $testmeta['sizes'] = 0;

        $this->subject->compress_attachment($testmeta, 1);
        $this->assertEquals(2, count($this->wp->getCalls('update_post_meta')));
    }
}
