<?php

require_once(dirname(__FILE__) . "/TinyTestCase.php");

class Tiny_Test_Base extends Tiny_WP_Base {
}

class Tiny_WP_Base_Test extends TinyTestCase {

    public function setUp() {
        parent::setUp();
        $this->subject = new Tiny_Test_Base();
    }

    public function testShouldAddInitHooks() {
        $this->assertEquals(array(
                array('init', array($this->subject, 'init')),
                array('admin_init', array($this->subject, 'admin_init'))
            ),
            $this->wp->getCalls('add_action')
        );
    }
}
