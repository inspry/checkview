<?php
/**
 * Hanldes CPT API functions.
 *
 * @link       https://faizanhaidar.com
 * @since      1.0.0
 *
 * @package    Wp_Task_Manager
 * @subpackage Wp_Task_Manager/includes
 */

/**
 * Fired for the plugin CPT API registeration and hadling CURD.
 *
 * This class defines all code necessary to run for handling custom post type API CURD operations.
 *
 * @since      1.0.0
 * @package    Wp_Task_Manager
 * @subpackage Wp_Task_Manager/includes
 * @author     Muhammad Faizan Haidar <faizanhaider594@gmail.com>
 */
class CheckView_Api {

	/**
	 * Store errors to display if the JWT Token is wrong
	 *
	 * @var WP_Error
	 */
	private $jwt_error = null;

	/**
	 * Registers the rest api routes for our tasks and related data.
	 *
	 * Registers the rest api routes for our taks and related data.
	 *
	 * @since    1.0.0
	 */
	public function checkview_register_rest_route() {
		register_rest_route(
			'checkview/v1',
			'/forms/formslist',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'checkview_get_available_forms_list' ),
				'permission_callback' => array( $this, 'checkview_get_items_permissions_check' ),
				'args'                => array(
					'_checkview_token' => array(
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'wptaskmanager/v1',
			'/tasks/update_task',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'wp_task_manager_update_task' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => array(
					'id'          => array(
						'required' => true,
					),
					'title'       => array(),
					'description' => array(),
					'status'      => array(),
					'due_date'    => array(),
				),
			)
		);

		register_rest_route(
			'wptaskmanager/v1',
			'/tasks/delete_task',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'wp_task_manager_delete_task' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => array(
					'id' => array(
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'wptaskmanager/v1',
			'/tasks/all_tasks',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'wp_task_manager_get_all_tasks' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => array(
					'search'         => array(),
					'due_start_date' => array(),
					'due_end_date'   => array(),
					'status'         => array(),
				),
			)
		);
	} // end register_rest_route_templates

	/**
	 * Retrieves available forms.
	 *
	 * @return void
	 */
	public function checkview_get_available_forms_list() {
		global $wpdb;
		$forms = array();
		// set no cache header.
		nocache_headers();

		if ( ! is_admin() ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
			$sql       = "Select * from {$wpdb->prefix}gf_form where is_active=1 and is_trash=0 order by ID ASC";
			$tablename = $wpdb->prefix . 'gf_form';
			$sql       = $wpdb->prepare( 'Select * from %s where is_active=%d and is_trash=%d order by ID ASC', $tablename, 1, 0 );
			$results   = $wpdb->get_results( $sql, ARRAY_A );
			if ( $results ) {
				foreach ( $results as $row ) {
					$forms['GravityForms'][ $row->id ] = array(
						'ID'   => $row->id,
						'Name' => $row->title,
					);
					$sql                               = "Select * from {$wpdb->prefix}gf_addon_feed where is_active=1 and form_id={$row->id}";
					$addons                            = $wpdb->get_results( $sql );
					$tablename                         = $wpdb->prefix . 'gf_addon_feed';
					$sql                               = $wpdb->prepare( 'Select * from %s where is_active=%d and form_id=%d', $tablename, 1, $row->id );
					$addons                            = $wpdb->get_results( $sql, ARRAY_A );
					foreach ( $addons as $addon ) {
						$forms['GravityForms'][ $row->id ]['addons'][] = $addon->addon_slug;
					}
					$sql        = "SELECT ID FROM {$wpdb->prefix}posts	 WHERE 1=1 and (post_content like '%wp:gravityforms/form {\"formId\":\"" . $row->id . "\"%' OR post_content like '%[gravityform id=\"" . $row->id . "\"%' OR post_content like '%[gravityform id=" . $row->id . "%'  OR post_content like \"%[gravityform id=" . $row->id . "%\") and post_status='publish' AND post_type NOT IN ('kadence_wootemplate', 'revision')";
					$form_pages = $wpdb->get_results( $sql );
					if ( $form_pages ) {
						foreach ( $form_pages as $form_page ) {

							if ( 'wp_block' === $form_page->post_type ) {

								$wp_block_pages = get_wp_block_pages( $form_page->ID );
								if ( $wp_block_pages ) {
									foreach ( $wp_block_pages as $wp_block_page ) {
										$forms['GravityForms'][ $row->id ]['pages'][] = array(
											'ID'  => $wp_block_page->ID,
											'url' => must_ssl_url( get_the_permalink( $wp_block_page->ID ) ),
										);
									}
								}
							} else {
								$forms['GravityForms'][ $row->id ]['pages'][] = array(
									'ID'  => $form_page->ID,
									'url' => must_ssl_url( get_the_permalink( $form_page->ID ) ),
								);
							}
						}
					}
					$form         = RGFormsModel::get_form_meta( $row->id );
					$fields       = $form['fields'];
					$button       = $form['button'];
					$button['id'] = 'gform_submit_button_' . $row->id;
					foreach ( $fields as $id => $val ) {
						$fields[ $id ]['field_id']   = 'input_' . $fields[ $id ]['formId'] . '_' . $fields[ $id ]['id'];
						$fields[ $id ]['field_name'] = 'input_' . $fields[ $id ]['id'];

						if ( is_array( $fields[ $id ]['inputs'] ) ) {

							$inputs = $fields[ $id ]['inputs'];

							foreach ( $inputs as $sid => $sval ) {

								$sval['field_id']   = 'input_' . $fields[ $id ]['formId'] . '_' . str_replace( '.', '_', $inputs[ $sid ]['id'] );
								$sval['field_name'] = 'input_' . $inputs[ $sid ]['id'];
								$inputs[ $sid ]     = $sval;

							}
							$fields[ $id ]['inputs'] = $inputs;

						} else {
							$inputs = array(
								'id'         => $fields[ $id ]['id'],
								'name'       => $fields[ $id ]['name'],
								'label'      => $fields[ $id ]['label'],
								'field_id'   => 'input_' . $fields[ $id ]['formId'] . '_' . $fields[ $id ]['id'],
								'field_name' => 'input_' . $fields[ $id ]['id'],
							);

							$fields[ $id ]['inputs'] = array( $inputs );
						}

						$forms['GravityForms'][ $row->id ]['fields'] = $fields;
						$forms['GravityForms'][ $row->id ]['button'] = $button;

					}
				}
			}
		} // For Gravity Form

		if ( is_plugin_active( 'fluentform/fluentform.php' ) ) {
			$sql       = "Select * from {$wpdb->prefix}fluentform_forms where status='published' order by ID ASC";
			$results   = $wpdb->get_results( $sql );
			$tablename = $wpdb->prefix . 'fluentform_forms';
			$sql       = $wpdb->prepare( 'Select * from %s where status=%s order by ID ASC', $tablename, 'published' );
			$results   = $wpdb->get_results( $sql, ARRAY_A );
			if ( $results ) {
				foreach ( $results as $row ) {
					$forms['FluentForms'][ $row->id ] = array(
						'ID'   => $row->id,
						'Name' => $row->title,
					);
					$sql                              = "SELECT ID FROM {$wpdb->prefix}posts	 WHERE 1=1 and (post_content like '%wp:fluentfom/guten-block {\"formId\":\"" . $row->id . "\"%' OR post_content like '%[fluentform id=\"" . $row->id . "\"%' OR post_content like '%[fluentform id=" . $row->id . "%' OR post_content like \"%[fluentform id=" . $row->id . "%\") and post_status='publish' AND post_type NOT IN ('kadence_wootemplate', 'revision')";
					$form_pages                       = $wpdb->get_results( $sql );
					foreach ( $form_pages as $form_page ) {

						if ( 'wp_block' === $form_page->post_type ) {

							$wp_block_pages = get_wp_block_pages( $form_page->ID );
							if ( $wp_block_pages ) {
								foreach ( $wp_block_pages as $wp_block_page ) {
									$forms['FluentForms'][ $row->id ]['pages'][] = array(
										'ID'  => $wp_block_page->ID,
										'url' => must_ssl_url( get_the_permalink( $wp_block_page->ID ) ),
									);
								}
							}
						} else {
							$forms['FluentForms'][ $row->id ]['pages'][] = array(
								'ID'  => $form_page->ID,
								'url' => must_ssl_url( get_the_permalink( $form_page->ID ) ),
							);
						}
					}
					$form   = json_decode( $row->form_fields, true );
					$fields = $form['fields'];
					$button = $form['submitButton'];

					foreach ( $fields as $index => $field ) {
						if ( isset( $fields[ $index ]['attributes']['id'] ) && '' === $fields[ $index ]['attributes']['id'] ) {
							$fields[ $index ]['attributes']['id'] = 'ff_' . $row->id . '_' . $fields[ $index ]['attributes']['name'];
						}
						if ( ! isset( $fields[ $index ]['attributes']['id'] ) && 'input_text' === $fields[ $index ]['element'] ) {
							$fields[ $index ]['attributes']['id'] = 'ff_' . $row->id . '_' . $fields[ $index ]['attributes']['name'];
						}

						if ( ! isset( $fields[ $index ]['attributes']['type'] ) && 'textarea' === $fields[ $index ]['element'] ) {
							$fields[ $index ]['attributes']['type'] = 'textarea';
						}
						// subfields.
						if ( isset( $fields[ $index ]['fields'] ) ) {
							foreach ( $fields[ $index ]['fields'] as $key => $subfield ) {

								$parent_name = $fields[ $index ]['attributes']['name'];
								$sub_name    = $key;
								if ( isset( $subfield['attributes']['id'] ) && '' === $subfield['attributes']['id'] ) {

									$sub_id = 'ff_' . $row->id . '_' . $parent_name . '_' . $sub_name . '_';
									$fields[ $index ]['fields'][ $key ]['attributes']['id'] = $sub_id;

								}
							}
						}
						if ( 'container' === $fields[ $index ]['element'] ) {
							foreach ( $fields[ $index ]['columns'] as $key => $subfield ) {

								$x        = 0;
								$end_loop = count( $subfield['fields'] );
								do {
									if ( isset( $fields[ $index ]['columns'][ $key ]['fields'][ $x ]['attributes']['name'] ) ) {
										$field_name = $fields[ $index ]['columns'][ $key ]['fields'][ $x ]['attributes']['name'];
									} else {
										$field_name = '';
									}

									if ( isset( $subfield['fields'][ $x ]['attributes']['id'] ) && '' === $subfield['fields'][ $x ]['attributes']['id'] ) {
										$fields[ $index ]['columns'][ $key ]['fields'][ $x ]['attributes']['id'] = 'ff_' . $row->id . '_' . $field_name;
									}
									if ( 'submit' === $subfield['fields'][ $x ]['attributes']['type'] ) {
										$fields[ $index ]['columns'][ $key ]['fields'][ $x ]['attributes']['id'] = 'ff-btn-submit';
									}
									++$x;
								} while ( $x < $end_loop );

							}
						}
					}

					if ( '' === $button['attributes']['class'] ) {
						$button['attributes']['class'] = 'ff-btn-submit';
					}

					$forms['FluentForms'][ $row->id ]['fields'] = $fields;
					$forms['FluentForms'][ $row->id ]['button'] = $button;

				}
			}
		} // FLUENT FORMS
		if ( is_plugin_active( 'ninja-forms/ninja-forms.php' ) ) {
			$sql       = "Select * from {$wpdb->prefix}nf3_forms order by ID ASC";
			$results   = $wpdb->get_results( $sql );
			$tablename = $wpdb->prefix . 'nf3_forms';
			$sql       = $wpdb->prepare( 'Select * from %s order by ID ASC', $tablename );
			$results   = $wpdb->get_results( $sql, ARRAY_A );
			if ( $results ) {
				foreach ( $results as $row ) {
					$forms['NinjaForms'][ $row->id ] = array(
						'ID'   => $row->id,
						'Name' => $row->title,
					);
					$sql                             = "SELECT * FROM {$wpdb->prefix}posts WHERE 1=1 and (post_content like '%wp:ninja-forms/form {\"formID\":" . $row->id . "%' OR post_content like '%[ninja_form id=\"" . $row->id . "\"]%' OR post_content like '%[ninja_form id=" . $row->id . "]%' OR post_content like \"%[ninja_form id='" . $row->id . "']%\" ) and post_status='publish' AND post_type NOT IN ('kadence_wootemplate', 'revision')";
					$form_pages                      = $wpdb->get_results( $sql );
					if ( $form_pages ) {
						foreach ( $form_pages as $form_page ) {
							if ( 'wp_block' === $form_page->post_type ) {
								$wp_block_pages = get_wp_block_pages( $form_page->ID );
								if ( $wp_block_pages ) {
									foreach ( $wp_block_pages as $wp_block_page ) {
										$forms['NinjaForms'][ $row->id ]['pages'][] = array(
											'ID'  => $wp_block_page->ID,
											'url' => must_ssl_url( get_the_permalink( $wp_block_page->ID ) ),
										);
									}
								}
							} else {
								$forms['NinjaForms'][ $row->id ]['pages'][] = array(
									'ID'  => $form_page->ID,
									'url' => must_ssl_url( get_the_permalink( $form_page->ID ) ),
								);
							}
						}
					}
					$sql         = "select * from {$wpdb->prefix}nf3_fields where parent_id={$row->id} order by id ASC";
					$fields_data = $wpdb->get_results( $sql );
					$tablename   = $wpdb->prefix . 'nf3_fields';
					$sql         = $wpdb->prepare( 'Select * from %s where parent_id=%d order by id ASC', $tablename, $row->id );
					$fields_data = $wpdb->get_results( $sql, ARRAY_A );

					$fields = array();

					foreach ( $fields_data as $field ) {
						$type     = $field->type;
						$field_id = 'nf-field-' . $field->id;
						switch ( $type ) {
							case 'listcheckbox':
								$fields[ $field->id ] = array(
									'type'       => $field->type,
									'id'         => $field->id,
									'formId'     => $field->parent_id,
									'label'      => $field->label,
									'name'       => $field_id,
									'field_name' => $field_id,
									'field_id'   => $field_id,
								);
								$options              = $this->nf_get_field_options( $field->id );
								foreach ( $options as $key => $option ) {
									$options[ $key ]['field_id'] = $field_id . '-' . $key;

								}
								$fields[ $field->id ]['choices'] = $options;
								break;
							case 'listradio':
								$fields[ $field->id ] = array(
									'type'       => $field->type,
									'id'         => $field->id,
									'formId'     => $field->parent_id,
									'label'      => $field->label,
									'name'       => $field_id,
									'field_name' => $field_id,
									'field_id'   => $field_id,
								);
								$options              = $this->nf_get_field_options( $field->id );
								foreach ( $options as $key => $option ) {
									$options[ $key ]['field_id'] = $field_id . '-' . $key;

								}
								$fields[ $field->id ]['choices'] = $options;
								break;
							default:
								$fields[ $field->id ] = array(
									'type'       => $field->type,
									'id'         => $field->id,
									'formId'     => $field->parent_id,
									'label'      => $field->label,
									'name'       => $field_id,
									'field_name' => $field_id,
									'field_id'   => $field_id,
								);
								break;
						}
						// var_dump($field);.
					}
					$forms['NinjaForms'][ $row->id ]['fields'] = $fields;

					// var_dump(Ninja_Forms()->form( $row->id )->field(1)->get());.
				}
			}
		} // NINJA FORMS

		if ( is_plugin_active( 'wpforms/wpforms.php' ) || is_plugin_active( 'wpforms-lite/wpforms.php' ) ) {
			$sql     = "Select * from {$wpdb->prefix}posts where post_type = 'wpforms' and post_status = 'publish' order by ID ASC";
			$results = $wpdb->get_results( $sql );
			$args    = array(
				'post_type'   => 'wpforms',
				'post_status' => 'publish',
				'order'       => 'ASC',
				'orderby'     => 'ID',
				'numberposts' => -1,
			);
			$results = get_posts( $args );
			if ( $results ) {
				foreach ( $results as $row ) {
					$forms['WpForms'][ $row->ID ] = array(
						'ID'   => $row->ID,
						'Name' => $row->post_title,
					);
					$form_data                    = json_decode( $row->post_content, true );
					$fields                       = $form_data['fields'];
					$form_location                = get_post_meta( $row->ID, 'wpforms_form_locations', true );
					if ( $form_location ) {
						foreach ( $form_location as $form_page ) {
							$forms['WpForms'][ $row->ID ]['pages'][] = array(
								'ID'  => $form_page['id'],
								'url' => must_ssl_url( get_the_permalink( $form_page['id'] ) ),
							);
						}
					}
					foreach ( $fields as $index => $field ) {
						$type = $field['type'];
						switch ( $type ) {

							case 'name':
								$name_format = $field['format'];
								if ( 'simple' === $name_format ) {
									$fields[ $index ]['sub_fields'][0]['type']     = 'text';
									$fields[ $index ]['sub_fields'][0]['name']     = 'Name';
									$fields[ $index ]['sub_fields'][0]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'];
								}
								if ( 'first-last' === $name_format ) {
									$fields[ $index ]['sub_fields'][0]['type']     = 'text';
									$fields[ $index ]['sub_fields'][0]['name']     = 'First Name';
									$fields[ $index ]['sub_fields'][0]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'];
									$fields[ $index ]['sub_fields'][1]['type']     = 'text';
									$fields[ $index ]['sub_fields'][1]['name']     = 'Last Name';
									$fields[ $index ]['sub_fields'][1]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'] . '-last';
								}

								if ( 'first-middle-last' === $name_format ) {
									$fields[ $index ]['sub_fields'][0]['type']     = 'text';
									$fields[ $index ]['sub_fields'][0]['name']     = 'First Name';
									$fields[ $index ]['sub_fields'][0]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'];
									$fields[ $index ]['sub_fields'][1]['type']     = 'text';
									$fields[ $index ]['sub_fields'][1]['name']     = 'Middle Name';
									$fields[ $index ]['sub_fields'][1]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'] . '-middle';
									$fields[ $index ]['sub_fields'][2]['type']     = 'text';
									$fields[ $index ]['sub_fields'][2]['name']     = 'Last Name';
									$fields[ $index ]['sub_fields'][2]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'] . '-last';
								}

								break;
							case 'checkbox':
								foreach ( $field['choices'] as $key => $val ) {
									$fields[ $index ]['choices'][ $key ]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'] . '_' . $key;
								}
								break;
							case 'radio':
								foreach ( $field['choices'] as $key => $val ) {
									$fields[ $index ]['choices'][ $key ]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'] . '_' . $key;
								}
								break;
							default:
								$fields[ $index ]['field_id'] = 'wpforms-' . $row->ID . '-field_' . $field['id'];
								break;
						}
					}
					$forms['WpForms'][ $row->ID ]['fields'] = $fields;
				}
			}
		} // WP Forms

		if ( is_plugin_active( 'formidable/formidable.php' ) ) {
			$sql       = "Select * from {$wpdb->prefix}frm_forms where 1=1 and status = 'published'";
			$tablename = $wpdb->prefix . 'frm_forms';
			$sql       = $wpdb->prepare( 'Select * from %s where 1=%d and status=%s', $tablename, 1, 'published' );
			$results   = $wpdb->get_results( $sql, ARRAY_A );

			$results = $wpdb->get_results( $sql );
			if ( $results ) {
				foreach ( $results as $row ) {
					$forms['Formidable'][ $row->id ] = array(
						'ID'   => $row->id,
						'Name' => $row->name,
					);

					$sql = "SELECT ID FROM {$wpdb->prefix}posts	 WHERE 1=1 and (post_content like '%[formidable id=\"" . $row->id . "\"%' OR post_content like '%[formidable id=" . $row->id . "]%') and post_status='publish' AND post_type NOT IN ('kadence_wootemplate', 'revision')";

					$form_pages = $wpdb->get_results( $sql );
					if ( $form_pages ) {
						foreach ( $form_pages as $form_page ) {

							if ( 'wp_block' === $form_page->post_type ) {

								$wp_block_pages = get_wp_block_pages( $form_page->ID );
								if ( $wp_block_pages ) {
									foreach ( $wp_block_pages as $wp_block_page ) {
										$forms['Formidable'][ $row->id ]['pages'][] = array(
											'ID'  => $wp_block_page->ID,
											'url' => must_ssl_url( get_the_permalink( $wp_block_page->ID ) ),
										);
									}
								}
							} else {
								$forms['Formidable'][ $row->id ]['pages'][] = array(
									'ID'  => $form_page->ID,
									'url' => must_ssl_url( get_the_permalink( $form_page->ID ) ),
								);
							}
						}
					}

					$fields      = array();
					$sql         = "Select * from {$wpdb->prefix}frm_fields where form_id={$row->id}";
					$fields_data = $wpdb->get_results( $sql );
					$tablename   = $wpdb->prefix . 'frm_fields';
					$sql         = $wpdb->prepare( 'Select * from %s where form_id=%d', $tablename, $row->id );
					$results     = $wpdb->get_results( $sql, ARRAY_A );
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
										'formId'      => $row->id,
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
										'type'    => $field->type,
										'key'     => $field->field_key,
										'id'      => $field->id,
										'formId'  => $row->id,
										'Name'    => $field->name,
										'label'   => $field->name,
										'choices' => $field_options,
									);
									break;
								case 'checkbox':
									$field_options = maybe_unserialize( $field->options );
									foreach ( $field_options as $key => $val ) {
										$field_options[ $key ]['field_id'] = $field_id . '-' . $key;
									}
									$fields[ $field->id ] = array(
										'type'    => $field->type,
										'key'     => $field->field_key,
										'id'      => $field->id,
										'formId'  => $row->id,
										'Name'    => $field->name,
										'label'   => $field->name,
										'choices' => $field_options,
									);

									break;
								default:
									$fields[ $field->id ] = array(
										'type'       => $field->type,
										'key'        => $field->field_key,
										'id'         => $field->id,
										'formId'     => $row->id,
										'Name'       => $field->name,
										'label'      => $field->name,
										'field_name' => $field_id,
										'field_id'   => $field_id,
									);
									break;
							}
						}
						$forms['Formidable'][ $row->id ]['fields'] = $fields;
					}
				}
			}
		} // Formidable.

		// wpcf7_contact_form.
		if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
			$sql     = "Select * from {$wpdb->prefix}posts where post_type = 'wpcf7_contact_form' and post_status = 'publish' order by ID ASC";
			$results = $wpdb->get_results( $sql );
			$args    = array(
				'post_type'   => 'wpcf7_contact_form',
				'post_status' => 'publish',
				'order'       => 'ASC',
				'orderby'     => 'ID',
				'numberposts' => -1,
			);
			$results = get_posts( $args );
			if ( $results ) {
				foreach ( $results as $row ) {
					$forms['CF7'][ $row->ID ] = array(
						'ID'   => $row->ID,
						'Name' => $row->post_title,
					);
					$form_data                = get_post_meta( $row->ID, '_form', true );
					$contact_form             = WPCF7_ContactForm::get_instance( $row->ID );
					$form_fields              = $contact_form->scan_form_tags();
					$fields                   = array();

					$sql        = "SELECT ID FROM {$wpdb->prefix}posts	 WHERE 1=1 and (post_content like '%wp:contact-form-7/contact-form-selector {\"id\":" . $row->ID . "%' OR post_content like '%[contact-form-7 id=\"" . $row->ID . "\"%' OR post_content like '%[contact-form-7 id=" . $row->ID . "%' OR post_content like \"%[contact-form-7 id=" . $row->ID . "%\") and post_status='publish' AND post_type NOT IN ('kadence_wootemplate', 'revision')";
					$form_pages = $wpdb->get_results( $sql );
					if ( $form_pages ) {
						foreach ( $form_pages as $form_page ) {
							if ( 'wp_block' === $form_page->post_type ) {

								$wp_block_pages = get_wp_block_pages( $form_page->ID );
								if ( $wp_block_pages ) {
									foreach ( $wp_block_pages as $wp_block_page ) {
										$forms['CF7'][ $row->ID ]['pages'][] = array(
											'ID'  => $wp_block_page->ID,
											'url' => must_ssl_url( get_the_permalink( $wp_block_page->ID ) ),
										);
									}
								}
							} else {
								$forms['CF7'][ $row->ID ]['pages'][] = array(
									'ID'  => $form_page->ID,
									'url' => must_ssl_url( get_the_permalink( $form_page->ID ) ),
								);
							}
						}
					}

					foreach ( $form_fields as $key => $field ) {
						$fields[ $key ]['type'] = $field->basetype;
						if ( 'submit' !== $field->basetype ) {
							$fields[ $key ]['name']       = $field->name;
							$fields[ $key ]['field_name'] = $field->name;
							if ( str_contains( $field->type, '*' ) ) {
								$fields[ $key ]['Required'] = 'TRUE';
							} else {
								$fields[ $key ]['Required'] = 'FAlSE';
							}
						}
					}
					$forms['CF7'][ $row->ID ]['fields'] = $fields;

				}
			}
		}
		set_transient( 'checkview_forms_list_transient', $forms, 12 * HOUR_IN_SECONDS );
		wp_send_json( $forms, 200 );
	}
	/**
	 * Validates Token.
	 *
	 * @param \WP_REST_Request $request request data with the api call.
	 * @return json/array
	 */
	public function get_items_permissions_check( \WP_REST_Request $request ) {
		// Wanted to Add JWT AUTH could not add because of limited time.
		// set no cache header.
		nocache_headers();
		$jwt_token = $request->get_param( '_checkview_token' );
		$jwt_token = isset( $jwt_token ) ? sanitize_text_field( $jwt_token ) : null;
		// checking for JWT token.
		if ( ! isset( $jwt_token ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'code'    => 400,
					'message' => esc_html__( 'Invalid Token', 'checkform-helper' ),
				),
				400
			);
			wp_die();
		}
		$valid_token = validate_jwt_token( $jwt_token );
		if ( true !== $valid_token ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'code'    => 400,
					'message' => esc_html( $valid_token ),
				),
				400
			);
			wp_die();
		}

		return array(
			'code' => 'jwt_auth_valid_token',
			'data' => array(
				'status' => 200,
			),
		);
	}
}
