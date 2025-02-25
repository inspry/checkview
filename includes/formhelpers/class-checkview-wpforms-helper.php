<?php
/**
 * Checkview_Wpforms_Helper class
 *
 * @since 1.0.0
 *
 * @package Checkview
 * @subpackage Checkview/includes/formhelpers
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not Allowed.' );
}

if ( ! class_exists( 'Checkview_Wpforms_Helper' ) ) {
	/**
	 * Adds support for WP Forms.
	 *
	 * During CheckView tests, modifies WP Forms hooks, overwrites the
	 * recipient email address, and handles test cleanup.
	 *
	 * @package Checkview
	 * @subpackage Checkview/includes/formhelpers
	 * @author Check View <support@checkview.io>
	 */
	class Checkview_Wpforms_Helper {
		/**
		 * Loader.
		 *
		 * @since 1.0.0
		 * @access protected
		 *
		 * @var Checkview_Loader $loader Maintains and registers all hooks for the plugin.
		 */
		protected $loader;
		/**
		 * Constructor.
		 *
		 * Initiates loader property, adds hooks.
		 */
		public function __construct() {
			$this->loader = new Checkview_Loader();
			if ( ! is_admin() ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$old_settings = (array) get_option( 'wpforms_settings', array() );
			if ( ! empty( $old_settings['turnstile-site-key'] ) && null !== $old_settings['turnstile-site-key'] && null !== $old_settings['turnstile-secret-key'] ) {
				if ( '1x00000000000000000000AA' !== $old_settings['turnstile-site-key'] ) {
					update_option( 'checkview_wpforms_turnstile-site-key', $old_settings['turnstile-site-key'], true );
					update_option( 'checkview_wpforms_turnstile-secret-key', $old_settings['turnstile-secret-key'], true );
					$old_settings['turnstile-site-key']   = '1x00000000000000000000AA';
					$old_settings['turnstile-secret-key'] = '1x0000000000000000000000000000000AA';
					update_option( 'wpforms_settings', $old_settings );
				}
			} else {
				// Disable reCAPTCHA assets and initialisation on the frontend.
				add_filter(
					'wpforms_frontend_recaptcha_disable',
					'__return_true',
					99
				);
			}

			// Disable validation and verification on the backend.
			add_filter(
				'wpforms_process_bypass_captcha',
				'__return_true',
				99
			);

			remove_action(
				'wpforms_frontend_output',
				array(
					wpforms()->get( 'frontend' ),
					'recaptcha',
				),
				20
			);

			add_action(
				'wpforms_process_complete',
				array(
					$this,
					'checkview_log_wpform_test_entry',
				),
				99,
				4
			);

			/**
			 * Disables the email address suggestion.
			 *
			 * @link https://wpforms.com/developers/how-to-disable-the-email-suggestion-on-the-email-form-field/
			 */
			add_filter(
				'wpforms_mailcheck_enabled',
				'__return_false'
			);

			if ( defined( 'TEST_EMAIL' ) ) {
				// change email to send to our test account.
				add_filter(
					'wpforms_entry_email_atts',
					array(
						$this,
						'checkview_inject_email',
					),
					99,
					1
				);
			}
			add_filter(
				'cfturnstile_whitelisted',
				'__return_true',
				999
			);

			// Bypass hCaptcha.
			add_filter( 'hcap_activate', '__return_false' );

			// Bypass Akismet.
			add_filter(
				'akismet_get_api_key',
				'__return_null',
				-10
			);

			add_filter(
				'wpforms_frontend_form_data',
				array(
					$this,
					'checkview_disable_wpforms_custom_captcha',
				),
				99,
				1
			);

			add_filter(
				'wpforms_process_before_form_data',
				array(
					$this,
					'checkview_disable_wpforms_custom_captcha',
				),
				99,
				1
			);
		}

		/**
		 * Injects testing email address.
		 *
		 * @param array $email Email address details.
		 * @return array
		 */
		public function checkview_inject_email( $email ) {
			if ( get_option( 'disable_email_receipt', false ) == false ) {
				$count = count( $email['address'] );
				for ( $i = 0; $i < $count; $i++ ) {
					$email['address'][ $i ]    = TEST_EMAIL;
					$email['carboncopy'][ $i ] = '';
				}
			} elseif ( is_array( $email['address'] ) ) {
				$email['address'][] = TEST_EMAIL;
			} else {
				$email['address'] .= ', ' . TEST_EMAIL;
			}
			return $email;
		}
		/**
		 * Stores the test results and finishes the testing session.
		 *
		 * Deletes test submission from Formidable database table.
		 *
		 * @param array $form_fields Form fields.
		 * @param array $entry Form entry details.
		 * @param array $form_data Form data.
		 * @param int   $entry_id Form entry ID.
		 * @return void
		 */
		public function checkview_log_wpform_test_entry( $form_fields, $entry, $form_data, $entry_id ) {
			global $wpdb;
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$form_id           = $form_data['id'];
			$checkview_test_id = get_checkview_test_id();

			if ( empty( $checkview_test_id ) ) {
				$checkview_test_id = $form_id . gmdate( 'Ymd' );
			}

			$entry_data  = array(
				'form_id'      => $form_id,
				'status'       => 'publish',
				'source_url'   => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
				'date_created' => current_time( 'mysql' ),
				'date_updated' => current_time( 'mysql' ),
				'uid'          => $checkview_test_id,
				'form_type'    => 'WpForms',
			);
			$entry_table = $wpdb->prefix . 'cv_entry';
			$wpdb->insert( $entry_table, $entry_data );
			$inserted_entry_id = $wpdb->insert_id;
			$entry_meta_table  = $wpdb->prefix . 'cv_entry_meta';
			$field_id_prefix   = 'wpforms-' . $form_id . '-field_';
			foreach ( $form_fields as $field ) {

				if ( ! isset( $field['value'] ) || '' === $field['value'] ) {
					continue;
				}
				$field_value = is_array( $field['value'] ) ? serialize( $field['value'] ) : $field['value'];
				$type        = isset( $field['type'] ) ? $field['type'] : '';
				switch ( $type ) {
					case 'name':
						if ( '' === $field['middle'] && '' === $field['last'] ) {
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $inserted_entry_id,
								'meta_key'   => $field_id_prefix . $field['id'],
								'meta_value' => $field['first'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );

						} elseif ( '' === $field['middle'] ) {
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $inserted_entry_id,
								'meta_key'   => $field_id_prefix . $field['id'],
								'meta_value' => $field['first'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $inserted_entry_id,
								'meta_key'   => $field_id_prefix . $field['id'] . '-last',
								'meta_value' => $field['last'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );

						} else {
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $inserted_entry_id,
								'meta_key'   => $field_id_prefix . $field['id'],
								'meta_value' => $field['first'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $inserted_entry_id,
								'meta_key'   => $field_id_prefix . $field['id'] . '-middle',
								'meta_value' => $field['middle'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $inserted_entry_id,
								'meta_key'   => $field_id_prefix . $field['id'] . '-last',
								'meta_value' => $field['last'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );

						}
						break;
					default:
						$entry_metadata = array(
							'uid'        => $checkview_test_id,
							'form_id'    => $form_id,
							'entry_id'   => $inserted_entry_id,
							'meta_key'   => $field_id_prefix . $field['id'],
							'meta_value' => $field_value,
						);
						$wpdb->insert( $entry_meta_table, $entry_metadata );
						break;
				}
			}

			// Remove entry if Pro plugin.
			if ( is_plugin_active( 'wpforms/wpforms.php' ) ) {
				// Remove Test Entry From WpForms Tables.
				$wpdb->delete(
					$wpdb->prefix . 'wpforms_entries',
					array(
						'entry_id' => $entry_id,
						'form_id'  => $form_id,
					)
				);
				$wpdb->delete(
					$wpdb->prefix . 'wpforms_entry_fields',
					array(
						'entry_id' => $entry_id,
						'form_id'  => $form_id,
					)
				);
			}
			$old_settings = (array) get_option( 'wpforms_settings', array() );
			if ( ! empty( $old_settings['turnstile-site-key'] ) && null !== $old_settings['turnstile-site-key'] && null !== $old_settings['turnstile-secret-key'] ) {
				if ( '1x00000000000000000000AA' === $old_settings['turnstile-site-key'] ) {
					$old_settings['turnstile-site-key']   = get_option( 'checkview_wpforms_turnstile-site-key' );
					$old_settings['turnstile-secret-key'] = get_option( 'checkview_wpforms_turnstile-secret-key' );
					update_option( 'wpforms_settings', $old_settings );
				}
			}

			complete_checkview_test( $checkview_test_id );
		}
		/**
		 * Disable Custom CAPTCHA in WPForms.
		 *
		 * @param array $form_data Form data and settings.
		 * @return array Modified form data.
		 */
		public function checkview_disable_wpforms_custom_captcha( $form_data ) {
			if ( empty( $form_data['fields'] ) ) {
				return $form_data;
			}

			foreach ( $form_data['fields'] as $id => $field ) {
				if ( 'captcha' === $field['type'] ) {
					unset( $form_data['fields'][ $id ] );
				}
			}

			return $form_data;
		}
	}
	$checkview_wpforms_helper = new Checkview_Wpforms_Helper();
}
