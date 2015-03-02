<?php
/**
 * View for the class reminders.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Admin_Tab_View_Reminders
 */
class ITELIC_Admin_Tab_View_Reminders extends ITELIC_Admin_Tab_View {

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Renewal Reminders", ITELIC::SLUG );
	}
}