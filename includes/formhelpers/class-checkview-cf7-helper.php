<?php
/**
 * Fired during CF7 is active.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    Checkview
 * @subpackage Checkview/includes/formhelpers
 */

if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not Allowed.' );
}

if ( ! class_exists( 'Checkview_Cf7_Helper' ) ) {
	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Helps in CF7 management.
	 *
	 * @package    Checkview
	 * @subpackage Checkview/includes/formhelpers
	 * @author     Check View <support@checkview.io>
	 */
	class Checkview_Cf7_Helper {
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
		 * Initializes class constructor.
		 */
		public function __construct() {
			$this->loader = new Checkview_Loader();
			if ( defined( 'TEST_EMAIL' ) ) {
				// change emial address.
				add_filter(
					'wpcf7_mail_components',
					array(
						$this,
						'checkview_inject_email',
					),
					99,
					1
				);
			}

			add_action(
				'wpcf7_before_send_mail',
				array(
					$this,
					'checkview_cf7_before_send_mail',
				),
				99,
				1
			);
			// remove test entry from cf7 submission table.
			add_action(
				'cfdb7_after_save_data',
				array(
					$this,
					'checkview_delete_entry',
				),
				999,
				1
			);
			add_filter(
				'wpcf7_spam',
				array(
					$this,
					'checkview_return_false',
				),
				999
			);
			add_filter(
				'wpcf7_skip_spam_check',
				'__return_true',
				999
			);

			add_filter(
				'wpcf7_submission_has_disallowed_words',
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
			// bypass hcaptcha.
			add_filter( 'hcap_activate', '__return_false' );
		}

		/**
		 * Adds the entry to DB after form has been saved.
		 *
		 * @param Object $form_tag form object by CFS.
		 * @return void
		 */
		public function checkview_cf7_before_send_mail( $form_tag ) {

			global $wpdb;

			$form_id              = $form_tag->id();
			$wp_filesystem_direct = new WP_Filesystem_Direct( array() );

			$checkview_test_id = get_checkview_test_id();

			if ( empty( $checkview_test_id ) ) {
				$checkview_test_id = $form_id . gmdate( 'Ymd' );
			}

			$upload_dir     = wp_upload_dir();
			$cv_cf7_dirname = $upload_dir['basedir'] . '/cv_cf7_uploads';

			if ( ! file_exists( $cv_cf7_dirname ) ) {
				$wp_filesystem_direct->mkdir( $cv_cf7_dirname, 0777, true );
			}

			$time_now = time();

			$submission = WPCF7_Submission::get_instance();
			if ( $submission ) {
				$contact_form = $submission->get_contact_form();
			}
			$tags_names = array();

			if ( $submission ) {

				$allowed_tags = array();

				$tags = $contact_form->scan_form_tags();
				foreach ( $tags as $tag ) {
					if ( ! empty( $tag->name ) ) {
						$tags_names[] = $tag->name;
					}
				}
				$allowed_tags = $tags_names;

				$not_allowed_tags = array( 'g-recaptcha-response' );

				$data           = $submission->get_posted_data();
				$files          = $submission->uploaded_files();
				$uploaded_files = array();

				foreach ( $_FILES as $file_key => $file ) {
					array_push( $uploaded_files, $file_key );
				}
				foreach ( $files as $file_key => $file ) {
					$file = is_array( $file ) ? reset( $file ) : $file;
					if ( empty( $file ) ) {
						continue;
					}
					copy( $file, $cv_cf7_dirname . '/' . $time_now . '-' . $file_key . '-' . basename( $file ) );
				}

				$form_data = array();

				foreach ( $data as $key => $d ) {

					if ( ! in_array( $key, $allowed_tags ) ) {
						continue;
					}

					if ( ! in_array( $key, $not_allowed_tags ) && ! in_array( $key, $uploaded_files ) ) {

						$tmp_d = $d;

						if ( ! is_array( $d ) ) {
							$bl    = array( '\"', "\'", '/', '\\', '"', "'" );
							$wl    = array( '&quot;', '&#039;', '&#047;', '&#092;', '&quot;', '&#039;' );
							$tmp_d = str_replace( $bl, $wl, $tmp_d );
						}
						if ( is_array( $d ) ) {
							$tmp_d = serialize( $d );
						}

						$form_data[ $key ] = $tmp_d;
					}
					if ( in_array( $key, $uploaded_files ) ) {
						$file                              = is_array( $files[ $key ] ) ? reset( $files[ $key ] ) : $files[ $key ];
						$file_name                         = empty( $file ) ? '' : $time_now . '-' . $key . '-' . basename( $file );
						$form_data[ $key . 'cv_cf7_file' ] = $file_name;
					}
				}

				// insert entry.
				$entry_data  = array(
					'form_id'      => $form_id,
					'status'       => 'publish',
					'source_url'   => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
					'date_created' => current_time( 'mysql' ),
					'date_updated' => current_time( 'mysql' ),
					'uid'          => $checkview_test_id,
					'form_type'    => 'CF7',
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

				// Test completed So Clear sessions.
				complete_checkview_test( $checkview_test_id );
			}
		}

		/**
		 * Deletes entry from DB.
		 *
		 * @param int $insert_id The inserted ID from CF7 form.
		 * @return void
		 */
		public function checkview_delete_entry( $insert_id ) {
			global $wpdb;
			// Remove Test Entry From WpForms Tables.
			$wpdb->delete( $wpdb->prefix . 'db7_forms', array( 'form_id' => $insert_id ) );
		}


		/**
		 * Injects email to CF7 supported emails.
		 *
		 * @param array $args emails array.
		 * @return array
		 */
		public function checkview_inject_email( $args ) {
			$args['recipient'] = TEST_EMAIL;
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
	}

	$checkview_cf7_helper = new checkview_cf7_helper();
}
