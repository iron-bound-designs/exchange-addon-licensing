<?php
/**
 * Upgrade path model.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

namespace ITELIC\Upgrades;

use ITELIC\Key;
use ITELIC\Upgrades\Discount\Chained;
use ITELIC\Upgrades\Discount\Flat;
use ITELIC\Upgrades\Discount\I_Discount;
use ITELIC\Upgrades\Discount\Simple_Prorate;

/**
 * Class Upgrade_Path
 *
 * @package ITELIC\Upgrades
 */
class Upgrade_Path {

	/**
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param \WP_Post $path
	 */
	public function __construct( \WP_Post $path ) {

		if ( get_post_type( $path ) !== CPT::SLUG ) {
			throw new \InvalidArgumentException( "Invalid upgrade path object." );
		}

		$this->post = $path;
	}

	/**
	 * Get an upgrade path.
	 *
	 * @since 1.0
	 *
	 * @param int $ID
	 *
	 * @return self
	 */
	public static function get( $ID ) {

		if ( get_post_type( $ID ) !== CPT::SLUG ) {
			throw new \InvalidArgumentException( "Invalid upgrade path ID." );
		}

		return new self( get_post( $ID ) );
	}

	/**
	 * Get the ID of the upgrade path.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_ID() {
		return $this->post->ID;
	}

	/**
	 * Get the product this upgrade path belongs to.
	 *
	 * @since 1.0
	 *
	 * @return \IT_Exchange_Product
	 */
	public function get_product() {
		return it_exchange_get_product( $this->post->menu_order );
	}

	/**
	 * Get the immediate prerequisite required to upgrade to this path.
	 *
	 * @since 1.0
	 *
	 * @return Upgrade_Path|null
	 */
	public function get_prerequisite() {

		if ( $this->post->post_parent ) {
			return self::get( $this->post->post_parent );
		}

		return null;
	}

	/**
	 * Get the product being upgraded to.
	 *
	 * @since 1.0
	 *
	 * @return \IT_Exchange_Product
	 */
	public function get_upgrade_product() {
		return it_exchange_get_product( get_post_meta( $this->get_ID(), '_itelic_upgrade_product', true ) );
	}

	/**
	 * Get the upgrade variant hash.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_upgrade_variant_hash() {
		return get_post_meta( $this->get_ID(), '_itelic_upgrade_variant', true );
	}

	/**
	 * Check if the upgrade path has variants.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function has_variant() {
		return (bool) $this->get_upgrade_variant_hash();
	}

	/**
	 * Check if this upgrade path is prorated.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	protected function is_prorated_discount() {
		return (bool) get_post_meta( $this->get_ID(), '_itelic_discount_prorate', true );
	}

	/**
	 * Check if this is flat discounted.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	protected function is_flat_discount() {
		return $this->get_flat_discount() > 0.00;
	}

	/**
	 * Get the amount of the flat discount.
	 *
	 * @since 1.0
	 *
	 * @return float
	 */
	protected function get_flat_discount() {

		$flat = get_post_meta( $this->get_ID(), '_itelic_discount_flat', true );

		if ( empty( $flat ) ) {
			$flat = 0.00;
		}

		return $flat;
	}

	/**
	 * Get the discount for upgrading to this upgrade path for a certain key.
	 *
	 * @since 1.0
	 *
	 * @param Key $key
	 *
	 * @return I_Discount
	 */
	public function get_discounts_for_key( Key $key ) {

		$chained = new Chained();

		if ( $this->is_prorated_discount() ) {
			$chained->chain(
				new Simple_Prorate( $key, $this->get_upgrade_product(),
					$this->get_upgrade_variant_hash()
				)
			);
		}

		if ( $this->is_flat_discount() ) {
			$chained->chain(
				new Flat( $this->get_flat_discount(), $key,
					$this->get_upgrade_product(),
					$this->get_upgrade_variant_hash()
				)
			);
		}

		return $chained;
	}
}