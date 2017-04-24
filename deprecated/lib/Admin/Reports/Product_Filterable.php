<?php
/**
 * Product filterable report type.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Reports;

/**
 * Interface Product_Filterable
 *
 * @package ITELIC\Admin\Reports
 */
interface Product_Filterable {

	/**
	 * Return boolean true if a product is required to view this report.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_product_required();
}