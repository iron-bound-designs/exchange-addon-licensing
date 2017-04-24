<?php
/**
 * Tests for Product model.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\Product;
use ITELIC\Release;

/**
 * Class ITELIC_Test_Product
 */
class ITELIC_Test_Product extends ITELIC_UnitTestCase {

	public function test_getting_latest_release_for_stable_track() {

		/** @var Product $product */
		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r1 */
		$r1 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR,
		) );
		$r1->activate( \ITELIC\make_date_time( '-1 week' ) );

		/** @var Release $r2 */
		$r2 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR,
		) );

		$r2->activate( \ITELIC\make_date_time( '-2 days' ) );

		/** @var Release $r3 */
		$r3 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.2-alpha',
			'type'    => Release::TYPE_PRERELEASE,
		) );

		$key = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => 1
		) );

		$activation = $this->activation_factory->create_and_get( array(
			'key'      => $key,
			'location' => 'example.com'
		) );

		$latest = $product->get_latest_release_for_activation( $activation );

		$this->assertEquals( $r2->get_pk(), $latest->get_pk() );
	}

	public function test_getting_latest_release_for_pre_release_track() {

		/** @var Product $product */
		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r1 */
		$r1 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR,
		) );
		$r1->activate( \ITELIC\make_date_time( '-1 week' ) );

		/** @var Release $r2 */
		$r2 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR,
		) );

		$r2->activate( \ITELIC\make_date_time( '-2 days' ) );

		/** @var Release $r3 */
		$r3 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.2-alpha',
			'type'    => Release::TYPE_PRERELEASE,
		) );

		$r3->activate( \ITELIC\make_date_time( 'yesterday' ) );

		$key = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => 1
		) );

		$activation = $this->activation_factory->create_and_get( array(
			'key'      => $key,
			'location' => 'example.com',
			'track'    => 'pre-release'
		) );

		$latest = $product->get_latest_release_for_activation( $activation );

		$this->assertEquals( $r3->get_pk(), $latest->get_pk() );
	}
}
