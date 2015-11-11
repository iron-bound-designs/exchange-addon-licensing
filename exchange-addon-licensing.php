<?php
/*
Plugin Name: iThemes Exchange - Licensing Add-on
Plugin URI: https://ironbounddesigns.com/product/licensing/
Description: Sell and manage license keys for your software.
Version: 1.0-beta
Author: Iron Bound Designs
Author URI: https://ironbounddesigns.com
License: AGPL
Text Domain: ibd-exchange-addon-licensing
Domain Path: /lang
*/

namespace ITELIC;

use IronBound\DB\Manager;
use ITELIC\Admin\Tab\Dispatch;
use ITELIC\Renewal\Reminder\CPT;
use ITELIC_Plugin_Updater;

/**
 * Class ITELIC
 */
class Plugin {

	/**
	 * Plugin Version
	 */
	const VERSION = '1.0-beta';

	/**
	 * Translation SLUG
	 */
	const SLUG = 'ibd-exchange-addon-licensing';

	/**
	 * @var int
	 */
	const ID = 1574;

	/**
	 * @var string
	 */
	static $dir;

	/**
	 * @var string
	 */
	static $url;

	/**
	 * @var ITELIC_Plugin_Updater
	 */
	static $updater;

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::$dir = plugin_dir_path( __FILE__ );
		self::$url = plugin_dir_url( __FILE__ );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_and_styles' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_and_styles' ), 5 );
		add_action( 'admin_notices', array( $this, 'display_register_admin_notice' ) );
		add_action( 'after_plugin_row_' . plugin_basename( __FILE__ ), array( $this, 'display_register_notice' ) );
		add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );

		self::autoload();
	}

	/**
	 * Run the upgrade routine if necessary.
	 */
	public static function upgrade() {
		$current_version = get_option( 'itelic_version', 0.1 );

		if ( $current_version != self::VERSION ) {

			/**
			 * Runs when the version upgrades.
			 *
			 * @param $current_version
			 * @param $new_version
			 */
			do_action( 'itelic_upgrade', self::VERSION, $current_version );

			update_option( 'itelic_version', self::VERSION );
		}

		foreach ( get_tables() as $table ) {
			if ( ! Manager::is_table_installed( $table ) ) {

				add_action( 'all_admin_notices', function () {
					echo '<div class="notice notice-error"><p>';
					_e( "We weren't able to install the tables necessary for Licensing to work. Please contact support.", Plugin::SLUG );
					echo '</p></div>';
				} );

				if ( ! function_exists( '\deactivate_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				\deactivate_plugins( __FILE__ );

				break;
			}
		}
	}

	/**
	 * Setup the licensing for EDD.
	 *
	 * @since 1.0
	 */
	public static function setup_licensing() {

		if ( ! function_exists( 'it_exchange_get_option' ) ) {
			return;
		}

		$options = it_exchange_get_option( 'addon_itelic' );

		// retrieve our license key from the DB
		$license_key = trim( $options['license'] );
		$activation  = trim( $options['activation'] );

		// setup the updater
		self::$updater = new ITELIC_Plugin_Updater( 'https://ironbounddesigns.com', self::ID, __FILE__, array(
				'license'       => $license_key,
				'activation_id' => $activation
			)
		);
	}

	/**
	 * Display a link to register the add-on on Licensing admin pages.
	 *
	 * @since 1.0
	 */
	public function display_register_admin_notice() {

		$options = it_exchange_get_option( 'addon_itelic' );

		if ( ! empty( $options['license'] ) && ! empty( $options['activation'] ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( $screen->post_type != CPT::TYPE && ( ! isset( $_GET['page'] ) || $_GET['page'] != Dispatch::PAGE_SLUG ) ) {
			return;
		}

		$ID = self::ID;
		?>

		<div class="notice notice-info" style="margin-bottom: 0">
			<p>
				<?php echo sprintf( esc_html__(
					'%sRegister%s Licensing to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.',
					self::SLUG ),
					'<a href="' . admin_url() . 'admin.php?page=it-exchange-addons&add-on-settings=licensing">',
					'</a>', "<a href=\"https://ironbounddesigns.com?p={$ID}\">",
					'</a>' ); ?>
			</p>
		</div>

		<?php
	}

	/**
	 * Display a notice to register this add-on.
	 *
	 * @since 1.0
	 */
	public function display_register_notice() {

		$options = it_exchange_get_option( 'addon_itelic' );

		if ( ! empty( $options['license'] ) && ! empty( $options['activation'] ) ) {
			return;
		}

		$ID = self::ID;

		echo '</tr><tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message">';
		echo sprintf( esc_html__(
			'%sRegister%s this add-on to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.',
			self::SLUG ),
			'<a href="' . admin_url() . 'admin.php?page=it-exchange-addons&add-on-settings=licensing">',
			'</a>', "<a href=\"https://ironbounddesigns.com?p={$ID}\">",
			'</a>' );
		echo '</div></td>';
	}

	/**
	 * Add Setup action link.
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public function action_links( $links ) {
		$links[] = sprintf( '<a href="%s">%s</a>',
			admin_url() . 'admin.php?page=it-exchange-addons&add-on-settings=licensing',
			__( "Setup", Plugin::SLUG ) );

		return $links;
	}

	/**
	 * The activation hook.
	 */
	public function activate() {
		do_action( 'itelic_activate' );
	}

	/**
	 * The deactivation hook.
	 */
	public function deactivate() {

	}

	/**
	 * Register admin scripts.
	 *
	 * @since 1.0
	 */
	public function scripts_and_styles() {

		wp_register_script( 'ithemes-chartjs', self::$url . 'assets/vendor/chartjs/js/Chart.min.js', array( 'jquery' ), '1.0.2', true );

		wp_register_style( 'jqueryui-editable', self::$url . 'assets/vendor/jqueryui-editable/css/jqueryui-editable.css', array(), '1.5.1' );
		wp_register_script( 'jqueryui-editable', self::$url . 'assets/vendor/jqueryui-editable/js/jqueryui-editable.js', array(
			'jquery-ui-core',
			'jquery-ui-tooltip',
			'jquery-ui-button',
			'jquery-ui-datepicker'
		), '1.5.1' );

		wp_register_script( 'itelic-modernizr', self::$url . 'assets/js/itelic-modernizr.min.js', array(), self::VERSION );

		wp_register_script( 'jquery-blockUI', self::$url . 'assets/vendor/blockUI/js/jquery.blockUI.js', array( 'jquery' ), '2.70.0' );

		wp_register_script( 'select2', self::$url . 'assets/vendor/select2/select2.full.min.js', array( 'jquery' ), '4.0' );
		wp_register_style( 'select2', self::$url . 'assets/vendor/select2/select2.min.css', array(), '4.0' );

		wp_register_script( 'dropzone', self::$url . 'assets/vendor/dropzone/dropzone.js', array(), '4.0' );
		wp_register_style( 'dropzone', self::$url . 'assets/vendor/dropzone/dropzone.css', array(), '4.0' );

		wp_register_style( 'itelic-add-edit-product', self::$url . 'assets/css/itelic-add-edit-product.css', array(), self::VERSION );
		wp_register_script( 'itelic-add-edit-product', self::$url . 'assets/js/itelic-add-edit-product.js', array( 'jquery' ), self::VERSION );

		wp_register_style( 'itelic-admin-licenses-new', self::$url . 'assets/css/itelic-admin-licenses-new.css', array( 'select2' ), self::VERSION );
		wp_register_script( 'itelic-admin-licenses-new', self::$url . 'assets/js/itelic-admin-licenses-new.js', array(
			'select2',
			'jquery-ui-datepicker',
			'itelic-modernizr'
		), self::VERSION );

		wp_register_style( 'itelic-admin-licenses-list', self::$url . 'assets/css/itelic-admin-licenses-list.css', array( 'select2' ), self::VERSION );
		wp_register_script( 'itelic-admin-licenses-list', self::$url . 'assets/js/itelic-admin-licenses-list.js', array( 'select2' ), self::VERSION );
		wp_register_script( 'itelic-admin-license-detail', self::$url . 'assets/js/itelic-admin-license-detail.js', array(
			'jquery',
			'jqueryui-editable',
			'itelic-modernizr'
		), self::VERSION );

		wp_register_style( 'itelic-admin-license-detail', self::$url . 'assets/css/admin-license-detail.css', array(
			'jqueryui-editable'
		), self::VERSION );

		wp_register_script( 'itelic-super-widget', self::$url . 'assets/js/itelic-super-widget.js', array( 'jquery' ), self::VERSION );
		wp_register_style( 'itelic-checkout', self::$url . 'assets/css/itelic-checkout.css', array(), self::VERSION );

		wp_register_style( 'itelic-renewal-reminder-edit', self::$url . 'assets/css/itelic-renewal-reminder-edit.css', array(), self::VERSION );
		wp_register_script( 'itelic-renewal-reminder-edit', self::$url . 'assets/js/itelic-renewal-reminder-edit.js', array( 'jquery' ), self::VERSION );

		wp_register_style( 'itelic-account-licenses', self::$url . 'assets/css/itelic-account-licenses.css', array(), self::VERSION );
		wp_register_script( 'itelic-account-licenses', self::$url . 'assets/js/itelic-account-licenses.js', array( 'jquery' ), self::VERSION );

		wp_register_style( 'itelic-admin-releases-new', self::$url . 'assets/css/itelic-admin-releases-new.css', array(
			'select2',
			'dropzone'
		), self::VERSION );
		wp_register_script( 'itelic-admin-releases-new', self::$url . 'assets/js/itelic-admin-releases-new.js', array(
			'jquery-effects-slide',
			'jquery-ui-tooltip',
			'select2',
			'dropzone'
		), self::VERSION );

		wp_register_script( 'itelic-admin-releases-list', self::$url . 'assets/js/itelic-admin-releases-list.js', array(
			'select2',
			'jquery'
		), self::VERSION );
		wp_register_style( 'itelic-admin-releases-list', self::$url . 'assets/css/itelic-admin-releases-list.css', array(
			'select2'
		), self::VERSION );

		wp_register_style( 'itelic-admin-releases-edit', self::$url . 'assets/css/itelic-admin-releases-edit.css', array(
			'jqueryui-editable'
		), self::VERSION );
		wp_register_script( 'itelic-admin-releases-edit', self::$url . 'assets/js/itelic-admin-releases-edit.js', array(
			'jquery-effects-slide',
			'jqueryui-editable',
			'jquery-blockUI',
			'jquery-ui-tooltip'
		), self::VERSION );

		wp_register_style( 'itelic-admin-reports-list', self::$url . 'assets/css/itelic-admin-reports-list.css', array(), self::VERSION );

		wp_register_style( 'itelic-admin-report-view', self::$url . 'assets/css/itelic-admin-report-view.css', array( 'select2' ), self::VERSION );
		wp_register_script( 'itelic-admin-report-view', self::$url . 'assets/js/itelic-admin-report-view.js', array( 'select2' ), self::VERSION );

		wp_register_style( 'itelic-admin-licenses-profile', self::$url . 'assets/css/admin-licenses-profile.css', array(), self::VERSION );
	}

	/**
	 * Autoloader.
	 *
	 * @since 1.0
	 */
	public static function autoload() {
		require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );

		if ( ! class_exists( '\ITELIC_Plugin_Updater' ) ) {
			require_once ( dirname( __FILE__ ) ) . '/updater.php';
		}
	}
}

new Plugin();

/**
 * This registers our add-on
 *
 * @since 1.0
 */
function register_addon() {
	$options = array(
		'name'              => __( 'Licensing', Plugin::SLUG ),
		'description'       => __( 'Sell and manage license keys for your software.', Plugin::SLUG ),
		'author'            => 'Iron Bound Designs',
		'author_url'        => 'http://www.ironbounddesigns.com',
		'file'              => dirname( __FILE__ ) . '/init.php',
		'icon'              => Plugin::$url . 'assets/img/icon-50.png',
		'category'          => 'other',
		'settings-callback' => array( 'ITELIC\Settings', 'display' ),
		'basename'          => plugin_basename( __FILE__ ),
		'labels'            => array(
			'singular_name' => __( 'Licensing', Plugin::SLUG ),
		)
	);
	it_exchange_register_addon( 'licensing', $options );
}

add_action( 'it_exchange_register_addons', 'ITELIC\register_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @since 1.0
 */
function set_textdomain() {
	load_plugin_textdomain( Plugin::SLUG, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}

add_action( 'plugins_loaded', 'ITELIC\set_textdomain' );

/**
 * On activation, set a time, frequency and name of an action hook to be scheduled.
 *
 * @since 1.0
 */
function activation() {
	wp_schedule_event( strtotime( 'Tomorrow 4AM' ), 'daily', 'it_exchange_itelic_daily_schedule' );
}

register_activation_hook( __FILE__, 'ITELIC\activation' );

/**
 * On deactivation, remove all functions from the scheduled action hook.
 *
 * @since 1.0
 */
function deactivation() {
	wp_clear_scheduled_hook( 'it_exchange_itelic_daily_schedule' );
}

register_deactivation_hook( __FILE__, 'ITELIC\deactivation' );