<?php
/**
 * Fired during Fluentforms is active.
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

if ( ! class_exists( 'Checkview_Fluent_Forms_Helper' ) ) {
	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Helps in Fluentforms management.
	 *
	 * @package    Checkview
	 * @subpackage Checkview/includes/formhelpers
	 * @author     Check View <support@checkview.io>
	 */
	class Checkview_Fluent_Forms_Helper {
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
			if ( defined( 'TEST_EMAIL' ) ) {
				// Change Email address to our test email.
				add_filter(
					'fluentform/email_to',
					array(
						$this,
						'checkview_inject_email',
					),
					99,
					4
				);
			}
			// clone entry after submission complete.
			add_action(
				'fluentform/submission_inserted',
				array(
					$this,
					'checkview_clone_fluentform_entry',
				),
				99,
				3
			);
			add_filter(
				'fluentform/has_recaptcha',
				function ( $isSpamCheck ) {
					return false;
				},
				20,
				1
			);

			add_filter(
				'fluentform/has_hcaptcha',
				function ( $status ) {
					// Do your stuff here.

					return false;
				},
				10,
				1
			);

			add_filter(
				'fluentform/has_turnstile',
				function ( $status ) {
					// Do your stuff here.

					return false;
				},
				10,
				1
			);
			add_filter(
				'fluentform/akismet_check_spam',
				function ( $isSpamCheck, $form_id, $formData ) {
					return false;
				},
				20,
				3
			);
			add_filter(
				'cfturnstile_whitelisted',
				'__return_true',
				999
			);

			$old_settings = (array) get_option( '_fluentform_turnstile_details', array() );
			if ( null !== $old_settings['siteKey'] && null !== $old_settings['secretKey'] ) {
				if ( '1x00000000000000000000AA' !== $old_settings['siteKey'] ) {
					update_option( 'checkview_ff_turnstile-site-key', $old_settings['siteKey'], true );
					update_option( 'checkview_ff_turnstile-secret-key', $old_settings['secretKey'], true );
					$old_settings['siteKey']   = '1x00000000000000000000AA';
					$old_settings['secretKey'] = '1x0000000000000000000000000000000AA';
					update_option( '_fluentform_turnstile_details', $old_settings );
				}
			}
			// add_filter(
			// 'fluentform/rendering_form',
			// function ( $form ) {
			// foreach ( $form->fields['fields'] as $index => $field ) {
			// if ( in_array( $field['element'], array( 'recaptcha', 'hcaptcha', 'turnstile', 'captcha' ) ) ) {
			// \FluentForm\Framework\Helpers\ArrayHelper::forget( $form->fields['fields'], $index );
			// }
			// }
			// return $form;
			// },
			// 20,
			// 1
			// );
			// $autoincluderecaptcha = array(
			// array(
			// 'type'        => 'hcaptcha',
			// 'is_disabled' => ! get_option( '_fluentform_hCaptcha_keys_status', false ),
			// ),
			// array(
			// 'type'        => 'recaptcha',
			// 'is_disabled' => ! get_option( '_fluentform_reCaptcha_keys_status', false ),
			// ),
			// array(
			// 'type'        => 'turnstile',
			// 'is_disabled' => ! get_option( '_fluentform_turnstile_keys_status', false ),
			// ),
			// );

			// foreach ( $autoincluderecaptcha as $input ) {

			// add_filter(
			// 'fluentform/has_' . $input['type'],
			// function () use ( $input ) {
			// $option   = get_option( '_fluentform_global_form_settings' );
			// $autoload = \FluentForm\Framework\Helpers\ArrayHelper::get( $option, 'misc.autoload_captcha' );
			// $type     = \FluentForm\Framework\Helpers\ArrayHelper::get( $option, 'misc.captcha_type' );

			// if ( $autoload || $type == $input['type'] ) {
			// return false;
			// }

			// return false;
			// },
			// 20,
			// 1
			// );

			// add_filter(
			// 'fluentform/validate_input_item_recaptcha',
			// function ( $error, $field, $form_data, $fields, $form, $errors ) {
			// $option   = get_option( '_fluentform_global_form_settings' );
			// $autoload = \FluentForm\Framework\Helpers\ArrayHelper::get( $option, 'misc.autoload_captcha' );
			// $type     = \FluentForm\Framework\Helpers\ArrayHelper::get( $option, 'misc.captcha_type' );

			// if ( $field['element'] == $type || $type == $field ) {
			// return false;
			// }

			// return false;
			// },
			// 20,
			// 6
			// );
			// }
			// add_action(
			// 'fluentform/before_form_validation',
			// function ( $fields, $form_data ) {

			// foreach ( $fields as $index => $field ) {
			// if ( in_array( $field['element'], array( 'recaptcha', 'hcaptcha', 'turnstile', 'captcha' ) ) ) {
			// unset( $fields[ $key ] );
			// }
			// }
			// },
			// 12,
			// 2
			// );
			// add_filter(
			// 'fluentform/disabled_components',
			// function ( $disabled ) {
			// Do your stuff here.
			// $disabled['recaptcha'] = array(
			// 'disabled'    => true,
			// 'title'       => __( 'reCaptcha', 'fluentform' ),
			// 'description' => __( 'Please enter a valid API key on FluentForms->Settings->reCaptcha', 'fluentform' ),
			// 'hidePro'     => true,
			// );
			// return $disabled;
			// },
			// 10,
			// 1
			// );
			add_filter(
				'fluentform/recaptcha_v3_ref_score',
				function ( $score ) {
					return -8;
				},
				99,
				1
			);
			// bypass hcaptcha.
			add_filter( 'hcap_activate', '__return_false' );
			add_filter(
				'akismet_get_api_key',
				'__return_null',
				-10
			);
		}

		/**
		 * Recursively remove validation rules from form fields.
		 *
		 * @param array $fields The fields array to process.
		 * @return array The modified fields array without validation rules.
		 *
		 * @author Taylor Drayson
		 * @link https://snippetclub.com
		 */
		public function remove_validation_rules( $fields ) {
			foreach ( $fields as &$field ) {
				// Remove validation rules if they exist in the field settings.
				if ( isset( $field['settings']['validation_rules'] ) ) {
					unset( $field['settings']['validation_rules'] );
				}

				// Check for nested fields and call the function recursively.
				if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
					$field['fields'] = $this->remove_validation_rules( $field['fields'] );
				}

				// Check for columns with nested fields and call the function recursively.
				if ( isset( $field['columns'] ) && is_array( $field['columns'] ) ) {
					foreach ( $field['columns'] as &$column ) {
						if ( isset( $column['fields'] ) && is_array( $column['fields'] ) ) {
							$column['fields'] = $this->remove_validation_rules( $column['fields'] );
						}
					}
				}
			}

			return $fields;
		}
		/**
		 * Injects email to fluentform supported emails.
		 *
		 * @param string $address email address.
		 * @param string $notification email notification.
		 * @param array  $submitted_data fluentforms submitted data.
		 * @param object $form fluentforms form object.
		 * @return string email.
		 */
		public function checkview_inject_email( $address, $notification, $submitted_data, $form ) {
			return TEST_EMAIL;
		}
		/**
		 * CLones the fluentforms enrty.
		 *
		 * @param int    $entry_id fluentform ID.
		 * @param array  $form_data fluentform data.
		 * @param object $form fluentform obj.
		 * @return void
		 */
		public function checkview_clone_fluentform_entry( $entry_id, $form_data, $form ) {
			global $wpdb;

			$form_id           = $form->id;
			$checkview_test_id = get_checkview_test_id();

			if ( empty( $checkview_test_id ) ) {
				$checkview_test_id = $form_id . gmdate( 'Ymd' );
			}

			// clone entry to check view tables.
			$tablename = $wpdb->prefix . 'fluentform_entry_details';
			$rows      = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where submission_id=%d and form_id=%d order by id ASC', $entry_id, $form_id ) );
			foreach ( $rows as $row ) {
				$meta_key = 'ff_' . $form_id . '_' . $row->field_name;
				if ( '' !== $row->sub_field_name ) {
					$meta_key .= '_' . $row->sub_field_name . '_';
				}
				$table = $wpdb->prefix . 'cv_entry_meta';
				$data  = array(
					'uid'        => $checkview_test_id,
					'form_id'    => $form_id,
					'entry_id'   => $row->submission_id,
					'meta_key'   => $meta_key,
					'meta_value' => $row->field_value,
				);
				$wpdb->insert( $table, $data );
			}
			$tablename = $wpdb->prefix . 'fluentform_submissions';
			$row       = $wpdb->get_row( $wpdb->prepare( 'Select * from ' . $tablename . ' where id=%d and form_id=%d LIMIT 1', $entry_id, $form_id ), ARRAY_A );
			$table1    = $wpdb->prefix . 'cv_entry';
			$data      = array(
				'uid'            => $checkview_test_id,
				'form_type'      => 'FluentForms',
				'form_id'        => $form_id,
				'source_url'     => isset( $row['source_url'] ) ? $row['source_url'] : 'n/a',
				'response'       => isset( $row['response'] ) ? $row['response'] : 'n/a',
				'user_agent'     => isset( $row['browser'] ) ? $row['browser'] : 'n/a',
				'ip'             => isset( $row['ip'] ) ? $row['ip'] : 'n/a',
				'date_created'   => isset( $row['created_at'] ) ? $row['created_at'] : 'n/a',
				'date_updated'   => isset( $row['updated_at'] ) ? $row['updated_at'] : 'n/a',
				'payment_status' => isset( $row['payment_status'] ) ? $row['payment_status'] : 'n/a',
				'payment_method' => isset( $row['payment_method'] ) ? $row['payment_payment'] : 'n/a',
				'payment_amount' => isset( $row['payment_total'] ) ? $row['payment_total'] : 0,
			);
			$wpdb->insert( $table1, $data );

			// remove entry from Fluent forms tables.
			$delete       = wpFluent()->table( 'fluentform_submissions' )
			->where( 'form_id', $form_id )
			->where( 'id', '=', $entry_id )
			->delete();
			$delete       = wpFluent()->table( 'fluentform_entry_details' )
			->where( 'form_id', $form_id )
			->where( 'submission_id', '=', $entry_id )
			->delete();
			$old_settings = (array) get_option( '_fluentform_turnstile_details', array() );
			if ( null !== $old_settings['siteKey'] && null !== $old_settings['secretKey'] ) {
				if ( '1x00000000000000000000AA' === $old_settings['siteKey'] ) {
					$old_settings['siteKey']   = get_option( 'checkview_ff_turnstile-site-key' );
					$old_settings['secretKey'] = get_option( 'checkview_ff_turnstile-secret-key' );
					update_option( '_fluentform_turnstile_details', $old_settings );
				}
			}
			// Test completed So Clear sessions.
			complete_checkview_test();
		}
	}

	$checkview_fluent_forms_helper = new Checkview_Fluent_Forms_Helper();
}
