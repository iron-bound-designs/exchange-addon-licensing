<?php
/**
 * Add New release view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\View;

use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;
use ITELIC\Release;

/**
 * Class Add_New
 * @package ITELIC\Admin\Releases\View
 */
class Add_New extends View {

	/**
	 * Render the view.
	 */
	public function render() {

		$this->render_types_tab();
	}

	/**
	 * Render the types selector.
	 *
	 * @since 1.0
	 *
	 * @param string $selected
	 */
	protected function render_types_tab( $selected = '' ) {

		?>

		<ul class="release-types">

			<?php foreach ( Release::get_types( true ) as $type => $label ): ?>

				<li data-type="<?php echo $type; ?>" class="<?php echo $type == $selected ? 'selected' : ''; ?>">
					<i class="dashicons-before <?php echo $this->get_icon_for_type( $type ); ?>"></i>
					<?php echo $label; ?>
				</li>

			<?php endforeach; ?>
		</ul>

		<?php

	}

	/**
	 * Get the dashicon for a certain release type.
	 *
	 * @since 1.0
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private function get_icon_for_type( $type ) {

		switch ( $type ) {
			case Release::TYPE_MAJOR:
				return 'dashicons-lightbulb';
			case Release::TYPE_MINOR:
				return 'dashicons-sos';
			case Release::TYPE_SECURITY:
				return 'dashicons-lock';
			case Release::TYPE_PRERELEASE;
				return 'dashicons-hammer';
			case Release::TYPE_RESTRICTED:
				return 'dashicons-hidden';
			default:
				/**
				 * Filter the icon for a particular release type.
				 *
				 * @since 1.0
				 *
				 * @param string $type
				 */
				return apply_filters( 'itelic_get_icon_for_release_type', $type );
		}

	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Add New Release", Plugin::SLUG );
	}
}