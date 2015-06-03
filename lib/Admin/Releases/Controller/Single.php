<?php
/**
 * Releases single view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\Controller;

use ITELIC\Admin\Releases\Controller;
use ITELIC\Release;

/**
 * Class Single
 * @package ITELIC\Admin\Releases\Controller
 */
class Single extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$release = Release::with_id( $_GET['ID'] );
	}
}