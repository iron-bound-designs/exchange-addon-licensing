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

	public function test_status_updated_on_deactivation() {

		/** @var Activation $activation */
		$activation = $this->activation_factory->create_and_get( array(
			'location' => 'loc.com',
			'key'      => $this->key_factory->create_and_get( array(
				'customer' => 1,
				'product'  => $this->product_factory->create()
			) )
		) );

		$activation->deactivate();

		$this->assertEquals( Activation::DEACTIVATED, $activation->get_status() );
	}

	public function test_deactivation_date_set_on_deactivation() {

		/** @var Activation $activation */
		$activation = $this->activation_factory->create_and_get( array(
			'location' => 'loc.com',
			'key'      => $this->key_factory->create_and_get( array(
				'customer' => 1,
				'product'  => $this->product_factory->create()
			) )
		) );

		$activation->deactivate();

		$this->assertEquals( \ITELIC\make_date_time()->getTimestamp(), $activation->get_deactivation()->getTimestamp(), '', 5 );
	}

	public function test_deactivation_date_set_on_deactivation_with_data_parameter() {

		/** @var Activation $activation */
		$activation = $this->activation_factory->create_and_get( array(
			'location' => 'loc.com',
			'key'      => $this->key_factory->create_and_get( array(
				'customer' => 1,
				'product'  => $this->product_factory->create()
			) )
		) );

		$activation->deactivate( \ITELIC\make_date_time( '+1 month' ) );

		$this->assertEquals( \ITELIC\make_date_time( '+1 month' ), $activation->get_deactivation() );
	}

	public function test_reactivation_rejected_if_status_not_deactivated() {

		/** @var Activation $activation */
		$activation = $this->activation_factory->create_and_get( array(
			'location' => 'loc.com',
			'key'      => $this->key_factory->create_and_get( array(
				'customer' => 1,
				'product'  => $this->product_factory->create()
			) )
		) );

		$this->setExpectedException( '\UnexpectedValueException' );

		$activation->reactivate();
	}

	public function test_status_updated_on_reactivation() {

		/** @var Activation $activation */
		$activation = $this->activation_factory->create_and_get( array(
			'location' => 'loc.com',
			'status'   => Activation::DEACTIVATED,
			'key'      => $this->key_factory->create_and_get( array(
				'customer' => 1,
				'product'  => $this->product_factory->create()
			) )
		) );

		$activation->reactivate();

		$this->assertEquals( 'active', $activation->get_status() );
	}

	public function test_activation_date_updated_on_reactivation() {

		/** @var Activation $activation */
		$activation = $this->activation_factory->create_and_get( array(
			'location'   => 'loc.com',
			'status'     => Activation::DEACTIVATED,
			'activation' => \ITELIC\make_date_time( '-1 month' ),
			'key'        => $this->key_factory->create_and_get( array(
				'customer' => 1,
				'product'  => $this->product_factory->create()
			) )
		) );

		$activation->reactivate();

		$this->assertEquals( $activation->get_activation()->getTimestamp(), \ITELIC\make_date_time()->getTimestamp(), '', 5 );
	}

	/**
	 * @depends test_deactivation_date_set_on_deactivation
	 */
	public function test_deactivation_date_cleared_on_reactivation() {

		/** @var Activation $activation */
		$activation = $this->activation_factory->create_and_get( array(
			'location' => 'loc.com',
			'key'      => $this->key_factory->create_and_get( array(
				'customer' => 1,
				'product'  => $this->product_factory->create()
			) )
		) );

		$activation->deactivate();
		$activation->reactivate();

		$this->assertNull( $activation->get_deactivation() );
	}
}