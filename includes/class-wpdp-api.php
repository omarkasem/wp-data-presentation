<?php
/**
 * ACLED API Integration Class
 *
 * Handles OAuth authentication and data retrieval from ACLED API
 *
 * @package WP_Data_Presentation
 */

class WPDP_API {

	/**
	 * Base URL for ACLED API
	 */
	const BASE_URL = 'https://acleddata.com/api/';

	/**
	 * OAuth token endpoint
	 */
	const TOKEN_URL = 'https://acleddata.com/oauth/token';

	/**
	 * Client ID for OAuth
	 */
	const CLIENT_ID = 'acled';

	/**
	 * Option name for storing access token
	 */
	const ACCESS_TOKEN_OPTION = 'wpdp_acled_access_token';

	/**
	 * Option name for storing refresh token
	 */
	const REFRESH_TOKEN_OPTION = 'wpdp_acled_refresh_token';

	/**
	 * Option name for storing token expiry time
	 */
	const TOKEN_EXPIRY_OPTION = 'wpdp_acled_token_expiry';

	/**
	 * Get ACLED credentials from ACF options
	 *
	 * @return array|false Array with 'email' and 'password' or false if not set
	 */
	private function get_credentials() {
		$email    = get_field( 'acled_email_address', 'option' );
		$password = get_field( 'acled_api_key', 'option' );

		if ( empty( $email ) || empty( $password ) ) {
			return false;
		}

		return array(
			'email'    => $email,
			'password' => $password,
		);
	}

	/**
	 * Request a new access token from ACLED OAuth endpoint
	 *
	 * @return array|WP_Error Array with token data or WP_Error on failure
	 */
	public function request_access_token() {
		$credentials = $this->get_credentials();

		if ( ! $credentials ) {
			return new WP_Error(
				'missing_credentials',
				'ACLED API credentials not configured. Please set email and password in Data Presentation settings.'
			);
		}

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'    => array(
					'username'   => $credentials['email'],
					'password'   => $credentials['password'],
					'grant_type' => 'password',
					'client_id'  => self::CLIENT_ID,
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code !== 200 ) {
			return new WP_Error(
				'token_request_failed',
				sprintf(
					'Failed to obtain access token. Status: %d, Response: %s',
					$status_code,
					$body
				)
			);
		}

		if ( empty( $data['access_token'] ) ) {
			return new WP_Error(
				'invalid_token_response',
				'Access token not found in response'
			);
		}

		// Store tokens and expiry time
		$this->save_tokens( $data );

		return $data;
	}

	/**
	 * Refresh access token using refresh token
	 *
	 * @return array|WP_Error Array with token data or WP_Error on failure
	 */
	public function refresh_access_token() {
		$refresh_token = get_option( self::REFRESH_TOKEN_OPTION );

		if ( empty( $refresh_token ) ) {
			// No refresh token available, request new token
			return $this->request_access_token();
		}

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'    => array(
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
					'client_id'     => self::CLIENT_ID,
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			// If refresh fails, try to get a new token
			return $this->request_access_token();
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code !== 200 || empty( $data['access_token'] ) ) {
			// Refresh failed, request new token
			return $this->request_access_token();
		}

		// Store new tokens and expiry time
		$this->save_tokens( $data );

		return $data;
	}

	/**
	 * Save tokens to WordPress options
	 *
	 * @param array $token_data Token data from OAuth response
	 */
	private function save_tokens( $token_data ) {
		if ( ! empty( $token_data['access_token'] ) ) {
			update_option( self::ACCESS_TOKEN_OPTION, $token_data['access_token'] );
		}

		if ( ! empty( $token_data['refresh_token'] ) ) {
			update_option( self::REFRESH_TOKEN_OPTION, $token_data['refresh_token'] );
		}

		if ( ! empty( $token_data['expires_in'] ) ) {
			// Store expiry time (current time + expires_in seconds - 60 second buffer)
			$expiry_time = time() + $token_data['expires_in'] - 60;
			update_option( self::TOKEN_EXPIRY_OPTION, $expiry_time );
		}
	}

	/**
	 * Get valid access token (refresh if expired)
	 *
	 * @return string|WP_Error Access token or WP_Error on failure
	 */
	public function get_valid_access_token() {
		$access_token = get_option( self::ACCESS_TOKEN_OPTION );
		$expiry_time  = get_option( self::TOKEN_EXPIRY_OPTION );

		// Check if token exists and is not expired
		if ( ! empty( $access_token ) && ! empty( $expiry_time ) && time() < $expiry_time ) {
			return $access_token;
		}

		// Token is expired or doesn't exist, refresh it
		$token_data = $this->refresh_access_token();

		if ( is_wp_error( $token_data ) ) {
			return $token_data;
		}

		return $token_data['access_token'];
	}

	/**
	 * Make authenticated API request to ACLED
	 *
	 * @param string $endpoint API endpoint (e.g., 'acled/read')
	 * @param array  $params   Query parameters
	 * @return array|WP_Error API response data or WP_Error on failure
	 */
	public function get_data( $endpoint = 'acled/read', $params = array() ) {
		$access_token = $this->get_valid_access_token();

		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		// Build URL with query parameters
		$url = self::BASE_URL . ltrim( $endpoint, '/' );
		if ( ! empty( $params ) ) {
			$url = add_query_arg( $params, $url );
		}

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		// If unauthorized, try refreshing token and retry once
		if ( $status_code === 401 ) {
			$token_data = $this->refresh_access_token();

			if ( is_wp_error( $token_data ) ) {
				return $token_data;
			}

			// Retry request with new token
			$response = wp_remote_get(
				$url,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token_data['access_token'],
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			$body        = wp_remote_retrieve_body( $response );
		}

		if ( $status_code !== 200 ) {
			return new WP_Error(
				'api_request_failed',
				sprintf(
					'API request failed. Status: %d, Response: %s',
					$status_code,
					$body
				)
			);
		}

		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'invalid_json_response',
				'Invalid JSON response from API'
			);
		}

		return $data;
	}

	/**
	 * Clear all stored tokens
	 */
	public function clear_tokens() {
		delete_option( self::ACCESS_TOKEN_OPTION );
		delete_option( self::REFRESH_TOKEN_OPTION );
		delete_option( self::TOKEN_EXPIRY_OPTION );
	}

	/**
	 * Get current token status (for debugging)
	 *
	 * @return array Token status information
	 */
	public function get_token_status() {
		$access_token = get_option( self::ACCESS_TOKEN_OPTION );
		$expiry_time  = get_option( self::TOKEN_EXPIRY_OPTION );
		$has_refresh  = ! empty( get_option( self::REFRESH_TOKEN_OPTION ) );

		return array(
			'has_access_token'  => ! empty( $access_token ),
			'is_expired'        => empty( $expiry_time ) || time() >= $expiry_time,
			'expiry_time'       => $expiry_time,
			'has_refresh_token' => $has_refresh,
			'credentials_set'   => (bool) $this->get_credentials(),
		);
	}
}
