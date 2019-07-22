<?php
namespace MailPoet\Segments;

use MailPoet\Config\Env;
use MailPoet\Models\ModelValidator;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class WooCommerce {
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  private $mailpoet_email_collation;
  private $wp_postmeta_value_collation;

  function __construct(SettingsController $settings, WPFunctions $wp) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  function synchronizeRegisteredCustomer($wp_user_id, $current_filter = null) {
    $wc_segment = Segment::getWooCommerceSegment();

    if ($wc_segment === false) return;

    $current_filter = $current_filter ?: $this->wp->currentFilter();
    switch ($current_filter) {
      case 'woocommerce_delete_customer':
        // subscriber should be already deleted in WP users sync
        $this->unsubscribeUsersFromSegment(); // remove leftover association
        break;
      case 'woocommerce_new_customer':
        $new_customer = true;
      case 'woocommerce_update_customer':
      default:
        $wp_user = $this->wp->getUserdata($wp_user_id);
        $subscriber = Subscriber::where('wp_user_id', $wp_user_id)
          ->findOne();

        if ($wp_user === false || $subscriber === false) {
          // registered customers should exist as WP users and WP segment subscribers
          return false;
        }

        $data = [
          'is_woocommerce_user' => 1,
        ];
        if (!empty($new_customer)) {
          $data['status'] = Subscriber::STATUS_SUBSCRIBED;
          $data['source'] = Source::WOOCOMMERCE_USER;
        }
        $data['id'] = $subscriber->id();
        $data['deleted_at'] = null; // remove the user from the trash

        $subscriber = Subscriber::createOrUpdate($data);
        if ($subscriber->getErrors() === false && $subscriber->id > 0) {
          // add subscriber to the WooCommerce Customers segment
          SubscriberSegment::subscribeToSegments(
            $subscriber,
            [$wc_segment->id]
          );
        }
        break;
    }

    return true;
  }

  function synchronizeGuestCustomer($order_id, $current_filter = null) {
    $wc_order = $this->wp->getPost($order_id);
    $wc_segment = Segment::getWooCommerceSegment();

    if ($wc_order === false or $wc_segment === false) return;

    $inserted_emails = $this->insertSubscribersFromOrders($order_id);
    if (empty($inserted_emails[0]['email'])) {
      return false;
    }
    $subscriber = Subscriber::where('email', $inserted_emails[0]['email'])
      ->findOne();

    if ($subscriber !== false) {
      // add subscriber to the WooCommerce Customers segment
      SubscriberSegment::subscribeToSegments(
        $subscriber,
        [$wc_segment->id]
      );
    }
  }

  function synchronizeCustomers() {
    $this->getColumnCollation();

    WP::synchronizeUsers(); // synchronize registered users

    $this->markRegisteredCustomers();
    $inserted_users_emails = $this->insertSubscribersFromOrders();
    $this->removeUpdatedSubscribersWithInvalidEmail($inserted_users_emails);
    $this->removeFromTrash();
    $this->updateFirstNames();
    $this->updateLastNames();
    $this->insertUsersToSegment();
    $this->unsubscribeUsersFromSegment();
    $this->removeOrphanedSubscribers();
    $this->updateStatus();

    return true;
  }

  private function getColumnCollation() {
    global $wpdb;
    $mailpoet_email_column = $wpdb->get_row(
      'SHOW FULL COLUMNS FROM ' . MP_SUBSCRIBERS_TABLE . ' WHERE Field = "email"'
    );
    $this->mailpoet_email_collation = $mailpoet_email_column->Collation;
    $wp_postmeta_value_column = $wpdb->get_row(
      'SHOW FULL COLUMNS FROM ' . $wpdb->postmeta . ' WHERE Field = "meta_value"'
    );
    $this->wp_postmeta_value_collation = $wp_postmeta_value_column->Collation;
  }

  private function needsCollationChange($collation1, $collation2) {
    if ($collation1 === $collation2) {
      return false;
    }
    $charset1 = substr($collation1, 0, strpos($collation1, '_'));
    $charset2 = substr($collation2, 0, strpos($collation2, '_'));
    return $charset1 === $charset2;
  }

  private function markRegisteredCustomers() {
    // Mark WP users having a customer role as WooCommerce subscribers
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE %1$s mps
        JOIN %2$s wu ON mps.wp_user_id = wu.id
        JOIN %3$s wpum ON wu.id = wpum.user_id AND wpum.meta_key = "' . $wpdb->prefix . 'capabilities"
      SET is_woocommerce_user = 1, source = "%4$s"
        WHERE wpum.meta_value LIKE "%%\"customer\"%%"
    ', $subscribers_table, $wpdb->users, $wpdb->usermeta, Source::WOOCOMMERCE_USER));
  }

  private function insertSubscribersFromOrders($order_id = null) {
    global $wpdb;
    $subscribers_table = Subscriber::$_table;
    $order_id = !is_null($order_id) ? (int)$order_id : null;

    $inserted_users_emails = \ORM::for_table($wpdb->users)->raw_query(
      'SELECT DISTINCT wppm.meta_value as email FROM `' . $wpdb->prefix . 'postmeta` wppm
        JOIN `' . $wpdb->prefix . 'posts` p ON wppm.post_id = p.ID AND p.post_type = "shop_order"
        WHERE wppm.meta_key = "_billing_email" AND wppm.meta_value != ""
        ' . ($order_id ? ' AND p.ID = "' . $order_id . '"' : '') . '
      ')->findArray();

    Subscriber::rawExecute(sprintf('
      INSERT IGNORE INTO %1$s (is_woocommerce_user, email, status, created_at, last_subscribed_at, source)
      SELECT 1, wppm.meta_value, "%2$s", CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), "%3$s" FROM `' . $wpdb->prefix . 'postmeta` wppm
        JOIN `' . $wpdb->prefix . 'posts` p ON wppm.post_id = p.ID AND p.post_type = "shop_order"
        WHERE wppm.meta_key = "_billing_email" AND wppm.meta_value != ""
        ' . ($order_id ? ' AND p.ID = "' . $order_id . '"' : '') . '
      ON DUPLICATE KEY UPDATE is_woocommerce_user = 1
    ', $subscribers_table, Subscriber::STATUS_SUBSCRIBED, Source::WOOCOMMERCE_USER));

    return $inserted_users_emails;
  }

  private function removeUpdatedSubscribersWithInvalidEmail($updated_emails) {
    $validator = new ModelValidator();
    $invalid_is_woocommerce_users = array_map(function($item) {
      return $item['email'];
    },
    array_filter($updated_emails, function($updated_email) use($validator) {
      return !$validator->validateEmail($updated_email['email']);
    }));
    if (!$invalid_is_woocommerce_users) {
      return;
    }
    \ORM::for_table(Subscriber::$_table)
      ->whereNull('wp_user_id')
      ->where('is_woocommerce_user', 1)
      ->whereIn('email', $invalid_is_woocommerce_users)
      ->delete_many();
  }

  private function updateFirstNames() {
    global $wpdb;
    $collate = '';
    if ($this->needsCollationChange($this->mailpoet_email_collation, $this->wp_postmeta_value_collation)) {
      $collate = ' COLLATE ' . $this->mailpoet_email_collation;
    }
    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE %1$s mps
        JOIN %2$s wppm ON mps.email = wppm.meta_value %3$s AND wppm.meta_key = "_billing_email"
        JOIN %2$s wppm2 ON wppm2.post_id = wppm.post_id AND wppm2.meta_key = "_billing_first_name"
        JOIN (SELECT MAX(post_id) AS max_id FROM %2$s WHERE meta_key = "_billing_email" GROUP BY meta_value) AS tmaxid ON tmaxid.max_id = wppm.post_id
      SET mps.first_name = wppm2.meta_value
        WHERE mps.first_name = ""
        AND mps.is_woocommerce_user = 1
        AND wppm2.meta_value IS NOT NULL
    ', $subscribers_table, $wpdb->postmeta, $collate));
  }

  private function updateLastNames() {
    global $wpdb;
    $collate = '';
    if ($this->needsCollationChange($this->mailpoet_email_collation, $this->wp_postmeta_value_collation)) {
      $collate = ' COLLATE ' . $this->mailpoet_email_collation;
    }
    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE %1$s mps
        JOIN %2$s wppm ON mps.email = wppm.meta_value %3$s AND wppm.meta_key = "_billing_email"
        JOIN %2$s wppm2 ON wppm2.post_id = wppm.post_id AND wppm2.meta_key = "_billing_last_name"
        JOIN (SELECT MAX(post_id) AS max_id FROM %2$s WHERE meta_key = "_billing_email" GROUP BY meta_value) AS tmaxid ON tmaxid.max_id = wppm.post_id
      SET mps.last_name = wppm2.meta_value
        WHERE mps.last_name = ""
        AND mps.is_woocommerce_user = 1
        AND wppm2.meta_value IS NOT NULL
    ', $subscribers_table, $wpdb->postmeta, $collate));
  }

  private function insertUsersToSegment() {
    $wc_segment = Segment::getWooCommerceSegment();
    $subscribers_table = Subscriber::$_table;
    $wp_mailpoet_subscriber_segment_table = SubscriberSegment::$_table;
    // Subscribe WC users to segment
    Subscriber::rawExecute(sprintf('
     INSERT IGNORE INTO %s (subscriber_id, segment_id, created_at)
      SELECT mps.id, "%s", CURRENT_TIMESTAMP() FROM %s mps
        WHERE mps.is_woocommerce_user = 1
    ', $wp_mailpoet_subscriber_segment_table, $wc_segment->id, $subscribers_table));
  }

  private function unsubscribeUsersFromSegment() {
    $wc_segment = Segment::getWooCommerceSegment();
    $subscribers_table = Subscriber::$_table;
    $wp_mailpoet_subscriber_segment_table = SubscriberSegment::$_table;
    // Unsubscribe non-WC or invalid users from segment
    Subscriber::rawExecute(sprintf('
     DELETE mpss FROM %s mpss
      LEFT JOIN %s mps ON mpss.subscriber_id = mps.id
        WHERE mpss.segment_id = %s AND (mps.is_woocommerce_user = 0 OR mps.email = "" OR mps.email IS NULL)
    ', $wp_mailpoet_subscriber_segment_table, $subscribers_table, $wc_segment->id));
  }

  private function removeFromTrash() {
    $subscribers_table = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE %1$s
      SET %1$s.deleted_at = NULL
        WHERE %1$s.is_woocommerce_user = 1
    ', $subscribers_table));
  }

  private function removeOrphanedSubscribers() {
    // Remove orphaned WooCommerce segment subscribers (not having a matching WC customer email),
    // e.g. if WC orders were deleted directly from the database
    // or a customer role was revoked and a user has no orders
    global $wpdb;

    $wc_segment = Segment::getWooCommerceSegment();

    // Unmark registered customers

    // Insert WC customer IDs to a temporary table for left join to use an index
    $tmp_table_name = Env::$db_prefix . 'tmp_wc_ids';
    // Registered users with orders
    Subscriber::rawExecute(sprintf('
      CREATE TEMPORARY TABLE %1$s
        (`id` int(11) unsigned NOT NULL, UNIQUE(`id`)) AS
      SELECT DISTINCT wppm.meta_value AS id FROM %2$s wppm
        JOIN %3$s wpp ON wppm.post_id = wpp.ID
        AND wpp.post_type = "shop_order"
        WHERE wppm.meta_key = "_customer_user"
    ', $tmp_table_name, $wpdb->postmeta, $wpdb->posts));
    // Registered users with a customer role
    Subscriber::rawExecute(sprintf('
      INSERT IGNORE INTO %1$s
      SELECT DISTINCT wpum.user_id AS id FROM %2$s wpum
      WHERE wpum.meta_key = "%3$s" AND wpum.meta_value LIKE "%%\"customer\"%%"
    ', $tmp_table_name, $wpdb->usermeta, $wpdb->prefix . 'capabilities'));

    // Unmark WC list registered users which aren't WC customers anymore
    Subscriber::tableAlias('mps')
      ->select('mps.*')
      ->join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        'mps.`id` = mpss.`subscriber_id` AND mpss.`segment_id` = "' . $wc_segment->id . '"',
        'mpss'
      )
      ->leftOuterJoin(
        $tmp_table_name,
        'mps.`wp_user_id` = wctmp.`id`',
        'wctmp'
      )
      ->where('is_woocommerce_user', 1)
      ->whereNull('wctmp.id')
      ->whereNotNull('wp_user_id')
      ->findResultSet()
      ->set('is_woocommerce_user', 0)
      ->save();

    Subscriber::rawExecute('DROP TABLE ' . $tmp_table_name);

    // Remove guest customers

    // Insert WC customer emails to a temporary table and ensure matching collations
    // between MailPoet and WooCommerce emails for left join to use an index
    $tmp_table_name = Env::$db_prefix . 'tmp_wc_emails';
    Subscriber::rawExecute(sprintf('
      CREATE TEMPORARY TABLE %1$s
        (`email` varchar(150) NOT NULL, UNIQUE(`email`)) COLLATE %2$s AS
      SELECT DISTINCT wppm.meta_value AS email FROM %3$s wppm
        JOIN %4$s wpp ON wppm.post_id = wpp.ID
        AND wpp.post_type = "shop_order"
        WHERE wppm.meta_key = "_billing_email"
    ', $tmp_table_name, $this->mailpoet_email_collation, $wpdb->postmeta, $wpdb->posts));

    // Remove WC list guest users which aren't WC customers anymore
    Subscriber::tableAlias('mps')
      ->select('mps.*')
      ->join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        'mps.`id` = mpss.`subscriber_id` AND mpss.`segment_id` = "' . $wc_segment->id . '"',
        'mpss'
      )
      ->leftOuterJoin(
        $tmp_table_name,
        'mps.`email` = wctmp.`email`',
        'wctmp'
      )
      ->where('is_woocommerce_user', 1)
      ->whereNull('wctmp.email')
      ->whereNull('wp_user_id')
      ->findResultSet()
      ->set('is_woocommerce_user', 0)
      ->delete();

    Subscriber::rawExecute('DROP TABLE ' . $tmp_table_name);
  }

  private function updateStatus() {
    $subscribe_old_customers = $this->settings->get('mailpoet_subscribe_old_woocommerce_customers.enabled', false);
    if ($subscribe_old_customers !== "1") {
      $status = Subscriber::STATUS_UNSUBSCRIBED;
    } else {
      $status = Subscriber::STATUS_SUBSCRIBED;
    }
    $subscribers_table = Subscriber::$_table;
    $subscriber_segment_table = SubscriberSegment::$_table;
    $wc_segment = Segment::getWooCommerceSegment();

    $sql = sprintf('
      UPDATE %1$s mpss
        JOIN %2$s mps ON mpss.subscriber_id = mps.id
      SET mpss.status = "%3$s"
        WHERE
          mpss.segment_id = %4$s
          AND mps.confirmed_at IS NULL
          AND mps.confirmed_ip IS NULL
          AND mps.is_woocommerce_user = 1
    ', $subscriber_segment_table, $subscribers_table, $status, $wc_segment->id);

    Subscriber::rawExecute($sql);
  }
}
