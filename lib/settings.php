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

		if ( ! empty( $_POST ) && is_admin() && 'it-exchange-addons' == $page && 'commissions' == $addon ) {
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
			'action' => 'admin.php?page=it-exchange-addons&add-on-settings=commissions',
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
		?>

		<div class="it-exchange-addon-settings it-exchange-itelic-addon-settings">
		</div>
	<?php
	}

	/**
	 * Render inline scripts.
	 *
	 * @since 1.0
	 */
	function inline_scripts() {

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

		if ( $defaults['pay-schedule-interval'] != $new_values['pay-schedule-interval'] ) {
			update_option( 'itelic_last_scheduled_pay_out', time() );
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