<?php
/**
 * Base Product Feature
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Product\Feature;

use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Plugin;
use ITELIC\Product;
use ITELIC\Release;

/**
 * Class Base
 *
 * Base product feature for licensing. This controls the versions of the
 * software released, as well as the ReadMe parser. If variants is not enabled,
 * this will also present an input to configure the number of times this
 * product's license can be activated.
 *
 * @since   1.0
 * @package ITELIC\Product|Feature\Base
 */
class Base extends \IT_Exchange_Product_Feature_Abstract {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$args = array(
			'slug'          => 'licensing',
			'description'   => __( "Manage product licenses.", Plugin::SLUG ),
			'metabox_title' => __( "Licensing", Plugin::SLUG ),
			'product_types' => array( 'digital-downloads-product-type' )
		);

		parent::IT_Exchange_Product_Feature_Abstract( $args );

		add_action( 'wp_ajax_itelic_get_key_type_settings', array(
			$this,
			'ajax_get_key_type_settings'
		) );
		add_action( 'transition_post_status', array( $this, 'activate_initial_release' ), 10, 3 );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0
	 *
	 * @param \WP_Post $post
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

		$settings = it_exchange_get_option( 'addon_itelic' );

		$downloads = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, 'downloads' );

		if ( ! is_array( $downloads ) ) {
			$downloads = array();
		}

		$data = it_exchange_get_product_feature( isset( $post->ID ) ? $post->ID : 0, $this->slug );

		$hidden          = $data['enabled'] ? '' : ' hide-if-js';
		$hidden_variants = $data['enabled_variant_activations'] ? '' : ' hide-if-js';
		$hidden_simple   = $hidden_variants ? '' : ' hide-if-js';

		$version_read = empty( $data['version'] ) ? '' : 'readonly';
		?>

		<p><?php echo $this->description; ?></p>

		<p>
			<input type="checkbox" id="itelic-enable" name="itelic[enabled]" <?php checked( true, $data['enabled'] ); ?>>
			<label for="itelic-enable"><?php _e( "Enable Licensing for this product", Plugin::SLUG ); ?></label>
		</p>

		<div class="itelic-settings<?php echo esc_attr( $hidden ); ?>">

			<?php if ( $settings['sell-online-software'] ): ?>
				<p>
					<input type="checkbox" id="itelic-online-software" name="itelic[online-software]" <?php checked( true, $data['online-software'] ); ?>>
					<label for="itelic-online-software"><?php _e( "Enable Online Software Tools for this product", Plugin::SLUG ); ?></label>
				</p>
			<?php endif; ?>

			<label for="itelic-update-file"><?php _e( "Update File", Plugin::SLUG ); ?></label>
			<select id="itelic-update-file" name="itelic[update-file]">
				<?php foreach ( $downloads as $download ): ?>
					<option value="<?php echo esc_attr( $download['id'] ); ?>" <?php selected( $data['update-file'], $download['id'] ); ?>>
						<?php echo $download['name']; ?>
					</option>
				<?php endforeach; ?>
			</select>

			<p class="description">
				<?php _e( "Select a file to be used for automatic updates.", Plugin::SLUG ); ?>
				<?php _e( "You shouldn't need to update this after the initial release.", Plugin::SLUG ); ?>
			</p>

			<label for="itelic-version">

				<?php if ( $version_read ): ?>
					<?php _e( "Current Version", Plugin::SLUG ); ?>
				<?php else: ?>
					<?php _e( "Initial Version", Plugin::SLUG ); ?>
				<?php endif; ?>
			</label>
			<input type="text" id="itelic-version" name="itelic[version]" <?php echo $version_read; ?> value="<?php echo esc_attr( $data['version'] ); ?>">

			<p class="description">
				<?php if ( ! $version_read ): ?>
					<?php _e( "Set the initial version of this product.", Plugin::SLUG ); ?>&nbsp;
				<?php else:; ?>
					<?php printf( __( 'Create a new release from the <a href="%s">releases</a> tab.', Plugin::SLUG ),
						add_query_arg( 'view', 'add-new', Dispatch::get_tab_link( 'releases' ) ) ); ?>
				<?php endif; ?>
			</p>

			<label for="itelic-changelog"><?php _e( "Changelog", Plugin::SLUG ); ?></label>
			<button id="view-changelog" class="button"><?php _e( "View Changelog", Plugin::SLUG ); ?></button>
			<p class="description"><?php _e( "View the compiled changelog from the last 10 releases.", Plugin::SLUG ); ?></p>

			<label for="itelic-key-type"><?php _e( "Key Type", Plugin::SLUG ); ?></label>
			<select id="itelic-key-type" name="itelic[key-type]">

				<option value=""><?php _e( "Select a Key Type", Plugin::SLUG ); ?></option>

				<?php foreach ( itelic_get_key_types() as $slug => $type ): ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $data['key-type'], $slug ); ?>>
						<?php echo itelic_get_key_type_name( $slug ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<p class="description"><?php _e( "How should license keys be generated for this product.", Plugin::SLUG ); ?></p>

			<div id="itelic-key-type-settings">
				<?php if ( $data['key-type'] != - 1 ): ?>
					<?php $this->get_key_type_settings( $data['key-type'], isset( $post->ID ) ? $post->ID : 0 ); ?>
				<?php endif; ?>
			</div>

			<?php $controller = $this->get_variants_controller( $post->ID ); ?>

			<?php if ( $controller ): ?>
				<p>
					<input type="checkbox" id="itelic-enable-variant-activations" name="itelic[enabled_variant_activations]"
						<?php checked( true, $data['enabled_variant_activations'] ); ?>>
					<label for="itelic-enable-variant-activations"><?php _e( "Enable Variant Activation limits", Plugin::SLUG ); ?></label>
				</p>
			<?php endif; ?>

			<div class="itelic-activation-limit<?php echo esc_attr( $hidden_simple ); ?>">
				<label for="itelic-limit"><?php _e( "Activation Limit", Plugin::SLUG ); ?></label>
				<input type="number" name="itelic[limit]" id="itelic-limit" min="0" value="<?php echo esc_attr( $data['limit'] ); ?>">
			</div>

			<?php if ( $controller && $controller->post_meta ): ?>
				<?php $hashes = $data['activation_variant']; ?>

				<div class="itelic-variants-activation-limit-table<?php echo esc_attr( $hidden_variants ); ?>">

					<div class="itelic-activation-limit-variant-header-row">
						<div class="itelic-activation-limit-variant-header-cell"><?php _e( "Variant", Plugin::SLUG ); ?></div>
						<div class="itelic-activation-limit-variant-header-cell itelic-activation-limit-variant-input-cell"><?php _e( "Limit", Plugin::SLUG ); ?></div>
					</div>

					<?php foreach ( $controller->post_meta as $hash => $variant ): ?>
						<div class="itelic-activation-limit-variant-row">

							<div class="itelic-activation-limit-variant-cell"><?php echo $variant['combos_title'] ?></div>

							<div class="itelic-activation-limit-variant-cell itelic-activation-limit-variant-input-cell">
								<input class="itelic-activation-limit-variant-input" name="itelic[activation_variant][<?php echo esc_attr( $hash ); ?>]"
								       type="number" min="0" value="<?php echo isset( $hashes[ $hash ] ) ? $hashes[ $hash ] : ''; ?>">
							</div>
						</div>
					<?php endforeach; ?>
				</div>

			<?php endif; ?>

			<p class="description"><?php _e( "How many times can this license be activated. Leave blank for unlimited.", Plugin::SLUG ); ?></p>
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
	 * @return \IT_Exchange_Variants_Addon_Product_Feature_Combos|null
	 */
	protected function get_variants_controller( $product_id ) {

		if ( function_exists( 'it_exchange_variants_addon_get_product_feature_controller' ) ) {
			$controller = it_exchange_variants_addon_get_product_feature_controller( $product_id, 'base-price', array(
				'setting' => 'variants'
			) );
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

		if ( isset( $_GET['post'] ) ) {
			$ID = $_GET['post'];
		} else {
			$ID = false;
		}

		?>

		<div id="itelic-edit-changelog-popup" style="display: none">
			<div class="wrap changelog-wrap">
				<?php echo $ID ? Product::get( $ID )->get_changelog() : ''; ?>
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

		$prev = it_exchange_get_product_feature( $product_id, $this->slug );

		$data = $_POST['itelic'];

		$data['enabled']                     = isset( $data['enabled'] ) ? it_exchange_str_true( $data['enabled'] ) : false;
		$data['enabled_variant_activations'] = isset( $data['enabled_variant_activations'] ) ? it_exchange_str_true( $data['enabled_variant_activations'] ) : false;
		$data['online-software']             = isset( $data['online-software'] ) ? it_exchange_str_true( $data['online-software'] ) : false;

		if ( ! empty( $prev['version'] ) ) {
			unset( $data['version'] );
		}

		$first_release = get_post_meta( $product_id, '_itelic_first_release', true );

		if ( ! $first_release && $data['update-file'] ) {

			$download      = $data['update-file'];
			$download_meta = get_post_meta( $download, '_it-exchange-download-info', true );
			$url           = $download_meta['source'];

			/**
			 * @var \wpdb $wpdb
			 */
			global $wpdb;

			$ID   = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid = %s", $url ) );
			$file = get_post( $ID );

			if ( $file->post_type == 'attachment' ) {

				$product = it_exchange_get_product( $product_id );

				if ( isset( $data['version'] ) ) {
					$version = $data['version'];
				} elseif ( isset( $prev['version'] ) ) {
					$version = $prev['version'];
				} else {
					$version = '';
				}

				$type      = Release::TYPE_MAJOR;
				$status    = get_post_status( $product_id ) == 'publish' ? Release::STATUS_ACTIVE : Release::STATUS_DRAFT;
				$changelog = '<ul><li>' . __( "Initial release.", Plugin::SLUG ) . '</li></ul>';

				if ( $version ) {

					try {
						$release = Release::create( $product, $file, $version, $type, $status, $changelog );

						if ( $release ) {
							update_post_meta( $product_id, '_itelic_first_release', $release->get_pk() );
						}
					}
					catch ( \Exception $e ) {

					}
				}
			}
		}

		it_exchange_update_product_feature( $product_id, $this->slug, $data );
	}

	/**
	 * Activate the initial release when the product is published.
	 *
	 * @since 1.0
	 *
	 * @param string   $new_status
	 * @param string   $old_status
	 * @param \WP_Post $post
	 */
	public function activate_initial_release( $new_status, $old_status, $post ) {

		$first_release = get_post_meta( $post->ID, '_itelic_first_release', true );

		if ( ! $first_release ) {
			return;
		}

		if ( $new_status == 'publish' && $old_status != 'publish' ) {
			$release = itelic_get_release( $first_release );

			if ( $release && $release->get_status() !== Release::STATUS_ACTIVE ) {
				$release->activate();
			}
		}
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

		if ( isset( $options['key-type'] ) ) {
			$prev_values['type'][ $options['key-type'] ] = $new_value;

			$values = $prev_values;
		} else {
			$values = \ITUtility::merge_defaults( $new_value, $prev_values );
		}

		update_post_meta( $product_id, '_it_exchange_itelic_enabled', $values['enabled'] );

		return update_post_meta( $product_id, '_it_exchange_itelic_feature', $values );
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.0
	 *
	 * @param mixed   $existing   the values passed in by the WP Filter API.
	 *                            Ignored here.
	 * @param integer $product_id the WordPress post ID
	 * @param array   $options
	 *
	 * @return string product feature
	 */
	public function get_feature( $existing, $product_id, $options = array() ) {

		$settings = it_exchange_get_option( 'addon_itelic' );

		$defaults = array(
			'enabled'                     => false,
			'online-software'             => $settings['sell-online-software'],
			'limit'                       => '',
			'key-type'                    => '',
			'update-file'                 => '',
			'version'                     => '',
			'enabled_variant_activations' => false,
			'activation_variant'          => array()
		);

		$values   = get_post_meta( $product_id, '_it_exchange_itelic_feature', true );
		$raw_meta = \ITUtility::merge_defaults( $values, $defaults );

		if ( ! function_exists( 'it_exchange_variants_addon_create_inital_presets' ) ) {
			$raw_meta['enabled_variant_activations'] = false;
		}

		if ( ! isset( $options['field'] ) ) { // if we aren't looking for a particular field
			return $raw_meta;
		}

		if ( $options['field'] == 'limit' && isset( $options['for_hash'] ) ) {
			$hash = $options['for_hash'];

			if ( isset( $raw_meta['activation_variant'][ $hash ] ) ) {
				return $raw_meta['activation_variant'][ $hash ];
			} else {

				$atts       = it_exchange_get_variant_combo_attributes_from_hash( $product_id, $hash );
				$alt_hashes = it_exchange_addon_get_selected_variant_alts( $atts['combo'], $product_id );

				foreach ( $alt_hashes as $alt_hash ) {

					if ( isset( $raw_meta['activation_variant'][ $alt_hash ] ) ) {
						return $raw_meta['activation_variant'][ $alt_hash ];
					}
				}

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