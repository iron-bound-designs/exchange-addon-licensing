<?php
/**
 * Releases tab controller.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab\Controller;

use ITELIC\Admin\Releases\Dispatch as Releases_Dispatch;
use ITELIC\Admin\Tab\Controller;

/**
 * Class Releases
 * @package ITELIC\Admin\Tab\Controller
 */
class Releases extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$releases_dispatch = new Releases_Dispatch();
		$releases_dispatch->dispatch();
	}
}