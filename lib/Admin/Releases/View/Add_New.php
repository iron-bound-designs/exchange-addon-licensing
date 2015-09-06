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

		?>

		<div class="main-editor" style="opacity: 0">

			<div class="row row-one">

				<div class="product-select-container">
					<?php $this->render_product_select(); ?>
				</div>

				<div class="version-number-container">
					<?php $this->render_version_number(); ?>
				</div>
			</div>

			<div class="row row-two">
				<div class="upload-container">
					<?php $this->render_upload(); ?>
				</div>
			</div>

			<div class="row row-three">
				<div class="whats-changed-container">
					<?php $this->render_whats_changed(); ?>
				</div>
			</div>

			<div class="row row-five hidden" id="security-message-row">
				<div class="security-message">
					<?php $this->render_security_message(); ?>
				</div>
			</div>

			<div class="row row-four">
				<div class="buttons">
					<?php $this->render_buttons(); ?>
				</div>
			</div>

		</div>

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
					<input type="radio" name="type-select" id="type-select-<?php echo $type; ?>" data-type="<?php echo $type; ?>">
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
				<?php $version = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'version' ) ); ?>
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
			<input type="hidden" name="upload-file" id="upload-file">
		</div>

		<a class="trash-file dashicons dashicons-trash" style="display: none"></a>

		<?php
	}

	/**
	 * Render the whats changed editor.
	 *
	 * @since 1.0
	 */
	protected function render_whats_changed() {

		?><label for="whats-changed"><?php _e( "What's Changed", Plugin::SLUG ); ?></label> <?php

		wp_editor( '', 'whats-changed', array(
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
	 */
	protected function render_security_message() {

		?>

		<label for="security-message"><?php _e( "Security Message", Plugin::SLUG ); ?></label>
		<textarea id="security-message" name="security-message" maxlength="200" rows="3"></textarea>
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