<?php
/**
 * Add New release view.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

namespace ITELIC\Admin\Releases\View;

use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;
use ITELIC\Release;

/**
 * Class Add_New
 * @package ITELIC\Admin\Releases\View
 */
class Add_New extends View {

	/**
	 * @var bool
	 */
	private $show_help = true;

	/**
	 * Add_New constructor.
	 *
	 * @param bool $show_help
	 */
	public function __construct( $show_help ) {
		$this->show_help = $show_help;
	}

	/**
	 * Render the view.
	 */
	public function render() {

		$selected = isset( $_POST['type-select'] ) ? $_POST['type-select'] : '';

		if ( ! array_key_exists( $selected, Release::get_types() ) ) {
			$selected = '';
		}

		if ( ! $selected ) {
			$style = 'style="opacity: 0;"';
		} else {
			$style = '';
		}

		$security_msg_hidden = $selected == Release::TYPE_SECURITY ? '' : ' hidden';
		?>
		<form method="POST" action="<?php echo esc_attr( add_query_arg( 'view', 'add-new', Dispatch::get_tab_link( 'releases' ) ) ); ?>">

			<?php do_action( 'itelic_add_new_release_screen_before_types' ); ?>
			<?php $this->render_types_tab( $selected ); ?>

			<?php if ( $this->show_help ): ?>
				<div class="release-help">
					<p class="release-help-major">
						<?php echo \ITELIC\Admin\Help\get_major_release_help_text(); ?>
					</p>

					<p class="release-help-minor">
						<?php echo \ITELIC\Admin\Help\get_minor_release_help_text(); ?>
					</p>

					<p class="release-help-security">
						<?php echo \ITELIC\Admin\Help\get_security_release_help_text(); ?>
					</p>

					<p class="release-help-pre-release">
						<?php echo \ITELIC\Admin\Help\get_pre_release_help_text(); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php do_action( 'itelic_add_new_release_screen_after_types' ); ?>

			<?php do_action( 'itelic_add_new_release_screen_before' ); ?>

			<div class="main-editor" <?php echo $style; ?>>

				<?php do_action( 'itelic_add_new_release_screen_begin' ); ?>

				<div class="row row-one">

					<div class="product-select-container">
						<?php $this->render_product_select( isset( $_POST['product'] ) ? $_POST['product'] : 0 ); ?>
					</div>

					<div class="version-number-container">
						<?php $this->render_version_number( isset( $_POST['version'] ) ? $_POST['version'] : 0 ); ?>
					</div>
				</div>

				<div class="row row-two">
					<div class="upload-container dropzone">
						<?php $this->render_upload(); ?>
					</div>
				</div>

				<div class="row row-three">
					<div class="whats-changed-container">
						<?php $this->render_whats_changed( isset( $_POST['whats-changed'] ) ? $_POST['whats-changed'] : '' ); ?>
					</div>
				</div>

				<div class="row row-five <?php echo $security_msg_hidden; ?>" id="security-message-row">
					<div class="security-message">
						<?php $this->render_security_message( isset( $_POST['security-message'] ) ? $_POST['security-message'] : '' ); ?>
					</div>
				</div>

				<div class="row row-four">
					<div class="buttons">
						<?php $this->render_buttons(); ?>
					</div>
				</div>

				<?php do_action( 'itelic_add_new_release_screen_end' ); ?>

			</div>

			<input type="hidden" name="itelic-action" value="add-new-release">

			<?php wp_nonce_field( 'itelic-add-new-release' ); ?>

			<?php do_action( 'itelic_add_new_release_screen_after' ); ?>
		</form>

		<?php

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

				<li class="<?php echo $type == $selected ? 'selected' : ''; ?>">
					<input type="radio" name="type-select" <?php checked( $selected, $type ); ?> id="type-select-<?php echo $type; ?>" value="<?php echo $type; ?>">
					<label for="type-select-<?php echo $type; ?>">
						<span class="dashicons <?php echo $this->get_icon_for_type( $type ); ?>"></span>
						<span class="type-description"><?php echo $label; ?></span>
					</label>
				</li>

			<?php endforeach; ?>
		</ul>

		<?php

	}

