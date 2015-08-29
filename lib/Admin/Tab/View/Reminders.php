<?php
/**
 * View for the class reminders.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Tab\View;

use ITELIC\Admin\Tab\View;
use IronBound\WP_Notifications\Template\Editor;
use IronBound\WP_Notifications\Template\Factory;
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

		$editor = new Editor( Factory::make( 'itelic-renewal-reminder' ), array(
			'mustSelectItem'    => __( "You must select an item", Plugin::SLUG ),
			'selectTemplateTag' => __( "Select Template Tag", Plugin::SLUG ),
			'templateTag'       => __( "Template Tag", Plugin::SLUG ),
			'selectATag'        => __( "Select a tag", Plugin::SLUG ),
			'insertTag'         => __( "Insert", Plugin::SLUG ),
			'cancel'            => __( "Cancel", Plugin::SLUG ),
			'insertTemplateTag' => __( "Insert Template Tag", Plugin::SLUG )
		) );

		add_action( 'admin_footer', function () use ( $editor ) {
			$editor->thickbox();
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