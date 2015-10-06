<?php
/**
 * Handles translating a response object into data for the client to consume.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\API\Responder;

use ITELIC\API\Response;
use ITELIC\API\Serializable;

/**
 * Class Responder
 * @package ITELIC\API\Responder
 */
abstract class Responder {

	/**
	 * Respond to the client with the given response object.
	 *
	 * @param Response $response
	 *
	 * @return string
	 */
	public abstract function respond( Response $response );

	/**
	 * Convert a response to an array.
	 *
	 * @param Response $response
	 *
	 * @return array
	 */
	protected function response_to_data( Response $response ) {
		return (array) $this->prepare_response( $response->get_data() );
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
	 * @param mixed $data Native representation
	 *
	 * @return array|string
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

				if ( $data instanceof Serializable ) {
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


}