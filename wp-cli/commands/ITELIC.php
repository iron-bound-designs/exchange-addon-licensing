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
	public function info() {

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

	/**
	 * Generate customers.
	 *
	 * ## Options
	 *
	 * [--count=<count>]
	 * : How many customers to generate. Default: 100
	 *
	 * [--billing]
	 * : Include billing addresses.
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand generate-customers
	 */
	public function generate_customers( $args, $assoc_args ) {

		$count = \WP_CLI\Utils\get_flag_value( $assoc_args, 'count', 100 );

		$notify = \WP_CLI\Utils\make_progress_bar( "Generating customers", $count );

		for ( $i = 0; $i < $count; $i ++ ) {
			$this->generate_customer( $assoc_args );

			$notify->tick();
		}

		$notify->finish();
	}

	/**
	 * Generate a customer.
	 *
	 * ## Options
	 *
	 * [--billing]
	 * : Generate billing addresses
	 *
	 * @param array $args
	 */
	protected function generate_customer( $args ) {

		$faker = \Faker\Factory::create();

		$first = $faker->firstName;
		$last  = $faker->lastName;

		$ID = wp_insert_user( array(
			'user_login'      => "$first $last",
			'first_name'      => $first,
			'last_name'       => $last,
			'user_email'      => $faker->safeEmail,
			'user_registered' => $faker->dateTimeBetween( '-2 years' )->format( 'Y-m-d H:i:s' ),
			'user_url'        => $faker->domainName,
			'user_pass'       => $faker->password
		) );

		if ( is_wp_error( $ID ) ) {
			return;
		}

		if ( \WP_CLI\Utils\get_flag_value( $args, 'billing', false ) ) {
			$billing = array(
				'first-name'   => $first,
				'last-name'    => $last,
				'company-name' => $faker->company,
				'address1'     => $faker->streetAddress,
				'address2'     => rand( 0, 1 ) ? $faker->secondaryAddress : '',
				'city'         => $faker->city,
				'state'        => $faker->stateAbbr,
				'zip'          => $faker->postcode,
				'country'      => 'US',
				'email'        => $faker->companyEmail,
				'phone'        => $faker->phoneNumber
			);

			update_user_meta( $ID, 'it-exchange-billing-address', $billing );
		}
	}

}

WP_CLI::add_command( 'itelic', 'ITELIC_Command' );