<?php
/**
 * Test the product endpoint.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Test_HTTP_API_Product
 */
class ITELIC_Test_HTTP_API_Product extends ITELIC_UnitTestCase {

	protected $key;
	protected $activation;

	/**
	 * Setup the object before each test.
	 */
	public function setUp() {
		parent::setUp();

		$mock_activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();

		$mock_release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$mock_release->method( 'get_version' )->willReturn( '1.2' );

		$mock_product             = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$mock_product->ID         = 1;
		$mock_product->post_title = 'Product Name';

		$mock_product->method( 'get_latest_release_for_activation' )->with( $mock_activation )->willReturn( $mock_release );
		$mock_product->method( 'get_changelog' )->willReturn( 'Changes' );
		$mock_product->method( 'get_feature' )->will( $this->returnValueMap( array(
			array( 'description', array(), 'This is the description.' ),
			array(
				'licensing-readme',
				array(),
				array(
					'author'       => 'User1,User2',
					'tested'       => '4.4',
					'requires'     => '4.3',
					'last_updated' => \ITELIC\make_date_time( '2014-12-31' ),
					'banner_low'   => 'www.example.com/low',
					'banner_high'  => 'www.example.com/high',
				)
			)
		) ) );

		$mock_key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$mock_key->method( 'get_product' )->willReturn( $mock_product );

		$this->key        = $mock_key;
		$this->activation = $mock_activation;
	}

	public function test_response_format() {

		WP_Mock::wpFunction( 'ITELIC\generate_download_link', array(
			'times'  => 1,
			'args'   => array( $this->activation ),
			'return' => 'www.example.com/download'
		) );

		$endpoint = new \ITELIC\API\Endpoint\Product();
		$endpoint->set_auth_license_key( $this->key );
		$endpoint->set_auth_activation( $this->activation );

		$response = $endpoint->serve( new ArrayObject(), new ArrayObject() );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'], "Response not marked as successful." );
		$this->assertInternalType( 'array', $data['body'], 'Response body is not an array.' );
		$this->assertArrayHasKey( 'list', $data['body'], 'Response does not contain the product list.' );
		$this->assertArrayHasKey( 1, $data['body']['list'], 'Product list does not contain the product from the license key.' );
	}
}