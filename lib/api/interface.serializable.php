<?php
/**
 * File Description
 *
 * @author Iron Bound Designs
 * @since
 */

/**
 * Interface ITELIC_API_Serializable
 */
interface ITELIC_API_Serializable {

	/**
	 * Get data suitable for the API.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_api_data();
}