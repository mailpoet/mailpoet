<?php
namespace MailPoet\Segments;

use MailPoet\Models\ModelValidator;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;

if (!defined('ABSPATH')) exit;

class WP {
  static function synchronizeUser($wp_user_id, $old_wp_user_data = false) {
    $wp_user = \get_userdata($wp_user_id);
    $wp_segment = Segment::getWPSegment();

    if ($wp_user === false or $wp_segment === false) return;

    $subscriber = Subscriber::where('wp_user_id', $wp_user->ID)
      ->findOne();
    $schedule_welcome_newsletter = false;

    switch (current_filter()) {
      case 'delete_user':
      case 'deleted_user':
      case 'remove_user_from_blog':
        if ($subscriber !== false) {
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
        if (empty($wp_user->first_name) && empty($wp_user->last_name)) {
          $first_name = $wp_user->display_name;
        }
        $signup_confirmation_enabled = (new SettingsController())->get('signup_confirmation.enabled');
        // subscriber data
        $data = [
          'wp_user_id' => $wp_user->ID,
          'email' => $wp_user->user_email,
          'first_name' => $first_name,
          'last_name' => $last_name,
          'status' => $signup_confirmation_enabled ? Subscriber::STATUS_UNCONFIRMED : Subscriber::STATUS_SUBSCRIBED,
          'source' => Source::WORDPRESS_USER,
        ];

        if ($subscriber !== false) {
          $data['id'] = $subscriber->id();
          $data['deleted_at'] = null; // remove the user from the trash
          unset($data['status']); // don't override status for existing users
          unset($data['source']); // don't override status for existing users
        }

        $subscriber = Subscriber::createOrUpdate($data);
        if ($subscriber->getErrors() === false && $subscriber->id > 0) {
          // add subscriber to the WP Users segment
          SubscriberSegment::subscribeToSegments(
            $subscriber,
            [$wp_segment->id]
          );

          // welcome email
          if ($schedule_welcome_newsletter === true) {
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

    $updated_users_emails = self::updateSubscribersEmails();
    $inserted_users_emails = self::insertSubscribers();
    self::removeUpdatedSubscribersWithInvalidEmail(array_merge($updated_users_emails, $inserted_users_emails));
    self::removeFromTrash();
    self::updateFirstNames();
    self::updateLastNames();
    self::updateFirstNameIfMissing();
    self::insertUsersToSegment();
    self::removeOrphanedSubscribers();
    self::markSpammyWordpressUsersAsUnconfirmed();

    return true;
  }

  private static function removeUpdatedSubscribersWithInvalidEmail($updated_emails) {
    $validator = new ModelValidator();
    $invalid_wp_user_ids = array_map(function($item) {
      return $item['id'];
    },
    array_filter($updated_emails, function($updated_email) use($validator) {
      return !$validator->validateEmail($updated_email['email']);
    }));
    if (!$invalid_wp_user_ids) {
      return;
    }
    \ORM::for_table(Subscriber::$_table)->whereIn('wp_user_id', $invalid_wp_user_ids)->delete_many();
  }

  private static function updateSubscribersEmails() {
    global $wpdb;
    Subscriber::rawExecute('SELECT NOW();');
    $start_time = Subscriber::getLastStatement()->fetch(\PDO::FETCH_COLUMN);

    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE IGNORE %1$s
        INNER JOIN %2$s as wu ON %1$s.wp_user_id = wu.id
      SET %1$s.email = wu.user_email;
    ', $subscribers_table, $wpdb->users));

    return \ORM::for_table(Subscriber::$_table)->raw_query(sprintf(
      'SELECT wp_user_id as id, email FROM %s
        WHERE updated_at >= \'%s\';
      ', $subscribers_table, $start_time))->findArray();
  }

  private static function insertSubscribers() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    $signup_confirmation_enabled = (new SettingsController())->get('signup_confirmation.enabled');

    $inserterd_user_ids = \ORM::for_table($wpdb->users)->raw_query(sprintf(
      'SELECT %2$s.id, %2$s.user_email as email FROM %2$s
        LEFT JOIN %1$s AS mps ON mps.wp_user_id = %2$s.id
        WHERE mps.wp_user_id IS NULL AND %2$s.user_email != ""
      ', $subscribers_table, $wpdb->users))->findArray();

    Subscriber::rawExecute(sprintf(
      '
        INSERT IGNORE INTO %1$s(wp_user_id, email, status, created_at, source)
        SELECT wu.id, wu.user_email, "%4$s", CURRENT_TIMESTAMP(), "%3$s" FROM %2$s wu
          LEFT JOIN %1$s mps ON wu.id = mps.wp_user_id
          WHERE mps.wp_user_id IS NULL AND wu.user_email != ""
        ON DUPLICATE KEY UPDATE wp_user_id = wu.id
      ',
      $subscribers_table,
      $wpdb->users,
      Source::WORDPRESS_USER,
      $signup_confirmation_enabled ? Subscriber::STATUS_UNCONFIRMED : Subscriber::STATUS_SUBSCRIBED
    ));

    return $inserterd_user_ids;
  }

  private static function updateFirstNames() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE %1$s
        JOIN %2$s as wpum ON %1$s.wp_user_id = wpum.user_id AND wpum.meta_key = "first_name"
      SET %1$s.first_name = wpum.meta_value
        WHERE %1$s.first_name = ""
        AND %1$s.wp_user_id IS NOT NULL
        AND wpum.meta_value IS NOT NULL
    ', $subscribers_table, $wpdb->usermeta));
  }

  private static function updateLastNames() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE %1$s
        JOIN %2$s as wpum ON %1$s.wp_user_id = wpum.user_id AND wpum.meta_key = "last_name"
      SET %1$s.last_name = wpum.meta_value
        WHERE %1$s.last_name = ""
        AND %1$s.wp_user_id IS NOT NULL
        AND wpum.meta_value IS NOT NULL
    ', $subscribers_table, $wpdb->usermeta));
  }

  private static function updateFirstNameIfMissing() {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE %1$s
        JOIN %2$s wu ON %1$s.wp_user_id = wu.id
      SET %1$s.first_name = wu.display_name
        WHERE %1$s.first_name = ""
        AND %1$s.wp_user_id IS NOT NULL
    ', $subscribers_table, $wpdb->users));
  }

  private static function insertUsersToSegment() {
    $wp_segment = Segment::getWPSegment();
    $subscribers_table = Subscriber::$_table;
    $wp_mailpoet_subscriber_segment_table = SubscriberSegment::$_table;
    Subscriber::rawExecute(sprintf('
     INSERT IGNORE INTO %s(subscriber_id, segment_id, created_at)
      SELECT mps.id, "%s", CURRENT_TIMESTAMP() FROM %s mps
        WHERE mps.wp_user_id > 0
    ', $wp_mailpoet_subscriber_segment_table, $wp_segment->id, $subscribers_table));
  }

  private static function removeFromTrash() {
    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE %1$s
      SET %1$s.deleted_at = NULL
        WHERE %1$s.wp_user_id IS NOT NULL
    ', $subscribers_table));
  }

  private static function removeOrphanedSubscribers() {
    // remove orphaned wp segment subscribers (not having a matching wp user id),
    // e.g. if wp users were deleted directly from the database
    global $wpdb;

    $wp_segment = Segment::getWPSegment();

    $wp_segment->subscribers()
      ->leftOuterJoin($wpdb->users, [MP_SUBSCRIBERS_TABLE . '.wp_user_id', '=', 'wu.id'], 'wu')
      ->whereRaw('(wu.id IS NULL OR ' . MP_SUBSCRIBERS_TABLE . '.email = "")')
      ->findResultSet()
      ->set('wp_user_id', null)
      ->delete();
  }

  private static function markSpammyWordpressUsersAsUnconfirmed() {
    global $wpdb;
    $query = '
      UPDATE %s as subscribers
      LEFT JOIN %s as clicks ON subscribers.id=clicks.subscriber_id 
      LEFT JOIN %s as opens ON subscribers.id=opens.subscriber_id 
      JOIN %s as usermeta ON usermeta.user_id=subscribers.wp_user_id AND usermeta.meta_key = "default_password_nag" AND usermeta.meta_value = "1"
      SET `status` = "unconfirmed"
      WHERE `wp_user_id` IS NOT NULL AND `status` = "subscribed" AND `confirmed_at` IS NULL AND clicks.id IS NULL AND opens.id IS NULL
    ';
    $wpdb->query(sprintf($query, Subscriber::$_table, StatisticsClicks::$_table, StatisticsOpens::$_table, $wpdb->usermeta));


    $column_exists = $wpdb->query(sprintf('SHOW COLUMNS FROM `%s` LIKE "user_status"', $wpdb->users));
    if ($column_exists) {
      $query = '
      UPDATE %s as subscribers
      JOIN %s as users ON users.ID=subscribers.wp_user_id
      SET `status` = "unconfirmed"
      WHERE `status` = "subscribed" AND users.user_status = 2
    ';
      $wpdb->query(sprintf($query, Subscriber::$_table, $wpdb->users));
    }

  }
}
