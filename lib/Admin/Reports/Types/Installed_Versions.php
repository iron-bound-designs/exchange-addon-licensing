<?php
/**
 * Installed versions report
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports\Types;

use ITELIC\Admin\Chart\Base as Chart;
use ITELIC\Admin\Reports\Report;
use ITELIC\Plugin;

/**
 * Class Installed_Versions
 * @package ITELIC\Admin\Reports\Types
 */
class Installed_Versions extends Report {

	/**
	 * Get the title of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_title() {
		return __( "Installed versions", Plugin::SLUG );
	}

	/**
	 * Get the slug of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'installed-versions';
	}

	/**
	 * Get the description of this report type.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_description() {
		return __(
			"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec euismod pharetra felis sit amet faucibus. ",
			Plugin::SLUG
		);
	}

	/**
	 * Get the chart for this report.
	 *
	 * @since 1.0
	 *
	 * @param string $date_type
	 *
	 * @return Chart
	 */
	public function get_chart( $date_type = 'this_year' ) {
		// TODO: Implement get_chart() method.
	}
}

