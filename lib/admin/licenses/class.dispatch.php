<?php
/**
 * Dispatch requests to the roster page.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITECLS_Admin_Roster_Dispatch
 */
class ITELIC_Admin_Licenses_Dispatch {

	/**
	 * @var string
	 */
	private $view;

	/**
	 * @var ITELIC_Admin_Licenses_Controller[]
	 */
	private static $views = array();

	/**
	 * Constructor.
	 */
	public function __construct() {

		if ( isset( $_GET['view'] ) && array_key_exists( $_GET['view'], self::$views ) ) {
			$this->view = $_GET['view'];
		} else {
			$this->view = 'list';
		}
	}

	/**
	 * Dispatch the request.
	 */
	public function dispatch() {
		self::$views[ $this->view ]->render();
	}

	/**
	 * Register a view.
	 *
	 * @param string                           $slug
	 * @param ITELIC_Admin_Licenses_Controller $controller
	 */
	public static function register_view( $slug, $controller ) {
		self::$views[ $slug ] = $controller;
	}
}