<?php
/**
 * Unit tests for activation class.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\Activation;

/**
 * Class ITELIC_Test_Activation
 */
class ITELIC_Test_Activation extends ITELIC_UnitTestCase {

	public function test_location_required() {

		$stub_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();

		$this->setExpectedException( '\InvalidArgumentException' );

		Activation::create( $stub_key, '' );
	}

	public function test_location_longer_than_191_is_rejected() {

		$stub_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();

		$this->setExpectedException( '\OverflowException' );

		Activation::create( $stub_key, str_repeat( '-', 192 ) );
	}

}