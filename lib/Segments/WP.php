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
          // unlink subscriber from wp user and delete
          $subscriber->set('wp_user_id', null);
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
    $s = microtime(true);

    // insert all wordpress users from wp table
    Subscriber::raw_execute('
      INSERT IGNORE INTO wp_mailpoet_subscribers(wp_user_id, email, status, created_at)
        SELECT wu.id, wu.user_email, "subscribed", CURRENT_TIMESTAMP() FROM wp_users wu
          LEFT JOIN wp_mailpoet_subscribers mps ON wu.id = mps.wp_user_id
          WHERE mps.wp_user_id IS NULL
    ');

    // update first name
    Subscriber::raw_execute('
      UPDATE wp_mailpoet_subscribers
        JOIN wp_usermeta ON wp_mailpoet_subscribers.wp_user_id = wp_usermeta.user_id AND meta_key = "first_name"
      SET first_name = meta_value
        WHERE wp_mailpoet_subscribers.first_name = ""
        AND wp_mailpoet_subscribers.wp_user_id IS NOT NULL
    ');

    // update last name
    Subscriber::raw_execute('
      UPDATE wp_mailpoet_subscribers
        JOIN wp_usermeta ON wp_mailpoet_subscribers.wp_user_id = wp_usermeta.user_id AND meta_key = "last_name"
      SET last_name = meta_value
        WHERE wp_mailpoet_subscribers.last_name = ""
        AND wp_mailpoet_subscribers.wp_user_id IS NOT NULL
    ');

    // use display name if first name is missing
    Subscriber::raw_execute('
      UPDATE wp_mailpoet_subscribers
        JOIN wp_users ON wp_mailpoet_subscribers.wp_user_id = wp_users.id
      SET first_name = display_name
        WHERE wp_mailpoet_subscribers.first_name = ""
        AND wp_mailpoet_subscribers.wp_user_id IS NOT NULL
    ');

    // insert users to the wp users list
    Subscriber::raw_execute(sprintf('
     INSERT IGNORE INTO wp_mailpoet_subscriber_segment(subscriber_id, segment_id, created_at)
      SELECT mps.id, "%s", CURRENT_TIMESTAMP() FROM wp_mailpoet_subscribers mps
        WHERE mps.wp_user_id IS NOT NULL
    ', $wp_segment->id));

    $e = microtime(true) - $s;

    file_put_contents('/tmp/whole', $e);
    $s = microtime(true);

    // fetch all wp users id
    $wp_users = \get_users(array(
      'count_total'  => false,
      'fields' => 'ID'
    ));

    // remove orphaned wp segment subscribers (not having a matching wp user id),
    // e.g. if wp users were deleted directly from the database
    $wp_segment->subscribers()
      ->whereNotIn('wp_user_id', $wp_users)
      ->findResultSet()
      ->set('wp_user_id', null)
      ->delete();
    $e = microtime(true) - $s;

    file_put_contents('/tmp/whole-e', $e);

    return true;
  }
}

