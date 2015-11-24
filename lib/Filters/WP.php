<?php
namespace MailPoet\Filters;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Segment;
use \MailPoet\Models\SubscriberSegment;

class WP {
  static function synchronizeUser($wp_user_id) {
    $wpUser = \get_userdata($wp_user_id);
    if($wpUser === false) return;

    $subscriber = Subscriber::where('wp_user_id', $wpUser->ID)
      ->findOne();

    switch(current_filter()) {
      case 'user_register':
      case 'added_existing_user':
      case 'profile_update':
        // get first name & last name
        $first_name = $wpUser->first_name;
        $last_name = $wpUser->last_name;
        if(empty($wpUser->first_name) && empty($wpUser->last_name)) {
          $first_name = $wpUser->display_name;
        }

        // subscriber data
        $data = array(
          'wp_user_id'=> $wpUser->ID,
          'email' => $wpUser->user_email,
          'first_name' => $first_name,
          'last_name' => $last_name,
          'status' => 'subscribed'
        );

        if($subscriber !== false) {
          $data['id'] = $subscriber->id();
        }
        $subscriber = Subscriber::createOrUpdate($data);

        if($subscriber !== false && $subscriber->id()) {
          $segment = Segment::getWPUsers();
          $segment->addSubscriber($subscriber->id());
        }
      break;

      case 'delete_user':
      case 'deleted_user':
      case 'remove_user_from_blog':
        if($subscriber !== false && $subscriber->id()) {
          $subscriber->delete();
        }
      break;
    }
  }
}