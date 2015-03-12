<?php
/**
 * Email Template
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Interface IBD_Email_Template
 */
interface IBD_Email_Template {

	/**
	 * Get the subject line of the template.
	 *
	 * @param bool $raw
	 *
	 * @return string
	 */
	public function get_subject( $raw = false );

	/**
	 * Get the email template content.
	 *
	 * @param bool $raw
	 *
	 * @return string
	 */
	public function get_content( $raw = false );
}