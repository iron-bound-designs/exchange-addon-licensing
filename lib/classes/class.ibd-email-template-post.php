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
	 * @param WP_Post $post
	 */
	public function __construct( WP_Post $post ) {
		$this->post = $post;
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