<?php
/**
 * View for the class reminders.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Tab\View;

use ITELIC\Admin\Tab\View;
use ITELIC\Notifications\Template\Editor;
use ITELIC\Notifications\Template\Factory;
use ITELIC\Plugin;

/**
 * Class Reminders
 * @package ITELIC\Admin\Tab\View
 */
class Reminders extends View {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$editor = new Editor( Factory::make( 'renewal-reminder' ) );

		add_action( 'admin_footer', function () use ( $editor ) {
			$editor->shortcode_popup();
			unset( $editor );
		} );
	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Renewal Reminders", Plugin::SLUG );
	}
}