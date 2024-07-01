<?php

class Test_Checkview_Activator extends WP_UnitTestCase {

    public function setUp(): Void {
        parent::setUp();
        require_once CHECKVIEW_PLUGIN_DIR . 'includes/class-checkview-activator.php';
    }
    public function test_activate() {
        update_option( 'siteurl', 'http://testsite.local' );
        update_option( 'home', 'http://testsite.local' );
        global $wpdb;
        $wpdb->base_prefix = 'wp_';
        //$this->expectNotToPerformAssertions();
        Checkview_Activator::activate();
        $cv_entry_table = $wpdb->prefix . 'cv_entry';
        $cv_entry_meta_table = $wpdb->prefix . 'cv_entry_meta';
        $cv_session_table = $wpdb->prefix . 'cv_session';
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_entry_table'" ) === $cv_entry_table );
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_entry_meta_table'" ) === $cv_entry_meta_table );
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_session_table'" ) === $cv_session_table );
    }

    public function test_checkview_run_sql() {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $cv_entry_table = $wpdb->prefix . 'cv_entry';
        $cv_entry_meta_table = $wpdb->prefix . 'cv_entry_meta';
        $cv_session_table = $wpdb->prefix . 'cv_session';
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_entry_table'" ) === $cv_entry_table );
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_entry_meta_table'" ) === $cv_entry_meta_table );
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_session_table'" ) === $cv_session_table );
        Checkview_Activator::checkview_run_sql();
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_entry_table'" ) === $cv_entry_table );
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_entry_meta_table'" ) === $cv_entry_meta_table );
        $this->assertTrue( $wpdb->get_var( "SHOW TABLES LIKE '$cv_session_table'" ) === $cv_session_table );
    }
}