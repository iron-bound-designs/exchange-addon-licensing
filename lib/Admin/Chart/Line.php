<?php
/**
 * Line chart.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Chart;

/**
 * Class Line
 * @package ITELIC\Admin\Chart
 */
class Line extends Separated_Labels {

	/**
	 * Constructor.
	 *
	 * @param array  $labels
	 * @param int    $width
	 * @param int    $height
	 * @param array  $options
	 */
	public function __construct( $labels, $width, $height, $options = array() ) {
		parent::__construct( $labels, $width, $height, 'Line', $options );
	}

}