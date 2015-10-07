<?php
/**
 * Test the version endpoint.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\API\Endpoint\Version;
use ITELIC\Release;

/**
 * Class ITELIC_Test_HTTP_API_Version
 */
class ITELIC_Test_HTTP_API_Version extends ITELIC_UnitTestCase {

	protected $product;
	protected $key;
	protected $activation;

	/**
	 * Do custom initialization.
	 */
	public function setUp() {
		parent::setUp();

		$this->product     = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$this->product->ID = 1;

		$this->key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$this->key->method( 'get_product' )->willReturn( $this->product );

		$this->activation = $this->getMockBuilder( '\ITELIC\Activation' )->disableOriginalConstructor()->getMock();
		$this->activation->method( 'get_key' )->willReturn( $this->key );
	}

	public function test_exception_thrown_if_no_release_available() {

		$version = new Version();

		$this->product->method( 'get_latest_release_for_activation' )->willReturn( null );

		$version->set_auth_license_key( $this->key );
		$version->set_auth_activation( $this->activation );

		$this->setExpectedException( '\UnexpectedValueException' );

		$version->serve( new ArrayObject(), new ArrayObject() );
	}

	public function test_response_format() {

		$version = new Version();

		$mock_release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$mock_release->method( 'get_type' )->willReturn( Release::TYPE_MINOR );
		$mock_release->method( 'get_version' )->willReturn( '1.2' );

		$this->product->method( 'get_latest_release_for_activation' )->willReturn( $mock_release );

		$version->set_auth_license_key( $this->key );
		$version->set_auth_activation( $this->activation );

		WP_Mock::wpFunction( 'ITELIC\generate_download_link', array(
			'times'  => 1,
			'args'   => array( $this->activation ),
			'return' => 'www.example.com/download'
		) );

		$response = $version->serve( new ArrayObject(), new ArrayObject() );
		$data     = $response->get_data();

		$this->assertTrue( $data['success'], "Response not marked as successful." );
		$this->assertInternalType( 'array', $data['body'], 'Response body is not an array.' );
		$this->assertArrayHasKey( 'list', $data['body'], 'Response does not contain the product list.' );
		$this->assertArrayHasKey( 1, $data['body']['list'], 'Product list does not contain the product from the license key.' );
	}


}