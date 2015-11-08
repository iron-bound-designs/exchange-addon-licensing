<?php

/**
 * Class ITELIC_Plugin_Updater
 */
class ITELIC_Plugin_Updater {

	/**
	 * Activate the site.
	 */
	const EP_ACTIVATE = 'activate';

	/**
	 * Deactivate the site.
	 */
	const EP_DEACTIVATE = 'deactivate';

	/**
	 * Returns info about the license key.
	 */
	const EP_INFO = 'info';

	/**
	 * Get the latest version.
	 */
	const EP_VERSION = 'version';

	/**
	 * Download the plugin file.
	 */
	const EP_DOWNLOAD = 'download';

	/**
	 * Return info about the product.
	 */
	const EP_PRODUCT = 'product';

	/**
	 * GET method.
	 */
	const METHOD_GET = 'GET';

	/**
	 * POST method.
	 */
	const METHOD_POST = 'POST';

	/**
	 * @var string
	 */
	private $store_url;

	/**
	 * @var int
	 */
	private $product_id;

	/**
	 * @var string
	 */
	private $file;

	/**
	 * @var string
	 */
	private $version;

	/**
	 * @var string
	 */
	private $key = '';

	/**
	 * @var int
	 */
	private $id = 0;

	/**
	 * Constructor.
	 *
	 * @param string $store_url  This is the URL to your store.
	 * @param int    $product_id This is the product ID of your plugin.
	 * @param string $file       The __FILE__ constant of your main plugin file.
	 * @param array  $args       Additional args.
	 *
	 * @throws Exception
	 */
	public function __construct( $store_url, $product_id, $file, $args = array() ) {
		$this->store_url  = trailingslashit( $store_url );
		$this->product_id = $product_id;
		$this->file       = $file;

		if ( empty( $args['version'] ) ) {

			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$data = get_plugin_data( $file, false, false );

			$this->version = $data['Version'];
		} else {
			$this->version = $args['version'];
		}

		if ( isset( $args['key'] ) ) {
			$this->key = $args['key'];
		} elseif ( isset( $args['license'] ) ) {
			$this->key = $args['license'];
		}

		if ( $args['activation_id'] ) {
			$this->id = absint( $args['activation_id'] );
		}

		$file = plugin_basename( $file );

		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_action( "after_plugin_row_$file", 'wp_plugin_update_row', 10, 2 );
		add_action( "in_plugin_update_message-{$file}", array( $this, 'show_upgrade_notice_on_list' ), 10, 2 );
		add_filter( 'plugins_api', array( $this, 'plugins_api_handler' ), 10, 3 );
		add_filter( 'all_plugins', array( $this, 'add_slug_to_plugins_list' ) );
	}

	/**
	 * Check for a plugin update.
	 *
	 * @since 1.0
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public function check_for_update( $transient ) {

		if ( empty( $transient->checked ) || empty( $this->key ) ) {
			return $transient;
		}

		try {
			$info = $this->get_latest_version( $this->key );
		}
		catch ( Exception $e ) {

			return $transient;
		}

		if ( ! is_wp_error( $info ) && version_compare( $info->version, $this->version, '>' ) ) {

			$basename = plugin_basename( $this->file );

			$split = explode( '/', $basename );

			$transient->response[ $basename ] = (object) array(
				'new_version' => $info->version,
				'package'     => $info->package,
				'slug'        => $split[0],
				'plugin'      => $basename
			);

			if ( ! empty( $info->upgrade_notice ) ) {
				$transient->response[ $basename ]->upgrade_notice = $info->upgrade_notice;
			}
		}

		return $transient;
	}

	/**
	 * Show the upgrade notice on the plugin list page.
	 *
	 * @since 1.0
	 *
	 * @param array $plugin_data
	 * @param array $r
	 */
	public function show_upgrade_notice_on_list( $plugin_data, $r ) {

		if ( ! empty( $plugin_data['upgrade_notice'] ) ) {
			echo '&nbsp;' . $plugin_data['upgrade_notice'];
		}
	}

