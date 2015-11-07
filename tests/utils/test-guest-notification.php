<?php
/**
 * Test the guest notification class.
 *
 * @author    Iron Bound Designs
 * @since     1.0
 * @license   AGPL
 * @copyright Iron Bound Designs, 2015.
 */

/**
 * Class ITELIC_Test_Utils_Guest_Notification
 *
 * @group guest
 */
class ITELIC_Test_Utils_Guest_Notification extends ITELIC_UnitTestCase {

	public function test_add_data_source() {

		$serializable = $this->getMock( '\Serializable' );

		$notification = $this->getMock( '\IronBound\WP_Notifications\Contract' );
		$notification->expects( $this->once() )->method( 'add_data_source' )->with( $serializable, 'name' )->willReturnSelf();

		$txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$guest = new \ITELIC\Utils\Guest_Notification( $notification, $txn );
		$self  = $guest->add_data_source( $serializable, 'name' );

		$this->assertSame( $guest, $self );
	}

	public function test_get_message() {

		$notification = $this->getMock( '\IronBound\WP_Notifications\Contract' );
		$notification->expects( $this->once() )->method( 'get_message' )->willReturn( 'message' );

		$txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$guest = new \ITELIC\Utils\Guest_Notification( $notification, $txn );

		$this->assertEquals( 'message', $guest->get_message() );
	}

	public function test_get_subject() {

		$notification = $this->getMock( '\IronBound\WP_Notifications\Contract' );
		$notification->expects( $this->once() )->method( 'get_subject' )->willReturn( 'subject' );

		$txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$guest = new \ITELIC\Utils\Guest_Notification( $notification, $txn );

		$this->assertEquals( 'subject', $guest->get_subject() );
	}

	public function test_get_tags() {

		$notification = $this->getMock( '\IronBound\WP_Notifications\Contract' );
		$notification->expects( $this->once() )->method( 'get_tags' )->willReturn( array(
			'{first_name}' => 'Name'
		) );

		$txn = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();

		$guest = new \ITELIC\Utils\Guest_Notification( $notification, $txn );

		$this->assertEquals( array( '{first_name}' => 'Name' ), $guest->get_tags() );
	}

	public function test_serialize() {

		$notification = $this->getMock( '\IronBound\WP_Notifications\Contract' );
		$notification->expects( $this->once() )->method( 'serialize' )->willReturn( serialize( array(
			'test' => 'data'
		) ) );

		$txn     = $this->getMockBuilder( '\IT_Exchange_Transaction' )->disableOriginalConstructor()->getMock();
		$txn->ID = 1;

		WP_Mock::wpFunction( 'it_exchange_get_transaction', array(
			'times'  => 1,
			'args'   => array( 1 ),
			'return' => $txn
		) );

		$guest = new \ITELIC\Utils\Guest_Notification( $notification, $txn );

		$serialized  = serialize( $guest );
		$unserialzed = unserialize( $serialized );

		$this->assertEquals( $guest, $unserialzed );
	}

}