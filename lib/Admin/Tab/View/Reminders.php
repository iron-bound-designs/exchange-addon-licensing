<?php
/**
 * View for the class reminders.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
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
	 * Display tabs.
	 *
	 * @param string $current_tab
	 */
	public function tabs( $current_tab ) {
		echo '<style type="text/css">

			h3.nav-tab-wrapper {
				margin-bottom: 1em;
			}

			@media screen and (max-width: 480px) {

				h3.nav-tab-wrapper {
					padding: 0;
				}

				h3 .nav-tab {
					width: 100%;
					margin: 0;
					display: block;
					padding: 10px 0;
					text-align: center;
				}
			}
		</style>';
		parent::tabs( $current_tab );
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