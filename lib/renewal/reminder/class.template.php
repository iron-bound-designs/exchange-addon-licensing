<?php
/**
 * Used for powering the email templates.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Renewal_Reminder_Template
 *
 * @since 1.0
 */
class ITELIC_Renewal_Reminder_Template extends IBD_Email_Template_Post {

	/**
	 * @var ITELIC_Key
	 */
	private $key;

	/**
	 * Constructor.
	 *
	 * @param ITELIC_Renewal_Reminder $reminder
	 * @param ITELIC_Key              $key
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( ITELIC_Renewal_Reminder $reminder, ITELIC_Key $key ) {

		$this->key = $key;

		parent::__construct( $reminder->get_post() );
	}

	/**
	 * Filter the content.
	 *
	 * Here we add our shortcode listener manager as a shortcode.
	 *
	 * @since 1.0
	 *
	 * @param string $raw
	 *
	 * @return string
	 */
	protected function filter_content( $raw ) {

		ITELIC_Renewal_Reminder_Type::register_shortcodes();

		new IBD_Shortcode_Listener_Manager( ITELIC_Renewal_Reminder_Type::SHORTCODE, $this->get_data_sources() );

		return parent::filter_content( $raw );
	}

	/**
	 * Get data sources for the email shortcodes.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	protected function get_data_sources() {
		$sources = array();

		$sources[] = $this->key;
		$sources[] = $this->key->get_customer();
		$sources[] = $this->key->get_product();
		$sources[] = $this->key->get_transaction();
		$sources[] = new ITELIC_Renewal_Discount( $this->key->get_product() );

		return $sources;
	}
}