	/**
	 * A function for the WordPress "plugins_api" filter. Checks if
	 * the user is requesting information about the current plugin and returns
	 * its details if needed.
	 *
	 * This function is called before the Plugins API checks
	 * for plugin information on WordPress.org.
	 *
	 * @param $res      bool|object The result object, or false (= default
	 *                  value).
	 * @param $action   string      The Plugins API action. We're interested in
	 *                  'plugin_information'.
	 * @param $args     object       The Plugins API parameters.
	 *
	 * @return object   The API response.
	 */
	public function plugins_api_handler( $res, $action, $args ) {

		if ( $action == 'plugin_information' ) {

			$requested_slug = isset( $args->slug ) ? $args->slug : '';

			$basename = plugin_basename( $this->file );
			$split    = explode( '/', $basename );
			$slug     = $split[0];

			if ( $requested_slug && $this->key && ( $requested_slug == $slug ) ) {

				try {
					$all_products = $this->get_product_info( $this->key, $this->id );
				}
				catch ( Exception $e ) {
					return $res;
				}

				if ( is_wp_error( $all_products ) ) {
					return $res;
				}

				if ( ! isset( $all_products->list ) ) {
					return $res;
				}


				$info = $all_products->list->{$this->product_id};

				$res = (object) array(
					'name'          => $info->name,
					'version'       => $info->version,
					'slug'          => $args->slug,
					'download_link' => $info->package_url,
					'tested'        => $info->tested,
					'requires'      => $info->requires,
					'last_updated'  => isset( $info->last_updated ) ? date( get_option( 'date_format' ), strtotime( $info->last_updated ) ) : '',
					'homepage'      => $info->description_url,
					'contributors'  => $info->contributors,
					'sections'      => $info->sections,
					'banners'       => array(
						'low'  => $info->banner_low,
						'high' => $info->banner_high
					),
					'external'      => true
				);

				if ( ! isset( $res->sections['description'] ) ) {
					$res->sections['description'] = $info->description;
				}

				if ( isset( $info->changelog ) ) {
					$res->sections['changelog'] = $info->changelog;
				}

				$data        = get_plugin_data( $this->file );
				$res->author = "<a href=\"{$data['AuthorURI']}\">{$data['Author']}</a>";

				return $res;
			}
		}

		// Not our request, let WordPress handle this.
		return $res;
	}

	/**
	 * Add the slug to the list of plugins, which allows for showing the view
	 * details link on the plugins list table.
	 *
	 * @param array $plugins
	 *
	 * @return array
	 */
	public function add_slug_to_plugins_list( $plugins ) {

		foreach ( $plugins as $file => $data ) {

			if ( $file == plugin_basename( $this->file ) ) {

				$split = explode( '/', $file );

				$plugins[ $file ]['slug'] = $split[0];
			}

		}

		return $plugins;
	}

	/**
	 * Activate a license key for this site.
	 *
	 * @param string $key   License Key
	 * @param string $track Either 'stable' or 'pre-release'
	 *
	 * @return int|WP_Error Activation Record ID on success, WP_Error object on
	 *                      failure.
	 */
	public function activate( $key, $track = 'stable' ) {

		$params = array(
			'body' => array(
				'location' => site_url(),
				'version'  => $this->version,
				'track'    => $track
			)
		);

		$response = $this->call_api( self::EP_ACTIVATE, self::METHOD_POST, $key, $this->id, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			return $response->id;
		}
	}

	/**
	 * Deactivate the license key on this site.
	 *
	 * @param string     $key License Key
	 * @param int|string $id  ID returned from activate method.
	 *
	 * @return boolean|WP_Error Boolean True on success, WP_Error object on
	 *                          failure.
	 */
	public function deactivate( $key, $id ) {

		$params = array(
			'body' => array(
				'id' => (int) $id
			)
		);

		$response = $this->call_api( self::EP_DEACTIVATE, self::METHOD_POST, $key, $id, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			return true;
		}
	}

