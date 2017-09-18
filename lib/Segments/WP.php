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

    self::updateSubscribersEmails();
    self::insertSubscribers();
    self::removeFromTrash();
    self::updateFirstNames();
    self::updateLastNames();
    self::updateFristNameIfMissing();
    self::insertUsersToSegment();
    self::removeOrphanedSubscribers();

    return true;
  }

  private static function updateSubscribersEmails() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::raw_execute(sprintf('
      UPDATE IGNORE %s
        JOIN %s as wu ON %s.wp_user_id = wu.id
      SET email = user_email
        WHERE %s.wp_user_id IS NOT NULL
    ', $subscribers_table, $wpdb->users, $subscribers_table, $subscribers_table));
  }

  private static function insertSubscribers() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::raw_execute(sprintf('
      INSERT IGNORE INTO %s(wp_user_id, email, status, created_at)
        SELECT wu.id, wu.user_email, "subscribed", CURRENT_TIMESTAMP() FROM %s wu
          LEFT JOIN %s mps ON wu.id = mps.wp_user_id
          WHERE mps.wp_user_id IS NULL
    ', $subscribers_table, $wpdb->users, $subscribers_table));
  }

  private static function updateFirstNames() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::raw_execute(sprintf('
      UPDATE %s
        JOIN %s as wpum ON %s.wp_user_id = wpum.user_id AND meta_key = "first_name"
      SET first_name = meta_value
        WHERE %s.first_name = ""
        AND %s.wp_user_id IS NOT NULL
    ', $subscribers_table, $wpdb->usermeta, $subscribers_table, $subscribers_table, $subscribers_table));
  }

  private static function updateLastNames() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::raw_execute(sprintf('
      UPDATE %s
        JOIN %s as wpum ON %s.wp_user_id = wpum.user_id AND meta_key = "last_name"
      SET last_name = meta_value
        WHERE %s.last_name = ""
        AND %s.wp_user_id IS NOT NULL
    ', $subscribers_table, $wpdb->usermeta, $subscribers_table, $subscribers_table, $subscribers_table));
  }

  private static function updateFristNameIfMissing() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::raw_execute(sprintf('
      UPDATE %s
        JOIN %s wu ON %s.wp_user_id = wu.id
      SET first_name = display_name
        WHERE %s.first_name = ""
        AND %s.wp_user_id IS NOT NULL
    ', $subscribers_table, $wpdb->users, $subscribers_table, $subscribers_table, $subscribers_table));
  }

  private static function insertUsersToSegment() {
    $wp_segment = Segment::getWPSegment();
    $subscribers_table = Subscriber::$_table;
    $wp_mailpoet_subscriber_segment_table = SubscriberSegment::$_table;
    Subscriber::raw_execute(sprintf('
     INSERT IGNORE INTO %s(subscriber_id, segment_id, created_at)
      SELECT mps.id, "%s", CURRENT_TIMESTAMP() FROM %s mps
        WHERE mps.wp_user_id > 0
    ', $wp_mailpoet_subscriber_segment_table, $wp_segment->id, $subscribers_table));
  }

  private static function removeFromTrash() {
    $subscribers_table = Subscriber::$_table;
    Subscriber::raw_execute(sprintf('
      UPDATE %s
      SET deleted_at = NULL
        WHERE %s.wp_user_id IS NOT NULL
    ', $subscribers_table, $subscribers_table));
  }

  private static function removeOrphanedSubscribers() {
    // remove orphaned wp segment subscribers (not having a matching wp user id),
    // e.g. if wp users were deleted directly from the database
    global $wpdb;

    $wp_segment = Segment::getWPSegment();

    $wp_segment->subscribers()
      ->leftOuterJoin($wpdb->users, array(MP_SUBSCRIBERS_TABLE . '.wp_user_id', '=', 'wu.id'), 'wu')
      ->whereNull('wu.id')
      ->findResultSet()
      ->set('wp_user_id', null)
      ->delete();
  }
}
