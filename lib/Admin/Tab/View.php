<?php
/**
 * Abstract view base class.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Tab;

/**
 * Class View
 * @package ITELIC\Admin\Tab
 */
abstract class View {

	/**
	 * Success notice class.
	 */
	const NOTICE_SUCCESS = 'notice-success';

	/**
	 * Warning notice class.
	 */
	const NOTICE_WARNING = 'notice-warning';

	/**
	 * Error notice class.
	 */
	const NOTICE_ERROR = 'notice-error';

	/**
	 * Info notice class.
	 */
	const NOTICE_INFO = 'notice-info';

	/**
	 * Begin the page.
	 */
	public function begin() {
		echo '<div class="wrap">';
		echo '<style type="text/css">
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
	}

	/**
	 * Render the page title.
	 */
	public function title() {
		echo '<h2>' . $this->get_title() . '</h2>';
	}

	/**
	 * Display tabs.
	 *
	 * @param string $current_tab
	 */
	public function tabs( $current_tab ) {
		?>

		<div class="classes-nav">
			<h3 class="nav-tab-wrapper">
				<?php foreach ( Dispatch::get_tabs() as $slug => $tab ): ?>
					<a class="nav-tab <?php echo ( $current_tab == $slug ) ? 'nav-tab-active' : ''; ?>" href="<?php echo $tab['link']; ?>">
						<?php echo $tab['name']; ?>
					</a>
				<?php endforeach; ?>
			</h3>
		</div>

		<?php
	}

	/**
	 * Render a message to the screen.
	 *
	 * @since 1.0
	 *
	 * @param $message string
	 * @param $type    ( NOTICE_SUCCESS | NOTICE_WARNING | NOTICE_ERROR | NOTICE_INFO )
	 */
	public function notice( $message, $type ) {

		if ( empty( $message ) ) {
			return;
		}

		?>

		<div class="notice <?php echo esc_attr( $type ); ?>">
			<p><?php echo $message; ?></p>
		</div>

		<?php

	}

	/**
	 * End the page.
	 */
	public function end() {
		echo '</div>';
	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected abstract function get_title();
}