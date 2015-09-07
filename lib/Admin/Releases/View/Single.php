<?php
/**
 * View for a single release.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\View;

use IronBound\WP_Notifications\Template\Editor;
use IronBound\WP_Notifications\Template\Factory;
use ITELIC\Admin\Chart;
use ITELIC\Admin\Tab\Dispatch;
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
	 * @var Chart
	 */
	protected $progress;

	/**
	 * Constructor.
	 *
	 * @param Release $release
	 * @param Chart   $progress
	 */
	public function __construct( Release $release = null, Chart $progress = null ) {
		$this->release  = $release;
		$this->progress = $progress;
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
			<?php $this->render_upgrades_bar(); ?>
			<?php $this->render_notification_editor(); ?>
			<?php $this->render_progress_line_chart(); ?>
			<?php $this->render_notify_button_section(); ?>
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

		<div class="spacing-wrapper bottom-border replace-file-block">

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

		<div class="spacing-wrapper bottom-border changelog-block">

			<h4><?php _e( "What's Changed", Plugin::SLUG ); ?></h4>

			<div class="whats-changed">
				<?php echo $this->release->get_changelog(); ?>
			</div>

		</div>

		<?php

	}

	/**
	 * Render the upgrades completion bar.
	 *
	 * @since 1.0
	 */
	protected function render_upgrades_bar() {

		$updated           = $this->release->get_total_updated();
		$total_activations = $this->release->get_total_active_activations();

		$percent = $updated / $total_activations * 100;

		?>

		<div class="spacing-wrapper bottom-border upgrade-progress-block">

			<h4>
				<?php _e( "Upgrades", Plugin::SLUG ); ?>
				<a href="javascript:" id="more-upgrades-link"><?php _e( "More", Plugin::SLUG ); ?></a>
			</h4>

			<div class="progress-container" data-percent="<?php echo $percent; ?>">

				<progress value="<?php echo esc_attr( $updated ); ?>" max="<?php echo esc_attr( $total_activations ); ?>">
					<div class="progress-bar">
						<span style="width: <?php echo $percent; ?>%;">Progress: <?php echo $percent; ?>%</span>
					</div>
				</progress>

				<a href="javascript:" class="button" id="notify-button"><?php _e( "Notify", Plugin::SLUG ); ?></a>
			</div>
		</div>

		<?php
	}

	/**
	 * Render the full-width notify button section.
	 *
	 * @since 1.0
	 */
	protected function render_notify_button_section() {

		?>

		<div class="spacing-wrapper bottom-border full-notify-button hidden">
			<a href="javascript:" class="button" id="notify-button-full">
				<?php _e( "Notify Outdated Customers", Plugin::SLUG ); ?>
			</a>
		</div>

		<?php
	}

	/**
	 * Render the progress line chart.
	 *
	 * @since 1.0
	 */
	protected function render_progress_line_chart() {

		?>

		<div class="spacing-wrapper bottom-border progress-line-chart hidden">

			<h4><?php _e( "Upgrades over the first 14 days", Plugin::SLUG ); ?></h4>

			<?php $this->progress->graph(); ?>
		</div>

		<?php
	}

	/**
	 * Render the notification editor.
	 *
	 * @since 1.0
	 */
	protected function render_notification_editor() {

		$editor = new Editor( Factory::make( 'itelic-renewal-reminder' ), array(
			'mustSelectItem'    => __( "You must select an item", Plugin::SLUG ),
			'selectTemplateTag' => __( "Select Template Tag", Plugin::SLUG ),
			'templateTag'       => __( "Template Tag", Plugin::SLUG ),
			'selectATag'        => __( "Select a tag", Plugin::SLUG ),
			'insertTag'         => __( "Insert", Plugin::SLUG ),
			'cancel'            => __( "Cancel", Plugin::SLUG ),
			'insertTemplateTag' => __( "Insert Template Tag", Plugin::SLUG )
		) );
		$editor->thickbox();

		?>

		<div class="spacing-wrapper hidden notifications-editor">

			<h4><?php _e( "Send Upgrade Reminders", Plugin::SLUG ); ?></h4>

			<p class="description">
				<?php printf(
					__( "Email your customers who have not yet upgrade to version %s of your software.", Plugin::SLUG ),
					$this->release->get_version()
				); ?>
			</p>

			<input type="text" id="notification-subject" placeholder="<?php esc_attr_e( "Enter your subject", Plugin::SLUG ); ?>">

			<?php $editor->display_template_tag_button(); ?>

			<?php wp_editor( '', 'notification-body', array(
				'teeny'         => true,
				'media_buttons' => false,
				'editor_height' => '250px'
			) ); ?>

			<p class="clearfix notification-buttons">
				<a href="javascript:" class="button button-secondary" id="cancel-notification">
					<?php _e( "Cancel", Plugin::SLUG ); ?>
				</a>
				<a href="javascript:" class="button button-primary" id="send-notification">
					<?php _e( "Send", Plugin::SLUG ); ?>
				</a>
			</p>
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

	/**
	 * Override title display to show an add new button.
	 *
	 * @since 1.0
	 */
	public function title() {
		echo '<h2>' . $this->get_title() . ' ';
		echo '<a href="' . add_query_arg( 'view', 'add-new', Dispatch::get_tab_link( 'releases' ) ) . '" class="add-new-h2">';
		echo __( "Add New", Plugin::SLUG );
		echo '</a>';
		echo '</h2>';
	}
}