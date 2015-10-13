<?php
/**
 * Reports tab.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab\Controller;

use ITELIC\Admin\Reports\Dispatch;
use ITELIC\Admin\Tab\Controller;

/**
 * Class Reports
 * @package ITELIC\Admin\Tab\Controller
 */
class Reports extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$dispatch = new Dispatch();
		$dispatch->dispatch();
	}
}