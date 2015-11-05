<?php
/**
 * Logs controller.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab\Controller;

use IronBound\DBLogger\ListTable;
use IronBound\DBLogger\Table;
use ITELIC\Admin\Tab\Controller;
use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Plugin;

/**
 * Class Logs
 * @package ITELIC\Admin\Tab\Controller
 */
class Logs extends Controller {

	/**
	 * @var ListTable
	 */
	private $table;

	/**
	 * Logs constructor.
	 */
	public function __construct() {
		add_action( 'load-exchange_page_it-exchange-licensing', array( $this, 'setup_table' ) );
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {

		$view = new \ITELIC\Admin\Tab\View\Logs( $this->table );

		$view->begin();
		$view->title();
		$view->tabs( 'logs' );
		$view->render();
		$view->end();
	}

	public function setup_table() {

		if ( ! Dispatch::is_current_view( 'logs' ) ) {
			return;
		}

		$this->table = new ListTable( array(
			'single' => __( "API Log", Plugin::SLUG ),
			'plural' => __( 'API Logs', Plugin::SLUG )
		), array(), new Table( 'itelic-api-logs' ), '\ITELIC\API\Log' );;
	}
}