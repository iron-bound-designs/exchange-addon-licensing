<?php
/**
 * Add-on Settings Page
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Settings callback specified in the register_addon function.
 *
 * @since 1.0
 */
function itelic_addon_settings() {
	$settings = new ITELIC_Settings();
	$settings->print_settings_page();
}

/**
 * List default values for the settings options.
 *
 * @since 1.0
 *
 * @param array $defaults
 *
 * @return array
 */
function itelic_addon_settings_defaults( $defaults ) {

	$defaults['enable-renewal-discounts'] = false;
	$defaults['renewal-discount-type']    = 'percent';
	$defaults['renewal-discount-amount']  = '';
	$defaults['renewal-discount-expiry']  = '';

	return $defaults;
}

add_filter( 'it_storage_get_defaults_exchange_addon_itelic', 'itelic_addon_settings_defaults' );

/**
 * Class ITELIC_Settings
 */
class ITELIC_Settings {

	/**
	 * @var string $status_message will be displayed if not empty
	 *
	 * @since 1.0
	 */
	private $status_message;

	/**
	 * @var string $error_message will be displayed if not empty
	 *
	 * @since 1.0
	 */
	private $error_message;

	/**
	 * @var array
	 *
	 * @since 1.0
	 */
	private $form_values;

	/**
	 * Class constructor
	 *
	 * Sets up the class.
	 *
	 * @since 1.0
	 */
	function __construct() {
		$page  = empty( $_GET['page'] ) ? false : $_GET['page'];
		$addon = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

		if ( ! empty( $_POST ) && is_admin() && 'it-exchange-addons' == $page && 'licensing' == $addon ) {
			add_action( 'it_exchange_save_add_on_settings_itelic', array( $this, 'save_settings' ) );
			do_action( 'it_exchange_save_add_on_settings_itelic' );
		}
	}

	/**
	 * Prints settings page
	 *
	 * @since 1.0
	 */
	function print_settings_page() {
		$settings          = it_exchange_get_option( 'addon_itelic', true );
		$this->form_values = empty( $this->error_message ) ? $settings : ITForm::get_post_data();

		$form_options = array(
			'id'     => 'it-exchange-add-on-itelic-settings',
			'action' => 'admin.php?page=it-exchange-addons&add-on-settings=licensing',
		);

		$form = new ITForm( $this->form_values, array( 'prefix' => 'it-exchange-add-on-itelic' ) );

		if ( ! empty ( $this->status_message ) ) {
			ITUtility::show_status_message( $this->status_message );
		}
		if ( ! empty( $this->error_message ) ) {
			ITUtility::show_error_message( $this->error_message );
		}
		?>
		<div class="wrap">
			<h2><?php _e( 'Licensing Settings', ITELIC::SLUG ); ?></h2>

			<?php do_action( 'it_exchange_itelic_settings_page_top' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>
			<?php $form->start_form( $form_options, 'it-exchange-itelic-settings' ); ?>
			<?php do_action( 'it_exchange_itelic_settings_form_top', $form ); ?>
			<?php $this->get_form_table( $form, $this->form_values ); ?>
			<?php do_action( 'it_exchange_itelic_settings_form_bottom', $form ); ?>

			<p class="submit">
				<?php $form->add_submit( 'submit', array(
					'value' => __( 'Save Changes', ITELIC::SLUG ),
					'class' => 'button button-primary button-large'
				) ); ?>
			</p>

			<?php $form->end_form(); ?>
			<?php $this->inline_scripts(); ?>
			<?php do_action( 'it_exchange_itelic_settings_page_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</div>
	<?php
	}

	/**
	 * Render the settings table
	 *
	 * @since 1.0
	 *
	 * @param ITForm $form
	 * @param array  $settings
	 */
	function get_form_table( $form, $settings = array() ) {
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $key => $var ) {
				$form->set_option( $key, $var );
			}
		}

		$erd_class = $form->get_option( 'enable-renewal-discounts' ) ? '' : 'hide-if-js';
		?>

		<div class="it-exchange-addon-settings it-exchange-itelic-addon-settings">

			<h3><?php _e( "Renewal Discounts", ITELIC::SLUG ); ?></h3>

			<div class="enable-renewal-discounts-container">

				<?php $form->add_check_box( 'enable-renewal-discounts' ); ?>
				<label for="enable-renewal-discounts"><?php _e( "Enable Global Renewal Discounts?", ITELIC::SLUG ); ?></label>

				<p class="description"><?php _e( "Don't worry, this can be overwritten on a per-product basis.", ITELIC::SLUG ); ?></p>
			</div>

			<div class="renewal-discount-type-container <?php echo esc_attr( $erd_class ); ?>">
				<label for="renewal-discount-type"><?php _e( "Discount Type", ITELIC::SLUG ); ?></label>

				<?php $form->add_drop_down( 'renewal-discount-type', array(
					ITELIC_Renewal_Discount::TYPE_FLAT    => __( 'Flat', ITELIC::SLUG ),
					ITELIC_Renewal_Discount::TYPE_PERCENT => __( "Percent", ITELIC::SLUG )
				) ); ?>
			</div>

			<div class="renewal-discount-amount-container <?php echo esc_attr( $erd_class ); ?>">
				<label for="renewal-discount-amount"><?php _e( "Discount Amount", ITELIC::SLUG ); ?></label>

				<?php $form->add_text_box( 'renewal-discount-amount' ); ?>
			</div>

			<div class="renewal-discount-expiry-container <?php echo esc_attr( $erd_class ); ?>">
				<label for="renewal-discount-expiry"><?php _e( "Valid Until", ITELIC::SLUG ); ?></label>

				<?php $form->add_text_box( 'renewal-discount-expiry' ); ?>

				<p class="description"><?php _e( "how many days after the key expires should the renewal discount be offered.", ITELIC::SLUG ); ?></p>
			</div>

		</div>
	<?php
	}

