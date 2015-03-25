<?php
/**
 * API Dispatcher.
 *
 * @author Iron Bound Designs|WP API ( many methods are taken from the WP API
 *         server class ).
 * @since  1.0
 */

/**
 * Class ITELIC_API_Dispatch
 */
class ITELIC_API_Dispatch {

	/**
	 * @var string
	 */
	const TAG = 'itelic_api';

	/**
	 * @var ITELIC_API_Endpoint[]
	 */
	private static $endpoints = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_rewrites' ) );
		add_action( 'template_redirect', array( $this, 'dispatch' ) );
	}

	/**
	 * Register the rewrite rules.
	 *
	 * @since 1.0
	 */
	public function register_rewrites() {
		add_rewrite_tag( '%' . self::TAG . '%', '([^&]+)' );
		add_rewrite_rule( 'itelic-api/([^&]+)/?', 'index.php?' . self::TAG . '=$matches[1]', 'top' );
	}

	/**
	 * Dispatch an API request.
	 */
	public function dispatch() {
		/**
		 * @var WP_Query $wp_query
		 */
		global $wp_query;

		$action = $wp_query->get( self::TAG );

		if ( $action ) {

			if ( ! isset( self::$endpoints[ $action ] ) ) {
				$response = new ITELIC_API_Response( array(
					'success' => false,
					'error'   => array(
						'code'    => 404,
						'message' => __( "API Action Not Found", ITELIC::SLUG )
					)
				), 404 );

				$this->send_response( $response );
			} else {
				$endpoint = self::$endpoints[ $action ];

				if ( $endpoint instanceof ITELIC_API_Interface_Authenticatable ) {
					if ( ! $this->handle_auth( $endpoint ) ) {
						$this->send_response( $this->generate_auth_missing( $endpoint ) );
					}
				}

				try {
					$response = $endpoint->serve( new ArrayObject( $_GET ), new ArrayObject( $_POST ) );
				}
				catch ( Exception $e ) {
					$response = $this->generate_response_from_exception( $e );
				}

				$this->send_response( $response );
			}
		}
	}

	/**
	 * Send the response to the client.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_API_Response $response
	 *
	 * @return void This method should end the request with die()
	 */
	protected function send_response( ITELIC_API_Response $response ) {

		$content_type = 'application/json';
		$this->send_header( 'Content-Type', $content_type . '; charset=' . get_option( 'blog_charset' ) );

		$this->send_headers( $response->get_headers() );
		$this->set_status( $response->get_status() );

		$result = $this->response_to_data( $response );
		$result = json_encode( $result );

		$json_error_message = $this->get_json_last_error();

		if ( $json_error_message ) {

			$json_error_obj = new ITELIC_API_Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 500,
					'message' => $json_error_message
				),
				500
			) );

			$result = $this->response_to_data( $json_error_obj );
			$result = json_encode( $result );
		}

		echo $result;

		die();
	}

	/**
	 * Check authentication, keeping the mode in mind.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_API_Interface_Authenticatable $endpoint
	 *
	 * @return bool
	 */
	protected function handle_auth( ITELIC_API_Interface_Authenticatable $endpoint ) {
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) || trim( $_SERVER['PHP_AUTH_USER'] ) == '' ) {
			return false;
		}

		$license_key = sanitize_text_field( $_SERVER['PHP_AUTH_USER'] );

		try {
			$key = itelic_get_key( $license_key );
		}
		catch ( Exception $e ) {
			return false;
		}

		$endpoint->give_license_key( $key );

		if ( $endpoint->get_mode() == ITELIC_API_Interface_Authenticatable::MODE_ACTIVE ) {
			return $key->get_status() == ITELIC_Key::ACTIVE;
		} else {
			return true;
		}
	}

	/**
	 * Retrieve the response object for when authentication is missing.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_API_Interface_Authenticatable $endpoint
	 *
	 * @return ITELIC_API_Response
	 */
	protected function generate_auth_missing( ITELIC_API_Interface_Authenticatable $endpoint ) {
		$response = new ITELIC_API_Response( array(
			'success' => false,
			'error'   => array(
				'code'    => $endpoint->get_error_code(),
				'message' => $endpoint->get_error_message()
			)
		), 401 );

		switch ( $endpoint->get_mode() ) {
			case ITELIC_API_Interface_Authenticatable::MODE_ACTIVE:
				$realm = __( "An active license key is required to access this resource, passed as the username. Leave password blank.", ITELIC::SLUG );
				break;

			case ITELIC_API_Interface_Authenticatable::MODE_EXISTS:
			default:
				$realm = __( "A license key is required to access this resource, passed as the username. Leave password blank.", ITELIC::SLUG );
				break;
		}

		$response->header( 'WWW-Authenticate', "Basic realm=\"{$realm}\"" );

		return $response;
	}

	/**
	 * Generate a response object from an Exception.
	 *
	 * @since 1.0
	 *
	 * @param Exception $e
	 *
	 * @return ITELIC_API_Response
	 */
	protected function generate_response_from_exception( Exception $e ) {

		return new ITELIC_API_Response( array(
			'success' => false,
			'error'   => array(
				'code'    => 00,
				'message' => sprintf( __( "Unknown error %s with code %d", ITELIC::SLUG ), $e->getMessage(), $e->getCode() )
			)
		) );
	}

	/**
	 * Convert a response to data to send
	 *
	 * @param ITELIC_API_Response $response Response object
	 *
	 * @return array
	 */
	public function response_to_data( ITELIC_API_Response $response ) {
		$data = $this->prepare_response( $response->get_data() );

		return $data;
	}

	/**
	 * Returns if an error occurred during most recent JSON encode/decode
	 * Strings to be translated will be in format like "Encoding error: Maximum
	 * stack depth exceeded"
	 *
	 * @return boolean|string Boolean false or string error message
	 */
	protected function get_json_last_error() {
		// see https://core.trac.wordpress.org/ticket/27799
		if ( ! function_exists( 'json_last_error' ) ) {
			return false;
		}

		$last_error_code = json_last_error();
		if ( ( defined( 'JSON_ERROR_NONE' ) && $last_error_code === JSON_ERROR_NONE ) || empty( $last_error_code ) ) {
			return false;
		}

		return json_last_error_msg();
	}

	/**
	 * Send a HTTP status code
	 *
	 * @param int $code HTTP status
	 */
	protected function set_status( $code ) {
		status_header( $code );
	}

	/**
	 * Send a HTTP header
	 *
	 * @param string $key   Header key
	 * @param string $value Header value
	 */
	protected function send_header( $key, $value ) {
		// Sanitize as per RFC2616 (Section 4.2):
		//   Any LWS that occurs between field-content MAY be replaced with a
		//   single SP before interpreting the field value or forwarding the
		//   message downstream.
		$value = preg_replace( '/\s+/', ' ', $value );
		header( sprintf( '%s: %s', $key, $value ) );
	}

	/**
	 * Send multiple HTTP headers
	 *
	 * @param $headers array Map of header name to header value
	 */
	protected function send_headers( $headers ) {
		foreach ( $headers as $key => $value ) {
			$this->send_header( $key, $value );
		}
	}

	/**
	 * Prepares response data to be serialized to JSON
	 *
	 * This supports the JsonSerializable interface for PHP 5.2-5.3 as well.
	 *
	 * @param mixed $data Native representation
	 *
	 * @return array|string Data ready for `json_encode()`
	 */
	public function prepare_response( $data ) {

		switch ( gettype( $data ) ) {
			case 'boolean':
			case 'integer':
			case 'double':
			case 'string':
			case 'NULL':
				// These values can be passed through
				return $data;

			case 'array':
				// Arrays must be mapped in case they also return objects
				return array_map( array( $this, 'prepare_response' ), $data );

			case 'object':

				if ( $data instanceof ITELIC_API_Serializable ) {
					$data = $data->get_api_data();
				} else {
					$data = get_object_vars( $data );
				}

				// Now, pass the array (or whatever was returned from
				// jsonSerialize through.)
				return $this->prepare_response( $data );

			default:
				return null;
		}
	}

	/**
	 * Register an endpoint.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_API_Endpoint $endpoint
	 * @param string              $action Action this endpoint responds to.
	 */
	public static function register_endpoint( ITELIC_API_Endpoint $endpoint, $action ) {
		self::$endpoints[ (string) $action ] = $endpoint;
	}

	/**
	 * Get the URL for an API Endpoint.
	 *
	 * @since 1.0
	 *
	 * @param $slug
	 *
	 * @return string
	 */
	public static function get_url( $slug ) {
		return site_url( "itelic-api/$slug/" );
	}
}