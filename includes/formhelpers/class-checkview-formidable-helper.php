<?php
/**
 * Fired during Formidableis active.
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

if ( ! class_exists( 'Checkview_Formidable_Helper' ) ) {
	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Helps in Formidable management.
	 *
	 * @package    Checkview
	 * @subpackage Checkview/includes/formhelpers
	 * @author     Check View <support@checkview.io>
	 */
	class Checkview_Formidable_Helper {
		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @access   protected
		 * @var      Checkview_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		protected $loader;
		/**
		 * Initializes the class constructor.
		 */
		public function __construct() {
			$this->loader = new Checkview_Loader();
			if ( defined( 'TEST_EMAIL' ) ) {
				// update email to our test email.
				add_filter(
					'frm_to_email',
					array(
						$this,
						'checkview_inject_email',
					),
					99,
					1
				);
			}

			add_action(
				'frm_after_create_entry',
				array(
					$this,
					'checkview_log_form_test_entry',
				),
				99,
				2
			);

			add_filter(
				'frm_fields_in_form',
				array(
					$this,
					'remove_recaptcha_field_from_list',
				),
				11,
				2
			);
			add_filter(
				'akismet_get_api_key',
				'__return_null',
				-10
			);
			add_filter(
				'frm_fields_to_validate',
				array(
					$this,
					'remove_recaptcha_field_from_list',
				),
				20,
				2
			);
			add_filter(
				'cfturnstile_whitelisted',
				'__return_true',
				999
			);
			// bypass hcaptcha.
			add_filter( 'hcap_activate', '__return_false' );
		}
		/**
		 * Injects email to Formidableis supported emails.
		 *
		 * @param string $email address.
		 * @return string email.
		 */
		public function checkview_inject_email( $email ) {
			$email = TEST_EMAIL;
			return $email;
		}

		/**
		 * Logs Test entry
		 *
		 * @param int $entry_id form's id.
		 * @param int $form_id forms entry id.
		 * @return void
		 */
		public function checkview_log_form_test_entry( $entry_id, $form_id ) {
			global $wpdb;

			$checkview_test_id = get_checkview_test_id();

			if ( empty( $checkview_test_id ) ) {
				$checkview_test_id = $form_id . gmdate( 'Ymd' );
			}

			// insert entry.
			$entry_data  = array(
				'form_id'      => $form_id,
				'status'       => 'publish',
				'source_url'   => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
				'date_created' => current_time( 'mysql' ),
				'date_updated' => current_time( 'mysql' ),
				'uid'          => $checkview_test_id,
				'form_type'    => 'Formidable',
			);
			$entry_table = $wpdb->prefix . 'cv_entry';
			$wpdb->insert( $entry_table, $entry_data );
			$inserted_entry_id = $wpdb->insert_id;

			// insert entry meta.
			$entry_meta_table = $wpdb->prefix . 'cv_entry_meta';
			$fields           = $this->get_form_fields( $form_id );
			$tablename        = $wpdb->prefix . 'frm_item_metas';
			$form_fields      = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where item_id=%d', $entry_id ) );
			foreach ( $form_fields as $field ) {

				if ( 'name' === $fields[ $field->field_id ]['type'] ) {

					$field_values = maybe_unserialize( $field->meta_value );

					$name_format = $fields[ $field->field_id ]['name_layout'];
					switch ( $name_format ) {
						case 'first_middle_last':
							// First.
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $entry_id,
								'meta_key'   => $fields[ $field->field_id ]['sub_fields'][0]['field_id'],
								'meta_value' => $field_values['first'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );

							// middle.
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $entry_id,
								'meta_key'   => $fields[ $field->field_id ]['sub_fields'][1]['field_id'],
								'meta_value' => $field_values['middle'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );

							// last.
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $entry_id,
								'meta_key'   => $fields[ $field->field_id ]['sub_fields'][2]['field_id'],
								'meta_value' => $field_values['last'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );

							break;
						case 'first_last':
							// First.
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $entry_id,
								'meta_key'   => $fields[ $field->field_id ]['sub_fields'][0]['field_id'],
								'meta_value' => $field_values['first'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );
							// last.
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $entry_id,
								'meta_key'   => $fields[ $field->field_id ]['sub_fields'][1]['field_id'],
								'meta_value' => $field_values['last'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );
							break;
						case 'last_first':
							// First.
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $entry_id,
								'meta_key'   => $fields[ $field->field_id ]['sub_fields'][1]['field_id'],
								'meta_value' => $field_values['first'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );
							// last.
							$entry_metadata = array(
								'uid'        => $checkview_test_id,
								'form_id'    => $form_id,
								'entry_id'   => $entry_id,
								'meta_key'   => $fields[ $field->field_id ]['sub_fields'][0]['field_id'],
								'meta_value' => $field_values['last'],
							);
							$wpdb->insert( $entry_meta_table, $entry_metadata );
							break;

					}
				} else {
					$field_value    = $field->meta_value;
					$entry_metadata = array(
						'uid'        => $checkview_test_id,
						'form_id'    => $form_id,
						'entry_id'   => $entry_id,
						'meta_key'   => $fields[ $field->field_id ]['field_id'],
						'meta_value' => $field_value,
					);
					$wpdb->insert( $entry_meta_table, $entry_metadata );
				}
			}

			// remove test entry form Form Tables.
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'frm_item_metas WHERE item_id=%d', $entry_id ) );
			$result = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'frm_items WHERE id=%d', $entry_id ) );

			// Test completed So Clear sessions.
			complete_checkview_test();
		}

		/**
		 * List of Form fields.
		 *
		 * @param int $form_id id of the form.
		 * @return array
		 */
		public function get_form_fields( $form_id ) {
			global $wpdb;

			$fields      = array();
			$tablename   = $wpdb->prefix . 'frm_fields';
			$fields_data = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where form_id=%d', $form_id ) );
			if ( $fields_data ) {
				foreach ( $fields_data as $field ) {
					$type     = $field->type;
					$field_id = 'field_' . $field->field_key;
					switch ( $type ) {
						case 'name':
							$field_options        = maybe_unserialize( $field->field_options );
							$fields[ $field->id ] = array(
								'type'        => $field->type,
								'key'         => $field->field_key,
								'id'          => $field->id,
								'formId'      => $form_id,
								'Name'        => $field->name,
								'label'       => $field->name,
								'name_layout' => $field_options['name_layout'],
							);
							$name_format          = $field_options['name_layout'];
							$index                = $field->id;

							if ( 'first_last' === $name_format ) {
								$fields[ $index ]['sub_fields'][0]['type']     = 'text';
								$fields[ $index ]['sub_fields'][0]['name']     = 'First Name';
								$fields[ $index ]['sub_fields'][0]['field_id'] = $field_id . '_first';
								$fields[ $index ]['sub_fields'][1]['type']     = 'text';
								$fields[ $index ]['sub_fields'][1]['name']     = 'Last Name';
								$fields[ $index ]['sub_fields'][1]['field_id'] = $field_id . '_last';
							}

							if ( 'last_first' === $name_format ) {
								$fields[ $index ]['sub_fields'][0]['type']     = 'text';
								$fields[ $index ]['sub_fields'][0]['name']     = 'Last Name';
								$fields[ $index ]['sub_fields'][0]['field_id'] = $field_id . '_last';
								$fields[ $index ]['sub_fields'][1]['type']     = 'text';
								$fields[ $index ]['sub_fields'][1]['name']     = 'First Name';
								$fields[ $index ]['sub_fields'][1]['field_id'] = $field_id . '_first';
							}

							if ( 'first_middle_last' === $name_format ) {
								$fields[ $index ]['sub_fields'][0]['type']     = 'text';
								$fields[ $index ]['sub_fields'][0]['name']     = 'First Name';
								$fields[ $index ]['sub_fields'][0]['field_id'] = $field_id . '_first';
								$fields[ $index ]['sub_fields'][1]['type']     = 'text';
								$fields[ $index ]['sub_fields'][1]['name']     = 'Middle Name';
								$fields[ $index ]['sub_fields'][1]['field_id'] = $field_id . '_middle';
								$fields[ $index ]['sub_fields'][2]['type']     = 'text';
								$fields[ $index ]['sub_fields'][2]['name']     = 'Last Name';
								$fields[ $index ]['sub_fields'][2]['field_id'] = $field_id . '_last';
							}

							break;
						case 'radio':
							$field_options = maybe_unserialize( $field->options );
							foreach ( $field_options as $key => $val ) {
								$field_options[ $key ]['field_id'] = $field_id . '-' . $key;
							}
							$fields[ $field->id ] = array(
								'type'     => $field->type,
								'key'      => $field->field_key,
								'id'       => $field->id,
								'formId'   => $form_id,
								'Name'     => $field->name,
								'label'    => $field->name,
								'choices'  => $field_options,
								'field_id' => $field_id,
							);
							break;
						case 'checkbox':
							$field_options = maybe_unserialize( $field->options );
							foreach ( $field_options as $key => $val ) {
								$field_options[ $key ]['field_id'] = $field_id . '-' . $key;
							}
							$fields[ $field->id ] = array(
								'type'     => $field->type,
								'key'      => $field->field_key,
								'id'       => $field->id,
								'formId'   => $form_id,
								'Name'     => $field->name,
								'label'    => $field->name,
								'choices'  => $field_options,
								'field_id' => $field_id,
							);

							break;
						default:
							$fields[ $field->id ] = array(
								'type'       => $field->type,
								'key'        => $field->field_key,
								'id'         => $field->id,
								'formId'     => $form_id,
								'Name'       => $field->name,
								'label'      => $field->name,
								'field_name' => $field_id,
								'field_id'   => $field_id,
							);
							break;
					}
				}
			}
			return $fields;
		}
		/**
		 * Removes recaptchabmit field from the list of fields.
		 *
		 * @param array $fields Array of fields.
		 * @param form  $form form.
		 */
		public function remove_recaptcha_field_from_list( $fields, $form ) {

			foreach ( $fields as $key => $field ) {
				if ( 'recaptcha' === FrmField::get_field_type( $field ) || 'captcha' === FrmField::get_field_type( $field ) || 'hcaptcha' === FrmField::get_field_type( $field ) || 'turnstile' === FrmField::get_field_type( $field ) ) {
					unset( $fields[ $key ] );
				}
			}
			return $fields;
		}
	}

	$checkview_formidable_helper = new Checkview_Formidable_Helper();
}
