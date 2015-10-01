<?php
/**
 * View for rendering the license.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Licenses\View;

use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;

/**
 * Class ListV
 * @package ITELIC\Admin\Licenses\View
 */
class ListV extends View {

	/**
	 * @var \WP_List_Table
	 */
	private $table;

	/**
	 * Constructor.
	 *
	 * @param \WP_List_Table $table
	 */
	public function __construct( $table ) {
		$this->table = $table;
		$this->table->prepare_items();
	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Licenses", Plugin::SLUG );
	}

	/**
	 * Render the view.
	 */
	public function render() {

		?>

		<form method="GET">
			<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>">

			<?php if ( isset( $_GET['status'] ) ): ?>
				<input type="hidden" name="status" value="<?php echo esc_attr( $_GET['status'] ); ?>">
			<?php endif; ?>

			<?php $this->table->views(); ?>
			<?php $this->table->search_box( __( "Search", Plugin::SLUG ), 'itelic-search' ); ?>
			<?php $this->table->display(); ?>
		</form>

		<?php
	}
}