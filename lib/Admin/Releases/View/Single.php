<?php
/**
 * View for a single release.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\View;

use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;
use ITELIC\Release;

/**
 * Class Single
 * @package ITELIC\Admin\Releases\View
 */
class Single extends View {

	/**
	 * @var Release
	 */
	protected $release;

	/**
	 * Constructor.
	 *
	 * @param Release $release
	 */
	public function __construct( Release $release = null ) {
		$this->release = $release;
	}

	/**
	 * Render the release view.
	 */
	public function render() {

		$df  = get_option( 'date_format' );
		$tf  = get_option( 'time_format' );
		$dtf = "$df $tf";

		?>

		<div id="release-details">
			<div class="spacing-wrapper bottom-border header-block">

				<div class="status status-<?php echo esc_attr( $this->release->get_status() ); ?>">
					<span data-value="<?php echo esc_attr( $this->release->get_status() ); ?>" title="<?php esc_attr_e( "Click to edit", Plugin::SLUG ); ?>">
						<?php echo $this->release->get_status( true ); ?>
					</span>
				</div>

				<div class="name-block">
					<h2 class="product-name"><?php echo $this->release->get_product()->post_title; ?></h2>

					<h2 class="version-name"><?php echo $this->release->get_version(); ?></h2>
				</div>
			</div>

			<div class="spacing-wrapper bottom-border third-row misc-block">
				<div class="third type">
					<h4><?php _e( "Type", Plugin::SLUG ); ?></h4>

					<h3 title="<?php esc_attr_e( "Click to edit", Plugin::SLUG ); ?>">
						<?php echo $this->release->get_type( true ); ?>
					</h3>
				</div>
				<div class="third release-date">
					<h4><?php _e( "Released", Plugin::SLUG ); ?></h4>

					<h3>
						<?php if ( null === $this->release->get_start_date() ): ?>
							–
						<?php else: ?>
							<?php echo $this->release->get_start_date()->format( $df ); ?>
						<?php endif; ?>
					</h3>
				</div>
				<div class="third version">
					<h4><?php _e( "Version", Plugin::SLUG ); ?></h4>

					<h3 title="<?php esc_attr_e( "Click to edit", Plugin::SLUG ); ?>"><?php echo $this->release->get_version(); ?></h3>
				</div>
			</div>

			<?php if ( $this->release->get_status() == Release::STATUS_DRAFT ): ?>
				<?php $this->render_replace_file_section(); ?>
			<?php endif; ?>

			<?php $this->render_whats_changed(); ?>
		</div>

		<?php

	}

	/**
	 * Render the replace file section.
	 *
	 * Only visible on draft views.
	 *
	 * @since 1.0
	 */
	protected function render_replace_file_section() {

		?>

		<div class="spacing-wrapper bottom-border">

			<span class="replace-file-container">
				<label>
					<?php
					$info = get_post_meta( $this->release->get_download(), '_it-exchange-download-info', true );
					echo basename( $info['source'] );
					?>
				</label>
				<a href="javascript:" class="button" id="replace-file"><?php _e( "Replace", Plugin::SLUG ); ?></a>
			</span>
		</div>
		<?php
	}

	/**
	 * Render the what's changed section.
	 *
	 * @since 1.0
	 */
	protected function render_whats_changed() {

		?>

		<div class="spacing-wrapper bottom-border">

			<h4><?php _e( "What's Changed", Plugin::SLUG ); ?></h4>

			<div class="whats-changed">
				<?php echo $this->release->get_changelog(); ?>
			</div>

		</div>

		<?php

	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Manage Release", Plugin::SLUG );
	}
}