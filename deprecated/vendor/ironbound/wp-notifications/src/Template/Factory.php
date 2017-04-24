<?php
/**
 * Template manager factory.
 *
 * @author Iron Bound Designs
 * @since  1.0
 *
 * @copyright   Copyright (c) 2015, Iron Bound Designs, Inc.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License v2 or later
 */

namespace IronBound\WP_Notifications\Template;

/**
 * Class Factory
 * @package IronBound\WP_Notifications\Template
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
		do_action( "ibd_wp_notifications_template_manager_$type", $manager );

		return $manager;
	}


}