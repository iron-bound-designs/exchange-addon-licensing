<?php
/**
 * API Dispatcher.
 *
 * @author Iron Bound Designs|WP API ( many methods are taken from the WP API
 *         server class ).
 * @since  1.0
 */

namespace ITELIC\API;

use ITELIC\Activation;
use ITELIC\API\Contracts\Endpoint;
use ITELIC\API\Responder\Responder;
use ITELIC\Plugin;
use ITELIC\Key;
use ITELIC\API\Exception as API_Exception;
use ITELIC\API\Contracts\Authenticatable;

/**
 * Class Dispatch
 *
 * @package ITELIC\API
 */
class Dispatch {

	/**
	 * @var Responder
	 */
	private $responder;

	/**
	 * @var string
	 */
	const TAG = 'itelic_api';

	/**
	 * @var Endpoint[]
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
	 * Set the responder.
	 *
	 * @param Responder $responder
	 */
	public function set_responder( Responder $responder ) {
		$this->responder = $responder;
	}

	/**
	 * Dispatch an API request.
	 */
	public function dispatch() {

		/**
		 * @var \WP_Query $wp_query
		 */
		global $wp_query;

		$response = $this->process( $wp_query );

		if ( $response ) {
			$this->responder->respond( $response );
		}
	}

	/**
	 * Dispatch the request.
	 *
	 * @param \WP_Query $query
	 *
	 * @return Response
	 */
	public function process( \WP_Query $query ) {

		$action = $query->get( self::TAG );

		if ( $action ) {

			if ( ! isset( self::$endpoints[ $action ] ) ) {
				$response = new Response( array(
					'success' => false,
					'error'   => array(
						'code'    => 404,
						'message' => __( "API Action Not Found", Plugin::SLUG )
					)
				), 404 );

				return $response;
			} else {
				$endpoint = self::$endpoints[ $action ];

				if ( $endpoint instanceof Authenticatable ) {
					if ( ! $this->handle_auth( $endpoint ) ) {
						$response = $this->generate_auth_missing( $endpoint );
					}
				}

				if ( ! isset( $response ) ) {
					try {
						$response = $endpoint->serve( new \ArrayObject( $_GET ), new \ArrayObject( $_POST ) );
					}
					catch ( \Exception $e ) {
						$response = $this->generate_response_from_exception( $e );
					}
				}

				return $response;
			}
		}

		return null;
	}

	/**
	 * Send the response to the client.
	 *
	 * @since 1.0
	 *
	 * @param Response $response
	 *
	 * @return void This method should end the request with die()
	 */
	protected function send_response( Response $response ) {

		if ( is_null( $this->responder ) ) {
			status_header( 500 );
			echo 'An unexpected error occurred.';

			die();
		}

		echo $this->responder->respond( $response );

		die();
	}

	/**
	 * Check authentication, keeping the mode in mind.
	 *
	 * @since 1.0
	 *
	 * @param Authenticatable $endpoint
	 *
	 * @return bool
	 */
	protected function handle_auth( Authenticatable $endpoint ) {
		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) || trim( $_SERVER['PHP_AUTH_USER'] ) == '' ) {
			return false;
		}

		$license_key = $_SERVER['PHP_AUTH_USER'];

		try {
			$key = itelic_get_key( $license_key );
		}
		catch ( \Exception $e ) {
			return false;
		}

		if ( ! $key ) {
			return false;
		}

		if ( ! empty( $_SERVER['PHP_AUTH_PW'] ) ) {
			$activation = itelic_get_activation( $_SERVER['PHP_AUTH_PW'] );
		} else {
			$activation = null;
		}

		$endpoint->set_auth_license_key( $key );
		$endpoint->set_auth_activation( $activation );

		switch ( $endpoint->get_auth_mode() ) {
			case Authenticatable::MODE_ACTIVE:
				return $key->get_status() == Key::ACTIVE;
			case Authenticatable::MODE_EXISTS;
				return true;
			case Authenticatable::MODE_VALID_ACTIVATION:

				if ( ! $activation ) {
					return false;
				}

				if ( $activation->get_status() != Activation::ACTIVE ) {
					return false;
				}

				if ( $activation->get_key()->get_key() != $key->get_key() ) {
					return false;
				}

				return true;
			default:
				return false;
		}
	}

	/**
	 * Retrieve the response object for when authentication is missing.
	 *
	 * @since 1.0
	 *
	 * @param Authenticatable $endpoint
	 *
	 * @return Response
	 */
	protected function generate_auth_missing( Authenticatable $endpoint ) {
		$response = new Response( array(
			'success' => false,
			'error'   => array(
				'code'    => $endpoint->get_auth_error_code(),
				'message' => $endpoint->get_auth_error_message()
			)
		), 401 );

		switch ( $endpoint->get_auth_mode() ) {

			case Authenticatable::MODE_VALID_ACTIVATION:
				$realm = __( "An active license key is required to access this resource, passed as the username, and the activation record ID as the password.", Plugin::SLUG );
				break;

			case Authenticatable::MODE_ACTIVE:
				$realm = __( "An active license key is required to access this resource, passed as the username. Leave password blank.", Plugin::SLUG );
				break;

			case Authenticatable::MODE_EXISTS:
			default:
				$realm = __( "A license key is required to access this resource, passed as the username. Leave password blank.", Plugin::SLUG );
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
	 * @param \Exception $e
	 *
	 * @return Response
	 */
	protected function generate_response_from_exception( \Exception $e ) {

		if ( $e instanceof API_Exception ) {
			$code    = $e->getCode();
			$message = $e->getMessage();
			$status  = 400;
		} else {
			$code    = 0;
			$message = sprintf( __( "Unknown error %s with code %d", Plugin::SLUG ), $e->getMessage(), $e->getCode() );
			$status  = 500;
		}

		return new Response( array(
			'success' => false,
			'error'   => array(
				'code'    => $code,
				'message' => $message
			)
		), $status );
	}

	/**
	 * Register an endpoint.
	 *
	 * @since 1.0
	 *
	 * @param Endpoint $endpoint
	 * @param string   $action Action this endpoint responds to.
	 */
	public static function register_endpoint( Endpoint $endpoint, $action ) {
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