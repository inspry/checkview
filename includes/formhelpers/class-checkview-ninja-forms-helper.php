<?php
/**
 * Fired if ninjaforms is active.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    Checkview
 * @subpackage Checkview/includes/formhelpers
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not Allowed.' );
}

if ( ! class_exists( 'Checkview_Ninja_Forms_Helper' ) ) {
	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Helps in Ninjaforms management.
	 *
	 * @package    Checkview
	 * @subpackage Checkview/includes/formhelpers
	 * @author     Check View <support@checkview.io>
	 */
	class Checkview_Ninja_Forms_Helper {
		/**
		 * Initializes the class constructor.
		 */
		public function __construct() {
			$this->loader->add_action(
				'ninja_forms_after_submission',
				$this,
				'checkview_clone_entry',
				99,
				1
			);
			if ( defined( 'TEST_EMAIL' ) ) {
				$this->loader->add_filter(
					'ninja_forms_action_email_send',
					$this,
					'checkview_inject_email',
					99,
					5
				);
			}
		}
		/**
		 * Injects email to Ninnja forms supported emails.
		 *
		 * @param [type] $sent status of emai.
		 * @param [type] $action_settings settings for actions.
		 * @param [type] $message message to be sent.
		 * @param [type] $headers headers details.
		 * @param [type] $attachments attachements if any.
		 * @return bool
		 */
		public function checkview_inject_email( $sent, $action_settings, $message, $headers, $attachments ) {
			wp_mail( TEST_EMAIL, wp_strip_all_tags( $action_settings['email_subject'] ), $message, $headers, $attachments );
			return true;
		}
		/**
		 * Clones entry after forms submission.
		 *
		 * @param array $form_data form data.
		 * @return void
		 */
		public function checkview_clone_entry( $form_data ) {
			global $wpdb;

			$form_id  = $form_data['form_id'];
			$entry_id = $form_data['actions']['save']['sub_id'];

			$checkview_test_id = get_checkview_test_id();

			if ( empty( $checkview_test_id ) ) {
				$checkview_test_id = $form_id . gmdate( 'Ymd' );
			}

			// Insert Entry.
			$entry_data  = array(
				'form_id'      => $form_id,
				'status'       => 'publish',
				'source_url'   => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
				'date_created' => current_time( 'mysql' ),
				'date_updated' => current_time( 'mysql' ),
				'uid'          => $checkview_test_id,
				'form_type'    => 'NinjaForms',
			);
			$entry_table = $wpdb->prefix . 'cv_entry';
			$wpdb->insert( $entry_table, $entry_data );
			$inserted_entry_id = $wpdb->insert_id;

			// Insert entry meta.
			$entry_meta_table = $wpdb->prefix . 'cv_entry_meta';
			$field_id_prefix  = 'nf';
			$tablename        = $wpdb->prefix . 'postmeta';
			$form_fields      = $wpdb->get_results( $wpdb->prepare( 'Select * from %s where post_id=%d', $tablename, $entry_id ) );
			foreach ( $form_fields as $field ) {
				if ( ! in_array( $field->meta_key, array( '_form_id', '_seq_num' ) ) ) {
					$entry_metadata = array(
						'uid'        => $checkview_test_id,
						'form_id'    => $form_id,
						'entry_id'   => $entry_id,
						'meta_key'   => $field_id_prefix . str_replace( '_', '-', $field->meta_key ),
						'meta_value' => $field->meta_value,
					);
					$wpdb->insert( $entry_meta_table, $entry_metadata );
				}
			}

			// remove test entry from ninja form.
			wp_delete_post( $entry_id, true );

			// Test completed So Clear sessions.
			complete_checkview_test();
		}
	}

	$checkview_ninjaforms_helper = new Checkview_Ninja_Forms_Helper();
}
