<?php
/**
 * Theme API class for looping through a customer's licenses.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class IT_Theme_API_Licenses
 */
class IT_Theme_API_Licenses implements IT_Theme_API {

	/**
	 * @var string
	 */
	private $_context = "licenses";

	/**
	 * @var array
	 */
	public $_tag_map = array(
		'licenses' => 'licenses'
	);

	/**
	 * Retrieve the API context.
	 *
	 * @return string
	 */
	public function get_api_context() {
		return $this->_context;
	}

	/**
	 * Loop through a customer's licenses.
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function licenses( $options = array() ) {
		if ( $options['has'] ) {
			return count( $this->get_licenses() ) > 0;
		}

		// If we made it here, we're doing a loop of classes for the current query.
		// This will init/reset the classes global and loop through them. the /api/theme/class.php file will handle individual classes.
		if ( empty( $GLOBALS['it_exchange']['licenses'] ) ) {
			$GLOBALS['it_exchange']['licenses'] = $this->get_licenses();
			$GLOBALS['it_exchange']['license']   = reset( $GLOBALS['it_exchange']['licenses'] );

			return true;
		} else {
			if ( next( $GLOBALS['it_exchange']['licenses'] ) ) {
				$GLOBALS['it_exchange']['license'] = current( $GLOBALS['it_exchange']['licenses'] );

				return true;
			} else {
				$GLOBALS['it_exchange']['licenses'] = array();
				end( $GLOBALS['it_exchange']['licenses'] );
				$GLOBALS['it_exchange']['license'] = false;

				return false;
			}
		}
	}

	/**
	 * Retrieve the licenses.
	 *
	 * @since 1.0
	 *
	 * @return ITELIC_Key[]
	 */
	protected function get_licenses() {

		$args = array(
			'customer' => it_exchange_get_current_customer_id()
		);

		return itelic_get_keys( $args );
	}
}