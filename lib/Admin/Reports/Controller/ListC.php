<?php
/**
 * Reports list controller.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports\Controller;

use ITELIC\Admin\Reports\Controller;
use ITELIC\Admin\Reports\Dispatch;
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

		$this->enqueue();

		$view = new ListV( Dispatch::get_reports() );

		$view->begin();
		$view->title();

		$view->tabs( 'reports' );

		$view->render();

		$view->end();
	}

	/**
	 * Enqueue the scripts.
	 *
	 * @since 1.0
	 */
	private function enqueue() {
		wp_enqueue_style( 'itelic-admin-reports-list' );
	}
}