<?php
/**
 * Dispatch requests to the reports page.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Reports;

use ITELIC\Admin\Tab\Dispatch as Tab_Dispatch;

/**
 * Class Dispatch
 * @package ITELIC\Admin\Licenses
 */
class Dispatch {

	/**
	 * @var string
	 */
	private $view;

	/**
	 * @var Controller[]
	 */
	private static $views = array();

	/**
	 * @var Report[]
	 */
	private static $reports = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->view = self::get_current_view();
	}

	/**
	 * Get the current view being displayed.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private static function get_current_view() {

		if ( isset( $_GET['view'] ) && array_key_exists( $_GET['view'], self::$views ) ) {
			return $_GET['view'];
		} else {
			return 'list';
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
	 * @param string     $slug
	 * @param Controller $controller
	 */
	public static function register_view( $slug, Controller $controller ) {
		self::$views[ $slug ] = $controller;
	}

	/**
	 * Register a report type.
	 *
	 * @since 1.0
	 *
	 * @param Report $report
	 */
	public static function register_report( Report $report ) {
		self::$reports[ $report->get_slug() ] = $report;
	}

	/**
	 * Get a report type.
	 *
	 * @since 1.0
	 *
	 * @param string $slug
	 *
	 * @return Report|null
	 */
	public static function get_report( $slug ) {
		return isset( self::$reports[ $slug ] ) ? self::$reports[ $slug ] : null;
	}

	/**
	 * Get all report types.
	 *
	 * @since 1.0
	 *
	 * @return Report[]
	 */
	public static function get_reports() {
		return self::$reports;
	}

	/**
	 * Check if the current view is for a certain tab.
	 *
	 * @since 1.0
	 *
	 * @param string $view
	 *
	 * @return bool
	 */
	public static function is_current_view( $view ) {

		if ( ! Tab_Dispatch::is_current_view( 'reports' ) ) {
			return false;
		}

		if ( ! isset( self::$views[ $view ] ) ) {
			return false;
		}

		return $view == self::get_current_view();
	}
}