<?php
namespace MailPoet\Segments;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Segment;

class WP {
  static function synchronizeUser($wp_user_id) {
    $wpUser = \get_userdata($wp_user_id);
    $segment = Segment::getWPUsers();

    if($wpUser === false or $segment === false) return;

    $subscriber = Subscriber::where('wp_user_id', $wpUser->ID)
      ->findOne();

    switch(current_filter()) {
      case 'delete_user':
      case 'deleted_user':
      case 'remove_user_from_blog':
        if($subscriber !== false && $subscriber->id()) {
          $subscriber->delete();
        }
      break;

      case 'user_register':
      case 'added_existing_user':
      case 'profile_update':
      default:
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
          if($segment !== false) {
            $segment->addSubscriber($subscriber->id());
          }
        }
      break;
    }
  }

  static function synchronizeUsers() {
    // get wordpress users list
    $segment = Segment::getWPUsers();

    // count WP users
    $users_count = \count_users()['total_users'];
    $linked_subscribers_count = $segment->subscribers()->count();

    if($users_count !== $linked_subscribers_count) {
      $linked_subscribers = Subscriber::select('wp_user_id')
        ->whereNotNull('wp_user_id')
        ->findArray();

      $exclude_ids = array();
      if(!empty($linked_subscribers)) {
        $exclude_ids = array_map(function($subscriber) {
          return $subscriber['wp_user_id'];
        }, $linked_subscribers);
      }

      $users = \get_users(array(
        'count_total'  => false,
        'fields' => 'ID',
        'exclude' => $exclude_ids
      ));

      foreach($users as $user) {
        static::synchronizeUser($user);
      }
    }
    return true;
  }
}