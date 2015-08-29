# WP Notifications
WP Notifications is a drop in framework for sending notifications with WordPress. It supports multiple sending strategies and batch processing via queues.

## Basic Usage

WP Notifications is easy, and straightforward to use.

### Sending a single notification

````php
$recipient = wp_get_current_user();
$manager = Factory::make( 'your-notification' );
$message = "Hey {first_name}, how's it going?";
$subject = "Hey!"

$notification = new Notification( $recipient, $manager, $message, $subject );
$notification->set_strategy( new WP_Mail() )->send();
````

### Sending multiple notifications

````php
$queue = new WP_Cron( new Options_Storage( 'your-project-name' ) );
$queue->process( $notifications, new WP_Mail() );
````

### Setting up template tag listeners
````php
add_action( 'ibd_wp_notifications_template_manager_your-notification', function( Manager $manager ) {
    // a template tag of {first_name} will be automatically replaced 
    // with the recipient's first name when sending.
    $manager->add_listener( new Listener( 'first_name', function( WP_User $recipient ) {
        return $recipient->first_name;
    } ) );
} );

````

## Supports
Strategies
 - WP_Mail
 - iThemes Exchange
 - Easy Digital Downloads
 - Mandrill
 
Queues
 - WP_Cron
 - Mandrill

## Installation
Via composer...
{todo}

## License
GPLv2 or Later