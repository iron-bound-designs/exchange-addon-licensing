<?php
/**
 * Single report view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports\View;

use ITELIC\Admin\Reports\Report;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;

/**
 * Class SingleV
 * @package ITELIC\Admin\Reports\View
 */
class SingleV extends View {

	/**
	 * @var Report
	 */
	private $report;

	/**
	 * Constructor.
	 *
	 * @param Report $report
	 */
	public function __construct( Report $report = null ) {
		$this->report = $report;
	}

	/**
	 * Render the view.
	 *
	 * @since 1.0
	 */
	public function render() {

		if ( ! $this->report ) {
			return;
		}
		?>

		<div class="report report-<?php echo $this->report->get_slug(); ?>">

			<h3><?php echo $this->report->get_title(); ?></h3>

			<p class="description">
				<?php echo $this->report->get_description(); ?>
			</p>

			<div class="chart">
				<?php $this->report->get_chart()->graph(); ?>
			</div>

			<div id="legend-<?php echo $this->report->get_slug(); ?>" class="chart-js-legend"></div>

		</div>

		<?php

	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( 'View Report', Plugin::SLUG );
	}
}