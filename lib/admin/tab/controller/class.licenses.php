<?php

/**
 * Roster tab controller
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
class ITELIC_Admin_Tab_Controller_Licenses extends ITELIC_Admin_Tab_Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$roster_dispatch = new ITELIC_Admin_Licenses_Dispatch();
		$roster_dispatch->dispatch();
	}

}