	/**
	 * Get the latest version of the plugin.
	 *
	 * @param string $key
	 *
	 * @return object|WP_Error
	 *
	 * @throws Exception
	 */
	public function get_latest_version( $key ) {

		if ( ! $this->id ) {
			throw new Exception( "License key must be activated before retrieving the latest version." );
		}

		$params = array(
			'installed_version' => $this->version
		);

		$response = $this->call_api( self::EP_VERSION, self::METHOD_GET, $key, $this->id, array(), $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( isset( $response->list->{$this->product_id} ) ) {
			return $response->list->{$this->product_id};
		} else {
			throw new Exception( "Product ID and License Key don't match." );
		}
	}

	/**
	 * Get info about a license key.
	 *
	 * @since 1.0
	 *
	 * @param string $key
	 *
	 * @return object|WP_Error
	 */
	public function get_info( $key ) {
		return $this->call_api( self::EP_INFO, self::METHOD_GET, $key );
	}

	/**
	 * Get info about the product this license key connects to.
	 *
	 * @since 1.0
	 *
	 * @param string $key
	 * @param int    $id
	 *
	 * @return object|WP_Error
	 * @throws Exception
	 */
	public function get_product_info( $key, $id ) {
		return $this->call_api( self::EP_PRODUCT, self::METHOD_GET, $key, $id );
	}

	/**
	 * Make a call to the API.
	 *
	 * This method is suitable for client consumption,
	 * but the convenience methods provided are preferred.
	 *
	 * @param string $endpoint
	 * @param string $method
	 * @param string $key
	 * @param int    $id
	 * @param array  $http_args
	 * @param array  $query_params
	 *
	 * @return object|WP_Error Decoded JSON on success, WP_Error object on
	 *                         error.
	 *
	 * @throws Exception If invalid HTTP method.
	 */
	public function call_api( $endpoint, $method, $key = '', $id = 0, $http_args = array(), $query_params = array() ) {

		$args = array(
			'headers' => array()
		);
		$args = wp_parse_args( $http_args, $args );

		if ( $key ) {
			$args['headers']['Authorization'] = $this->generate_basic_auth( $key, $id );
		}

		$url = $this->generate_endpoint_url( $endpoint, $query_params );

		if ( $method == self::METHOD_GET ) {
			$response = wp_remote_get( $url, $args );
		} elseif ( $method == self::METHOD_POST ) {
			$response = wp_remote_post( $url, $args );
		} else {
			throw new Exception( "Invalid HTTP Method" );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );

		$json = json_decode( $response_body );

		if ( ! $json->success ) {

			if ( ! $json ) {
				$json = (object) array(
					'error' => array(
						'code'    => $response['code'],
						'message' => $response['message']
					)
				);
			}

			return $this->response_to_error( $json );
		} else {
			return $json->body;
		}
	}

	/**
	 * Convert the JSON decoded response to an error object.
	 *
	 * @param stdClass $response
	 *
	 * @return WP_Error
	 *
	 * @throws Exception If response is not an error. To check for an error
	 *                   look at the 'success' property.
	 */
	protected function response_to_error( stdClass $response ) {

		if ( $response->success ) {
			throw new Exception( "Response object is not an error." );
		}

		return new WP_Error( $response->error->code, $response->error->message );
	}

	/**
	 * Generate the endpoint URl.
	 *
	 * @param string $endpoint
	 * @param array  $query_params
	 *
	 * @return string
	 */
	protected function generate_endpoint_url( $endpoint, $query_params = array() ) {

		$base = $this->store_url . 'itelic-api';

		$url = "$base/$endpoint/";

		if ( ! empty( $query_params ) ) {
			$url = add_query_arg( $query_params, $url );
		}

		return $url;
	}

	/**
	 * Generate a basic auth header based on the license key.
	 *
	 * @param string     $key
	 * @param int|string $activation
	 *
	 * @return string
	 */
	protected function generate_basic_auth( $key, $activation = '' ) {
		return 'Basic ' . base64_encode( $key . ':' . $activation );
	}
}