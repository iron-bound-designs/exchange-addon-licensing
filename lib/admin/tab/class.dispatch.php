<?php
/**
 * Dispatcher for roster admin page.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Admin_Tab_Dispatch
 */
class ITELIC_Admin_Tab_Dispatch {

	/**
	 * @var string
	 */
	const PAGE_SLUG = 'it-exchange-licensing';

	/**
	 * @var string
	 */
	private $tab;

	/**
	 * @var array
	 */
	private static $tabs = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->tab = self::get_current_tab();
	}

	/**
	 * Get the current tab being shown.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private static function get_current_tab() {

		if ( isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], self::$tabs ) ) {
			return $_GET['tab'];
		} else {
			return 'licenses';
		}
	}

	/**
	 * Dispatch the request.
	 */
	public function dispatch() {
		self::$tabs[ $this->tab ]['class']->render();
	}

	/**
	 * Register a tab.
	 *
	 * @param string                      $slug
	 * @param string                      $name
	 * @param ITELIC_Admin_Tab_Controller $controller
	 */
	public static function register_tab( $slug, $name, $controller ) {
		self::$tabs[ $slug ] = array(
			'class' => $controller,
			'name'  => $name
		);
	}

	/**
	 * Get tabs
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_tabs() {
		$tabs = array();

		foreach ( self::$tabs as $slug => $data ) {
			$tabs[ $slug ] = array(
				'name' => $data['name'],
				'link' => self::get_tab_link( $slug )
			);
		}

		return $tabs;
	}

	/**
	 * Get a link to a tab.
	 *
	 * @since 1.0
	 *
	 * @param string $tab
	 *
	 * @return string
	 */
	public static function get_tab_link( $tab ) {
		return admin_url( "admin.php?page=" . self::PAGE_SLUG . "&tab=$tab" );
	}

	/**
	 * Check if the current view is for a certain tab.
	 *
	 * @since 1.0
	 *
	 * @param string $tab
	 *
	 * @return bool
	 */
	public static function is_current_view( $tab ) {
		if ( ! isset( self::$tabs[ $tab ] ) ) {
			return false;
		}

		return $tab == self::get_current_tab();
	}
}