<?php
/**
 * Theme API class for retrieving information about an activation.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class IT_Theme_API_Activation
 */
class IT_Theme_API_Activation implements IT_Theme_API {

	/**
	 * @var string
	 */
	private $_context = 'activation';

	/**
	 * @var \ITELIC\Activation
	 */
	private $activation;

	/**
	 * @var array
	 */
	public $_tag_map = array(
		'location'         => 'location',
		'status'           => 'status',
		'id'               => 'id',
		'activationdate'   => 'activation_date',
		'deactivationdate' => 'deactivation_date',
		'deactivatelink'   => 'deactivate_link',
		'activate'         => 'activate'
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->activation = isset( $GLOBALS['it_exchange']['license_activation'] ) ? $GLOBALS['it_exchange']['license_activation'] : null;
	}

	/**
	 * @return string
	 */
	public function get_api_context() {
		return $this->_context;
	}

	/**
	 * Retrieve the location.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function location( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'label'  => __( 'Location', ITELIC\Plugin::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->activation ) {
			$value = $this->activation->get_location();
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
	 * Retrieve the status.
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
			'label'  => __( 'Status', ITELIC\Plugin::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->activation ) {
			$value = $this->activation->get_status( true );
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
	 * Retrieve the location.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function id( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'label'  => __( 'ID', ITELIC\Plugin::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->activation ) {
			$value = $this->activation->get_id();
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
	 * Retrieve the activation date.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function activation_date( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'df'     => str_replace( 'F', 'M', get_option( 'date_format' ) ),
			'label'  => __( 'Activation', ITELIC\Plugin::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->activation ) {
			$value = $this->activation->get_activation()->format( $options['df'] );
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
	 * Retrieve the deactivation date.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function deactivation_date( $options = array() ) {
		$defaults = array(
			'format' => 'html',
			'df'     => str_replace( 'F', 'M', get_option( 'date_format' ) ),
			'label'  => __( 'Deactivation', ITELIC\Plugin::SLUG )
		);
		$options  = ITUtility::merge_defaults( $options, $defaults );

		if ( $this->activation ) {
			if ( $this->activation->get_deactivation() === null ) {
				$value = '';
			} else {
				$value = $this->activation->get_deactivation()->format( $options['df'] );
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
	 * Outputs a deactivation link.
	 *
	 * @since 1.0
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	public function deactivate_link( $options = array() ) {
		$defaults = array(
			'format'  => 'html',
			'label'   => __( 'Deactivate', ITELIC\Plugin::SLUG ),
			'working' => __( "Deactivating", ITELIC\Plugin::SLUG )
		);

		$options = ITUtility::merge_defaults( $options, $defaults );

		$label   = $options['label'];
		$id      = $this->activation->get_id();
		$nonce   = wp_create_nonce( "itelic-deactivate-$id" );
		$working = $options['working'];

		$link = "<a href=\"javascript:\" class=\"deactivate-location\" data-id=\"$id\" data-nonce=\"$nonce\" data-working=\"$working\">";
		$link .= $label;
		$link .= "</a>";

		return $link;
	}
}