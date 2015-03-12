<?php
/**
 * Renewal reminder object.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Renewal_Reminder
 */
class ITELIC_Renewal_Reminder {

	/**
	 * For emails sent before expiry.
	 */
	const TYPE_BEFORE = 'before';

	/**
	 * For emails sent after expiry.
	 */
	const TYPE_AFTER = 'after';

	/**
	 * @var WP_Post
	 */
	private $post;

	/**
	 * @var int
	 */
	private $days;

	/**
	 * @var string
	 */
	private $boa;

	/**
	 * Constructor.
	 *
	 * @param WP_Post $post
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( WP_Post $post ) {

		if ( $post->post_type !== ITELIC_Renewal_Reminder_Type::TYPE ) {
			throw new InvalidArgumentException( __( "Invalid post type for post.", ITELIC::SLUG ) );
		}

		$this->days = absint( get_post_meta( $post->ID, '_itelic_renewal_reminder_days', true ) );

		if ( in_array( $boa = get_post_meta( $post->ID, '_itelic_renewal_reminder_boa', true ), array(
			self::TYPE_BEFORE,
			self::TYPE_AFTER
		) ) ) {
			$this->boa = $boa;
		} else {
			throw new InvalidArgumentException( __( "Invalid value for before or after.", ITELIC::SLUG ) );
		}

		$this->post = $post;
	}

	/**
	 * Convert days to a date interval to the expiry date.
	 *
	 * So, if a expiry date is of TYPE_AFTER, the invert flag will be set to true.
	 *
	 * @return DateInterval
	 */
	public function get_interval() {

		$spec = 'P' . $this->get_days() . 'D';

		$interval = new DateInterval( $spec );

		if ( $this->get_boa() == self::TYPE_AFTER ) {
			$interval->invert = true;
		}

		return $interval;
	}

	/**
	 * Get whether or not this email is sent before or after.
	 *
	 * @since 1.0
	 *
	 * @return string ( TYPE_BEFORE|TYPE_AFTER )
	 */
	public function get_boa() {
		return $this->boa;
	}

	/**
	 * Get the days to/after expiry.
	 *
	 * @since 1.0
	 *
	 * @return int 0 <= days < INF
	 */
	public function get_days() {
		return $this->days;
	}

	/**
	 * Get the underlying post object.
	 *
	 * @since 1.0
	 *
	 * @return WP_Post
	 */
	public function get_post() {
		return $this->post;
	}
}