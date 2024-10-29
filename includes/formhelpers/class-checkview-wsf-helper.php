<?php
/**
 * Fired if WSforms is active.
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

if ( ! class_exists( 'Checkview_WSF_Helper' ) ) {
	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Helps in WS Forms management.
	 *
	 * @package    Checkview
	 * @subpackage Checkview/includes/formhelpers
	 * @author     Check View <support@checkview.io>
	 */
	class Checkview_WSF_Helper {
		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Checkview_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		public $loader;
		/**
		 * Initializes the class constructor.
		 */
		public function __construct() {
			$this->loader = new Checkview_Loader();
			add_action(
				'wsf_submit_post_complete',
				array(
					$this,
					'checkview_clone_entry',
				),
				10,
				1
			);
			add_filter(
				'akismet_get_api_key',
				'__return_null',
				-10
			);

			add_filter(
				'cfturnstile_whitelisted',
				'__return_true',
				999
			);
			if ( defined( 'TEST_EMAIL' ) ) {
				add_filter(
					'wsf_action_email_to',
					array(
						$this,
						'checkview_inject_email',
					),
					99,
					4
				);
			}

			add_filter(
				'wsf_pre_render',
				array(
					$this,
					'checkview_remove_unwanted_fields',
				),
				99,
				2
			);
		}

		/**
		 * Injects email to WS forms supported emails.
		 *
		 * @param array  $to An array of email addresses in RFC 2822 format to send the email to.
		 * @param object $form The form object.
		 * @param string $submit The submit object.
		 * @param array  $action The action configuration.
		 * @return bool
		 */
		public function checkview_inject_email( $to, $form, $submit, $action ) {
			$to_array = array(
				'CheckView <' . TEST_EMAIL . '>',
			);
			return $to_array;
		}
		/**
		 * Clones entry after forms submission.
		 *
		 * @param array $form_data form data.
		 * @return void
		 */
		public function checkview_clone_entry( $form_data ) {
			global $wpdb;

			$form_id  = $form_data->form_id;
			$entry_id = $form_data->id;

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
				'form_type'    => 'WSForms',
			);
			$entry_table = $wpdb->prefix . 'cv_entry';
			$wpdb->insert( $entry_table, $entry_data );
			$inserted_entry_id = $wpdb->insert_id;

			// Insert entry meta.
			$entry_meta_table = $wpdb->prefix . 'cv_entry_meta';
			$field_id_prefix  = 'wsf';
			$tablename        = $wpdb->prefix . 'wsf_submit_meta';
			$form_fields      = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where parent_id=%d', $entry_id ) );
			foreach ( $form_fields as $field ) {
				if ( ! in_array( $field->meta_key, array( '_form_id', 'post_id', 'wsf_meta_key_hidden' ) ) ) {
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

			// remove test entry from WS form.
			$ws_form_submit          = new WS_Form_Submit();
			$ws_form_submit->id      = $entry_id;
			$ws_form_submit->form_id = $form_id;
			$ws_form_submit->db_delete( true, true, true );

			// Test completed So Clear sessions.
			complete_checkview_test( $checkview_test_id );
		}

		/**
		 * Undocumented function
		 *
		 * @param [Form Object] $form Form Object The form object.

		 * @param [bool]        $preview Boolean Whether the form rendering is in preview mode.
		 * @return Object $form form object.
		 */
		public function checkview_remove_unwanted_fields( $form, $preview ) {
			$fields = WS_Form_Common::get_fields_from_form( $form, true );

			// Process fields.
			foreach ( $fields as $field ) {

				if ( ! isset( $field->type ) ) {
					continue;
				}
				switch ( $field->type ) {
					case 'recaptcha':
					case 'hcaptcha':
					case 'turnstile':
						// Get keys.
						$field_key   = $field->field_key;
						$section_key = $field->section_key;
						$group_key   = $field->group_key;
						unset( $form->groups[ $group_key ]->sections[ $section_key ]->fields[ $field_key ] );
						break;
					default:
						break;
				}
			}
			// Return value.
			return $form;
		}
	}

	$checkview_wsf_helper = new Checkview_WSF_Helper();
}
