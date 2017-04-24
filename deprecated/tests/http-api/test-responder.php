<?php
/**
 * Test the responder class.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Test_HTTP_API_Responder
 */
class ITELIC_Test_HTTP_API_Responder extends ITELIC_UnitTestCase {

	public function test_prepare_response_scalar() {

		/** @var \ITELIC\API\Responder\Responder $stub */
		$stub = $this->getMockForAbstractClass( '\ITELIC\API\Responder\Responder' );

		$this->assertTrue( $stub->prepare_response( true ), 'boolean true' );
		$this->assertFalse( $stub->prepare_response( false ), 'boolean false' );
		$this->assertEquals( 1, $stub->prepare_response( 1 ), 'integer' );
		$this->assertEquals( 5.3, $stub->prepare_response( 5.3 ), 'float' );
		$this->assertEquals( 'test', $stub->prepare_response( 'test' ), 'string' );
		$this->assertEquals( null, $stub->prepare_response( null ), 'null' );
	}

	public function test_prepare_response_array() {

		$data = array(
			true,
			false,
			1,
			5.3,
			'test',
			null
		);

		/** @var \ITELIC\API\Responder\Responder $stub */
		$stub = $this->getMockForAbstractClass( '\ITELIC\API\Responder\Responder' );

		$this->assertEquals( $data, $stub->prepare_response( $data ) );
	}

	public function test_prepare_response_stdClass() {

		$data = (object) array(
			true,
			false,
			1,
			5.3,
			'test',
			null
		);

		/** @var \ITELIC\API\Responder\Responder $stub */
		$stub = $this->getMockForAbstractClass( '\ITELIC\API\Responder\Responder' );

		$this->assertEquals( (array) $data, $stub->prepare_response( $data ) );
	}

	public function test_prepare_response_object() {

		$post = new WP_Post( (object) array(
			'ID'         => 1,
			'post_title' => 'Title'
		) );

		/** @var \ITELIC\API\Responder\Responder $stub */
		$stub = $this->getMockForAbstractClass( '\ITELIC\API\Responder\Responder' );

		$response = $stub->prepare_response( $post );

		$this->assertEquals( 1, $response['ID'] );
		$this->assertEquals( 'Title', $response['post_title'] );
	}

	public function test_prepare_response_serializable() {

		$serializable = $this->getMockBuilder( '\ITELIC\API\Serializable' )->getMock();
		$serializable->method( 'get_api_data' )->willReturn( array(
			'ID'      => 1,
			'product' => 'Product Name'
		) );

		/** @var \ITELIC\API\Responder\Responder $stub */
		$stub = $this->getMockForAbstractClass( '\ITELIC\API\Responder\Responder' );

		$response = $stub->prepare_response( $serializable );

		$this->assertEquals( 1, $response['ID'] );
		$this->assertEquals( 'Product Name', $response['product'] );
	}
}