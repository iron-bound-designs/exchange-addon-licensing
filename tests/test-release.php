<?php
/**
 * Test release object.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\Release;

/**
 * Class ITELIC_Test_Release
 */
class ITELIC_Test_Release extends ITELIC_UnitTestCase {

	public function test_invalid_status_is_rejected() {

		$mock_product = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$mock_file    = new WP_Post( new stdClass() );

		$version = '';
		$type    = Release::TYPE_MAJOR;
		$status  = 'garbage';

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $mock_product, $mock_file, $version, $type, $status );
	}

	public function test_invalid_type_is_rejected() {

		$mock_product = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$mock_file    = new WP_Post( new stdClass() );

		$version = '';
		$type    = 'garbage';

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $mock_product, $mock_file, $version, $type );
	}

	public function test_invalid_post_type_for_file_is_rejected() {

		$mock_product         = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$mock_file            = new WP_Post( new stdClass() );
		$mock_file->post_type = 'not-attachment';

		$version = '';
		$type    = Release::TYPE_MAJOR;

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $mock_product, $mock_file, $version, $type );
	}

	public function test_product_with_licensing_feature_disabled_is_rejected() {

		$mock_product         = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$mock_file            = new WP_Post( new stdClass() );
		$mock_file->post_type = 'attachment';

		$version = '';
		$type    = Release::TYPE_MAJOR;

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $mock_product, $mock_file, $version, $type );
	}

}