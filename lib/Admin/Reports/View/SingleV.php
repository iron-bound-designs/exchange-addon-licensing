<?php
/**
 * Single report view.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Reports\View;

use ITELIC\Admin\Reports\Date_Filterable;
use ITELIC\Admin\Reports\Product_Filterable;
use ITELIC\Admin\Reports\Report;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;

/**
 * Class SingleV
 *
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

		$selected_type    = isset( $_GET['date_type'] ) ? $_GET['date_type'] : 'this_year';
		$selected_product = isset( $_GET['product'] ) ? absint( $_GET['product'] ) : 0;
		$chart            = $this->report->get_chart( $selected_type, $selected_product );

		$show_button = false;
		?>

		<form method="GET" class="filter-form">

			<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">
			<input type="hidden" name="tab" value="<?php echo $_GET['tab']; ?>">
			<input type="hidden" name="view" value="<?php echo $_GET['view']; ?>">
			<input type="hidden" name="report" value="<?php echo $_GET['report']; ?>">

			<?php if ( $this->report instanceof Date_Filterable ): ?>

				<label for="date-type" class="screen-reader-text">
					<?php _e( "Select a date range.", Plugin::SLUG ); ?>
				</label>

				<select name="date_type" id="date-type">

					<?php foreach ( $this->report->get_date_types() as $type => $label ): ?>

						<option value="<?php echo $type; ?>" <?php selected( $type, $selected_type ) ?>>
							<?php echo $label; ?>
						</option>

					<?php endforeach; ?>
				</select>

				<?php $show_button = true; ?>
			<?php endif; ?>

			<?php if ( $this->report instanceof Product_Filterable ): ?>

				<label for="product" class="screen-reader-text">
					<?php _e( "Select a product.", Plugin::SLUG ); ?>
				</label>
				<select name="product" id="product">

					<option value="">
						<?php if ( $this->report->is_product_required() ): ?>
							<?php _e( "Select a Product", Plugin::SLUG ); ?>
						<?php else: ?>
							<?php _e( "All Products", Plugin::SLUG ); ?>
						<?php endif; ?>
					</option>

					<?php foreach ( itelic_get_products_with_licensing_enabled() as $product ): ?>

						<option value="<?php echo $product->ID; ?>" <?php selected( $product->ID, $selected_product ) ?>>
							<?php echo $product->post_title; ?>
						</option>

					<?php endforeach; ?>
				</select>

				<?php $show_button = true; ?>

			<?php endif; ?>

			<?php if ( $show_button ): ?>
				<?php submit_button( __( "Filter", Plugin::SLUG ), 'button', 'submit', false ); ?>
			<?php endif; ?>

		</form>

		<?php if ( ! $chart && $this->report instanceof Product_Filterable && $this->report->is_product_required() && ! $selected_product ): ?>
			<?php $this->notice( __( "You must select a product to view this report.", Plugin::SLUG ), View::NOTICE_INFO ); ?>
			<?php return; ?>
		<?php endif; ?>

		<?php if ( ! $chart || ! $chart->get_total_items() ): ?>
			<?php $this->notice( __( "No data exists for this combination.", Plugin::SLUG ), View::NOTICE_WARNING ); ?>
			<?php return; ?>
		<?php endif; ?>

		<div class="report report-<?php echo $this->report->get_slug(); ?>">

			<h3><?php echo $this->report->get_title(); ?></h3>

			<p class="description">
				<?php echo $this->report->get_description(); ?>
			</p>

			<div class="chart">
				<?php $chart->graph(); ?>
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