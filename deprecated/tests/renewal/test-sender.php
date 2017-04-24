<?php
/**
 * Test the renewal reminder sender.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

use IronBound\WP_Notifications\Notification;
use IronBound\WP_Notifications\Strategy\Null as Null_Strategy;
use ITELIC\Renewal\Reminder;
use ITELIC\Renewal\Reminder\CPT;

/**
 * Class ITELIC_Test_Renewal_Sender
 */
class ITELIC_Test_Renewal_Sender extends ITELIC_UnitTestCase {

	/**
	 * @dataProvider listeners_data_provider
	 */
	public function test_registered_listeners( $listener ) {

		$manager = \IronBound\WP_Notifications\Template\Factory::make( 'itelic-renewal-reminder' );

		$this->assertNotInstanceOf( '\IronBound\WP_Notifications\Template\Null_Listener', $manager->get_listener( $listener ) );
	}

	public function listeners_data_provider() {
		return array(
			'key'                  => array( 'key' ),
			'key expiry date'      => array( 'key_expiry_date' ),
			'days from expiration' => array( 'key_days_from_expiry' ),
			'product name'         => array( 'product_name' ),
			'transaction order no' => array( 'transaction_order_number' ),
			'discount amount'      => array( 'discount_amount' ),
			'renewal link'         => array( 'renewal_link' )
		);
	}

	public function test_notifications_sent() {

		// unfortunately, there isn't a good way to go about this besides
		// creating a bunch of DB records and actually performing the query

		$before = $this->factory->post->create( array(
			'post_type' => CPT::TYPE
		) );
		update_post_meta( $before, '_itelic_renewal_reminder_days', 2 );
		update_post_meta( $before, '_itelic_renewal_reminder_boa', Reminder::TYPE_BEFORE );

		$after = $this->factory->post->create( array(
			'post_type' => CPT::TYPE
		) );
		update_post_meta( $after, '_itelic_renewal_reminder_days', 2 );
		update_post_meta( $after, '_itelic_renewal_reminder_boa', Reminder::TYPE_AFTER );

		$product = $this->product_factory->create( array(
			'interval'       => 'month',
			'interval-count' => 1
		) );

		$key_expires_3_days_before_now = $this->key_factory->create( array(
			'customer' => 1,
			'product'  => $product,
			'expires'  => \ITELIC\make_date_time( '-3 days' )
		) );

		$key_expires_2_days_before_now = $this->key_factory->create( array(
			'customer' => 1,
			'product'  => $product,
			'expires'  => \ITELIC\make_date_time( '-2 days' )
		) );

		$key_expires_1_days_before_now = $this->key_factory->create( array(
			'customer' => 1,
			'product'  => $product,
			'expires'  => \ITELIC\make_date_time( '-1 days' )
		) );

		$key_expires_now = $this->key_factory->create( array(
			'customer' => 1,
			'product'  => $product,
			'expires'  => \ITELIC\make_date_time()
		) );

		$key_expires_1_days_after_now = $this->key_factory->create( array(
			'customer' => 1,
			'product'  => $product,
			'expires'  => \ITELIC\make_date_time( '+1 days' )
		) );

		$key_expires_2_days_after_now = $this->key_factory->create( array(
			'customer' => 1,
			'product'  => $product,
			'expires'  => \ITELIC\make_date_time( '+2 days' )
		) );

		$key_expires_3_days_after_now = $this->key_factory->create( array(
			'customer' => 1,
			'product'  => $product,
			'expires'  => \ITELIC\make_date_time( '+3 days' )
		) );

		$keys = array(
			$key_expires_2_days_after_now  => $after,
			$key_expires_2_days_before_now => $before
		);

		$sender        = new Reminder\Sender();
		$notifications = $sender->get_notifications();

		foreach ( $notifications as $notification ) {

			$tags = $notification->get_tags();
			$key  = $tags['{key}'];

			if ( ! isset( $keys[ $key ] ) ) {
				$this->fail( 'Notification sent for invalid key.' );
			}

			$template = get_post( $keys[ $key ] );

			$this->assertEquals( $template->post_title, $notification->get_subject(),
				sprintf( 'Wrong renewal reminder used as template for key %s.', $key ) );

			unset( $keys[ $key ] );
		}

		$this->assertEmpty( $keys, "Not all keys received notifications." );
	}

	public function test_guest_notification_used_for_guest_purchase() {

		$reflection        = new ReflectionClass( '\ITELIC\Renewal\Reminder\Sender' );
		$make_notification = $reflection->getMethod( 'make_notification' );
		$make_notification->setAccessible( true );

		$sender = new Reminder\Sender();

		$reminder = $this->getMockBuilder( '\ITELIC\Renewal\Reminder' )->disableOriginalConstructor()->getMock();
		$reminder->expects( $this->once() )->method( 'get_post' )->willReturn( (object) array(
			'post_content' => 'Content',
			'post_title'   => 'Title'
		) );

		$customer          = $this->getMockBuilder( '\IT_Exchange_Customer' )->disableOriginalConstructor()->getMock();
		$customer->wp_user = $this->getMockBuilder( '\WP_User' )->disableOriginalConstructor()->getMock();

		$transaction               = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$transaction->cart_details = (object) array(
			'is_guest_checkout' => true
		);

		$product = $this->getMockBuilder( '\ITELIC\Product' )->disableOriginalConstructor()->getMock();
		$product->expects( $this->any() )->method( $this->anything() );

		$key = $this->getMockBuilder( '\ITELIC\Key' )->disableOriginalConstructor()->getMock();
		$key->expects( $this->once() )->method( 'get_customer' )->willReturn( $customer );
		$key->expects( $this->once() )->method( 'get_transaction' )->willReturn( $transaction );
		$key->expects( $this->any() )->method( 'get_product' )->willReturn( $product );

		$manager = $this->getMockBuilder( '\IronBound\WP_Notifications\Template\Manager' )->disableOriginalConstructor()->getMock();

		$notification = $make_notification->invoke( $sender, $reminder, $key, $manager );

		$this->assertInstanceOf( '\ITELIC\Utils\Guest_Notification', $notification );
	}

}