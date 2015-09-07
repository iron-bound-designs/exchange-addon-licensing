<?php
/**
 * Polar chart.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Chart;

/**
 * Class Polar
 * @package ITELIC\Admin\Chart
 */
class Polar extends Integrated_Labels {

	/**
	 * Constructor.
	 *
	 * @param int   $width
	 * @param int   $height
	 * @param array $options
	 */
	public function __construct( $width, $height, $options = array() ) {
		parent::__construct( $width, $height, 'PolarArea', $options );
	}

}