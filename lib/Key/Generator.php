<?php
/**
 * License Generator
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Key;

use ITELIC\Product;

/**
 * Class Generator
 *
 * This class should be overridden for each license generation type.
 * It should be instantiated for each license generation.
 *
 * @since   1.0
 * @package ITELIC\Key\Generator
 */
interface Generator {

	/**
	 * Generate a license according to this method's algorithm.
	 *
	 * @since 1.0
	 *
	 * @param array                    $options
	 * @param Product                  $product
	 * @param \IT_Exchange_Customer    $customer
	 * @param \IT_Exchange_Transaction $transaction
	 *
	 * @return string
	 */
	public function generate( $options = array(), Product $product, \IT_Exchange_Customer $customer, \IT_Exchange_Transaction $transaction );

}