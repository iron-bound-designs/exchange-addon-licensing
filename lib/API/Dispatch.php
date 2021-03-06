<?php
/**
 * API Dispatcher.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API;

use ITELIC\Activation;
use ITELIC\API\Contracts\Endpoint;
use ITELIC\API\Responder\Responder;
use ITELIC\Plugin;
use ITELIC\Key;
use ITELIC\API\Exception as API_Exception;
use ITELIC\API\Contracts\Authenticatable;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Dispatch
 *
 * @package ITELIC\API
 */
class Dispatch implements LoggerAwareInterface {

	/**
	 * @var Responder
	 */
	private $responder;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var \WP_User|null
	 */
	private $current_user;

	/**
	 * @var string
	 */
	const TAG = 'itelic_api';

	/**
	 * @var Endpoint[]
	 */
	private $endpoints = array();

	/**
	 * Dispatch constructor.
	 */
	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * Register WordPress hooks.
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'register_rewrites' ) );
		add_action( 'parse_request', array( $this, 'dispatch' ) );
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
	 * Sets a logger instance on the object
	 *
	 * @param LoggerInterface $logger
	 *
	 * @return null
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Dispatch an API request.
	 *
	 * @param \WP $wp
	 */
	public function dispatch( \WP $wp ) {

		$response = $this->process( $wp );

		if ( $response ) {

			/**
			 * Filter the API response before passing to the responder.
			 *
			 * @since 1.0
			 *
			 * @param Response $response
			 * @param \WP      $wp
			 */
			$response = apply_filters( 'itelic_api_response', $response, $wp );

			$this->send_response( $response );

			die();
		}
	}

	/**
	 * Dispatch the request.
	 *
	 * @param \WP $wp
	 *
	 * @return Response
	 */
	public function process( \WP $wp ) {

		$action = isset( $wp->query_vars[ self::TAG ] ) ? $wp->query_vars[ self::TAG ] : '';

		if ( $action ) {

			$response = $this->process_action( $action );

			$this->logger->info( 'Dispatched {action} request', array(
				'action'   => $action,
				'get'      => $_GET,
				'post'     => $_POST,
				'response' => array(
					'body'   => isset( $this->responder ) ? $this->responder->prepare_response( $response->get_data() ) : $response->get_data(),
					'status' => $response->get_status()
				),
				'_user'    => $this->current_user ? $this->current_user->ID : false
			) );

			return $response;
		}

		return null;
	}

	/**
	 * Process an actual API action.
	 *
	 * @since 1.0
	 *
	 * @param string $action
	 *
	 * @return Response
	 */
	protected function process_action( $action ) {

		if ( ! isset( $this->endpoints[ $action ] ) ) {
			$response = new Response( array(
				'success' => false,
				'error'   => array(
					'code'    => 404,
					'message' => __( "API Action Not Found", Plugin::SLUG )
				)
			), 404 );

			return $response;
		} else {
			$endpoint = $this->endpoints[ $action ];

			if ( $endpoint instanceof LoggerAwareInterface ) {
				$endpoint->setLogger( $this->logger );
			}

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
					$response = $this->generate_response_from_exception( $e, $action );
				}
			}

			return $response;
		}
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

		/**
		 * Filter the responder being used when processing an API request.
		 *
		 * @since 1.0
		 *
		 * @param Responder $responder
		 * @param Response  $response
		 */
		$responder = apply_filters( 'itelic_api_responder', $this->responder, $response );

		if ( is_null( $responder ) ) {
			status_header( 500 );
			echo 'An unexpected error occurred.';

			die();
		}

		echo $responder->respond( $response );

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

		$this->current_user = $key->get_customer() ? $key->get_customer()->wp_user : null;

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

				if ( $key->get_status() != Key::ACTIVE ) {
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
	 * @param string     $action
	 *
	 * @return Response
	 */
	protected function generate_response_from_exception( \Exception $e, $action ) {

		if ( $e instanceof API_Exception ) {
			$code    = $e->getCode();
			$message = $e->getMessage();
			$status  = 400;
		} else {
			$code    = 0;
			$message = sprintf( __( "Unknown error %s with code %d", Plugin::SLUG ), $e->getMessage(), $e->getCode() );
			$status  = 500;

			$this->logger->error( 'Unexpected exception during {action} request.', array(
				'exception' => $e,
				'action'    => $action,
				'_user'     => $this->current_user ? $this->current_user->ID : false
			) );
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
	public function register_endpoint( Endpoint $endpoint, $action ) {

		if ( isset( $this->endpoints[ $action ] ) ) {

			$original = $this->endpoints[ $action ];

			if ( ! $endpoint instanceof $original ) {
				throw new \InvalidArgumentException( "The '{$action}' has already been registered. Endpoint class must be a subclass." );
			}
		}

		$this->endpoints[ $action ] = $endpoint;
	}

	/**
	 * Get all endpoints registered.
	 *
	 * @since 1.0
	 *
	 * @return Contracts\Endpoint[]
	 */
	public function get_endpoints() {
		return $this->endpoints;
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