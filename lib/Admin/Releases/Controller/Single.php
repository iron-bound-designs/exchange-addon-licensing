<?php
/**
 * Releases single view.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Releases\Controller;

use ITELIC\Admin\Releases\Controller;
use ITELIC\Admin\Releases\View\Single as Single_View;
use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Plugin;
use ITELIC\Release;

/**
 * Class Single
 * @package ITELIC\Admin\Releases\Controller
 */
class Single extends Controller {

	/**
	 * Render the view for this controller.
	 *
	 * @return void
	 */
	public function render() {
		$release = Release::with_id( $_GET['ID'] );

		if ( ! $release ) {
			wp_redirect( Dispatch::get_tab_link( 'releases' ) );

			return;
		}

		$this->enqueue();

		$view = new Single_View( $release );

		$view->begin();
		$view->title();

		$view->tabs( 'releases' );

		$view->render();

		$view->end();
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @since 1.0
	 */
	private function enqueue() {
		wp_enqueue_style( 'itelic-admin-releases-edit' );
		wp_enqueue_script( 'itelic-admin-releases-edit' );
		wp_localize_script( 'itelic-admin-releases-edit', 'ITELIC', array(
			'prevVersion'  => __( "Previous version: %s", Plugin::SLUG ),
			'uploadTitle'  => __( "Choose Software File", Plugin::SLUG ),
			'uploadButton' => __( "Replace File", Plugin::SLUG ),
			'uploadLabel'  => __( "Upload File", Plugin::SLUG ),
			'lessUpgrade'  => __( "Less", Plugin::SLUG ),
			'moreUpgrade'  => __( "More", Plugin::SLUG )
		) );

		wp_enqueue_media();
	}
}