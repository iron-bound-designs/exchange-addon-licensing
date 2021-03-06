<?php
/**
 * Product Endpoint API
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API\Endpoint;

use ITELIC\Activation;
use ITELIC\API\Endpoint;
use ITELIC\API\Contracts\Authenticatable;
use ITELIC\Key;
use ITELIC\API\Response;
use ITELIC\Plugin;

/**
 * Class Product
 *
 * @package ITELIC\API\Endpoint
 */
class Product extends Endpoint implements Authenticatable {

	/**
	 * @var Key
	 */
	protected $key;

	/**
	 * @var Activation|null
	 */
	protected $activation;

	/**
	 * Serve the request to this endpoint.
	 *
	 * @param \ArrayAccess $get
	 * @param \ArrayAccess $post
	 *
	 * @return Response
	 */
	public function serve( \ArrayAccess $get, \ArrayAccess $post ) {

		$readme = $this->key->get_product()->get_feature( 'licensing-readme' );

		$contributors = array();

		if ( $readme['author'] ) {
			$usernames = explode( ',', $readme['author'] );

			foreach ( $usernames as $username ) {
				$contributors[ $username ] = "//profiles.wordpress.org/$username";
			}
		}

		$release = $this->key->get_product()->get_latest_release_for_activation( $this->activation );

		$info = array(
			'id'              => $this->key->get_product()->ID,
			'name'            => $this->key->get_product()->post_title,
			'description'     => $this->key->get_product()->get_feature( 'description' ),
			'version'         => $release->get_version(),
			'tested'          => $readme['tested'],
			'requires'        => $readme['requires'],
			'contributors'    => $contributors,
			'last_updated'    => empty( $readme['last_updated'] ) ? '' : $readme['last_updated']->format( \DateTime::ISO8601 ),
			'banner_low'      => $readme['banner_low'],
			'banner_high'     => $readme['banner_high'],
			'package_url'     => \ITELIC\generate_download_link( $this->activation ),
			'description_url' => get_permalink( $this->key->get_product()->ID ),
			'changelog'       => $this->key->get_product()->get_changelog(),
			'sections'        => array()
		);

		/**
		 * Filter the product info returned by the API.
		 *
		 * @since 1.0
		 *
		 * @param array   $info
		 * @param Product $product
		 */
		$info = apply_filters( 'itelic_api_product_info', $info, $this->key->get_product() );

		return new Response( array(
			'success' => true,
			'body'    => array(
				'list' => array(
					$this->key->get_product()->ID => $info
				)
			)
		) );
	}

	/**
	 * Retrieve the mode of authentication.
	 *
	 * @since 1.0
	 *
	 * @return string One of MODE_VALID, MODE_ACTIVE
	 */
	public function get_auth_mode() {
		return Authenticatable::MODE_VALID_ACTIVATION;
	}

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_auth_error_message() {
		return __( "An active license key and valid activation record is required.", Plugin::SLUG );
	}

	/**
	 * Get the error code to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_auth_error_code() {
		return self::CODE_INVALID_ACTIVATION;
	}

	/**
	 * Give a reference of the API key to this object.
	 *
	 * @since 1.0
	 *
	 * @param Key $key
	 */
	public function set_auth_license_key( Key $key ) {
		$this->key = $key;
	}

	/**
	 * Give a reference of the activation record to this object.
	 *
	 * @since 1.0
	 *
	 * @param Activation $activation
	 */
	public function set_auth_activation( Activation $activation = null ) {
		$this->activation = $activation;
	}
}