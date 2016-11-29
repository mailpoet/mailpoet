<?php
namespace MailPoet\Segments;

use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\Scheduler;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-includes/pluggable.php');

class WP {
  static function synchronizeUser($wp_user_id, $old_wp_user_data = false) {
    $wp_user = \get_userdata($wp_user_id);
    $wp_segment = Segment::getWPSegment();

    if($wp_user === false or $wp_segment === false) return;

    $subscriber = Subscriber::where('wp_user_id', $wp_user->ID)
      ->findOne();
    $schedule_welcome_newsletter = false;

    switch(current_filter()) {
      case 'delete_user':
      case 'deleted_user':
      case 'remove_user_from_blog':
        if($subscriber !== false) {
          // unlink subscriber to wp user
          $subscriber->setExpr('wp_user_id', 'NULL')->save();

          // delete subscription to wp segment
          SubscriberSegment::where('subscriber_id', $subscriber->id)
            ->where('segment_id', $wp_segment->id)
            ->deleteMany();
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
          'status' => Subscriber::STATUS_SUBSCRIBED,
        );

        if($subscriber !== false) {
          $data['id'] = $subscriber->id();
          $data['deleted_at'] = null; // remove the user from the trash
          unset($data['status']); // don't override status for existing users
        }

        $subscriber = Subscriber::createOrUpdate($data);
        if($subscriber->getErrors() === false && $subscriber->id > 0) {
          // add subscriber to the WP Users segment
          SubscriberSegment::subscribeToSegments(
            $subscriber,
            array($wp_segment->id)
          );

          // welcome email
          if($schedule_welcome_newsletter === true) {
            Scheduler::scheduleWPUserWelcomeNotification(
              $subscriber->id,
              (array)$wp_user,
              (array)$old_wp_user_data
            );
          }
        }
        break;
    }
  }

  static function synchronizeUsers() {
    // get wordpress users list
    $wp_segment = Segment::getWPSegment();

    // fetch all wp users id
    $wp_users = \get_users(array(
      'count_total'  => false,
      'fields' => 'ID'
    ));

    // update data for each wp user
    foreach($wp_users as $wp_user_id) {
      static::synchronizeUser($wp_user_id);
    }

    return true;
  }
}
