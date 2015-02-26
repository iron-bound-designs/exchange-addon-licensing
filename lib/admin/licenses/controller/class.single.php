<?php
/**
 * Single license controller.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Admin_Licenses_Controller_Single
 */
class ITELIC_Admin_Licenses_Controller_Single extends ITELIC_Admin_Licenses_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'load-exchange_page_it-exchange-licensing', array( $this, 'add_screen_options' ) );
	}

	/**
	 * Add help tabs.
	 *
	 * @since 1.0
	 */
	public function add_screen_options() {
		if ( ITELIC_Admin_Licenses_Dispatch::is_current_view( 'single' ) ) {
			$screen = get_current_screen();
			// todo render help tabs
		}
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$view = new ITELIC_Admin_Licenses_View_Single( $this->get_current_key() );

		$view->begin();
		$view->title();

		$view->tabs( 'licenses' );

		$view->render();

		$view->end();
	}

	/**
	 * Get the currently displayed key.
	 *
	 * @since 1.0
	 *
	 * @return ITELIC_Key
	 */
	protected function get_current_key() {
		if ( ! isset( $_GET['key'] ) ) {
			return null;
		}

		return ITELIC_Key::with_key( $_GET['key'] );
	}
}