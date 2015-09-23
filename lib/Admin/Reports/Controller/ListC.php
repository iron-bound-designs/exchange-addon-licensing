<?php
/**
 * Reports list controller.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports\Controller;

use ITELIC\Admin\Reports\Controller;
use ITELIC\Admin\Reports\View\ListV;

/**
 * Class ListC
 * @package ITELIC\Admin\Reports\Controller
 */
class ListC extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		$view = new ListV();

		$view->begin();
		$view->title();

		$view->tabs( 'reports' );

		$view->end();
	}
}