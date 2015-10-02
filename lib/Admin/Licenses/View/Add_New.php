<?php
/**
 * Add new license view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Licenses\View;

use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Admin\Tab\View;
use ITELIC\Plugin;
use ITELIC\Product;

/**
 * Class Add_New
 *
 * @package ITELIC\Admin\Licenses\View
 */
class Add_New extends View {

	/**
	 * @var \ITELIC\Product[]
	 */
	protected $products;

	/**
	 * Constructor.
	 *
	 * @param Product[] $products
	 */
	public function __construct( array $products ) {
		$this->products = $products;
	}

	/**
	 * Render the page.
	 *
	 * @since 1.0
	 */
	public function render() {

		$df = it_exchange_php_date_format_to_jquery_datepicker_format( get_option( 'date_format' ) );

		$options = it_exchange_get_option( 'settings_general' );

		$position  = $options['currency-symbol-position'];
		$decimals  = $options['currency-decimals-separator'];
		$thousands = $options['currency-thousands-separator'];
		$symbol    = it_exchange_get_currency_symbol( $options['default-currency'] );
		?>

		<form method="POST" action="<?php echo esc_attr( add_query_arg( 'view', 'add-new', Dispatch::get_tab_link( 'licenses' ) ) ); ?>">

			<div class="main-editor">

				<ol>

					<li>
						<label for="product"><?php _e( "Select a Product", Plugin::SLUG ); ?></label>

						<div class="product-container">
							<select id="product" name="product">
								<?php foreach ( $this->products as $product ): ?>
									<option value="<?php echo $product->ID; ?>"><?php echo $product->post_title; ?></option>                                <?php endforeach; ?>
							</select>
						</div>
					</li>

					<li>
						<fieldset id="customer-type">

							<label><?php _e( "Select a Customer", Plugin::SLUG ); ?></label>

							<div class="new-customer-container">
								<p>
									<input type="radio" id="new-customer" name="customer-type" value="new">
									<label for="new-customer"><?php _e( "New Customer", Plugin::SLUG ); ?></label>
								</p>
							</div>

							<div class="existing-customer-container">
								<p>
									<input type="radio" id="existing-customer" name="customer-type" value="existing" <?php checked( 'existing', 'existing' ); ?>>
									<label for="existing-customer"><?php _e( "Existing Customer", Plugin::SLUG ); ?></label>
								</p>
							</div>

						</fieldset>

						<fieldset class="new-customer-form hide-if-js">

							<p>
								<label for="username"><?php _e( "Username", Plugin::SLUG ); ?></label>
								<input type="text" id="username" name="username">
							</p>

							<p>
								<label for="email"><?php _e( "Email", Plugin::SLUG ); ?></label>
								<input type="email" id="email" name="email">
							</p>

							<p>
								<label for="first"><?php _e( "First Name", Plugin::SLUG ); ?></label>
								<input type="text" id="first" name="first">
							</p>

							<p>
								<label for="last"><?php _e( "Last Name", Plugin::SLUG ); ?></label>
								<input type="text" id="last" name="last">
							</p>

						</fieldset>

						<fieldset class="existing-customer-form">

							<p>
								<label for="customer" class="screen-reader-text"><?php _e( 'Customer', Plugin::SLUG ); ?></label>
								<select id="customer" name="customer">
									<?php foreach ( get_users() as $user ): ?>
										<option value="<?php echo $user->ID; ?>"><?php echo $user->user_login; ?></option>                                    <?php endforeach; ?>
								</select>
							</p>


						</fieldset>

					</li>

					<li>

						<div class="activations-container">
							<label for="activations"><?php _e( "Activation Limit", Plugin::SLUG ); ?></label>
							<input type="number" id="activations" name="activations" min="0">

							<p class="description"><?php _e( "Leave blank for unlimited activations." ); ?></p>
						</div>
					</li>

					<li>

						<div class="expiration-container">

							<label for="expiration"><?php _e( "Expiration Date", Plugin::SLUG ); ?></label>
							<input type="text" id="expiration" name="expiration" data-format="<?php echo esc_attr( $df ); ?>">

						</div>

					</li>

					<li>

						<div class="key-container">

							<label for="license"><?php _e( 'License Key', Plugin::SLUG ); ?></label>

							<p>
								<a href="javascript:" id="trigger-manual-key">
									<?php _e( "Set the license key manually.", Plugin::SLUG ); ?>
								</a>

								<a href="javascript:" id="trigger-automatic-key" class="hide-if-js">
									<?php _e( "Let Exchange automatically generate a license key for you.", Plugin::SLUG ); ?>
								</a>
							</p>

							<input type="text" name="license" id="license" class="hide-if-js">

						</div>

					</li>

					<li>

						<div class="paid-container">

							<label for="paid"><?php _e( "Amount Paid", Plugin::SLUG ); ?></label>
							<input type="text" name="paid" id="paid"
							       data-symbol="<?php echo $symbol; ?>" data-symbol-position="<?php echo $position; ?>"
							       data-thousands-separator="<?php echo $thousands; ?>"
							       data-decimals-separator="<?php echo $decimals; ?>">

						</div>

					</li>

				</ol>

				<p class="buttons">
					<input type="reset" class="button button-secondary" value="<?php _e( "Clear", Plugin::SLUG ); ?>"> <?php submit_button( __( "Create", Plugin::SLUG ), 'primary', 'submit', false ); ?>
				</p>

			</div>

		</form>

		<?php

	}

	/**
	 * Get the title of this view.
	 *
	 * @return string
	 */
	protected function get_title() {
		return __( "Add New License", Plugin::SLUG );
	}
}