<?php
/**
 * Abstract controller for the admin tabs view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Admin_Tab_Controller
 */
abstract class ITELIC_Admin_Tab_Controller {

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
		return admin_url( "admin.php?page=" . ITELIC_Admin_Tab_Dispatch::PAGE_SLUG . "&tab=$tab_slug" );
	}
}