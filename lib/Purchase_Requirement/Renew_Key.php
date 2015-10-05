<?php
/**
 * Key renewal purchase requirement.
 *
 * @author Iron Bound Designs
 * @since  10
 */

namespace ITELIC\Purchase_Requirement;

use ITELIC\Plugin;
use ITELIC\Renewal\Discount;

/**
 * Class Renew_Key
 * @package ITELIC\Purchase_Requirement
 */
class Renew_Key extends Base {

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param string   $slug
	 * @param array    $args
	 * @param \Closure $complete Becomes the requirement met function. ($this) is passed as a parameter.
	 */
	public function __construct( $slug, array $args, \Closure $complete ) {
		parent::__construct( $slug, $args, $complete );

		add_action( 'it_exchange_super_widget_product_end_purchase_options_element', array(
			$this,
			'add_renew_button_to_sw'
		) );
		add_action( 'it_exchange_processing_super_widget_ajax_renew_key', array( $this, 'enter_renewal_process_sw' ) );
		add_action( 'wp_ajax_itelic_renew_product_purchase_requirement', array(
			$this,
			'process_purchase_requirement_renewal_ajax'
		) );
		add_action( 'init', array( $this, 'process_purchase_requirement_renewal_checkout' ) );

		add_filter( 'it_exchange_set_inital_sw_state', array( $this, 'set_initial_state_to_login' ) );
		add_action( 'it_exchange_super_widget_login_end_form', array( $this, 'add_renewal_key_to_login_form' ) );
		add_action( 'wp_login', array( $this, 'add_product_to_cart_on_login' ), 10, 2 );

		add_filter( 'it_exchange_theme_api_cart-item_title', array( $this, 'modify_cart_item_title' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'remove_variant_options' ), 20 );
		add_filter( 'it_exchange_multi_item_product_allowed', array( $this, 'disable_multi_item_product' ), 10, 2 );
		add_filter( 'it_exchange_get_cart_product_base_price', array( $this, 'apply_renewal_discount' ), 20, 3 );

		add_filter( 'it_exchange_generate_transaction_object_products', array(
			$this,
			'save_renewal_info_to_transaction_object'
		), 10, 3 );

		add_filter( 'it_exchange_transaction_object', array( $this, 'clear_renewal_session_on_purchase' ) );
		add_action( 'it_exchange_delete_cart_product', array(
			$this,
			'remove_renewal_info_on_cart_product_removal'
		), 10, 2 );
		add_action( 'it_exchange_empty_shopping_cart', array( $this, 'clear_cache_on_empty_cart' ) );
	}

