<?php
/**
 * Base command for the plugin.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Command
 */
class ITELIC_Command extends WP_CLI_Command {

	/**
	 * Get information about the licensing add-on.
	 */
	function info() {

		$settings = it_exchange_get_option( 'addon_itelic' );

		if ( $settings['renewal-discount-type'] == 'percent' ) {
			$discount = $settings['renewal-discount-amount'] . '%';
		} else {
			$discount = it_exchange_format_price( $settings['renewal-discount-amount'] );
		}

		$info = array(
			'version'                     => ITELIC\Plugin::VERSION,
			'online_software_enabled'     => $settings['sell-online-software'] ? 'yes' : 'no',
			'remote_activation_enabled'   => $settings['enable-remote-activation'] ? 'yes' : 'no',
			'remote_deactivation_enabled' => $settings['enable-remote-deactivation'] ? 'yes' : 'no',
			'renewal_discounts_enabled'   => $settings['enable-renewal-discounts'] ? 'yes' : 'no',
			'renewal_discount'            => $discount,
			'renewal_discount_expiry'     => sprintf( "%d days", $settings['renewal-discount-expiry'] )
		);

		$args = array(
			'fields' => array_keys( $info )
		);

		$formatter = new \WP_CLI\Formatter( $args );
		$formatter->display_item( $info );
	}
}

WP_CLI::add_command( 'itelic', 'ITELIC_Command' );