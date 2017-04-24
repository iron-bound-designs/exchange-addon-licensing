<?php
/**
 * Send notifications via Mandrill
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace IronBound\WP_Notifications\Queue;

use IronBound\WP_Notifications\Contract;
use IronBound\WP_Notifications\Strategy\Null;
use IronBound\WP_Notifications\Strategy\Strategy;

/**
 * Class Mandrill
 * @package IronBound\WP_Notifications\Queue
 */
class Mandrill implements Queue {

	/**
	 * @var \Mandrill
	 */
	private $mandrill;

	/**
	 * @var array
	 */
	private $defaults = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param \Mandrill $mandrill
	 * @param array     $defaults
	 */
	public function __construct( \Mandrill $mandrill, array $defaults = array() ) {
		$this->mandrill = $mandrill;
		$this->defaults = $defaults;
	}

	/**
	 * Process a batch of notifications.
	 *
	 * @since 1.0
	 *
	 * @param Contract[] $notifications
	 * @param Strategy   $strategy This will end up being unused.
	 *
	 * @throws \Exception|\Mandrill_Error|\Mandrill_Exception
	 */
	public function process( array $notifications, Strategy $strategy ) {

		/** @var Contract $one */
		$one = reset( $notifications );

		$subject    = $this->convert_tags( $one->get_subject() );
		$message    = $this->convert_tags( $one->get_message() );
		$merge_vars = $this->prepare_merge_vars( $notifications );
		$tos        = $this->prepare_to( $notifications );

		$args = array(
			'html'                => $message,
			'subject'             => $subject,
			'from_name'           => get_option( 'blogname' ),
			'from_email'          => get_option( 'admin_email' ),
			'to'                  => $tos,
			'auto_text'           => true,
			'merge_vars'          => $merge_vars,
			'preserve_recipients' => false
		);

		$args = wp_parse_args( $args, $this->defaults );

		if ( method_exists( $this->mandrill, 'messages_send' ) ) {
			$this->mandrill->messages_send( $args );
		} else {
			$this->mandrill->messages->send( $args );
		}

		foreach ( $notifications as $notification ) {
			$notification->set_strategy( new Null() )->send();
		}
	}

	/**
	 * Convert template tags from our format to Mandrill's.
	 *
	 * Translates {vars} to *|vars|*
	 *
	 * @since 1.0
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	protected function convert_tags( $content ) {
		return str_replace( array( '{', '}' ), array( '*|', '|*' ), $content );
	}

	/**
	 * Prepare merge vars for the API.
	 *
	 * @since 1.0
	 *
	 * @param Contract[] $notifications
	 *
	 * @return array[]
	 */
	protected function prepare_merge_vars( array $notifications ) {

		$merge_vars = array();

		foreach ( $notifications as $notification ) {
			$merge = array(
				'rcpt' => $notification->get_recipient()->user_email,
				'vars' => array()
			);

			foreach ( $notification->get_tags() as $tag => $val ) {

				$tag = str_replace( array( '{', '}' ), '', $tag );

				$merge['vars'][] = array(
					'name'    => $tag,
					'content' => $val
				);
			}

			$merge_vars[] = $merge;
		}

		return $merge_vars;
	}

	/**
	 * Prepare the to array for the API.
	 *
	 * @since 1.0
	 *
	 * @param Contract[] $notifications
	 *
	 * @return array[]
	 */
	protected function prepare_to( array $notifications ) {
		$tos = array();

		foreach ( $notifications as $notification ) {

			$to = $notification->get_recipient();

			$tos[] = array(
				'email' => $to->user_email,
				'name'  => "{$to->first_name} {$to->last_name}",
				'type'  => 'to'
			);
		}

		return $tos;
	}
}