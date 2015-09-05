<?php
/**
 * Controller for add new release view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\Controller;

use ITELIC\Admin\Releases\Controller;
use ITELIC\Admin\Releases\View\Add_New as Add_New_View;
use ITELIC\Plugin;

/**
 * Class Add_New
 * @package ITELIC\Admin\Releases\Controller
 */
class Add_New extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		$this->enqueue();

		$view = new Add_New_View();

		$view->begin();
		$view->title();

		$view->tabs( 'releases' );

		$view->render();

		$view->end();
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0
	 */
	private function enqueue() {
		wp_enqueue_style( 'itelic-admin-releases' );
		wp_enqueue_script( 'itelic-admin-releases' );
		wp_localize_script( 'itelic-admin-releases', 'ITELIC', array(
			'prevVersion' => __( "Previous version: %s", Plugin::SLUG )
		) );
	}
}