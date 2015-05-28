<?php
/**
 * Roster tab controller
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Tab\Controller;
use ITELIC\Admin\Licenses\Dispatch as Licenses_Dispatch;
use ITELIC\Admin\Tab\Controller;

/**
 * Class Licenses
 * @package ITELIC\Admin\Tab\Controller
 */
class Licenses extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$licenses_dispatch = new Licenses_Dispatch();
		$licenses_dispatch->dispatch();
	}

}