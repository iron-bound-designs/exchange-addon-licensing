<?php
/**
 * Email template WP_Post data source.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class IBD_Email_Template_Post
 */
class IBD_Email_Template_Post implements IBD_Email_Template {

	/**
	 * @var WP_Post
	 */
	private $post;

	/**
	 * Constructor.
	 *
	 * @param WP_Post $reminder
	 */
	public function __construct( WP_Post $reminder ) {
		$this->post = $reminder;
	}

	/**
	 * Get the subject line of the template.
	 *
	 * @param bool $raw
	 *
	 * @return string
	 */
	public function get_subject( $raw = false ) {
		$content = $this->post->post_title;

		if ( $raw ) {
			return $content;
		}

		return $this->filter_subject( $content );
	}

	/**
	 * Filter the subject. Preparing it for sending.
	 *
	 * @param $raw
	 *
	 * @return string
	 */
	protected function filter_subject( $raw ) {

		$raw = apply_filters( 'the_content', $raw );

		$raw = str_replace( ']]>', ']]&gt;', $raw );
		$raw = strip_tags( $raw );

		return $raw;
	}

	/**
	 * Get the email template content.
	 *
	 * @param bool $raw
	 *
	 * @return string
	 */
	public function get_content( $raw = false ) {
		$content = $this->post->post_content;

		if ( $raw ) {
			return $content;
		}

		return $this->filter_content( $content );
	}

	/**
	 * Filter the content. Preparing it for sending.
	 *
	 * @param string $raw
	 *
	 * @return string
	 */
	protected function filter_content( $raw ) {
		$raw = apply_filters( 'the_content', $raw );
		$raw = str_replace( ']]>', ']]&gt;', $raw );

		return $raw;
	}
}