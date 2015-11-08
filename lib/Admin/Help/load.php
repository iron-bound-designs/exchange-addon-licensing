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
use ITELIC\Renewal\Reminder\CPT;

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

		$html .= '<p>';
		$html .= sprintf( __( "If you have the <a href='%s' target='_blank'>Manual Purchases</a> add-on enabled, you can manually create a new license key using the <em>Add New</em> button.", Plugin::SLUG ), 'https://ithemes.com/purchase/manual-purchases-add-on/' );
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

	$help->add( __( "Overview", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "The <em>Manage License</em> screen displays detailed information about a license key. Across the top of the screen is the customer’s name and the title of the purchased product. Below that is the full license key. In the top right hand corner, the key’s status is represented by a color. Hovering over the color reveals the textual description of the status.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "A license can either be <em>active</em>, <em>disabled</em> or <em>expired</em>. A disabled license is restricted from being used for automatic updates. A license is automatically set to disabled when a transaction is refunded or voided. When the expiration date is passed, the key’s status will be set to expired.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "The expiration date of the license is listed in the <em>Expires</em> section. If this is a lifetime license, the expiration date will show as <em>Never</em>. Next, a link to the transaction that generated this key is displayed. Followed by the max activations this key is allowed.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "All of this license key’s activations are displayed in a table in the <em>Activations</em> section. This list includes both the active and deactivated installs. The activation will show the date it was activated, and if applicable, its deactivation date. Lastly, the currently installed version is displayed. This value is updated whenever the software contacts the API.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "You can manually create an activation record by entering the location URL and clicking the <em>Activate</em> button.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "Lastly, if the license key has been renewed at least once, the renewal history will be displayed: a list of renewal dates and links to the renewal transactions.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'licenses' ) && \ITELIC\Admin\Licenses\Dispatch::is_current_view( 'single' );
	} );

	$help->add( __( "Editing", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "A license key’s status can be edited by hovering over the color in the top right corner, and clicking on the status label. This will prompt you to select the new status. When this new value is selected, the value will automatically be saved.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "To edit the expiration date of a license, click on the expiration date. This will generate a date picker to select the new expiration date. To save this value, press the <em>enter</em> or <em>return</em> key on your keyboard. To cancel the changes, press the <em>escape</em> key.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "The max activations available to a license can be changed by clicking on the activation limit number. To give the license unlimited activation, leave the input empty. Again to save, press the <em>enter</em> or <em>return</em> key on your keyboard.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "You can manually deactivate an activation record by clicking on the <em>deactivate</em> link. If desired, the <em>x</em> can be pressed to completely remove the activation record from the database.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'licenses' ) && \ITELIC\Admin\Licenses\Dispatch::is_current_view( 'single' );
	} );

	$help->add( __( "Overview", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "Licenses can be manually created by using the <em>Add New License</em> form. This will create a <em>Manual Purchases</em> transaction and generate the new license key.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "To begin a product should be selected. Next the customer should be chosen. This can either be an existing customer, or a new customer. When creating a new customer, the user will automatically receive the WordPress new user email with a link to set her password.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "The activation limit and expiration date can both be set as well. If left blank, the key will have an unlimited number of activations and a lifetime expiration date.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "If necessary, the license key can be manually set by clicking the <em>Set the license key manually</em> link. Otherwise, the key will be automatically generated based on the key type specified in Exchange.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "Lastly, the amount the customer paid can be set. This will become the <em>Total</em> in the new transaction. If the customer didn’t pay anything for this license, it is important to set the amount paid to $0.00. Otherwise the revenue statistics will be inaccurate.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'licenses' ) && \ITELIC\Admin\Licenses\Dispatch::is_current_view( 'add-new' );
	} );

	$help->add( __( "Overview", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "All of the releases for your products appear on this screen. This includes the initial release for your product, as well as all the subsequent releases. You can use the Screen Options tab to customize the display of this screen.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "You can narrow the list by status using the text links at the top of the screen. You can also filter the list to only show releases for a certain product. Only products that have licensing enabled will appear on this list. Additionally, the releases can be qualified by type.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "The search box lets you look for a particular release by searching through the changelogs. You can find a release by version by prefixing your search with <em>v</em>. For example searching with <em>v1.5</em> will find all releases with the version number 1.5", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'releases' ) && \ITELIC\Admin\Releases\Dispatch::is_current_view( 'list' );
	} );

	$help->add( __( "Available Actions", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "To view more details about a release, click the <em>View</em> link.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "You’ll also notice there is no way to delete releases. This is intentional. If you need to stop a release from going out, you should use the pause feature.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'releases' ) && \ITELIC\Admin\Releases\Dispatch::is_current_view( 'list' );
	} );

	$help->add( __( "Overview", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "The <em>Manage Release</em> screen displays detailed information about a release. Across the top of the screen is the product’s name and the version of the release. In the top right hand corner, the release’s status is represented by a color. Hovering over the color reveals the textual description of the status.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "A release can have a status of <em>draft</em>, <em>active</em>, <em>paused</em> or <em>archived</em>.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "The type of the release, <em>major</em>, <em>minor</em>, <em>security</em> or <em>pre-release</em> is displayed in the type section. If the release has been published, the release date is displayed. This is followed by the version number of the release.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "The changelog for a release is displayed in the <em>What’s Changed</em> section. This shouldn’t contain the release date or version number as these will be automatically added to the compiled changelog.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "Finally, for non-draft releases, a summary of the updates completed is displayed, as well as a link to notify users who haven’t yet upgraded to the latest release.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'releases' ) && \ITELIC\Admin\Releases\Dispatch::is_current_view( 'single' );
	} );

	$help->add( __( "Statuses", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<h4>' . __( 'Draft', Plugin::SLUG ) . '</h4>';
		$html .= '<p>';
		$html .= __( "Draft mode is your staging ground for new releases. These releases aren’t yet available to be downloaded. At this time, you can edit the type of release by clicking the release type and selecting a new release. You can also change the version number by clicking on the version number in the version box.", Plugin::SLUG );
		$html .= __( "Additionally, the download file can be replaced by clicking the <em>Replace</em> button. Finally, to edit the release’s changelog just click the text, enter your changes, and click the save button.", Plugin::SLUG );
		$html .= __( "To release the new version, hover over the status indicator, and change the release’s status to <em>Active</em>. This will update the version number of the product, and will update the download file made available to new customers as well.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<h4>' . __( 'Active', Plugin::SLUG ) . '</h4>';
		$html .= '<p>';
		$html .= __( "Active releases are available to your customers to update to. When a release is active, <em>all</em> update records are kept allowing you to notify customers who haven’t yet updated to that version. As such, just because a release is active, does not mean it is the latest release available." );
		$html .= __( "When a release is active, the only thing that can be edited is the changelog. If the update file needs to be changed, you should pause this release and create a new one.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<h4>' . __( 'Paused', Plugin::SLUG ) . '</h4>';
		$html .= '<p>';
		$html .= __( "When a release is paused, the version number and download file is reverted to the latest active release. When a customer goes to update their install, they will receive the latest active release, not the paused release." );
		$html .= __( "Paused releases can’t be reactivated, instead you should create a new minor release and bump the version number.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<h4>' . __( 'Archived', Plugin::SLUG ) . '</h4>';
		$html .= '<p>';
		$html .= __( "Full update data is kept for the latest 10 releases – this exact number can be controlled via a filter. After that, the release will be automatically archived." );
		$html .= __( "Archived releases aggregate the update data to save database space. After a release has been archived, you can no longer notify customers who haven’t updated.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'releases' ) && \ITELIC\Admin\Releases\Dispatch::is_current_view( 'single' );
	} );

	$help->add( __( "Update Statistics", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "The plugin stores statistics about how many customers have updated to the new version of your software. These numbers are fairly accurate, but not 100%. The total number of updates is displayed in a progress bar. ", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "To view more detailed information click the <em>More</em> link. In addition to the progress bar, a line chart is displayed that shows the number of updates over time.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "This is limited to show the first 14 days of updates. A pie chart is also displayed showing up to the top 5 versions your customers have updated from when installing the new version.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'releases' ) && \ITELIC\Admin\Releases\Dispatch::is_current_view( 'single' );
	} );

	$help->add( __( "Notification Editor", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "Pressing the <em>Notify</em> button, launches the notification editor. This is how you can email customers who have not yet updated to the selected release or later. ", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "A customer will only receive one notification, even if they have multiple installs on an outdated version. The emails can be customized per customer by using the <em>Insert Template Tag</em> button.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "To receive the best performance you should install the wpMandrill plugin. This will send notifications in bulk using Mandrill.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'releases' ) && \ITELIC\Admin\Releases\Dispatch::is_current_view( 'single' );
	} );

	$help->add( __( "Overview", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "Releases are how customers can update to the latest version of your software. Each new version should have a release.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "Creating a new release begins with selecting the release type. Each release type functions a bit differently. You can learn more about the different release types in the <em>Release Types</em> tab.", Plugin::SLUG );
		$html .= __( "Then upload the new version by either liking the <em>Upload File</em> link, or dragging and dropping the zip file into the upload area. If you want to remove the file, simply click the trash icon in the top right hand corner.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "Finally enter in what has changed in the new version. Don’t include the version number or date, as these will be automatically added by the plugin. If this is a security release, you will have a chance to enter a message that will be displayed on the updates page in WordPress.", Plugin::SLUG );
		$html .= '</p>';

		$html .= '<p>';
		$html .= __( "To release the new version immediately, click the <em>Release</em> button. Otherwise, click the <em>Save for Later</em> button to save the release as a draft.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'releases' ) && \ITELIC\Admin\Releases\Dispatch::is_current_view( 'add-new' );
	} );

	$help->add( __( "Release Types", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<h4>' . __( 'Major', Plugin::SLUG ) . '</h4>';
		$html .= '<p>';
		$html .= get_major_release_help_text();
		$html .= '</p>';

		$html .= '<h4>' . __( 'Minor', Plugin::SLUG ) . '</h4>';
		$html .= '<p>';
		$html .= get_minor_release_help_text();
		$html .= '</p>';

		$html .= '<h4>' . __( 'Security', Plugin::SLUG ) . '</h4>';
		$html .= '<p>';
		$html .= get_security_release_help_text();
		$html .= '</p>';

		$html .= '<h4>' . __( 'Pre-release', Plugin::SLUG ) . '</h4>';
		$html .= '<p>';
		$html .= get_pre_release_help_text();
		$html .= '</p>';

		return $html;
	}, function () {
		return Dispatch::is_current_view( 'releases' ) && \ITELIC\Admin\Releases\Dispatch::is_current_view( 'add-new' );
	} );

	$help->add( __( "Overview", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "Renewal reminders are sent to your customers whenever their license keys are approaching their expiration date. You can create as many renewal reminders as needed. However, as the number of reminders is increase there is a performance impact. It is recommended to have no more than ten reminders.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return get_current_screen()->post_type == CPT::TYPE;
	} );

	$help->add( __( "Creating Reminders", Plugin::SLUG ), function () {

		$html = '';

		$html .= '<p>';
		$html .= __( "Creating a renewal reminder is similar to creating a WordPress post. The title is used as the subject line, and the post content is used as the message body. The message can be customized using the <em>Insert Template Tag</em> button. Use the <em>Scheduling</em> metabox to control when the reminder is sent out.", Plugin::SLUG );
		$html .= '</p>';

		return $html;
	}, function () {
		return get_current_screen()->post_type == CPT::TYPE;
	} );

	/**
	 * Fires when additional help tabs should be registered.
	 *
	 * @since 1.0
	 *
	 * @param Help $help
	 */
	do_action( 'itelic_register_help_tabs', $help );
} );

/**
 * Get the help text for major releases.
 *
 * @since 1.0
 *
 * @return string
 */
function get_major_release_help_text() {
	return __( "Major releases should be used when new features are developed. Depending on your version scheme, this could be either a 1.0 &rarr; 2.0 or a 1.1.0 &rarr; 1.2.0 release. Customers will be reminded to create a backup before updating.", Plugin::SLUG );
}

/**
 * Get the help text for minor releases.
 *
 * @since 1.0
 *
 * @return string
 */
function get_minor_release_help_text() {
	return __( "Minor releases are for bug fixes or feature tweak releases. This could represent a version change of 1.0 &rarr; 1.1 or 1.0.0 &rarr; 1.0.1. A minor release should not have breaking changes, and should be a simple update for your customers.", Plugin::SLUG );
}

/**
 * Get the help text for security releases.
 *
 * @since 1.0
 *
 * @return string
 */
function get_security_release_help_text() {
	return __( "Whenever a vulnerability is fixed a security release should be created. The security message can be used to inform your customers about the urgency of the update.", Plugin::SLUG );
}

/**
 * Get the help text for pre-releases.
 *
 * @since 1.0
 *
 * @return string
 */
function get_pre_release_help_text() {
	$text = __( "Pre-releases can be used to offer beta versions of your software to your customers. Customers need to opt-in to the pre-release track when activating their license key. Pre-releases won’t appear in the changelog.", Plugin::SLUG );
	$text .= '<br><br>';
	$text .= __( "You MUST version these releases using semantic versioning to ensure updates are properly deployed. For example to release a beta for version 1.2, your releases should be versioned 1.2-beta.", Plugin::SLUG );

	return $text;
}