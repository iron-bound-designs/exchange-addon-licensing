<?php
/**
 * Build upgrade path objects.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Upgrades;

/**
 * Class Path_Builder
 * @package ITELIC\Upgrades
 */
class Path_Builder {

	/**
	 * @var \IT_Exchange_Product
	 */
	private $licensed_product;

	/**
	 * @var \IT_Exchange_Product
	 */
	private $upgrade_product;

	/**
	 * @var string
	 */
	private $variant_hash;

	/**
	 * @var Upgrade_Path
	 */
	private $prerequisite = null;

	/**
	 * @var bool
	 */
	private $prorate = false;

	/**
	 * @var float
	 */
	private $flat = 0.00;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param \IT_Exchange_Product $licensed_product
	 */
	public function __construct( \IT_Exchange_Product $licensed_product ) {
		$this->licensed_product = $licensed_product;
	}

	/**
	 * Set the product being upgraded to.
	 *
	 * @since 1.0
	 *
	 * @param \IT_Exchange_Product $product
	 * @param string               $variant_hash
	 */
	public function upgrade_to( \IT_Exchange_Product $product, $variant_hash = '' ) {
		$this->upgrade_product = $product;
		$this->variant_hash    = $variant_hash;
	}

	/**
	 * Set the previous path.
	 *
	 * @since 1.0
	 *
	 * @param Upgrade_Path $path
	 */
	public function set_prerequisite( Upgrade_Path $path ) {
		$this->prerequisite = $path;
	}

	/**
	 * Discount by proration.
	 *
	 * Depending on settings configuration, either a simple or advanced technique will be used.
	 *
	 * @since 1.0
	 */
	public function discount_by_proration() {
		$this->prorate = true;
	}

	/**
	 * Discount the upgrade by a flat amount.
	 *
	 * @since 1.0
	 *
	 * @param float $amount
	 */
	public function discount_by_flat_amount( $amount ) {
		$this->flat = $amount;
	}

	/**
	 * Create the object.
	 *
	 * @since 1.0
	 *
	 * @return Upgrade_Path
	 */
	public function create() {

		if ( ! $this->upgrade_product ) {
			throw new \UnexpectedValueException( "You must set an upgrade product before creating an upgrade path." );
		}

		$args = array(
			'post_type'  => CPT::SLUG,
			'menu_order' => $this->licensed_product->ID
		);

		if ( $this->prerequisite ) {
			$args['post_parent'] = $this->prerequisite->get_ID();
		}

		$ID = wp_insert_post( $args );

		update_post_meta( $ID, '_itelic_upgrade_product', $this->upgrade_product->ID );

		if ( $this->variant_hash ) {
			update_post_meta( $ID, '_itelic_upgrade_variant', $this->variant_hash );
		}

		if ( $this->prorate ) {
			update_post_meta( $ID, '_itelic_discount_prorate', true );
		}

		if ( $this->flat > 0 ) {
			update_post_meta( $ID, '_itelic_discount_flat', $this->flat );
		}
	}
}