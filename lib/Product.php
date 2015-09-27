<?php
/**
 * Contains method useful for interacting with a licensing product.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC;

use ITELIC_API\Query\Releases;

/**
 * Class Product
 * @package ITELIC
 */
class Product extends \IT_Exchange_Product {

	/**
	 * Constructor.
	 *
	 * @param \IT_Exchange_Product $product
	 */
	public function __construct( \IT_Exchange_Product $product ) {

		if ( ! $product->product_type == 'digital-downloads-product-type' ) {
			throw new \InvalidArgumentException( "Product must have the digital downloads product type." );
		}

		parent::IT_Exchange_Product( $product->ID );
	}

	/**
	 * Get a product instance.
	 *
	 * @since 1.0
	 *
	 * @param int $ID
	 *
	 * @return Product|null
	 */
	public static function get( $ID ) {
		$product = it_exchange_get_product( $ID );

		if ( ! $product ) {
			return null;
		}

		try {
			return new self( $product );
		}
		catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Get the changelog for this product.
	 *
	 * @since 1.0
	 *
	 * @param int $num_releases
	 *
	 * @return string
	 */
	public function get_changelog( $num_releases = 10 ) {

		$log = wp_cache_get( $this->ID, 'itelic-changelog' );

		if ( ! $log ) {

			$query = new Releases( array(
				'product'             => $this->ID,
				'status'              => array(
					Release::STATUS_ACTIVE,
					Release::STATUS_ARCHIVED,
					Release::STATUS_PAUSED
				),
				'order'               => array( 'start_date' => 'DESC' ),
				'items_per_page'      => $num_releases,
				'sql_calc_found_rows' => false
			) );

			/**
			 * @var Release[] $releases
			 */
			$releases = $query->get_results();

			$log = '';

			foreach ( $releases as $release ) {
				$log .= "<strong>v{$release->get_version()} – {$release->get_start_date()->format( get_option( 'date_format' ) )}</strong>";
				$log .= $release->get_changelog();
			}

			wp_cache_set( $this->ID, $log, 'itelic-changelog' );
		}

		return $log;
	}

	/**
	 * Get the latest release available for an activation record.
	 *
	 * By default, returns the latest version saved. But is used for getting pre-release or restricted versions.
	 *
	 * @since 1.0
	 *
	 * @param Activation $activation
	 *
	 * @return Release
	 */
	public function get_latest_release_for_activation( Activation $activation ) {

		$track = $activation->get_meta( 'track', true );

		if ( ! $track || $track != 'pre-release' ) {
			$version = it_exchange_get_product_feature( $this->ID, 'licensing', array( 'field' => 'version' ) );
			$release = itelic_get_release_by_version( $this->ID, $version );
		} else {
			$query = new Releases( array(
				'product'             => $activation->get_key()->get_product()->ID,
				'order'               => array(
					'start_date' => 'DESC'
				),
				'items_per_page'      => 1,
				'sql_calc_found_rows' => false
			) );

			$releases = $query->get_results();
			$release = reset( $releases );
		}

		/**
		 * Filter the latest release for an activation record.
		 *
		 * @since 1.0
		 *
		 * @param Release    $release
		 * @param Activation $activation
		 */

		return apply_filters( 'itelic_get_latest_release_for_activation', $release, $activation );
	}
}