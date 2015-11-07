<?php
/**
 * Endpoint for directly downloading a file.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API\Endpoint;

use ITELIC\Activation;
use ITELIC\API\Endpoint;
use ITELIC\Key;
use ITELIC\API\Response;
use ITELIC\Plugin;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Download
 *
 * @package ITELIC\API\Endpoint
 */
class Download extends Endpoint implements LoggerAwareInterface {

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

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
	 * Serve the request to this endpoint.
	 *
	 * @param \ArrayAccess $get
	 * @param \ArrayAccess $post
	 *
	 * @return Response
	 */
	public function serve( \ArrayAccess $get, \ArrayAccess $post ) {

		/**
		 * Fires before the download query args are validated.
		 *
		 * If add-ons are completely overriding how product updates
		 * are delivered. They should use this action.
		 *
		 * @since 1.0
		 *
		 * @param \ArrayAccess $get
		 * @param \ArrayAccess $post
		 */
		do_action( 'itelic_pre_validate_download', $get, $post );

		if ( ! \ITELIC\validate_query_args( $get ) ) {

			$this->logger->notice( 'Invalid download link used.', array(
				'get'  => $get,
				'post' => $post
			) );

			status_header( 403 );

			_e( "This download link is invalid or has expired.", Plugin::SLUG );

			if ( ! defined( 'DOING_TESTS' ) || ! DOING_TESTS ) {
				die();
			} else {
				return;
			}
		}

		$activation = itelic_get_activation( $get['activation'] );
		$key        = $get['key'];

		if ( ! $activation || $activation->get_key()->get_key() != $key ) {

			$key_object = itelic_get_key( $key );

			if ( $key_object && $key_object->get_customer() ) {
				$user = $key_object->get_customer()->id;
			} elseif ( $activation->get_key()->get_customer() ) {
				$user = $activation->get_key()->get_customer()->id;
			} else {
				$user = 0;
			}

			$this->logger->notice( 'Invalid download link used. Key activation mismatch.', array(
				'get'   => $get,
				'post'  => $post,
				'_user' => $user
			) );

			status_header( 403 );

			_e( "This download link is invalid or has expired.", Plugin::SLUG );

			if ( ! defined( 'DOING_TESTS' ) || ! DOING_TESTS ) {
				die();
			} else {
				return;
			}
		}

		$release = $activation->get_key()->get_product()->get_latest_release_for_activation( $activation );

		$file = $release->get_download();

		itelic_create_update( array(
			'activation' => $activation,
			'release'    => $release
		) );

		$this->logger->info( 'Download for {product} delivered.', array(
			'product' => $activation->get_key()->get_product()->post_title,
			'_user'   => $activation->get_key()->get_customer()->id,
			'get'     => $get,
			'post'    => $post
		) );

		/**
		 * Fires before a download is served.
		 *
		 * Download links are only generated from the Product endpoint
		 * if both the license key and activation records are valid.
		 *
		 * If you are generating download links differently, you should
		 * probably validate the activation status and key status again.
		 *
		 * @since 1.0
		 *
		 * @param \WP_Post   $file       WordPress attachment object for the software update.
		 * @param Key        $key        License key used for validation.
		 * @param Activation $activation Activation the download is being delivered to.
		 */
		do_action( 'itelic_pre_serve_download', $file, $key, $activation );

		\ITELIC\serve_download( wp_get_attachment_url( $file->ID ) );
	}
}