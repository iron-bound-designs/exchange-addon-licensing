<?php
/**
 * Template manager factory.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Notifications\Template;

/**
 * Class Factory
 * @package ITELIC\Notifications\Template
 */
class Factory {

	/**
	 * Make a manager object with listener objects already attached.
	 *
	 * @since 1.0
	 *
	 * @param string $type
	 *
	 * @return Manager
	 */
	public static function make( $type ) {
		$manager = new Manager( $type );

		/**
		 * Fires when a template manager is constructed. Allowing for listener objects to be attached.
		 *
		 * @since 1.0
		 *
		 * @param Manager $manager
		 */
		do_action( "itelic_notifications_template_manager_$type", $manager );

		return $manager;
	}


}