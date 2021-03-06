<?php
/**
 * Abstract controller for the admin roster view.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Reports;

/**
 * Class Controller
 * @package ITELIC\Admin\Reports
 */
abstract class Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	abstract public function render();
}