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
		'key'             => 'key',
		'productname'     => 'product_name',
		'status'          => 'status',
		'activationcount' => 'activation_count',
		'expirationdate'  => 'expiration_date'
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
			'label'  => __( 'Expiration', ITELIC::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->license ) {
			if ( $this->license->get_expires() === null ) {
				$value = __( "Forever", ITELIC::SLUG );
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
}