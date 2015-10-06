<?php
/**
 * JSON Responder.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\API\Responder;

use ITELIC\API\Response;

/**
 * Class JSON_Responder
 * @package ITELIC\API\Responder
 */
class JSON_Responder extends Responder {

	/**
	 * Respond to the client with the given response object.
	 *
	 * @param Response $response
	 *
	 * @return string
	 */
	public function respond( Response $response ) {

		$content_type = 'application/json';
		$this->send_header( 'Content-Type', $content_type . '; charset=' . get_option( 'blog_charset' ) );

		$this->send_headers( $response->get_headers() );
		$this->set_status( $response->get_status() );

		$result = $this->response_to_data( $response );
		$result = json_encode( $result );

		$json_error_message = $this->get_json_last_error();

		if ( $json_error_message ) {

			$json_error_obj = new Response( array(
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

		return $result;
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
}