<?php
/**
 * Doughnut chart.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\Admin\Chart;

/**
 * Class Doughnut
 * @package ITELIC\Admin\Chart
 */
class Doughnut extends Integrated_Labels {

	/**
	 * Constructor.
	 *
	 * @param int   $width
	 * @param int   $height
	 * @param array $options
	 */
	public function __construct( $width, $height, $options = array() ) {
		parent::__construct( $width, $height, 'Doughnut', $options );
	}

}