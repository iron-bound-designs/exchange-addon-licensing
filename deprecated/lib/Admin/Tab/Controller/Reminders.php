<?php
/**
 * Renewal Reminders Controller
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab\Controller;

use ITELIC\Admin\Tab\Controller;
use ITELIC\Admin\Tab\View\Reminders as Reminders_View;
use ITELIC\Renewal\Reminder;

/**
 * Class Reminders
 * @package ITELIC\Admin\Tab\Controller
 */
class Reminders extends Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'views_edit-it_exchange_licrenew', array( $this, 'override_views' ) );
		add_action( 'edit_form_top', array( $this, 'tabs_on_edit' ) );
	}

	/**
	 * Override the views to output our tab links.
	 *
	 * @since 1.0
	 *
	 * @param array $views
	 *
	 * @return array
	 */
	public function override_views( $views ) {

		$this->render();

		return $views;
	}

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$view = new Reminders_View();
		$view->tabs( 'reminders' );
	}

	/**
	 * Add the tabs on the edit screen.
	 */
	public function tabs_on_edit() {
		$screen = get_current_screen();

		if ( $screen->post_type == Reminder\CPT::TYPE ) {
			$this->render();
		}
	}

	/**
	 * Override the tab link.
	 *
	 * @param string $tab_slug
	 *
	 * @return string
	 */
	public function get_tab_link( $tab_slug ) {
		$type = Reminder\CPT::TYPE;

		return admin_url( "edit.php?post_type=$type" );
	}
}