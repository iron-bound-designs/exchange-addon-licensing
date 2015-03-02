<?php
/**
 * Renewal Reminders Controller
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Admin_Tab_Controller_Reminders
 */
class ITELIC_Admin_Tab_Controller_Reminders extends ITELIC_Admin_Tab_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'views_edit-it_exchange_licrenew', array( $this, 'render' ) );
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$view = new ITELIC_Admin_Tab_View_Reminders();
		$view->tabs('reminders');
	}

	/**
	 * Override the tab link.
	 *
	 * @param string $tab_slug
	 *
	 * @return string
	 */
	public function get_tab_link( $tab_slug ) {
		$type = ITELIC_Renewal_Reminder_Type::TYPE;

		return admin_url( "edit.php?post_type=$type" );
	}
}