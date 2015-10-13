<?php
/**
 * Single report controller.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Reports\Controller;

use ITELIC\Admin\Reports\Controller;
use ITELIC\Admin\Reports\Dispatch;
use ITELIC\Admin\Reports\View\SingleV;
use ITELIC\Admin\Tab\Dispatch as Tab_Dispatch;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;

/**
 * Class SingleC
 * @package ITELIC\Admin\Reports\Controller
 */
class SingleC extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		$report = Dispatch::get_report( $_GET['report'] );

		if ( is_null( $report ) ) {
			wp_redirect( Tab_Dispatch::get_tab_link( 'reports' ) );
			exit;
		}

		$this->enqueue();

		$view = new SingleV( $report );

		$view->begin();
		$view->title();

		if ( ! $report ) {
			$view->notice( __( "Invalid report.", Plugin::SLUG ), View::NOTICE_ERROR );
		}

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

		wp_enqueue_style( 'itelic-admin-report-view' );
		wp_enqueue_script( 'itelic-admin-report-view' );
	}
}