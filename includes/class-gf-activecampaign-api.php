<?php

class GF_ActiveCampaign_API {

	/**
	 * ActiveCampaign API URL.
	 *
	 * @since  unknown
	 * @access protected
	 * @var    string $api_url ActiveCampaign API URL.
	 */
	protected $api_url;

	/**
	 * ActiveCampaign API Key.
	 *
	 * @since  unkown
	 * @access protected
	 * @var    string $api_key ActiveCampaign API Key.
	 */
	protected $api_key;

	function __construct( $api_url, $api_key = null ) {

		$this->api_url = $api_url;
		$this->api_key = $api_key;

	}

	function default_options() {

		return array(
			'api_key'    => $this->api_key,
			'api_output' => 'json',
		);

	}

	/**
	 * Makes a request to the ActiveCampaign API.
	 *
	 * @since unknown
	 * @since 2.0 Updated to return WP_Error instead of die().
	 *
	 * @param string $action  The API action.
	 * @param array  $options The request body or query string arguments.
	 * @param string $method  The request method; defaults to GET.
	 *
	 * @return array|WP_Error
	 */
	function make_request( $action, $options = array(), $method = 'GET' ) {

		/* Build request options string. */
		$request_options               = $this->default_options();
		$request_options['api_action'] = $action;

		if ( $request_options['api_action'] == 'contact_edit' ) {
			$request_options['overwrite'] = '0';
		}

		$request_options = http_build_query( $request_options );
		$request_options .= ( $method == 'GET' ) ? '&' . http_build_query( $options ) : null;

		/* Build request URL. */
		$request_url = untrailingslashit( $this->api_url ) . '/admin/api.php?' . $request_options;

		/**
		 * Allows request timeout to Active Campaign to be changed. Timeout is in seconds
		 *
		 * @since 1.5
		 */
		$timeout = apply_filters( 'gform_activecampaign_request_timeout', 20 );

		/* Execute request based on method. */
		switch ( $method ) {

			case 'POST':
				$args     = array(
					'body'    => $options,
					'timeout' => $timeout,
				);
				$response = wp_remote_post( $request_url, $args );
				break;

			case 'GET':
				$args     = array( 'timeout' => $timeout );
				$response = wp_remote_get( $request_url, $args );
				break;

		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			return new WP_Error( $response_code, wp_remote_retrieve_response_message( $response ) );
		}

		return json_decode( $response['body'], true );

	}

	/**
	 * Test the provided API credentials.
	 *
	 * @since unknown
	 * @since 2.0 Updated to use make_request().
	 *
	 * @access public
	 * @return bool|WP_Error
	 */
	function auth_test() {

		$response = $this->make_request( 'list_paginator' );

		return is_wp_error( $response ) ? $response : true;

	}


	/**
	 * Add a new custom list field.
	 *
	 * @access public
	 *
	 * @param array $custom_field
	 *
	 * @return array|WP_Error
	 */
	function add_custom_field( $custom_field ) {

		return $this->make_request( 'list_field_add', $custom_field, 'POST' );

	}

	/**
	 * Get all custom list fields.
	 *
	 * @access public
	 * @return array|WP_Error
	 */
	function get_custom_fields() {

		return $this->make_request( 'list_field_view', array( 'ids' => 'all' ) );

	}

	/**
	 * Get all forms in the system.
	 *
	 * @access public
	 * @return array|WP_Error
	 */
	function get_forms() {

		return $this->make_request( 'form_getforms' );

	}

	/**
	 * Get specific list.
	 *
	 * @access public
	 *
	 * @param int $list_id
	 *
	 * @return array|WP_Error
	 */
	function get_list( $list_id ) {

		return $this->make_request( 'list_view', array( 'id' => $list_id ) );

	}

	/**
	 * Get all lists in the system.
	 *
	 * @access public
	 * @return array|WP_Error
	 */
	function get_lists() {

		return $this->make_request( 'list_list', array( 'ids' => 'all' ) );

	}

	/**
	 * Add or edit a contact.
	 *
	 * @access public
	 *
	 * @param mixed $contact
	 *
	 * @return array|WP_Error
	 */
	function sync_contact( $contact ) {

		return $this->make_request( 'contact_sync', $contact, 'POST' );

	}

	/**
	 * Add note to contact.
	 *
	 * @param string $contact_id The ActiveCampaign contact ID.
	 * @param string $list_id    The ActiveCampaign list ID.
	 * @param string $note       The note to be added.
	 *
	 * @return array|WP_Error
	 */
	function add_note( $contact_id, $list_id, $note ) {

		$request = array(
			'id'     => $contact_id,
			'listid' => $list_id,
			'note'   => $note
		);

		return $this->make_request( 'contact_note_add', $request, 'POST' );
	}
}
