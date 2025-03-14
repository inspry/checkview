<?php
/**
 * Checkview_Elementor_Helper class
 *
 * @since 1.0.0
 *
 * @package Checkview
 * @subpackage Checkview/includes/formhelpers
 */

if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not Allowed.' );
}

if ( ! class_exists( 'Checkview_Elementor_Helper' ) ) {
	/**
	 * Adds support for Contact Form 7.
	 *
	 * During CheckView tests, modifies Contact Form 7 hooks, overwrites the
	 * recipient email address, and handles test cleanup.
	 *
	 * @package Checkview
	 * @subpackage Checkview/includes/formhelpers
	 * @author Check View <support@checkview.io>
	 */
	class Checkview_Elementor_Helper {
		/**
		 * Loader.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @var Checkview_Loader $loader Maintains and registers all hooks for the plugin.
		 */
		public $loader;
		/**
		 * Constructor.
		 *
		 * Initiates loader property, adds hooks.
		 */
		public function __construct() {
			$this->loader = new Checkview_Loader();
			if ( defined( 'TEST_EMAIL' ) ) {
				// change emial address.
				add_filter(
					'wpelementor_mail_components',
					array(
						$this,
						'checkview_inject_email',
					),
					99,
					1
				);
			}
			add_action(
				'wpelementor_before_send_mail',
				array(
					$this,
					'checkview_elementor_before_send_mail',
				),
				99,
				1
			);

			add_action(
				'elementor_pro/forms/new_record',
				array(
					$this,
					'checkview_clone_elementor_entry',
				),
				999,
				2
			);
			add_filter(
				'wpelementor_spam',
				array(
					$this,
					'checkview_return_false',
				),
				999
			);
			add_filter(
				'wpelementor_skip_spam_check',
				'__return_true',
				999
			);

			add_filter(
				'wpelementor_submission_has_disallowed_words',
				'__return_false',
				999,
				2
			);
			add_filter(
				'cfturnstile_whitelisted',
				'__return_true',
				999
			);
			add_filter(
				'akismet_get_api_key',
				'__return_null',
				-10
			);

			// Bypass hCaptcha.
			add_filter(
				'hcap_activate',
				'__return_false'
			);
			add_filter(
				'wpelementor_flamingo_submit_if',
				array(
					$this,
					'checkview_bypass_flamingo',
				),
				99
			);
		}

		/**
		 * Stores the test results and finishes the testing session.
		 *
		 * @param Object $form_tag Form object by CFS.
		 * @return void
		 */
		public function checkview_clone_elementor_entry( $record, $handler ) {

			global $wpdb;
			$checkview_test_id = get_checkview_test_id();

			if ( empty( $checkview_test_id ) ) {
				$checkview_test_id = $form_id . gmdate( 'Ymd' );
			}
            
			// Check if it's a form submission
			if ( ! $record || ! $handler ) {
				return;
			}

			$form_data = $record->get_formatted_data();
			$email     = isset( $form_data['Email'] ) ? sanitize_email( $form_data['Email'] ) : '';

			// Define keywords or email addresses to simulate submission for
			$simulate_keywords = array( 'checkview', 'io', 'test-mail' );
			$simulate_emails   = array( 'test@example.com', '@test-mail.checkview.io' );

			// Check if email matches simulation conditions
			$simulate = true;
			if ( in_array( $email, $simulate_emails ) ) {
				$simulate = true;
			} else {
				foreach ( $simulate_keywords as $keyword ) {
					if ( strpos( $email, $keyword ) !== false ) {
						$simulate = true;
						break;
					}
				}
			}
            
			// Stop email sending and DB storage for simulated submissions
			if ( $simulate ) {
				// Clone the entry into custom tables
				$form_id = $record->get_form_settings( 'id' );

				$entry_data  = array(
					'form_id'      => $form_id,
					'status'       => 'publish',
					'source_url'   => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
					'date_created' => current_time( 'mysql' ),
					'date_updated' => current_time( 'mysql' ),
					'uid'          => $checkview_test_id,
					'form_type'    => 'Elementor',
				);
				$entry_table = $wpdb->prefix . 'cv_entry';
				$wpdb->insert( $entry_table, $entry_data );
				$inserted_entry_id = $wpdb->insert_id;

				$entry_meta_table = $wpdb->prefix . 'cv_entry_meta';

				foreach ( $form_data as $key => $val ) {
					$entry_metadata = array(
						'uid'        => $checkview_test_id,
						'form_id'    => $form_id,
						'entry_id'   => $inserted_entry_id,
						'meta_key'   => $key,
						'meta_value' => $val,
					);
					$wpdb->insert( $entry_meta_table, $entry_metadata );
				}
				complete_checkview_test( $checkview_test_id );
			}
		}

		/**
		 * Deletes the form entry from the database.
		 *
		 * @param int $insert_id The inserted ID from CF7 form.
		 * @return void
		 */
		public function checkview_delete_entry( $insert_id ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'db7_forms', array( 'form_id' => $insert_id ) );
		}


		/**
		 * Injects testing email recipient.
		 *
		 * @param array $args Emails.
		 * @return array
		 */
		public function checkview_inject_email( $args ) {
			if ( defined( 'CV_DISABLE_EMAIL_RECEIPT' ) ) {
				$args['recipient'] .= ', ' . TEST_EMAIL;
			} else {
				$args['recipient'] = TEST_EMAIL;
				$headers           = '';
				// Remove bcc and cc headers.
				$headers = preg_replace( '/^(bcc:|cc:).*$/mi', '', $args['additional_headers'] );

				// Clean up any extra newlines.
				$headers                    = preg_replace( '/^\s*[\r\n]+/m', '', $headers );
				$args['additional_headers'] = $headers;
			}
			return $args;
		}

		/**
		 * Returns false.
		 *
		 * @return bool
		 */
		public function checkview_return_false() {
			return false;
		}

		/**
		 * Bypass flaimgo.
		 *
		 * @param array $cases cases to bypass.
		 * @return array cases.
		 */
		public function checkview_bypass_flamingo( array $cases ): array {
			$cases   = array();
			$cases[] = 'checkview_bot';
			return $cases;
		}
	}

	$checkview_elementor_helper = new Checkview_Elementor_Helper();
}
