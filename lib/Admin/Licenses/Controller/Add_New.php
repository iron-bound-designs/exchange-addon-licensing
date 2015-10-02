<?php
/**
 * Add New License Controller.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Licenses\Controller;

use ITELIC\Admin\Licenses\Controller;
use ITELIC\Admin\Licenses\View\Add_New as Add_New_View;

/**
 * Class Add_New
 * @package ITELIC\Admin\Licenses\Controller
 */
class Add_New extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		wp_enqueue_script( 'itelic-admin-licenses-new' );
		wp_enqueue_style( 'itelic-admin-licenses-new' );

		$view = new Add_New_View( itelic_get_products_with_licensing_enabled() );

		$view->begin();
		$view->title();

		$view->tabs( 'licenses' );

		$view->render();

		$view->end();
	}
}