<?php
/**
 * Register our help tabs.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     AGPL
 */

namespace ITELIC\Admin\Help;

use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Plugin;

add_action( 'init', function () {

	$help = new Help();

	$help->add( __( "Overview", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "All of the license keys your customers have purchased, as well as keys generated manually or via WP CLI, appear on this screen. License keys are what give your customers access to automatic updates. You can use the Screen Options tab to customize the display of this screen.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "You can narrow the list by status using the text links at the top of the screen. You can also filter the list to only show keys for a certain product. Only products that have licensing enabled will show up on this list.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "The search box lets you look for all licenses associated with a certain customer. You can search by their username, display name, or email address. Searching with the full license key will redirect you to the license details view.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'licenses' ) && \ITELIC\Admin\Licenses\Dispatch::is_current_view( 'list' );
	} );

	$help->add( __( "Available Actions", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "To view more details about the license key, click the <em>View</em> link.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "To delete a license key, click the <em>Delete</em> link that appears when hovering over a license key, or check the bulk actions checkbox and select the <em>Delete</em> bulk action.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "By clicking the <em>Extend</em> link you can increase the key’s expiration date. For example, if after purchasing a product a key is valid for one year, then clicking the <em>Extend</em> link will increase the expiration date by an additional year.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "Using the <em>Increase</em> and <em>Decrease</em> links you can change the maximum number of activations available to the key.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'licenses' ) && \ITELIC\Admin\Licenses\Dispatch::is_current_view( 'list' );
	} );
} );