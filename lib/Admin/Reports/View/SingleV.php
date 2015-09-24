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

		$selected_type = isset( $_GET['date_type'] ) ? $_GET['date_type'] : 'this_year';
		?>

		<form method="GET" class="filter-form">

			<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">
			<input type="hidden" name="tab" value="<?php echo $_GET['tab']; ?>">
			<input type="hidden" name="view" value="<?php echo $_GET['view']; ?>">
			<input type="hidden" name="report" value="<?php echo $_GET['report']; ?>">

			<label for="date-type" class="screen-reader-text"><?php _e( "Select a date range.", Plugin::SLUG ); ?></label>
			<select name="date_type" id="date-type">

				<?php foreach ( $this->report->get_date_types() as $type => $label ): ?>

					<option value="<?php echo $type; ?>" <?php selected( $type, $selected_type ) ?>>
						<?php echo $label; ?>
					</option>

				<?php endforeach; ?>
			</select>

			<?php submit_button( __( "Filter", Plugin::SLUG ), 'button', 'submit', false ); ?>

		</form>

		<div class="report report-<?php echo $this->report->get_slug(); ?>">

			<h3><?php echo $this->report->get_title(); ?></h3>

			<p class="description">
				<?php echo $this->report->get_description(); ?>
			</p>

			<div class="chart">
				<?php $this->report->get_chart( $selected_type )->graph(); ?>
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