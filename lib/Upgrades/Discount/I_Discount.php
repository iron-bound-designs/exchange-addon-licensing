<?php
/**
 * Upgrade discount interface.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Upgrades\Discount;

/**
 * Interface I_Discount
 *
 * @package ITELIC\Upgrades\Discount
 */
interface I_Discount {

	/**
	 * Get the total amount of the upgrade discount.
	 *
	 * @since 1.0
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_discount( $format = false );

	/**
	 * Get the price a customer can upgrade to for.
	 *
	 * @since 1.0
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_upgrade_price( $format = false );

	/**
	 * Get the original price before any discounts are applied.
	 *
	 * @since 1.0
	 *
	 * @param bool $format
	 *
	 * @return float|string
	 */
	public function get_original_price( $format = false );

	/**
	 * Get the upgrade price formatted.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function __toString();
}