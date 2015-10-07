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

	public function test_version() {

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

		$this->assertEquals( '1.2', $data['body']['list'][1]['version'] );
	}

	public function test_package() {

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

		$this->assertEquals( 'www.example.com/download', $data['body']['list'][1]['package'] );
	}

	public function test_expires() {

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

		$this->assertEquals( \ITELIC\make_date_time( '+1 day' )->getTimestamp(),
			\ITELIC\make_date_time( $data['body']['list'][1]['expires'] )->getTimestamp(), '', 5 );
	}

	public function test_type() {

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

		$this->assertEquals( Release::TYPE_MINOR, $data['body']['list'][1]['type'] );
	}

	public function test_upgrade_notice_for_major_release() {

		$version = new Version();

		$mock_release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$mock_release->method( 'get_type' )->willReturn( Release::TYPE_MAJOR );
		$mock_release->method( 'get_version' )->willReturn( '1.2' );
		$mock_release->expects( $this->never() )->method( 'get_meta' )->with( 'security-message' );

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

		$this->assertNotEmpty( $data['body']['list'][1]['upgrade_notice'] );
	}

	public function test_upgrade_notice_empty_for_minor_release() {

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

		$this->assertEmpty( $data['body']['list'][1]['upgrade_notice'] );
	}

	public function test_upgrade_notice_for_security_release() {

		$version = new Version();

		$mock_release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$mock_release->method( 'get_type' )->willReturn( Release::TYPE_SECURITY );
		$mock_release->method( 'get_version' )->willReturn( '1.2' );
		$mock_release->expects( $this->once() )->method( 'get_meta' )->with( 'security-message' )->willReturn( 'Security Now!' );

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

		$this->assertEquals( 'Security Now!', $data['body']['list'][1]['upgrade_notice'] );
	}

	public function test_upgrade_notice_for_pre_release() {

		$version = new Version();

		$mock_release = $this->getMockBuilder( '\ITELIC\Release' )->disableOriginalConstructor()->getMock();
		$mock_release->method( 'get_type' )->willReturn( Release::TYPE_PRERELEASE );
		$mock_release->method( 'get_version' )->willReturn( '1.2' );
		$mock_release->expects( $this->never() )->method( 'get_meta' )->with( 'security-message' );

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

		$this->assertEmpty( $data['body']['list'][1]['upgrade_notice'] );
	}

}