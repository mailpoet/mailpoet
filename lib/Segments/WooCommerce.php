<?php

namespace MailPoet\Segments;

use MailPoet\Config\Env;
use MailPoet\Models\ModelValidator;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

class WooCommerce {
  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var WP */
  private $wpSegment;

  /** @var string|null */
  private $mailpoetEmailCollation;

  /** @var string|null */
  private $wpPostmetaValueCollation;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var WCHelper */
  private $woocommerceHelper;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    WCHelper $woocommerceHelper,
    SubscribersRepository $subscribersRepository,
    WP $wpSegment
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->wpSegment = $wpSegment;
    $this->subscribersRepository = $subscribersRepository;
    $this->woocommerceHelper = $woocommerceHelper;
  }

  public function shouldShowWooCommerceSegment() {
    $isWoocommerceActive = $this->woocommerceHelper->isWooCommerceActive();
    $woocommerceUserExists = $this->subscribersRepository->woocommerceUserExists();

    if (!$isWoocommerceActive && !$woocommerceUserExists) {
      return false;
    }
    return true;
  }

  public function synchronizeRegisteredCustomer($wpUserId, $currentFilter = null) {
    $wcSegment = Segment::getWooCommerceSegment();

    if ($wcSegment === false) return;

    $currentFilter = $currentFilter ?: $this->wp->currentFilter();
    switch ($currentFilter) {
      case 'woocommerce_delete_customer':
        // subscriber should be already deleted in WP users sync
        $this->unsubscribeUsersFromSegment(); // remove leftover association
        break;
      case 'woocommerce_new_customer':
      case 'woocommerce_created_customer':
        $newCustomer = true;
      case 'woocommerce_update_customer':
      default:
        $wpUser = $this->wp->getUserdata($wpUserId);
        $subscriber = Subscriber::where('wp_user_id', $wpUserId)
          ->findOne();

        if ($wpUser === false || $subscriber === false) {
          // registered customers should exist as WP users and WP segment subscribers
          return false;
        }

        $data = [
          'is_woocommerce_user' => 1,
        ];
        if (!empty($newCustomer)) {
          $data['source'] = Source::WOOCOMMERCE_USER;
        }
        $data['id'] = $subscriber->id();
        if ($wpUser->first_name) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          $data['first_name'] = $wpUser->first_name; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        }
        if ($wpUser->last_name) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          $data['last_name'] = $wpUser->last_name; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        }
        $subscriber = Subscriber::createOrUpdate($data);
        if ($subscriber->getErrors() === false && $subscriber->id > 0) {
          // add subscriber to the WooCommerce Customers segment
          SubscriberSegment::subscribeToSegments(
            $subscriber,
            [$wcSegment->id]
          );
        }
        break;
    }

    return true;
  }

  public function synchronizeGuestCustomer($orderId) {
    $wcOrder = $this->woocommerceHelper->wcGetOrder($orderId);
    $wcSegment = Segment::getWooCommerceSegment();

    if ((!$wcOrder instanceof \WC_Order) || $wcSegment === false) return;
    $signupConfirmation = $this->settings->get('signup_confirmation');
    $status = Subscriber::STATUS_UNCONFIRMED;
    if ((bool)$signupConfirmation['enabled'] === false) {
      $status = Subscriber::STATUS_SUBSCRIBED;
    }

    $insertedEmails = $this->insertSubscribersFromOrders($orderId, $status);

    if (empty($insertedEmails[0]['email'])) {
      return false;
    }
    $subscriber = Subscriber::where('email', $insertedEmails[0]['email'])
      ->findOne();

    if ($subscriber !== false) {
      $firstName = $wcOrder->get_billing_first_name(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $lastName = $wcOrder->get_billing_last_name(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      if ($firstName) {
        $subscriber->firstName = $firstName;
      }
      if ($lastName) {
        $subscriber->lastName = $lastName;
      }
      if ($firstName || $lastName) {
        $subscriber->save();
      }
      // add subscriber to the WooCommerce Customers segment
      SubscriberSegment::subscribeToSegments(
        $subscriber,
        [$wcSegment->id]
      );
    }
  }

  public function synchronizeCustomers() {
    $this->wpSegment->synchronizeUsers(); // synchronize registered users

    $this->markRegisteredCustomers();
    $insertedUsersEmails = $this->insertSubscribersFromOrders();
    $this->removeUpdatedSubscribersWithInvalidEmail($insertedUsersEmails);
    unset($insertedUsersEmails);
    $this->updateFirstNames();
    $this->updateLastNames();
    $this->insertUsersToSegment();
    $this->unsubscribeUsersFromSegment();
    $this->removeOrphanedSubscribers();
    $this->updateStatus();
    $this->updateGlobalStatus();

    return true;
  }

  private function ensureColumnCollation(): void {
    if ($this->mailpoetEmailCollation && $this->wpPostmetaValueCollation) {
      return;
    }
    global $wpdb;
    $mailpoetEmailColumn = $wpdb->get_row(
      'SHOW FULL COLUMNS FROM ' . MP_SUBSCRIBERS_TABLE . ' WHERE Field = "email"'
    );
    $this->mailpoetEmailCollation = $mailpoetEmailColumn->Collation; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $wpPostmetaValueColumn = $wpdb->get_row(
      'SHOW FULL COLUMNS FROM ' . $wpdb->postmeta . ' WHERE Field = "meta_value"'
    );
    $this->wpPostmetaValueCollation = $wpPostmetaValueColumn->Collation; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  /**
   * In MySQL, if you have the same charset and collation in joined tables' columns it's perfect;
   * if you have different charsets, utf8 and utf8mb4, it works too; but if you have the same charset
   * with different collations, e.g. utf8mb4_unicode_ci and utf8mb4_unicode_520_ci, it will fail
   * with an 'Illegal mix of collations' error. That's why we need an optional COLLATE clause to fix this.
   */
  private function needsCollationChange(): bool {
    $this->ensureColumnCollation();
    $collation1 = (string)$this->mailpoetEmailCollation;
    $collation2 = (string)$this->wpPostmetaValueCollation;

    if ($collation1 === $collation2) {
      return false;
    }
    list($charset1) = explode('_', $collation1);
    list($charset2) = explode('_', $collation2);

    return $charset1 === $charset2;
  }

  private function markRegisteredCustomers() {
    // Mark WP users having a customer role as WooCommerce subscribers
    global $wpdb;
    $subscribersTable = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE LOW_PRIORITY %1$s mps
        JOIN %2$s wu ON mps.wp_user_id = wu.id
        JOIN %3$s wpum ON wu.id = wpum.user_id AND wpum.meta_key = "' . $wpdb->prefix . 'capabilities"
      SET is_woocommerce_user = 1, source = "%4$s"
        WHERE wpum.meta_value LIKE "%%\"customer\"%%"
    ', $subscribersTable, $wpdb->users, $wpdb->usermeta, Source::WOOCOMMERCE_USER));
  }

  private function insertSubscribersFromOrders($orderId = null, $status = Subscriber::STATUS_SUBSCRIBED) {
    global $wpdb;
    $subscribersTable = Subscriber::$_table;
    $orderId = !is_null($orderId) ? (int)$orderId : null;

    $insertedUsersEmails = ORM::for_table($wpdb->users)->raw_query(
      'SELECT DISTINCT wppm.meta_value as email FROM `' . $wpdb->prefix . 'postmeta` wppm
        JOIN `' . $wpdb->prefix . 'posts` p ON wppm.post_id = p.ID AND p.post_type = "shop_order"
        WHERE wppm.meta_key = "_billing_email" AND wppm.meta_value != ""
        ' . ($orderId ? ' AND p.ID = "' . $orderId . '"' : '') . '
      ')->findArray();

    Subscriber::rawExecute(sprintf('
      INSERT IGNORE INTO %1$s (is_woocommerce_user, email, status, created_at, last_subscribed_at, source)
      SELECT 1, wppm.meta_value, "%2$s", CURRENT_TIMESTAMP(), CURRENT_TIMESTAMP(), "%3$s" FROM `' . $wpdb->prefix . 'postmeta` wppm
        JOIN `' . $wpdb->prefix . 'posts` p ON wppm.post_id = p.ID AND p.post_type = "shop_order"
        WHERE wppm.meta_key = "_billing_email" AND wppm.meta_value != ""
        ' . ($orderId ? ' AND p.ID = "' . $orderId . '"' : '') . '
      ON DUPLICATE KEY UPDATE is_woocommerce_user = 1
    ', $subscribersTable, $status, Source::WOOCOMMERCE_USER));

    return $insertedUsersEmails;
  }

  private function removeUpdatedSubscribersWithInvalidEmail($updatedEmails) {
    $validator = new ModelValidator();
    $invalidIsWoocommerceUsers = array_map(function($item) {
      return $item['email'];
    },
    array_filter($updatedEmails, function($updatedEmail) use($validator) {
      return !$validator->validateEmail($updatedEmail['email']);
    }));
    if (!$invalidIsWoocommerceUsers) {
      return;
    }
    ORM::for_table(Subscriber::$_table)
      ->whereNull('wp_user_id')
      ->where('is_woocommerce_user', 1)
      ->whereIn('email', $invalidIsWoocommerceUsers)
      ->delete_many();
  }

  private function updateFirstNames() {
    global $wpdb;
    $collate = '';
    if ($this->needsCollationChange()) {
      $collate = ' COLLATE ' . $this->mailpoetEmailCollation;
    }
    $subscribersTable = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE LOW_PRIORITY %1$s mps
        JOIN %2$s wppm ON mps.email = wppm.meta_value %3$s AND wppm.meta_key = "_billing_email"
        JOIN %2$s wppm2 ON wppm2.post_id = wppm.post_id AND wppm2.meta_key = "_billing_first_name"
        JOIN (SELECT MAX(post_id) AS max_id FROM %2$s WHERE meta_key = "_billing_email" GROUP BY meta_value) AS tmaxid ON tmaxid.max_id = wppm.post_id
      SET mps.first_name = wppm2.meta_value
        WHERE  mps.first_name = ""
        AND mps.is_woocommerce_user = 1
        AND wppm2.meta_value IS NOT NULL
    ', $subscribersTable, $wpdb->postmeta, $collate));
  }

  private function updateLastNames() {
    global $wpdb;
    $collate = '';
    if ($this->needsCollationChange()) {
      $collate = ' COLLATE ' . $this->mailpoetEmailCollation;
    }
    $subscribersTable = Subscriber::$_table;
    Subscriber::rawExecute(sprintf('
      UPDATE LOW_PRIORITY %1$s mps
        JOIN %2$s wppm ON mps.email = wppm.meta_value %3$s AND wppm.meta_key = "_billing_email"
        JOIN %2$s wppm2 ON wppm2.post_id = wppm.post_id AND wppm2.meta_key = "_billing_last_name"
        JOIN (SELECT MAX(post_id) AS max_id FROM %2$s WHERE meta_key = "_billing_email" GROUP BY meta_value) AS tmaxid ON tmaxid.max_id = wppm.post_id
      SET mps.last_name = wppm2.meta_value
        WHERE mps.last_name = ""
        AND mps.is_woocommerce_user = 1
        AND wppm2.meta_value IS NOT NULL
    ', $subscribersTable, $wpdb->postmeta, $collate));
  }

  private function insertUsersToSegment() {
    $wcSegment = Segment::getWooCommerceSegment();
    $subscribersTable = Subscriber::$_table;
    $wpMailpoetSubscriberSegmentTable = SubscriberSegment::$_table;
    // Subscribe WC users to segment
    Subscriber::rawExecute(sprintf('
     INSERT IGNORE INTO %s (subscriber_id, segment_id, created_at)
      SELECT mps.id, "%s", CURRENT_TIMESTAMP() FROM %s mps
        WHERE mps.is_woocommerce_user = 1
    ', $wpMailpoetSubscriberSegmentTable, $wcSegment->id, $subscribersTable));
  }

  private function unsubscribeUsersFromSegment() {
    $wcSegment = Segment::getWooCommerceSegment();
    $subscribersTable = Subscriber::$_table;
    $wpMailpoetSubscriberSegmentTable = SubscriberSegment::$_table;
    // Unsubscribe non-WC or invalid users from segment
    Subscriber::rawExecute(sprintf('
     DELETE mpss FROM %s mpss
      LEFT JOIN %s mps ON mpss.subscriber_id = mps.id
        WHERE mpss.segment_id = %s AND (mps.is_woocommerce_user = 0 OR mps.email = "" OR mps.email IS NULL)
    ', $wpMailpoetSubscriberSegmentTable, $subscribersTable, $wcSegment->id));
  }

  private function updateGlobalStatus() {
    $subscribersTable = Subscriber::$_table;
    $subscriberSegmentTable = SubscriberSegment::$_table;
    $wcSegment = Segment::getWooCommerceSegment();
    // Set global status unsubscribed to all woocommerce users without any segment
    $sql = sprintf('
      UPDATE %1$s mps
        LEFT JOIN %2$s mpss ON mpss.subscriber_id = mps.id
      SET mps.status = "unsubscribed"
        WHERE
          mpss.id IS NULL
          AND mps.is_woocommerce_user = 1
    ', $subscribersTable, $subscriberSegmentTable);
    Subscriber::rawExecute($sql);
    // SET global status unsubscribed to all woocommerce users who have only 1 segment and it is woocommerce segment and they are not subscribed
    // You can't specify target table 'mps' for update in FROM clause
    $sql = sprintf('
      UPDATE %1$s as mps
        JOIN %2$s as mpss on mps.id = mpss.subscriber_id AND mpss.segment_id = "%3$s" AND mpss.status = "unsubscribed"
      SET mps.status = "unsubscribed"
        WHERE mps.id IN (
          SELECT s.id -- get all subscribers with exactly 1 list
            FROM ( SELECT id FROM %1$s WHERE is_woocommerce_user = 1) as s
            JOIN %2$s as l on s.id=l.subscriber_id
            GROUP BY s.id
            HAVING COUNT(l.id) = 1
        )
    ', $subscribersTable, $subscriberSegmentTable, $wcSegment->id);
    Subscriber::rawExecute($sql);
  }

  private function removeOrphanedSubscribers() {
    // Remove orphaned WooCommerce segment subscribers (not having a matching WC customer email),
    // e.g. if WC orders were deleted directly from the database
    // or a customer role was revoked and a user has no orders
    global $wpdb;

    $wcSegment = Segment::getWooCommerceSegment();

    // Unmark registered customers

    // Insert WC customer IDs to a temporary table for left join to use an index
    $tmpTableName = Env::$dbPrefix . 'tmp_wc_ids';
    // Registered users with orders
    Subscriber::rawExecute(sprintf('
      CREATE TEMPORARY TABLE %1$s
        (`id` int(11) unsigned NOT NULL, UNIQUE(`id`)) AS
      SELECT DISTINCT wppm.meta_value AS id FROM %2$s wppm
        JOIN %3$s wpp ON wppm.post_id = wpp.ID
        AND wpp.post_type = "shop_order"
        WHERE wppm.meta_key = "_customer_user"
    ', $tmpTableName, $wpdb->postmeta, $wpdb->posts));
    // Registered users with a customer role
    Subscriber::rawExecute(sprintf('
      INSERT IGNORE INTO %1$s
      SELECT DISTINCT wpum.user_id AS id FROM %2$s wpum
      WHERE wpum.meta_key = "%3$s" AND wpum.meta_value LIKE "%%\"customer\"%%"
    ', $tmpTableName, $wpdb->usermeta, $wpdb->prefix . 'capabilities'));

    // Unmark WC list registered users which aren't WC customers anymore
    Subscriber::tableAlias('mps')
      ->select('mps.*')
      ->join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        'mps.`id` = mpss.`subscriber_id` AND mpss.`segment_id` = "' . $wcSegment->id . '"',
        'mpss'
      )
      ->leftOuterJoin(
        $tmpTableName,
        'mps.`wp_user_id` = wctmp.`id`',
        'wctmp'
      )
      ->where('is_woocommerce_user', 1)
      ->whereNull('wctmp.id')
      ->whereNotNull('wp_user_id')
      ->findResultSet()
      ->set('is_woocommerce_user', 0)
      ->save();

    Subscriber::rawExecute('DROP TABLE ' . $tmpTableName);

    // Remove guest customers

    // Insert WC customer emails to a temporary table and ensure matching collations
    // between MailPoet and WooCommerce emails for left join to use an index
    $tmpTableName = Env::$dbPrefix . 'tmp_wc_emails';
    Subscriber::rawExecute(sprintf('
      CREATE TEMPORARY TABLE %1$s
        (`email` varchar(150) NOT NULL, UNIQUE(`email`)) COLLATE %2$s AS
      SELECT DISTINCT wppm.meta_value AS email FROM %3$s wppm
        JOIN %4$s wpp ON wppm.post_id = wpp.ID
        AND wpp.post_type = "shop_order"
        WHERE wppm.meta_key = "_billing_email"
    ', $tmpTableName, $this->mailpoetEmailCollation, $wpdb->postmeta, $wpdb->posts));

    // Remove WC list guest users which aren't WC customers anymore
    Subscriber::tableAlias('mps')
      ->select('mps.*')
      ->join(
        MP_SUBSCRIBER_SEGMENT_TABLE,
        'mps.`id` = mpss.`subscriber_id` AND mpss.`segment_id` = "' . $wcSegment->id . '"',
        'mpss'
      )
      ->leftOuterJoin(
        $tmpTableName,
        'mps.`email` = wctmp.`email`',
        'wctmp'
      )
      ->where('is_woocommerce_user', 1)
      ->whereNull('wctmp.email')
      ->whereNull('wp_user_id')
      ->findResultSet()
      ->set('is_woocommerce_user', 0)
      ->delete();

    Subscriber::rawExecute('DROP TABLE ' . $tmpTableName);
  }

  private function updateStatus() {
    $subscribeOldCustomers = $this->settings->get('mailpoet_subscribe_old_woocommerce_customers.enabled', false);
    if ($subscribeOldCustomers !== "1") {
      $status = Subscriber::STATUS_UNSUBSCRIBED;
    } else {
      $status = Subscriber::STATUS_SUBSCRIBED;
    }
    $subscribersTable = Subscriber::$_table;
    $subscriberSegmentTable = SubscriberSegment::$_table;
    $wcSegment = Segment::getWooCommerceSegment();

    $sql = sprintf('
      UPDATE LOW_PRIORITY %1$s mpss
        JOIN %2$s mps ON mpss.subscriber_id = mps.id
      SET mpss.status = "%3$s"
        WHERE
          mpss.segment_id = %4$s
          AND mps.confirmed_at IS NULL
          AND mps.confirmed_ip IS NULL
          AND mps.is_woocommerce_user = 1
    ', $subscriberSegmentTable, $subscribersTable, $status, $wcSegment->id);

    Subscriber::rawExecute($sql);
  }
}
