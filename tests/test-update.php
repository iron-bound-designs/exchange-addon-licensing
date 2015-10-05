<?php
/**
 * Update model tests.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

use ITELIC\Release;
use ITELIC\Update;

/**
 * Class ITELIC_Test_Update
 */
class ITELIC_Test_Update extends ITELIC_UnitTestCase {

	public function test_previous_version_retrieved_from_activation_if_no_version_passed() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r1 */
		$r1 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR
		) );

		/** @var Release $r2 */
		$r2 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$key = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => 1
		) );

		$activation = $this->activation_factory->create_and_get( array(
			'key'     => $key,
			'release' => $r1->get_pk()
		) );

		/** @var Update $u */
		$u = $this->update_factory->create_and_get( array(
			'release'    => $r2,
			'activation' => $activation
		) );

		$this->assertEquals( '1.0', $u->get_previous_version() );
	}

	public function test_activation_release_updates_when_update_record_created() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r1 */
		$r1 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR
		) );

		/** @var Release $r2 */
		$r2 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$key = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => 1
		) );

		$activation = $this->activation_factory->create( array(
			'key'     => $key,
			'release' => $r1->get_pk()
		) );

		$this->update_factory->create_and_get( array(
			'release'    => $r2,
			'activation' => $activation
		) );

		$this->assertEquals( $r2->get_pk(), itelic_get_activation( $activation )->get_release()->get_pk() );
	}
}