	/**
	 * Render the product select dropdown.
	 *
	 * @since 1.0
	 *
	 * @param int $selected
	 */
	protected function render_product_select( $selected = 0 ) {

		$products = itelic_get_products_with_licensing_enabled();

		?>

		<label for="product"><?php _e( "Select a Product", Plugin::SLUG ); ?></label>
		<select id="product" name="product">

			<option value="">– <?php _e( "Select", Plugin::SLUG ); ?> –</option>

			<?php foreach ( $products as $product ): ?>
				<?php $version = $product->get_feature( 'licensing', array( 'field' => 'version' ) ); ?>
				<option value="<?php echo $product->ID; ?>" data-version="<?php echo esc_attr( $version ); ?>"
					<?php selected( $selected, $product->ID ); ?>>
					<?php echo $product->post_title; ?>
				</option>
			<?php endforeach; ?>
		</select>

		<?php

	}

	/**
	 * Render the version number input.
	 *
	 * @since 1.0
	 *
	 * @param int $current
	 */
	protected function render_version_number( $current = 0 ) {

		?>

		<label for="version"><?php _e( "Version Number", Plugin::SLUG ); ?></label>
		<input type="text" id="version" name="version" value="<?php echo empty( $current ) ? '' : $current; ?>">
		<p class="description" id="prev-version" style="opacity: 0;">&nbsp;</p>

		<?php

	}

	/**
	 * Render the upload form.
	 *
	 * @since 1.0
	 */
	protected function render_upload() {

		?>

		<div class="upload-inputs">
			<a href="javascript:">
				<label for="file"><?php _e( "Upload File", Plugin::SLUG ); ?></label>
			</a>

			<div class="progress-container">
				<progress max="100" value="0"></progress>
			</div>
			<input type="hidden" name="upload-file" id="upload-file">
		</div>

		<a class="trash-file dashicons dashicons-trash" style="display: none"></a>

		<?php
	}

	/**
	 * Render the whats changed editor.
	 *
	 * @since 1.0
	 *
	 * @param string $content
	 */
	protected function render_whats_changed( $content = '' ) {

		?><label for="whats-changed">
		<?php _e( "What's Changed", Plugin::SLUG ); ?>
		<span class="tip" title="<?php _e( "Don't include the version number or date, they'll be added automatically.", Plugin::SLUG ) ?>">i</span>
		</label>

		<?php wp_editor( $content, 'whats-changed', array(
			'media_buttons' => false,
			'teeny'         => true,
			'textarea_rows' => 5,
		) );
	}

	/**
	 * Optionally displayed when the release type is a security release.
	 *
	 * Allows for displaying a security message on the upgrade page.
	 *
	 * @since 1.0
	 *
	 * @param string $msg
	 */
	protected function render_security_message( $msg = '' ) {

		?>

		<label for="security-message"><?php _e( "Security Message", Plugin::SLUG ); ?></label>
		<textarea id="security-message" name="security-message" maxlength="200" rows="3"><?php echo $msg; ?></textarea>
		<p class="description">
			<?php _e( "Optionally display a security message on the software update page, alerting your customer to the urgency of the issue.", Plugin::SLUG ); ?>
		</p>

		<?php
	}

	/**
	 * Render the controls for entering keys that can access a restricted release.
	 *
	 * @since 1.0
	 */
	protected function render_restricted_keys() {

		?>


		<?php
	}

	/**
	 * Render the action buttons.
	 */
	protected function render_buttons() {
		submit_button( __( "Save for Later", Plugin::SLUG ), 'secondary large', 'draft', false );
		submit_button( __( "Release", Plugin::SLUG ), 'primary large', 'release', false );
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