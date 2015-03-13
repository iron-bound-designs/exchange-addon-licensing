<?php
/**
 * Base Product Feature
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Product_Feature_Base
 *
 * Base product feature for licensing. This controls the versions of the software released,
 * as well as the ReadMe parser. If variants is not enabled, this will also present an
 * input to configure the number of times this product's license can be activated.
 *
 * @since 1.0
 */
class ITELIC_Product_Feature_Base extends IT_Exchange_Product_Feature_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$args = array(
			'slug'          => 'licensing',
			'description'   => __( "Manage product licenses.", ITELIC::SLUG ),
			'metabox_title' => __( "Licensing", ITELIC::SLUG ),
			'product_types' => array( 'digital-downloads-product-type' )
		);

		parent::IT_Exchange_Product_Feature_Abstract( $args );

		add_action( 'wp_ajax_itelic_get_key_type_settings', array( $this, 'ajax_get_key_type_settings' ) );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0
	 *
	 * @param WP_Post $post
	 */
	function print_metabox( $post ) {
		add_action( 'admin_footer', array( $this, 'changelog_popup' ) );

		wp_enqueue_style( 'itelic-add-edit-product' );
		wp_enqueue_script( 'itelic-add-edit-product' );
		wp_localize_script( 'itelic-add-edit-product', 'ITELIC', array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'product' => isset( $post->ID ) ? $post->ID : 0,
			'df'      => it_exchange_php_date_format_to_jquery_datepicker_format( get_option( 'date_format' ) )
		) );

		$downloads = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, 'downloads' );
		$data      = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, $this->slug );

		$hidden          = $data['enabled'] ? '' : ' hide-if-js';
		$hidden_variants = $data['enabled_variant_activations'] ? '' : ' hide-if-js';
		$hidden_simple   = $hidden_variants ? '' : ' hide-if-js';
		?>

		<p><?php echo $this->description; ?></p>

		<p>
			<input type="checkbox" id="itelic-enable" name="itelic[enabled]" <?php checked( true, $data['enabled'] ); ?>>
			<label for="itelic-enable"><?php _e( "Enable Licensing for this product", ITELIC::SLUG ); ?></label>
		</p>

		<?php $controller = $this->get_variants_controller( $post->ID ); ?>

		<div class="itelic-settings<?php echo esc_attr( $hidden ); ?>">

			<label for="itelic-update-file"><?php _e( "Update File", ITELIC::SLUG ); ?></label>
			<select id="itelic-update-file" name="itelic[update-file]">
				<?php foreach ( $downloads as $download ): ?>
					<option value="<?php echo esc_attr( $download['id'] ); ?>" <?php selected( $data['update-file'], $download['id'] ); ?>>
						<?php echo $download['name']; ?>
					</option>
				<?php endforeach; ?>
			</select>

			<p class="description"><?php _e( "Select a file to be used for automatic updates.", ITELIC::SLUG ); ?></p>

			<label for="itelic-version"><?php _e( "Current Version", ITELIC::SLUG ); ?></label>
			<input type="text" id="itelic-version" name="itelic[version]" value="<?php echo esc_attr( $data['version'] ); ?>">

			<p class="description"><?php _e( "Update this whenever you want to push out an update.", ITELIC::SLUG ); ?></p>

			<label for="itelic-changelog"><?php _e( "Changelog", ITELIC::SLUG ); ?></label>
			<textarea id="itelic-changelog" class="thickbox" name="itelic[changelog]" readonly><?php echo $data['changelog']; ?></textarea>

			<p class="description"><?php _e( "You should update this whenever you update your software.", ITELIC::SLUG ); ?></p>

			<label for="itelic-key-type"><?php _e( "Key Type", ITELIC::SLUG ); ?></label>
			<select id="itelic-key-type" name="itelic[key-type]">

				<option value=""><?php _e( "Select a Key Type", ITELIC::SLUG ); ?></option>

				<?php foreach ( itelic_get_key_types() as $slug => $type ): ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $data['key-type'], $slug ); ?>>
						<?php echo itelic_get_key_type_name( $slug ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<p class="description"><?php _e( "How should license keys be generated for this product.", ITELIC::SLUG ); ?></p>

			<div id="itelic-key-type-settings">
				<?php if ( $data['key-type'] != - 1 ): ?>
					<?php $this->get_key_type_settings( $data['key-type'], isset( $post->ID ) ? $post->ID : 0 ); ?>
				<?php endif; ?>
			</div>

			<?php if ( $controller ): ?>
				<p>
					<input type="checkbox" id="itelic-enable-variant-activations" name="itelic[enabled_variant_activations]"
						<?php checked( true, $data['enabled_variant_activations'] ); ?>>
					<label for="itelic-enable-variant-activations"><?php _e( "Enable Variant Activation limits", ITELIC::SLUG ); ?></label>
				</p>
			<?php endif; ?>

			<div class="itelic-activation-limit<?php echo esc_attr( $hidden_simple ); ?>">
				<label for="itelic-limit"><?php _e( "Activation Limit", ITELIC::SLUG ); ?></label>
				<input type="number" name="itelic[limit]" id="itelic-limit" min="0" value="<?php echo esc_attr( $data['limit'] ); ?>">
			</div>

			<?php if ( $controller ): ?>
				<?php $hashes = $data['activation_variant']; ?>

				<div class="itelic-variants-activation-limit-table<?php echo esc_attr( $hidden_variants ); ?>">

					<div class="itelic-activation-limit-variant-header-row">
						<div class="itelic-activation-limit-variant-header-cell"><?php _e( "Variant", ITELIC::SLUG ); ?></div>
						<div class="itelic-activation-limit-variant-header-cell itelic-activation-limit-variant-input-cell"><?php _e( "Limit", ITELIC::SLUG ); ?></div>
					</div>

					<?php foreach ( $controller->all_variant_combos_for_product as $combo ): ?>
						<?php $hash = $this->combo_to_hash( $combo ); ?>
						<div class="itelic-activation-limit-variant-row">

							<div class="itelic-activation-limit-variant-cell"><?php echo $controller->generate_title_from_combos( $combo ); ?></div>

							<div class="itelic-activation-limit-variant-cell itelic-activation-limit-variant-input-cell">
								<input class="itelic-activation-limit-variant-input" name="itelic[activation_variant][<?php echo esc_attr( $hash ); ?>]" type="number" min="0"
								       value="<?php echo isset( $hashes[ $hash ] ) ? $hashes[ $hash ] : ''; ?>">
							</div>
						</div>
					<?php endforeach; ?>
				</div>

			<?php endif; ?>

			<p class="description"><?php _e( "How many times can this license be activated. Leave blank for unlimited.", ITELIC::SLUG ); ?></p>
		</div>
	<?php
	}

	/**
	 * Get the variants controller.
	 *
	 * @since 1.0
	 *
	 * @param $product_id int
	 *
	 * @return IT_Exchange_Variants_Addon_Product_Feature_Combos|null
	 */
	protected function get_variants_controller( $product_id ) {

		if ( function_exists( 'it_exchange_variants_addon_get_product_feature_controller' ) ) {
			$controller                                 = it_exchange_variants_addon_get_product_feature_controller( $product_id, $this->slug, array() );
			$controller->all_variant_combos_for_product = it_exchange_variants_addon_get_all_variant_combos_for_product( $product_id, false );
		} else {
			$controller = null;
		}

		return $controller;
	}

	/**
	 * Convert a combo to a hash.
	 *
	 * @since 1.0
	 *
	 * @param $combo array
	 *
	 * @return null|string
	 */
	protected function combo_to_hash( $combo ) {
		if ( function_exists( 'it_exchange_variants_addon_get_selected_variants_id_hash' ) ) {

			$variants_to_hash = array();

			foreach ( $combo as $id ) {
				if ( $variant = it_exchange_variants_addon_get_variant( $id ) ) {
					$variants_to_hash[ empty( $variant->post_parent ) ? $id : $variant->post_parent ] = $id;
				}
			}

			$hash = it_exchange_variants_addon_get_selected_variants_id_hash( $variants_to_hash );
		} else {
			$hash = null;
		}

		return $hash;
	}

	/**
	 * Process the AJAX callback for the settings form for a certain key type.
	 *
	 * @since 1.0
	 */
	public function ajax_get_key_type_settings() {
		$type    = sanitize_text_field( $_POST['type'] );
		$product = absint( $_POST['product'] );

		$this->get_key_type_settings( $type, $product );

		die();
	}

	/**
	 * Get the key type settings form.
	 *
	 * @since 1.0
	 *
	 * @param string $type
	 * @param int    $product
	 */
	protected function get_key_type_settings( $type, $product ) {

		$prefix = "itelic[type][$type]";

		$values = it_exchange_get_product_feature( $product, $this->slug, array( 'field' => "type.$type" ) );

		$this->render_key_type_settings( $type, $product, $prefix, $values );
	}

	/**
	 * Render key type settings.
	 *
	 * @since 1.0
	 *
	 * @param string $type
	 * @param int    $product
	 * @param string $prefix
	 * @param array  $values
	 */
	private function render_key_type_settings( $type, $product, $prefix, $values ) {
		/**
		 * Fires when the settings form for a key type should be shown.
		 *
		 * @since 1.0
		 *
		 * @param int    $product
		 * @param string $prefix
		 * @param array  $values
		 */
		do_action( "it_exchange_itelic_render_key_type_{$type}_settings", $product, $prefix, $values );
	}

	/**
	 * Outputs the change log edit popup.
	 *
	 * @since 1.0
	 */
	public function changelog_popup() {
		?>

		<div id="itelic-edit-changelog-popup" style="display: none">
			<div class="wrap">
				<p><?php _e( "Select a piece of data to insert" ); ?></p>

				<label for="itelic-edit-changelog"><?php _e( "Changelog", ITELIC::SLUG ); ?></label>
				<textarea id="itelic-edit-changelog"></textarea>
			</div>

			<div style="padding: 15px 15px 15px 0">
				<input type="button" class="button-primary update-changelog" value="<?php _e( 'Update Changelog' ); ?>" />
				&nbsp;&nbsp;&nbsp;
				<a class="button cancel-update-changelog" style="color:#bbb;" href="javascript:">
					<?php _e( 'Cancel' ); ?>
				</a>
			</div>
		</div>

	<?php
	}

	/**
	 * This saves the value.
	 *
	 * @since 1.0
	 */
	public function save_feature_on_product_save() {

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];

		if ( ! $product_id ) {
			return;
		}

		$data = $_POST['itelic'];

		$data['enabled']                     = isset( $data['enabled'] ) ? it_exchange_str_true( $data['enabled'] ) : false;
		$data['enabled_variant_activations'] = isset( $data['enabled_variant_activations'] ) ? it_exchange_str_true( $data['enabled_variant_activations'] ) : false;

		it_exchange_update_product_feature( $product_id, $this->slug, $data );
	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.0
	 *
	 * @param integer $product_id the product id
	 * @param array   $new_value  the new value
	 * @param array   $options
	 *
	 * @return boolean
	 */
	public function save_feature( $product_id, $new_value, $options = array() ) {

		$prev_values = it_exchange_get_product_feature( $product_id, $this->slug );
		$values      = ITUtility::merge_defaults( $new_value, $prev_values );

		return update_post_meta( $product_id, '_it_exchange_itelic_feature', $values );
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.0
	 *
	 * @param mixed   $existing   the values passed in by the WP Filter API. Ignored here.
	 * @param integer $product_id the WordPress post ID
	 * @param array   $options
	 *
	 * @return string product feature
	 */
	public function get_feature( $existing, $product_id, $options = array() ) {
		$defaults = array(
			'enabled'                     => false,
			'limit'                       => '',
			'key-type'                    => '',
			'update-file'                 => '',
			'version'                     => '',
			'changelog'                   => '',
			'enabled_variant_activations' => false,
			'activation_variant'          => array()
		);

		$values   = get_post_meta( $product_id, '_it_exchange_itelic_feature', true );
		$raw_meta = ITUtility::merge_defaults( $values, $defaults );

		if ( ! isset( $options['field'] ) ) { // if we aren't looking for a particular field
			return $raw_meta;
		}

		if ( $options['field'] == 'limit' && isset( $options['for_hash'] ) ) {
			$hash = $options['for_hash'];

			if ( isset( $raw_meta['activation_variant'][ $hash ] ) ) {
				return $raw_meta['activation_variant'][ $hash ];
			} else {
				return null;
			}
		}

		$field = $options['field'];
		if ( isset( $raw_meta[ $field ] ) ) { // if the field exists with that name just return it
			return $raw_meta[ $field ];
		} else if ( strpos( $field, "." ) !== false ) { // if the field name was passed using array dot notation
			$pieces  = explode( '.', $field );
			$context = $raw_meta;
			foreach ( $pieces as $piece ) {
				if ( ! is_array( $context ) || ! array_key_exists( $piece, $context ) ) {
					// error occurred
					return null;
				}
				$context = &$context[ $piece ];
			}

			return $context;
		} else {
			return null; // we didn't find the data specified
		}
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.0
	 *
	 * @param mixed   $result Not used by core
	 * @param integer $product_id
	 * @param array   $options
	 *
	 * @return boolean
	 */
	public function product_has_feature( $result, $product_id, $options = array() ) {
		return (bool) it_exchange_get_product_feature( $product_id, $this->slug, array( 'field' => 'enabled' ) );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.0
	 *
	 * @param mixed   $result Not used by core
	 * @param integer $product_id
	 * @param array   $options
	 *
	 * @return boolean
	 */
	function product_supports_feature( $result, $product_id, $options = array() ) {
		$product_type = it_exchange_get_product_type( $product_id );

		return it_exchange_product_type_supports_feature( $product_type, $this->slug );
	}
}