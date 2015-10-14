<?php
/**
 * Help manager.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     AGPL
 */

namespace ITELIC\Admin\Help;

use ITELIC\Plugin;
use ITELIC\Renewal\Reminder\CPT;

/**
 * Class Help
 *
 * @package ITELIC\Admin\Help
 */
class Help {

	/**
	 * @var array
	 */
	private $tabs;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$fn = array( $this, 'register' );

		add_action( 'load-exchange_page_it-exchange-licensing', $fn );

		add_action( 'load-edit.php', function () use ( $fn ) {
			$screen = get_current_screen();

			if ( $screen->post_type == CPT::TYPE ) {
				call_user_func( $fn );
			}
		} );
	}

	/**
	 * Add a help tab.
	 *
	 * @param string          $title
	 * @param string|callable $content
	 * @param \Closure        $show
	 */
	public function add( $title, $content, \Closure $show ) {
		$this->tabs[] = array(
			'title'   => $title,
			'content' => is_string( $content ) ? $content : $content(),
			'show'    => $show
		);
	}

	/**
	 * Register help tabs.
	 *
	 * @since 1.0
	 */
	public function register() {

		$screen = get_current_screen();

		$screen->set_help_sidebar( $this->help_sidebar() );

		foreach ( $this->tabs as $tab ) {

			if ( $tab['show']() ) {
				$screen->add_help_tab( array(
					'id'       => sanitize_title( $tab['title'] ),
					'title'    => $tab['title'],
					'content'  => $tab['content'],
					'callback' => function () use ( $tab ) {

					}
				) );
			}
		}
	}

	/**
	 * Help sidebar.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	protected function help_sidebar() {

		$html = '<h4>' . __( "For more information:", Plugin::SLUG ) . '</h4>';

		$html .= '<p>' . sprintf( '<a href="%s">%s</a>', 'https://ironbounddesigns.com/contact?reason=support',
			__( "Talk to a human", Plugin::SLUG ) ) . '</p>';

		$html .= '<p>' . sprintf( '<a href="%s">%s</a>', 'https://ironbounddesigns.zendesk.com/hc/en-us',
				__( "View the documentation", Plugin::SLUG ) ) . '</p>';

		return $html;
	}
}