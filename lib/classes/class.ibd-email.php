<?php
/**
 * Class for sending emails.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class IBD_Email
 */
class IBD_Email {

	/**
	 * @var IBD_Email_Template
	 */
	private $template;

	/**
	 * @var WP_User
	 */
	private $to;

	/**
	 * @var string
	 */
	private $override_content = null;

	/**
	 * @var string
	 */
	private $override_subject = null;

	/**
	 * @var array
	 */
	private $headers = array();

	/**
	 * Constructor.
	 *
	 * @param IBD_Email_Template $template
	 * @param WP_User            $to
	 */
	public function __construct( IBD_Email_Template $template, WP_User $to ) {
		$this->template    = $template;
		$this->to          = $to;

		$this->headers[] = 'Content-Type: text/html;';

		$name            = get_option( 'blogname' );
		$email           = get_option( 'admin_email' );
		$this->headers[] = "From: $name <$email>";
	}

	/**
	 * Send this email.
	 *
	 * @return bool
	 */
	public function send() {
		return wp_mail( $this->to->user_email, $this->get_subject(), $this->get_content(), $this->headers );
	}

	/**
	 * Add a BCC email address to this email.
	 *
	 * @param $email_address string
	 */
	public function add_bcc( $email_address ) {
		$this->headers[] = "Bcc: $email_address";
	}

	/**
	 * Set the to for this email.
	 *
	 * @param WP_User $to
	 */
	public function set_to( WP_User $to ) {
		$this->to = $to;
	}

	/**
	 * Override the content for this email.
	 *
	 * @param $content string
	 */
	public function override_content( $content ) {
		$this->override_content = $content;
	}

	/**
	 * Override the subject line for this email.
	 *
	 * @param $subject string
	 */
	public function override_subject( $subject ) {
		$this->override_subject = $subject;
	}

	/**
	 * Get the content for this email.
	 *
	 * If the content has been overridden then use that.
	 *
	 * @return string
	 */
	protected function get_content() {
		if ( $this->override_content == null ) {
			return $this->template->get_content();
		} else {
			$content = $this->override_content;

			/**
			 * Filter the post content.
			 *
			 * @since 0.71
			 *
			 * @param string $content Content of the current post.
			 */
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );

			return $content;
		}
	}

	/**
	 * Get the subject line for this email.
	 *
	 * If the subject has been overridden then use that.
	 *
	 * @return string
	 */
	protected function get_subject() {
		if ( $this->override_subject == null ) {
			return $this->template->get_subject();
		} else {
			$content = $this->override_subject;

			/**
			 * Filter the post content.
			 *
			 * @since 0.71
			 *
			 * @param string $content Content of the current post.
			 */
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );
			$content = strip_tags( $content );

			return $content;
		}
	}

	/**
	 * @return WP_User
	 */
	public function get_to() {
		return $this->to;
	}

	/**
	 * Send an email to multiple contacts.
	 *
	 * @param IBD_Email $email
	 * @param WP_User[]       $tos
	 */
	public static function send_to_many( IBD_Email $email, $tos ) {

		foreach ( $tos as $to ) {
			$email->set_to( $to );
			$email->send();
		}
	}
}