	/**
	 * Add the renew button to the super widget.
	 *
	 * @since 1.0
	 */
	public function add_renew_button_to_sw() {

		$product_id = \ITELIC\get_current_product_id();

		if ( ! $product_id ) {
			return;
		}

		$product = itelic_get_product( $product_id) ;

		if ( ! $product->has_feature( 'licensing' ) || ! $product->has_feature( 'recurring-payments' ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			$keys = itelic_get_keys( array(
				'product'  => $product_id,
				'customer' => it_exchange_get_current_customer_id()
			) );

			if ( empty( $keys ) ) {
				return;
			}
		}
		?>

		<form method="POST" class="it-exchange-sw-purchase-options it-exchange-sw-itelic-renew">
			<input type="hidden" name="it-exchange-renew-product" value="<?php echo esc_attr( $product_id ); ?>">
			<?php wp_nonce_field( 'itelic-renew-' . $product_id, 'nonce' ); ?>
			<input type="submit" value="<?php esc_attr_e( "Renew", Plugin::SLUG ); ?>" class="itelic-renew-button" style="width:100%;">
		</form>

		<?php
	}

	/**
	 * Enter the renewal process when the renew button is pressed on the super widget.
	 *
	 * @since 1.0
	 */
	public function enter_renewal_process_sw() {

		if ( ! isset( $_GET['sw-product'] ) || ! isset( $_GET['nonce'] ) ) {
			return;
		}

		$product = $_GET['sw-product'];

		if ( ! wp_verify_nonce( $_GET['nonce'], "itelic-renew-$product" ) ) {
			it_exchange_add_message( 'error', __( "Something went wrong. Please refresh and try again.", Plugin::SLUG ) );

			return;
		}

		it_exchange_add_product_to_shopping_cart( $product, 1 );

		$keys = itelic_get_keys( array(
			'product'  => $product,
			'customer' => it_exchange_get_current_customer_id()
		) );

		if ( count( $keys ) == 1 ) {
			$key = reset( $keys );
		} else {
			$key = null;
		}

		$this->update_cache_data( array(
			"p$product" => is_null( $key ) ? null : $key->get_key()
		) );

		die( 1 );
	}

	/**
	 * Process the AJAX call from the renew product purchase requirement.
	 *
	 * @since 1.0
	 */
	public function process_purchase_requirement_renewal_ajax() {

		if ( ! isset( $_POST['nonce'] ) || ! isset( $_POST['key'] ) || ! isset( $_POST['product'] ) || ! isset( $_POST['renew'] ) ) {
			wp_send_json_error( array(
				'message' => __( "Invalid request format.", Plugin::SLUG )
			) );
		}

		$nonce   = $_POST['nonce'];
		$key     = itelic_get_key( $_POST['key'] );
		$product = itelic_get_product( $_POST['product'] );
		$renew   = (bool) $_POST['renew'];

		if ( ! wp_verify_nonce( $nonce, 'itelic_renew_product_sw' ) ) {
			wp_send_json_error( array(
				'message' => __( "Sorry, this request has expired. Please refresh and try again.", Plugin::SLUG )
			) );
		}

		if ( $key->get_product()->ID != $product->ID || it_exchange_get_current_customer_id() != $key->get_customer()->id ) {
			wp_send_json_error( array(
				'message' => __( "Invalid license key selected.", Plugin::SLUG )
			) );
		}

		if ( $renew === true ) {
			$this->update_cache_data( array(
				"p{$product->ID}" => $key->get_key()
			) );
		} else {
			$this->remove_cache_data( "p{$product->ID}" );
		}

		wp_send_json_success();
	}

	/**
	 * Process the renewal purchase requirement from the checkout screen.
	 *
	 * @since 1.0
	 */
	public function process_purchase_requirement_renewal_checkout() {

		if ( ! isset( $_POST['itelic_renew_keys_checkout'] ) || ! isset( $_POST['_wpnonce'] ) || empty( $_POST['itelic_key'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'itelic-renew-keys-checkout' ) ) {
			it_exchange_add_message( 'error', __( "Sorry this request has expired. Please refresh and try again.", Plugin::SLUG ) );

			return;
		}

		$session = $this->get_cache_data();
		$keys    = $_POST['itelic_key'];

		foreach ( $session as $product => $key ) {

			$product = str_replace( 'p', '', $product );

			if ( $key === null ) {
				if ( isset( $keys[ $product ] ) ) {
					$session["p$product"] = $keys[ $product ];
				}
			}
		}

		$this->set_cache_data( $session );
	}

	/**
	 * When a user is renewing their key from a renewal URL, set the initial state to login.
	 *
	 * @since 1.0
	 *
	 * @param string $state
	 *
	 * @return string
	 */
	public function set_initial_state_to_login( $state ) {

		if ( isset( $_GET['renew_key'] ) ) {

			if ( is_user_logged_in() ) {

				$key = itelic_get_key( $_GET['renew_key'] );

				if ( $key && $key->get_customer()->id == it_exchange_get_current_customer_id() ) {

					it_exchange_add_product_to_shopping_cart( $key->get_product()->ID );

					$this->update_cache_data( array(
						"p{$key->get_product()->ID}" => $key->get_key()
					) );

					$state = 'checkout';
				}

			} else {
				$state = 'login';
			}
		}

		return $state;
	}

	/**
	 * Add the desired renewal key to the login form as a hidden input.
	 */
	public function add_renewal_key_to_login_form() {

		if ( isset( $_GET['renew_key'] ) ) {
			echo '<input type="hidden" name="renew_key" value="' . $_GET['renew_key'] . '">';
		}
	}

	/**
	 * When a user is renewing their key from a renewal URL, after they login,
	 * immediately add the product to the cart.
	 *
	 * @since 1.0
	 *
	 * @param string   $username
	 * @param \WP_User $user
	 */
	public function add_product_to_cart_on_login( $username, $user ) {

		if ( ! isset( $_POST['renew_key'] ) ) {
			return;
		}

		$key = itelic_get_key( $_POST['renew_key'] );

		if ( ! $key ) {
			return;
		}

		if ( $key->get_customer()->id != $user->ID ) {
			return;
		}

		it_exchange_add_product_to_shopping_cart( $key->get_product()->ID );

		$this->update_cache_data( array(
			"p{$key->get_product()->ID}" => $key->get_key()
		) );
	}

	/**
	 * Modify the cart item title to specify that it is a renewal.
	 *
	 * @since 1.0
	 *
	 * @param string $title
	 *
	 * @return string
	 */
	public function modify_cart_item_title( $title ) {
		$product = $GLOBALS['it_exchange']['cart-item']['product_id'];
		$session = $this->get_cache_data();

		if ( isset( $session["p$product"] ) && ! is_null( $session["p$product"] ) ) {
			$title .= __( " – Renewal", Plugin::SLUG );
		}

		return $title;
	}

	/**
	 * Remove the variant options from the page.
	 *
	 * @since 1.0
	 */
	public function remove_variant_options() {

		$product = \ITELIC\get_current_product_id();

		$session = $this->get_cache_data();

		if ( ! isset( $session[ "p" . $product ] ) || $session[ "p" . $product ] === null ) {
			return;
		}

		add_filter( 'it_exchange_multi_item_product_allowed', function ( $allowed, $product_id ) use ( $product ) {

			if ( $product_id == $product ) {
				$allowed = false;
			}

			return $allowed;

		}, 10, 2 );

		wp_dequeue_script( 'it-exchange-variants-addon-frontend-product' );
		wp_dequeue_style( 'it-exchange-variants-addon-frontend-product' );

		remove_filter( 'wp_footer', 'it_exchange_variants_addon_print_product_variant_js' );
		remove_filter( 'it_exchange_get_content_product_product_info_loop_elements', 'it_exchange_variants_addon_register_template_loop' );
	}

	/**
	 * Disable the multi-item product if a key is being renewed.
	 *
	 * @param bool $allowed
	 * @param int  $product_id
	 *
	 * @return bool
	 */
	function disable_multi_item_product( $allowed, $product_id ) {

		$products = it_exchange_get_cart_products();

		foreach ( $products as $product ) {

			if ( $product['product_id'] == $product_id ) {

				$session = $this->get_cache_data();

				if ( isset( $session[ "p" . $product_id ] ) && $session[ "p" . $product_id ] !== null ) {
					$allowed = false;
				}
			}
		}

		return $allowed;
	}

	/**
	 * Apply the renewal discount.
	 *
	 * @since 1.0
	 *
	 * @param string|float $db_base_price
	 * @param array        $product
	 * @param bool         $format
	 *
	 * @return string|float
	 */
	public function apply_renewal_discount( $db_base_price, $product, $format ) {

		try {
			$product = itelic_get_product( $product['product_id'] );
		} catch  ( \Exception $e ) {
			return $db_base_price;
		}

		if ( ! $product->get_feature( 'licensing' ) ) {
			return $db_base_price;
		}

		$session = $this->get_cache_data();

		if ( ! isset( $session[ "p" . $product['product_id'] ] ) || $session[ "p" . $product['product_id'] ] === null ) {
			return $db_base_price;
		}

		$key = $session[ "p" . $product['product_id'] ];
		$key = itelic_get_key( $key );

		$discount = new Discount( $key );

		return $discount->get_discount_price( $format );
	}

	/**
	 * Save our renewal info with the transaction object.
	 *
	 * @since 1.0
	 *
	 * @param $products array
	 * @param $key      string
	 * @param $product  array
	 *
	 * @return object
	 */
	public function save_renewal_info_to_transaction_object( $products, $key, $product ) {

		$session = $this->get_cache_data();

		foreach ( $session as $renewed_product => $license_key ) {

			$renewed_product = str_replace( 'p', '', $renewed_product );

			if ( $renewed_product == $product['product_id'] ) {
				$products[ $key ]['renewed_key'] = $license_key;
			}
		}

		return $products;
	}

	/**
	 * Clear our renewal info data
	 * after the transaction object has been generated
	 *
	 * @since 1.0
	 *
	 * @param $transaction_object object
	 *
	 * @return object
	 */
	public function clear_renewal_session_on_purchase( $transaction_object ) {
		$this->clear_cache_data();
		$this->persist();

		return $transaction_object;
	}

	/**
	 * Remove the renewal info, when a key is removed from the cart.
	 *
	 * @since 1.0
	 *
	 * @param int   $cart_product_id
	 * @param array $products
	 */
	public function remove_renewal_info_on_cart_product_removal( $cart_product_id, $products ) {
		$this->remove_cache_data( "p$cart_product_id" );
		$this->persist();
	}

	/**
	 * When the cart is emptied, clear the session data.
	 *
	 * @since 1.0
	 */
	public function clear_cache_on_empty_cart() {

		$this->clear_cache_data();
		$this->persist();
	}
}