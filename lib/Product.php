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
				$log .= "<h3>v{$release->get_version()} – {$release->get_start_date()->format( get_option( 'date_format' ) )}</h3>";
				$log .= $release->get_changelog();
			}

			wp_cache_set( $this->ID, $log, 'itelic-changelog' );
		}

		return $log;
	}
}