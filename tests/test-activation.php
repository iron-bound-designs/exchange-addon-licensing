<?php
/**
 * Unit tests for activation class.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\Activation;
use ITELIC\Key;

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

	public function test_activation_rejected_if_max_activations_reached() {

		$stub_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$stub_key->method( 'get_max' )->willReturn( 1 );
		$stub_key->method( 'get_active_count' )->willReturn( 1 );

		$this->setExpectedException( '\LogicException' );

		Activation::create( $stub_key, 'loc' );
	}

	public function test_activation_allowed_if_unlimited_activations() {

		/** @var Key $key */
		$key = $this->key_factory->create_and_get( array(
			'product'  => $this->product_factory->create(),
			'customer' => 1
		) );

		$key->set_max( '0' );

		$activation = Activation::create( $key, 'loc' );

		$this->assertInstanceOf( '\ITELIC\Activation', $activation );
	}
}