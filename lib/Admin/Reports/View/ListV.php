<?php
/**
 * Reports list view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Reports\View;

use ITELIC\Admin\Reports\Report;
use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;

/**
 * Class ListV
 * @package ITELIC\Admin\Reports\View
 */
class ListV extends View {

	/**
	 * @var Report[]
	 */
	private $reports = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param array $reports
	 */
	public function __construct( array $reports = array() ) {
		$this->reports = $reports;
	}

	/**
	 * Render the reports list view.
	 */
	public function render() {

		?>

		<div class="reports-list">
			<?php foreach ( $this->reports as $report ): ?>
				<?php $this->render_report( $report ); ?>
			<?php endforeach; ?>
		</div>

		<?php
	}

	/**
	 * Render a report type preview.
	 *
	 * @since 1.0
	 *
	 * @param Report $report
	 */
	protected function render_report( Report $report ) {

		$link = add_query_arg( array(
			'view'   => 'single',
			'report' => $report->get_slug()
		), Dispatch::get_tab_link( 'reports' ) );

		$desc = $report->get_description();

		if ( strlen( $desc ) > 100 ) {
			$desc = substr( $desc, 0, 97 );
			$desc .= '&hellip;';
		}
		?>

		<div class="report report-<?php echo $report->get_slug(); ?>">

			<h3><?php echo $report->get_title(); ?></h3>

			<p><?php echo $desc; ?></p>

			<a href="<?php echo $link; ?>"><?php _e( "View", Plugin::SLUG ); ?></a>
		</div>

		<?php

	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Reports", Plugin::SLUG );
	}
}