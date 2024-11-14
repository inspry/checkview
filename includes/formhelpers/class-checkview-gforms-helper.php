<?php
/**
 * TODO: Grayson
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

if ( ! class_exists( 'Checkview_Gforms_Helper' ) ) {
	/**
	 * TODO: Grayson
	 *
	 * @package    Checkview
	 * @subpackage Checkview/includes/formhelpers
	 * @author     Check View <support@checkview.io>
	 */
	class Checkview_Gforms_Helper {
		/**
		 * TODO: Grayson
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Checkview_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		protected $loader;
		/**
		 * TODO: Grayson
		 */
		public function __construct() {
			$this->loader = new Checkview_Loader();
			if ( defined( 'TEST_EMAIL' ) ) {
				// Change email address to our test email.
				add_filter(
					'gform_pre_send_email',
					array(
						$this,
						'checkview_inject_email',
					),
					99,
					1
				);
			}
			// disable addons found in forms.
			add_filter(
				'gform_addon_pre_process_feeds',
				array(
					$this,
					'checkview_disable_addons_feed',
				),
				999,
				3
			);
			// disable pdf addon if added to form.
			add_filter(
				'gfpdf_pdf_config',
				array(
					$this,
					'checkview_disable_pdf_addon',
				),
				999,
				2
			);
			// disable zero spam for form testing.
			add_filter(
				'gf_zero_spam_check_key_field',
				array(
					$this,
					'checkview_disable_zero_spam_addon',
				),
				99,
				4
			);
			// clone entry after submission complete.
			add_action(
				'gform_after_submission',
				array(
					$this,
					'checkview_clone_entry',
				),
				99,
				2
			);

			add_filter(
				'cfturnstile_whitelisted',
				'__return_true',
				999
			);
			add_filter(
				'gform_pre_render',
				array( $this, 'maybe_hide_recaptcha' )
			);

			// Note: when changing choice values, we also need to use the gform_pre_validation so that the new values are available when validating the field.
			add_filter(
				'gform_pre_validation',
				array( $this, 'maybe_hide_recaptcha' )
			);

			// Note: when changing choice values, we also need to use the gform_admin_pre_render so that the right values are displayed when editing the entry.
			add_filter(
				'gform_admin_pre_render',
				array( $this, 'maybe_hide_recaptcha' )
			);

			// Note: this will allow for the labels to be used during the submission process in case values are enabled.
			add_filter(
				'gform_pre_submission_filter',
				array( $this, 'maybe_hide_recaptcha' )
			);
			// bypass hcaptcha.
			add_filter( 'hcap_activate', '__return_false' );
			// bypass akimet.
			add_filter(
				'akismet_get_api_key',
				'__return_null',
				-10
			);
		}
		/**
		 * TODO: Grayson
		 *
		 * @param [Ninaja form] $form form object.
		 * @return form.
		 */
		public function maybe_hide_recaptcha( $form ) {

			// Add a placeholder to field id 8, is not used with multi-select or radio, will overwrite placeholder set in form editor.
			// Replace 8 with your actual field id.
			$fields = $form['fields'];

			foreach ( $form['fields'] as $key => $field ) {
				if ( 'captcha' === $field->type || 'hcaptcha' === $field->type || 'turnstile' === $field->type ) {
					unset( $fields[ $key ] );
				}
			}

			$form['fields'] = $fields;
			return $form;
		}

		/**
		 * TODO: Grayson
		 *
		 * @param array  $entry form entry data.
		 * @param object $form form object.
		 * @return void
		 */
		public function checkview_clone_entry( $entry, $form ) {
			$form_id           = rgar( $form, 'id' );
			$checkview_test_id = get_checkview_test_id();

			if ( empty( $checkview_test_id ) ) {
				$checkview_test_id = $form_id . gmdate( 'Ymd' );
			}
			self::checkview_clone_gf_entry( $entry['id'], $form_id, $checkview_test_id );
			if ( isset( $entry['id'] ) ) {
				// Remove entry after submission.
				GFAPI::delete_entry( $entry['id'] );
			}
			// Test completed So Clear sessions.
			complete_checkview_test( $checkview_test_id );
		}
		/**
		 * TODO: Grayson
		 *
		 * @param array $email address.
		 * @return array email.
		 */
		public function checkview_inject_email( $email ) {
			if ( get_option( 'disable_email_receipt' ) == true ) {
				$email['to'] = TEST_EMAIL;
			} elseif ( is_array( $email['to'] ) ) {
				$email['to'][] = TEST_EMAIL;
			} else {
				$email['to'] .= ', ' . TEST_EMAIL;
			}
			return $email;
		}
		/**
		 * TODO: Grayson
		 *
		 * @param int $entry_id entry id of the form.
		 * @param int $form_id form submitted id.
		 * @param int $uid user submitted id.
		 * @return void
		 */
		public function checkview_clone_gf_entry( $entry_id, $form_id, $uid ) {
			global $wpdb;

			$tablename = $wpdb->prefix . 'gf_entry_meta';
			$rows      = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where entry_id=%d and form_id=%d order by id ASC', $entry_id, $form_id ) );
			foreach ( $rows as $row ) {
				$table = $wpdb->prefix . 'cv_entry_meta';
				$data  = array(
					'uid'        => $uid,
					'form_id'    => $row->form_id,
					'entry_id'   => $row->entry_id,
					'meta_key'   => $row->meta_key,
					'meta_value' => $row->meta_value,
				);
				$wpdb->insert( $table, $data );
			}
			$tablename = $wpdb->prefix . 'gf_entry';
			$row       = $wpdb->get_row( $wpdb->prepare( 'Select * from ' . $tablename . ' where id=%d and form_id=%d LIMIT 1', $entry_id, $form_id ), ARRAY_A );
			unset( $row['id'] );
			$table1           = $wpdb->prefix . 'cv_entry';
			$row['uid']       = $uid;
			$row['form_type'] = 'GravityForms';
			$wpdb->insert( $table1, $row );
		}

		/**
		 * TODO: Grayson
		 *
		 * @param int    $form_id form's id.
		 * @param int    $should_check_key_field check for filed.
		 * @param object $form forms object.
		 * @param array  $entry entry details.
		 * @return bool
		 */
		public function checkview_disable_zero_spam_addon( $form_id, $should_check_key_field, $form, $entry ) {
			return false;
		}

		/**
		 * TODO: Grayson
		 *
		 * @param array $settings settinfs for form helper.
		 * @param int   $form_id id of the form submitted.
		 * @return array
		 */
		public function checkview_disable_pdf_addon( $settings, $form_id ) {

			$settings['notification']       = '';
			$settings['conditional']        = 1;
			$settings['enable_conditional'] = 'Yes';
			$settings['conditionalLogic']   = array(
				'actionType' => 'hide',
				'logicType'  => 'all',
				'rules'      =>
					array(
						array(
							'fieldId'  => 1,
							'operator' => 'isnot',
							'value'    => esc_html__( 'Check Form Helper', 'checkview' ),
						),
					),
			);

			return $settings;
		}

		/**
		 * TODO: Grayson
		 *
		 * @param array  $feeds form feeds.
		 * @param array  $entry form entry data.
		 * @param object $form form obbject.
		 * @return array
		 */
		public function checkview_disable_addons_feed( $feeds, $entry, $form ) {
			$form_id = rgar( $form, 'id' );
			if ( $feeds ) {
				foreach ( $feeds as &$feed ) {
					if ( isset( $feed['meta'] ) ) {
						$feed['meta']['feed_condition_conditional_logic']        = true;
						$feed['meta']['feed_condition_conditional_logic_object'] = array(
							'conditionalLogic' => array(
								'actionType' => 'show',
								'logicType'  => 'all',
								'rules'      =>
								array(
									array(
										'fieldId'  => 1,
										'operator' => 'is',
										'value'    => esc_html__( 'Check Form Helper', 'checkview' ),
									),
								),
							),
						);
					}
				}
			}
			return $feeds;
		}
	}
	$checkview_gforms_helper = new Checkview_Gforms_Helper();
}
