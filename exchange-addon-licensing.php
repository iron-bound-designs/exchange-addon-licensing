<?php
/*
Plugin Name: iThemes Exchange - Licensing Add-on
Plugin URI: http://ironbounddesigns.com
Description: Sell licenses for your digital products.
Version: 1.0
Author: Iron Bound Designs
Author URI: http://ironbounddesigns.com
License: GPLv2
Text Domain: ibd-exchange-addon-licensing
Domain Path: /lang
*/

namespace ITELIC;

/**
 * Class ITELIC
 */
class Plugin {

	/**
	 * Plugin Version
	 */
	const VERSION = '1.0';

	/**
	 * Translation SLUG
	 */
	const SLUG = 'ibd-exchange-addon-licensing';

	/**
	 * @var string
	 */
	static $dir;

	/**
	 * @var string
	 */
	static $url;

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

		wp_register_script( 'jquery-blockUI', self::$url . 'assets/vendor/blockUI/js/jquery.blockUI.js', array( 'jquery' ), '2.70.0' );

		wp_register_style( 'itelic-add-edit-product', self::$url . 'assets/css/itelic-add-edit-product.css', array(), self::VERSION );
		wp_register_script( 'itelic-add-edit-product', self::$url . 'assets/js/itelic-add-edit-product.js', array( 'jquery' ), self::VERSION );

		wp_register_script( 'itelic-admin-licenses-list', self::$url . 'assets/js/itelic-admin-licenses-list.js', array( 'jquery' ), self::VERSION );
		wp_register_script( 'itelic-admin-license-detail', self::$url . 'assets/js/itelic-admin-license-detail.js', array(
			'jquery',
			'jqueryui-editable'
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

		wp_register_style( 'itelic-admin-releases-new', self::$url . 'assets/css/itelic-admin-releases-new.css', array(), self::VERSION );
		wp_register_script( 'itelic-admin-releases-new', self::$url . 'assets/js/itelic-admin-releases-new.js', array(
			'jquery-effects-slide'
		), self::VERSION );

		wp_register_style( 'itelic-admin-releases-edit', self::$url . 'assets/css/itelic-admin-releases-edit.css', array(
			'jqueryui-editable'
		), self::VERSION );
		wp_register_script( 'itelic-admin-releases-edit', self::$url . 'assets/js/itelic-admin-releases-edit.js', array(
			'jquery-effects-slide',
			'jqueryui-editable',
			'jquery-blockUI'
		), self::VERSION );
	}

	/**
	 * Autoloader.
	 *
	 * @since 1.0
	 */
	public static function autoload() {

		require_once( self::$dir . 'autoloader.php' );

		$autoloader = new Psr4AutoloaderClass();
		$autoloader->addNamespace( 'ITELIC', self::$dir . 'lib' );
		$autoloader->addNamespace( 'ITELIC_API', self::$dir . 'api' );
		$autoloader->addNamespace( 'IronBound', self::$dir . 'vendor/IronBound' );
		$autoloader->addNamespace( 'IronBound\\WP_Notifications', self::$dir . 'vendor/IronBound/WP_Notifications/src' );
		$autoloader->addNamespace( 'IronBound\\DB', self::$dir . 'vendor/IronBound/DB/src' );
		$autoloader->addNamespace( 'IronBound\\Cache', self::$dir . 'vendor/IronBound/Cache/src' );
		$autoloader->addNamespace( 'URL', self::$dir . 'vendor/URL' );

		$autoloader->register();
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
		'description'       => __( 'Sell licenses for your digital products.', Plugin::SLUG ),
		'author'            => 'Iron Bound Designs',
		'author_url'        => 'http://www.ironbounddesigns.com',
		'file'              => dirname( __FILE__ ) . '/init.php',
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