<?php
/**
 * Logs view.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Tab\View;

use IronBound\DBLogger\ListTable;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;

/**
 * Class Logs
 * @package ITELIC\Admin\Tab\View
 */
class Logs extends View {

	/**
	 * @var ListTable
	 */
	private $table;

	/**
	 * Logs constructor.
	 *
	 * @param ListTable $table
	 */
	public function __construct( ListTable $table ) {
		$this->table = $table;
	}

	/**
	 * Render the view.
	 */
	public function render() {

		$this->table->prepare_items();

		?>

		<style type="text/css">
			.column-message {
				width: 40%;
			}

			.column-level {
				width: 10%;
			}

			.column-time {
				width: 20%;
			}
		</style>

		<form method="GET">
			<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $_GET['tab'] ); ?>">

			<?php $this->table->views(); ?>
			<?php $this->table->search_box( __( "Search", Plugin::SLUG ), 'itelic-search' ); ?>
			<?php $this->table->display(); ?>
		</form>


		<?php foreach ( $this->table->items as $item ):

			/** @var \ITELIC\API\Log $item */
			?>

			<div class="hidden" id="log_details_<?php echo $item->get_pk(); ?>">

				<?php
				$context   = (array) $item->get_context();
				$get       = (array) $context['get'];
				$post      = (array) $context['post'];
				$exception = $item->get_exception();
				$trace     = $item->get_trace();
				?>

				<h4>GET Request</h4>
				<pre><?php print_r( $get ); ?></pre>

				<h4>POST Request</h4>
				<pre><?php print_r( $post ); ?></pre>

				<?php if ( ! empty( $exception ) ): ?>

					<h4><?php _e( "Exception Details", Plugin::SLUG ); ?></h4>

					<p><?php printf( __( "Class: %s", Plugin::SLUG ), $exception ); ?></p>

					<?php if ( ! empty( $trace ) ): ?>
						<p><?php echo $trace; ?></p>
					<?php endif ?>

				<?php endif; ?>

			</div>

		<?php endforeach; ?>

		<?php
	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Logs", Plugin::SLUG );
	}
}