<?php
/**
 * Serializable API interface.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\API;

/**
 * Interface Serializable
 * @package ITELIC\API
 */
interface Serializable {

	/**
	 * Get data suitable for the API.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_api_data();
}