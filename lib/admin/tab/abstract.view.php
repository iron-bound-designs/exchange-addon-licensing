<?php
/**
 * Abstract view base class.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITECLS_Admin_Roster_View
 */
abstract class ITELIC_Admin_Tab_View {

	/**
	 * Begin the page.
	 */
	public function begin() {
		echo '<div class="wrap">';
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
				<?php foreach ( ITELIC_Admin_Tab_Dispatch::get_tabs() as $slug => $tab ): ?>
					<a class="nav-tab <?php echo ( $current_tab == $slug ) ? 'nav-tab-active' : ''; ?>" href="<?php echo $tab['link']; ?>">
						<?php echo $tab['name']; ?>
					</a>
				<?php endforeach; ?>
			</h3>
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