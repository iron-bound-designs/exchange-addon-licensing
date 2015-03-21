<?php
/**
 * Theme API class for retrieving information about a license.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class IT_Theme_API_License
 */
class IT_Theme_API_License implements IT_Theme_API {

	/**
	 * @var string
	 */
	private $_context = 'license';

	/**
	 * @var ITELIC_Key
	 */
	private $license;

	/**
	 * @var array
	 */
	public $_tag_map = array(
		'key'                 => 'key',
		'productname'         => 'product_name',
		'status'              => 'status',
		'activationcount'     => 'activation_count',
		'expirationdate'      => 'expiration_date',
		'activations'         => 'activations',
		'manage'              => 'manage',
		'activate'            => 'activate',
		'renewlink'           => 'renew_link',
		'canremoteactivate'   => 'can_remote_activate',
		'canremotedeactivate' => 'can_remote_deactivate'
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->license = isset( $GLOBALS['it_exchange']['license'] ) ? $GLOBALS['it_exchange']['license'] : null;
	}

	/**
	 * @return string
	 */
	public function get_api_context() {
		return $this->_context;
	}

	/**
	 * Retrieve the license key.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function key( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'label'  => __( 'License Key', ITELIC::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->license ) {
			$value = $this->license->get_key();
		} else {
			$value = '';
		}

		switch ( $options['format'] ) {
			case 'html' :
				return $value;
				break;
			case 'value' :
				return $value;
				break;
			case 'label' :
				return $options['label'];
				break;
			default :
				return $value;
				break;
		}
	}

	/**
	 * Retrieve the product name.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function product_name( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'label'  => __( 'Product', ITELIC::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->license ) {
			$value = $this->license->get_product()->post_title;
		} else {
			$value = '';
		}

		switch ( $options['format'] ) {
			case 'html' :
				return $value;
				break;
			case 'value' :
				return $value;
				break;
			case 'label' :
				return $options['label'];
				break;
			default :
				return $value;
				break;
		}
	}

	/**
	 * Retrieve the key's status.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function status( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'label'  => __( 'Status', ITELIC::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->license ) {
			$value = $this->license->get_status( true );
		} else {
			$value = '';
		}

		switch ( $options['format'] ) {
			case 'html' :
				return $value;
				break;
			case 'value' :
				return $value;
				break;
			case 'label' :
				return $options['label'];
				break;
			default :
				return $value;
				break;
		}
	}

	/**
	 * Retrieve the key's activation count.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function activation_count( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'label'  => __( 'Active', ITELIC::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->license ) {
			$format = '%1$d / %2$d';
			$value  = sprintf( $format, $this->license->get_active_count(), $this->license->get_max() );
		} else {
			$value = '';
		}

		switch ( $options['format'] ) {
			case 'html' :
				return $value;
				break;
			case 'value' :
				return $value;
				break;
			case 'label' :
				return $options['label'];
				break;
			default :
				return $value;
				break;
		}
	}

	/**
	 * Retrieve the key's activation count.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function expiration_date( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'df'     => str_replace( 'F', 'M', get_option( 'date_format' ) ),
			'label'  => __( 'Expires', ITELIC::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->license ) {
			if ( $this->license->get_expires() === null ) {
				$value = __( "Never", ITELIC::SLUG );
			} else {
				$value = $this->license->get_expires()->format( $options['df'] );
			}
		} else {
			$value = '';
		}

		switch ( $options['format'] ) {
			case 'html' :
				return $value;
				break;
			case 'value' :
				return $value;
				break;
			case 'label' :
				return $options['label'];
				break;
			default :
				return $value;
				break;
		}
	}

	/**
	 * Loop through a license's activations.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function activations( $options = array() ) {

		if ( $options['has'] ) {
			return count( $this->get_activations() ) > 0;
		}

		// If we made it here, we're doing a loop of classes for the current query.
		// This will init/reset the classes global and loop through them. the /api/theme/class.php file will handle individual classes.
		if ( empty( $GLOBALS['it_exchange']['license_activations'] ) ) {
			$GLOBALS['it_exchange']['license_activations'] = $this->get_activations();
			$GLOBALS['it_exchange']['license_activation']  = reset( $GLOBALS['it_exchange']['license_activations'] );

			return true;
		} else {
			if ( next( $GLOBALS['it_exchange']['license_activations'] ) ) {
				$GLOBALS['it_exchange']['license_activation'] = current( $GLOBALS['it_exchange']['license_activations'] );

				return true;
			} else {
				$GLOBALS['it_exchange']['license_activations'] = array();
				end( $GLOBALS['it_exchange']['license_activations'] );
				$GLOBALS['it_exchange']['license_activation'] = false;

				return false;
			}
		}
	}

	/**
	 * Outputs a manage link.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function manage( $options = array() ) {

		$defaults = array(
			'format' => 'html',
			'label'  => __( "Manage", ITELIC::SLUG )
		);

		$options = ITUtility::merge_defaults( $options, $defaults );

		if ( $options['format'] == 'html' ) {
			$output = "<a href=\"javascript:\">{$options['label']}</a>";
		} else {
			$output = '';
		}

		return $output;
	}

	/**
	 * Outputs an activate form.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function activate( $options = array() ) {

		$defaults = array(
			'format'       => 'html',
			'label'        => __( "Remote Activate", ITELIC::SLUG ),
			'submit_label' => __( "Activate", ITELIC::SLUG ),
			'placeholder'  => 'http://www.example.com',
			'description'  => __( 'Authorize a website for activation.', ITELIC::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		$submit = $options['submit_label'];
		$key    = $this->license->get_key();
		$nonce  = wp_create_nonce( "itelic-remote-activate-$key" );

		ob_start();
		?>

		<form class="itelic-activate-form">
			<label for="itelic-remote-activate-url"><?php echo $options['label']; ?></label>
			<input type="url" class="remote-activate-url" placeholder="<?php echo $options['placeholder']; ?>">
			<input type="submit" class="remote-activate" data-key="<?php echo $key; ?>" data-nonce="<?php echo $nonce; ?>" value="<?php echo $submit ?>">

			<p class="description"><?php echo $options['description']; ?></p>
		</form>

		<?php
		return ob_get_clean();
	}

	/**
	 * Outputs a renew link.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function renew_link( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'label'  => __( "Renew this license key", ITELIC::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		$link = itelic_generate_auto_renewal_url( $this->license );

		switch ( $options['format'] ) {
			case 'link':
				return $link;
			case 'label':
				return $options['label'];
			case 'html':
			default:
				return '<a href="' . $link . '">' . $options['label'] . '</a>';
		}
	}

	/**
	 * Check if we can remotely activate this license key.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function can_remote_activate( $options = array() ) {
		$settings = it_exchange_get_option( 'addon_itelic' );

		return $settings['enable-remote-activation'] && it_exchange_get_product_feature( $this->license->get_product()->ID,
			'licensing', array( 'field' => 'online-software' ) );
	}

	/**
	 * Check if we can remotely deactivate this license key.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function can_remote_deactivate( $options = array() ) {
		$settings = it_exchange_get_option( 'addon_itelic' );

		return $settings['enable-remote-deactivation'] && it_exchange_get_product_feature( $this->license->get_product()->ID,
			'licensing', array( 'field' => 'online-software' ) );
	}

	/**
	 * Retrieve the activations.
	 *
	 * @since 1.0
	 *
	 * @return ITELIC_Activation[]
	 */
	protected function get_activations() {
		if ( $this->license ) {
			return $this->license->get_activations( ITELIC_Activation::ACTIVE );
		} else {
			return array();
		}
	}
}