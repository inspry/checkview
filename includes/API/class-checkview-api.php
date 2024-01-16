<?php
/**
 * Hanldes CPT API functions.
 *
 * @link       https://checkview.io
 * @since      1.0.0
 *
 * @package    CheckView
 * @subpackage CheckView/includes/API
 */

/**
 * Fired for the plugin Forms API registeration and hadling CURD.
 *
 * This class defines all code necessary to run for handling CheckView Form API CURD operations.
 *
 * @since      1.0.0
 * @package    CheckView
 * @subpackage CheckView/includes/API
 * @author     CheckView <checkview> https://checkview.io/
 */
class CheckView_Api {

	/**
	 * Store errors to display if the JWT Token is wrong
	 *
	 * @var WP_Error
	 */
	private $jwt_error = null;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}
	/**
	 * Registers the rest api routes for our forms and related data.
	 *
	 * Registers the rest api routes for our forms and related data.
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
			'checkview/v1',
			'/forms/registerformtest',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'checkview_register_form_test' ),
				'permission_callback' => array( $this, 'checkview_get_items_permissions_check' ),
				'args'                => array(
					'_checkview_token' => array(
						'required' => true,
					),
					'frm_id'           => array(
						'required' => true,
					),
					'pg_id'            => array(
						'required' => true,
					),
					'type'             => array(
						'required' => true,
					),
					'send_to'          => array(
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'checkview/v1',
			'/forms/formstestresults',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'checkview_get_available_forms_test_results' ),
				'permission_callback' => array( $this, 'checkview_get_items_permissions_check' ),
				'args'                => array(
					'uid'              => array(
						'required' => true,
					),
					'_checkview_token' => array(
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'checkview/v1',
			'/forms/deleteformstest',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'checkview_delete_forms_test_results' ),
				'permission_callback' => array( $this, 'checkview_get_items_permissions_check' ),
				'args'                => array(
					'uid'              => array(
						'required' => true,
					),
					'_checkview_token' => array(
						'required' => true,
					),
				),
			)
		);
	} // end checkview_register_rest_route

	/**
	 * Retrieves the available forms.
	 *
	 * @return WP_REST_Response/json
	 */
	public function checkview_get_available_forms_list() {
		global $wpdb;
		$forms_list = get_transient( 'checkview_forms_list_transient' );
		if ( null !== $this->$jwt_error ) {
			return new WP_Error(
				400,
				esc_html__( 'Use a valid JWT token.', 'checkview' ),
				esc_html( $this->$jwt_error )
			);
			wp_die();
		}
		if ( '' !== $forms_list && null !== $forms_list && false !== $forms_list) {
			return new WP_REST_Response(
				array(
					'status'        => 200,
					'response'      => esc_html__( 'Successfully retrieved the forms list.', 'checkview' ),
					'body_response' => $forms_list,
				)
			);
			wp_die();
		}
		$forms = array();
		if ( ! is_admin() ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
			$tablename = $wpdb->prefix . 'gf_form';
			$results   = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where is_active=%d and is_trash=%d order by ID ASC', 1, 0 ) );
			if ( $results ) {
				foreach ( $results as $row ) {
					$forms['GravityForms'][ $row->id ] = array(
						'ID'   => $row->id,
						'Name' => $row->title,
					);
					$tablename                         = $wpdb->prefix . 'gf_addon_feed';
					$addons                            = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where is_active=%d and form_id=%d', 1, $row->id ) );
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
				}
			}
		} // For Gravity Form

		if ( is_plugin_active( 'fluentform/fluentform.php' ) ) {
			$tablename = $wpdb->prefix . 'fluentform_forms';
			$results   = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where status=%s order by ID ASC', 'published' ) );
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
				}
			}
		} // FLUENT FORMS
		if ( is_plugin_active( 'ninja-forms/ninja-forms.php' ) ) {
			$tablename = $wpdb->prefix . 'nf3_forms';
			$results   = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . 'order by ID ASC' ) );
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
				}
			}
		} // NINJA FORMS

		if ( is_plugin_active( 'wpforms/wpforms.php' ) || is_plugin_active( 'wpforms-lite/wpforms.php' ) ) {
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
					$form_location                = get_post_meta( $row->ID, 'wpforms_form_locations', true );
					if ( $form_location ) {
						foreach ( $form_location as $form_page ) {
							$forms['WpForms'][ $row->ID ]['pages'][] = array(
								'ID'  => $form_page['id'],
								'url' => must_ssl_url( get_the_permalink( $form_page['id'] ) ),
							);
						}
					}
				}
			}
		} // WP Forms

		if ( is_plugin_active( 'formidable/formidable.php' ) ) {
			$tablename = $wpdb->prefix . 'frm_forms';
			$results   = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where 1=%d and status=%s', 1, 'published' ) );
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
				}
			}
		} // Formidable.

		// wpcf7_contact_form.
		if ( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
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
				}
			}
		}
		if ( $forms && ! empty( $forms ) && false !== $forms && '' !== $forms ) {
			set_transient( 'checkview_forms_list_transient', $forms, 12 * HOUR_IN_SECONDS );
			return new WP_REST_Response(
				array(
					'status'        => 200,
					'response'      => esc_html__( 'Successfully retrieved the forms list.', 'checkview' ),
					'body_response' => $forms,
				)
			);
		} else {
			return new WP_REST_Response(
				array(
					'status'        => 200,
					'response'      => esc_html__( 'No forms to show.', 'checkview' ),
					'body_response' => $forms,
				)
			);
		}
		wp_die();
	}

	/**
	 * Reterieves all the avaiable test results for forms.
	 *
	 * @param WP_REST_Request $request the request param with the API call.
	 * @return WP_REST_Response/WP_Error/json
	 */
	public function checkview_get_available_forms_test_results( WP_REST_Request $request ) {
		global $wpdb;
		$uid = $request->get_param( 'uid' );
		$uid = isset( $uid ) ? sanitize_text_field( $uid ) : null;

		$error   = array(
			'status'  => 'error',
			'code'    => 400,
			'message' => esc_html__( 'No Result Found', 'checkview' ),
		);
		$results = array();
		if ( '' === $uid || null === $uid ) {
			return new WP_Error(
				400,
				esc_html__( 'Empty UID.', 'checkview' ),
				$error
			);
			wp_die();
		} else {
			$tests_transients = get_transient( 'checkview_forms_test_transient' );
			if ( '' !== $tests_transients && null !== $tests_transients ) {
				return new WP_REST_Response(
					array(
						'status'        => 200,
						'response'      => esc_html__( 'Successfully retrieved the test results.', 'checkview' ),
						'body_response' => $tests_transients,
					)
				);
				wp_die();
			}
			$tablename = $wpdb->prefix . 'cv_entry';
			$result    = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where uid=%d', $uid ) );
			$tablename = $wpdb->prefix . 'cv_entry_meta';
			$rows      = $wpdb->get_results( $wpdb->prepare( 'Select * from ' . $tablename . ' where uid=%d order by id ASC', $uid ) );
			if ( $rows ) {
				foreach ( $rows as $row ) {
					if ( strtolower( 'gravityforms' === $result->form_type ) ) {
						$results[] = array(
							'field_id'    => 'input_' . $row->form_id . '_' . str_replace( '.', '_', $row->meta_key ),
							'field_value' => $row->meta_value,
						);

					} elseif ( 'cf7' === strtolower( $result->form_type ) ) {
						$value = $row->meta_value;
						if ( strpos( $value, 'htt' ) !== false ) {
							$value = html_entity_decode( $value );
						}
						$results[] = array(
							'field_id'    => '',
							'field_name'  => $row->meta_key,
							'field_value' => $value,
						);
					} else {

						$results[] = array(
							'field_id'    => $row->meta_key,
							'field_value' => $row->meta_value,
						);
					}
				}
				set_transient( 'checkview_forms_test_transient', $results, 12 * HOUR_IN_SECONDS );
				return new WP_REST_Response(
					array(
						'status'        => 200,
						'response'      => esc_html__( 'Successfully retrieved the results.', 'checkview' ),
						'body_response' => $results,
					)
				);
				wp_die();
			} else {
				return new WP_Error(
					400,
					esc_html__( 'Failed to retrieve the results.', 'checkview' ),
					$error
				);
				wp_die();
			}
		}
	}

	/**
	 * Registers form test to be validated.
	 *
	 * @param WP_REST_Request $request Object with the API call.
	 * @return WP_REST_Response/WP_Error
	 */
	public function checkview_register_form_test( WP_REST_Request $request ) {
		$frm_id  = $request->get_param( 'frm_id' );
		$frm_id  = isset( $frm_id ) ? intval( $frm_id ) : '';
		$pg_id   = $request->get_param( 'pg_id' );
		$pg_id   = isset( $pg_id ) ? intval( $pg_id ) : '';
		$type    = $request->get_param( 'type' );
		$type    = isset( $type ) ? sanitize_text_field( $type ) : '';
		$send_to = $request->get_param( 'send_to' );
		$send_to = isset( $send_to ) ? sanitize_text_field( $send_to ) : '';

		if ( ! empty( $frm_id ) && ! empty( $pg_id ) && ! empty( $type ) && ! empty( $send_to ) ) {
			$args['form_id'] = $frm_id;
			$args['page_id'] = $pg_id;
			$args['type']    = $type;
			$args['send_to'] = $send_to;
			$cf_test         = get_option( 'CF_TEST_' . $args['page_id'], '' );
			update_option( 'CF_TEST_' . $args['page_id'], wp_json_encode( $args ) );
			return new WP_REST_Response(
				array(
					'status'        => 200,
					'response'      => 'success',
					'body_response' => esc_html__( 'Check Form Test Successfully Added', 'checkview' ),
				)
			);
			wp_die();
		} else {
			$error = array(
				'status'  => 'error',
				'code'    => 400,
				'message' => esc_html__( 'Details to register form test are not correct.', 'checkview-helper' ),
			);

			return new WP_Error(
				400,
				esc_html__( 'Details to register form test are not correct.', 'checkview' ),
				$error
			);
			wp_die();
		}
	}

	/**
	 * Deletes all the avaiable test results for forms.
	 *
	 * @param WP_REST_Request $request the request param with the API call.
	 * @return WP_REST_Response/WP_Error/json
	 */
	public function checkview_delete_forms_test_results( WP_REST_Request $request ) {
		global $wpdb;
		$uid = $request->get_param( 'uid' );
		$uid = isset( $uid ) ? sanitize_text_field( $uid ) : null;

		$error   = array(
			'status'  => 'error',
			'code'    => 400,
			'message' => esc_html__( 'No Result Found', 'checkview' ),
		);
		$results = array();
		if ( '' === $uid || null === $uid ) {
			return new WP_Error(
				400,
				esc_html__( 'Empty UID.', 'checkview' ),
				$error
			);
			wp_die();
		} else {
			$tablename = $wpdb->prefix . 'cv_entry';
			$result    = $wpdb->get_results( $wpdb->prepare( 'DELETE * from %s where uid=%d', $tablename, $uid ) );
			$tablename = $wpdb->prefix . 'cv_entry_meta';
			$rows      = $wpdb->get_results( $wpdb->prepare( 'DELETE * from %s where uid=%d order by id ASC', $tablename, $uid ) );
			if ( $rows ) {
				return new WP_REST_Response(
					array(
						'status'        => 200,
						'response'      => esc_html__( 'Successfully removed the results.', 'checkview' ),
						'body_response' => $results,
					)
				);
				wp_die();
			} else {
				return new WP_Error(
					400,
					esc_html__( 'Failed to remove the results.', 'checkview' ),
					$error
				);
				wp_die();
			}
		}
	}
	/**
	 * Validates Token.
	 *
	 * @param \WP_REST_Request $request request data with the api call.
	 * @return json/array
	 */
	public function checkview_get_items_permissions_check( \WP_REST_Request $request ) {
		// Wanted to Add JWT AUTH could not add because of limited time.
		// set no cache header.
		nocache_headers();
		$jwt_token = $request->get_param( '_checkview_token' );
		$jwt_token = isset( $jwt_token ) ? sanitize_text_field( $jwt_token ) : null;
		// checking for JWT token.
		if ( ! isset( $jwt_token ) ) {
			return new WP_Error(
				400,
				esc_html__( 'Invalid Token.', 'checkview' ),
				''
			);
			wp_die();
		}
		$valid_token = validate_jwt_token( $jwt_token );
		if ( true !== $valid_token ) {
			$this->$jwt_error = $valid_token;
			return new WP_Error(
				400,
				esc_html__( 'Invalid Token.', 'checkview' ),
				esc_html( $valid_token )
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
