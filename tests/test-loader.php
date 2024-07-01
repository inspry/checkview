<?php

class Test_Checkview_Loader extends WP_UnitTestCase {

    public function setUp() : Void {
        parent::setUp();
        $this->loader = new Checkview_Loader();
    }

    public function test_add_action() {
        $hook = 'test_hook';
        $component = new StdClass();
        $callback = 'test_callback';
        $priority = 10;
        $accepted_args = 1;
        $this->loader->add_action( $hook, $component, $callback, $priority, $accepted_args );
        $actions = $this->loader->actions;
        $this->assertArrayHasKey( 0, $actions );
        $this->assertEquals( $component, $actions[0]['component'] );
        $this->assertEquals( $callback, $actions[0]['callback'] );
        $this->assertEquals( $priority, $actions[0]['priority'] );
        $this->assertEquals( $accepted_args, $actions[0]['accepted_args'] );
    }

    public function test_add_filter() {
        $hook = 'test_hook';
        $component = new StdClass();
        $callback = 'test_callback';
        $priority = 10;
        $accepted_args = 1;
        $this->loader->add_filter( $hook, $component, $callback, $priority, $accepted_args );
        $filters = $this->loader->filters;
        $this->assertArrayHasKey( 0 , $filters );
        $this->assertEquals( $component, $filters[0]['component'] );
        $this->assertEquals( $callback, $filters[0]['callback'] );
        $this->assertEquals( $priority, $filters[0]['priority'] );
        $this->assertEquals( $accepted_args, $filters[0]['accepted_args'] );
    }

    public function test_run() {
        $hook = 'test_hook';
        $component = new StdClass();
        $callback = 'test_callback';
        $priority = 10;
        $accepted_args = 1;
        $this->loader->add_action( $hook, $component, $callback, $priority, $accepted_args );
        $this->loader->add_filter( $hook, $component, $callback, $priority, $accepted_args );
        $this->loader->run();
    
        
        $this->assertEquals( 10, has_action( $hook, array( $component, $callback ) ) );
        $this->assertEquals( 10, has_filter( $hook, array( $component, $callback ) ) );

        $this->assertTrue( has_action( $hook ) );
        $this->assertTrue( has_filter( $hook ) );
    }
}