	/**
	 * Render inline scripts.
	 *
	 * @since 1.0
	 */
	function inline_scripts() {
		wp_enqueue_script( 'jquery' );
		?>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {

				$("#enable-renewal-discounts").change(function (e) {
					var type_container = $(".renewal-discount-type-container");
					var amount_container = $(".renewal-discount-amount-container");
					var expiry_container = $(".renewal-discount-expiry-container");

					if ($(this).is(":checked")) {
						type_container.removeClass('hide-if-js');
						amount_container.removeClass('hide-if-js');
						expiry_container.removeClass('hide-if-js');
					} else {
						type_container.addClass('hide-if-js');
						amount_container.addClass('hide-if-js');
						expiry_container.addClass('hide-if-js');
					}
				});
			});
		</script>

	<?php
	}

	/**
	 * Save settings.
	 *
	 * @since 1.0
	 */
	function save_settings() {
		$defaults = it_exchange_get_option( 'addon_itelic' );

		$new_values = wp_parse_args( ITForm::get_post_data(), $defaults );
		// Check nonce
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-itelic-settings' ) ) {
			$this->error_message = __( 'Error. Please try again', ITELIC::SLUG );

			return;
		}

		/**
		 * Filter the settings errors before saving.
		 *
		 * @since 1.0
		 *
		 * @param string[] $errors     Errors
		 * @param array    $new_values Mixed
		 */
		$errors = apply_filters( 'it_exchange_add_on_itelic_validate_settings', $this->get_form_errors( $new_values ), $new_values );

		if ( ! $errors && it_exchange_save_option( 'addon_itelic', $new_values ) ) {
			$this->status_message = __( 'Settings saved.', ITELIC::SLUG );
		} else if ( $errors ) {
			$errors              = implode( '<br />', $errors );
			$this->error_message = $errors;
		} else {
			$this->error_message = __( 'Settings not saved.', ITELIC::SLUG );
		}
	}

	/**
	 * Validates for values.
	 *
	 * @since 1.0
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	public function get_form_errors( $values ) {
		$errors = array();

		return $errors;
	}
}