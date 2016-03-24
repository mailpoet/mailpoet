<?php
namespace MailPoet\Segments;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Segment;
use MailPoet\Newsletter\Scheduler\Scheduler;

class WP {
  static function synchronizeUser($wp_user_id, $old_wp_user_data = false) {
    $wp_user = \get_userdata($wp_user_id);
    $segment = Segment::getWPUsers();
    if($wp_user === false or $segment === false) return;
    $subscriber = Subscriber::where('wp_user_id', $wp_user->ID)
      ->findOne();
    switch(current_filter()) {
      case 'delete_user':
      case 'deleted_user':
      case 'remove_user_from_blog':
        if($subscriber !== false && $subscriber->id()) {
          $subscriber->delete();
        }
        break;
      case 'profile_update':
      case 'user_register':
        $schedule_welcome_newsletter = true;
      case 'added_existing_user':
      default:
        // get first name & last name
        $first_name = $wp_user->first_name;
        $last_name = $wp_user->last_name;
        if(empty($wp_user->first_name) && empty($wp_user->last_name)) {
          $first_name = $wp_user->display_name;
        }
        // subscriber data
        $data = array(
          'wp_user_id' => $wp_user->ID,
          'email' => $wp_user->user_email,
          'first_name' => $first_name,
          'last_name' => $last_name,
          'status' => 'subscribed'
        );

        if($subscriber !== false) {
          $data['id'] = $subscriber->id();
        }
        $subscriber = Subscriber::createOrUpdate($data);
        if($subscriber->getErrors() === false && $subscriber->id > 0) {
          if($segment !== false) {
            $segment->addSubscriber($subscriber->id);
          }
          if(isset($schedule_welcome_newsletter)) {
            Scheduler::welcomeForNewWPUser(
              $subscriber->id,
              (array) $wp_user,
              $old_wp_user_data
            );
          }
        }
        break;
    }
  }

  static function synchronizeUsers() {
    // get wordpress users list
    $segment = Segment::getWPUsers();

    // count WP users
    $users_count = \count_users();
    $linked_subscribers_count = $segment->subscribers()->count();

    if($users_count['total_users'] !== $linked_subscribers_count) {
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