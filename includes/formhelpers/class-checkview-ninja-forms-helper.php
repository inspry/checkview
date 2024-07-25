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
				'ninja_forms_after_submission',
				array(
					$this,
					'checkview_clone_entry',
				),
				99,
				1
			);
			add_filter(
				'akismet_get_api_key',
				'__return_null',
				-10
			);
			add_filter(
				'ninja_forms_display_fields',
				array( $this, 'maybe_remove_v2_field' ),
				10,
				2
			);

			add_filter(
				'ninja_forms_form_fields',
				array(
					$this,
					'checkview_maybe_remove_v2_field',
				),
				20
			);
			add_filter(
				'ninja_forms_validate_fields',
				function ( $check, $data ) {
					return false;
				},
				99,
				2
			);

			add_filter(
				'cfturnstile_whitelisted',
				'__return_true',
				999
			);
			add_filter(
				'ninja_forms_action_recaptcha__verify_response',
				'__return_true',
				99
			);
			if ( defined( 'TEST_EMAIL' ) ) {
				add_filter(
					'ninja_forms_action_email_send',
					array(
						$this,
						'checkview_inject_email',
					),
					99,
					5
				);
			}
			// bypass hcaptcha.
			add_filter( 'hcap_activate', '__return_false' );
		}

		/**
		 * Injects email to Ninnja forms supported emails.
		 *
		 * @param string $sent status of emai.
		 * @param array  $action_settings settings for actions.
		 * @param string $message message to be sent.
		 * @param array  $headers headers details.
		 * @param array  $attachments attachements if any.
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
			$entry_id = isset( $form_data['actions']['save']['sub_id'] ) ? $form_data['actions']['save']['sub_id'] : 0;

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
			$form_fields      = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where post_id=%d', $entry_id ) );
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

		/**
		 * Remove v2 reCAPTCHA fields if still configured, when using the v3 Action
		 *
		 * @param array $fields fields of the form.
		 *
		 * @return array
		 */
		public function checkview_maybe_remove_v2_field( $fields ) {
			foreach ( $fields as $key => $field ) {
				if ( 'recaptcha' === $field->get_setting( 'type' ) || 'hcaptcha' === $field->get_setting( 'type' ) || 'akismet' === $field->get_setting( 'type' ) ) {
					// Remove v2 reCAPTCHA, hcaptcha fields if still configured.
					unset( $fields[ $key ] );
				}
			}
			return $fields;
		}

		/**
		 * Removes V2 field.
		 *
		 * @param array $fields fields.
		 * @param int   $form_id form id.
		 * @return fields
		 */
		public function maybe_remove_v2_field( $fields, $form_id ) {
			foreach ( $fields as $key => $field ) {
				if ( 'hcaptcha-for-ninja-forms' === $field['type'] ) {
					// Remove v2 reCAPTCHA fields if still configured.
					unset( $fields[ $key ] );
				}
			}
			return $fields;
		}
	}

	$checkview_ninjaforms_helper = new Checkview_Ninja_Forms_Helper();
}
