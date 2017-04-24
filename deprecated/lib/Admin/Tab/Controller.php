<?php
/**
 * Abstract controller for the admin tabs view.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab;

/**
 * Class Controller
 * @package ITELIC\Admin\Tab
 */
abstract class Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	abstract public function render();

	/**
	 * Get the tab link.
	 *
	 * @param $tab_slug string
	 *
	 * @return string
	 */
	public function get_tab_link( $tab_slug ) {
		return admin_url( "admin.php?page=" . Dispatch::PAGE_SLUG . "&tab=$tab_slug" );
	}
}