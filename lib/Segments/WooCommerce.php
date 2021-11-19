<?php declare(strict_types = 1);

namespace MailPoet\Segments;

use MailPoet\Config\Env;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Models\ModelValidator;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommerce {
  public const BATCH_SIZE = 10000;

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

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var WCHelper */
  private $woocommerceHelper;

  /** @var EntityManager */
  private $entityManager;

  /** @var Connection */
  private $connection;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    WCHelper $woocommerceHelper,
    SubscribersRepository $subscribersRepository,
    SegmentsRepository $segmentsRepository,
    WP $wpSegment,
    EntityManager $entityManager,
    Connection $connection
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->wpSegment = $wpSegment;
    $this->subscribersRepository = $subscribersRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->entityManager = $entityManager;
    $this->connection = $connection;
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
    $status = SubscriberEntity::STATUS_UNCONFIRMED;
    if ((bool)$signupConfirmation['enabled'] === false) {
      $status = SubscriberEntity::STATUS_SUBSCRIBED;
    }

    $insertedEmails = $this->insertSubscribersFromOrders($orderId, $status);

    if (empty($insertedEmails[0])) {
      return false;
    }
    $subscriber = Subscriber::where('email', $insertedEmails[0])
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
    }
  }

  public function synchronizeCustomers(int $countOfSynchronized = 0): int {
    if ($countOfSynchronized === 0) {
      $this->resetSynchronization();
    }

    $this->wpSegment->synchronizeUsers(); // synchronize registered users

    $this->markRegisteredCustomers();

    $insertedUsersEmails = $this->insertSubscribersFromOrders();
    $this->updateNames($insertedUsersEmails);

    if (count($insertedUsersEmails) < self::BATCH_SIZE) {
      $this->insertUsersToSegment();
      $this->unsubscribeUsersFromSegment();
      $this->removeOrphanedSubscribers();
      $this->updateStatus();
      $this->updateGlobalStatus();
    }

    return count($insertedUsersEmails);
  }

  public function resetSynchronization(): void {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->connection->executeQuery("
      UPDATE {$subscribersTable}
      SET is_woocommerce_synced = 0
    ");
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
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->connection->executeQuery("
      UPDATE LOW_PRIORITY {$subscribersTable} mps
        JOIN {$wpdb->users} wu ON mps.wp_user_id = wu.id
        JOIN {$wpdb->usermeta} wpum ON wu.id = wpum.user_id AND wpum.meta_key = :capabilities
      SET is_woocommerce_user = 1, source = :source
        WHERE wpum.meta_value LIKE '%\"customer\"%'
    ", ['capabilities' => $wpdb->prefix . 'capabilities', 'source' => Source::WOOCOMMERCE_USER]);
  }

  private function insertSubscribersFromOrders($orderId = null, $status = Subscriber::STATUS_SUBSCRIBED): array {
    global $wpdb;
    $validator = new ModelValidator();
    $orderId = !is_null($orderId) ? (int)$orderId : null;

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subQuery = " AND wppm.meta_value NOT IN (
        SELECT email
        FROM {$subscribersTable}
        WHERE is_woocommerce_synced = 1
        ORDER BY email
    )";
    $parameters = ['batchSize' => self::BATCH_SIZE];
    if ($orderId) {
      $parameters['orderId'] = $orderId;
    }

    $usersEmails = $this->connection->executeQuery('
      SELECT DISTINCT wppm.meta_value as email FROM `' . $wpdb->prefix . 'postmeta` wppm
      JOIN `' . $wpdb->prefix . 'posts` p ON wppm.post_id = p.ID AND p.post_type = "shop_order"
      WHERE wppm.meta_key = "_billing_email" AND wppm.meta_value != ""
      ' . ($orderId ? ' AND p.ID = :orderId' : $subQuery) . '
      ORDER BY wppm.meta_value
      LIMIT :batchSize
    ', $parameters, ['batchSize' => \PDO::PARAM_INT])->fetchAllAssociative();
    $usersEmails = array_column($usersEmails, 'email');

    $subscribersValues = [];
    $insertedUsersEmails = [];
    $now = (Carbon::createFromTimestamp($this->wp->currentTime('timestamp')))->format('Y-m-d H:i:s');
    $source = Source::WOOCOMMERCE_USER;
    foreach ($usersEmails as $email) {
      if (!$validator->validateEmail($email)) {
        continue;
      }
      $insertedUsersEmails[] = $email;
      $subscribersValues[] = "(1, '{$email}', '{$status}', '{$now}', '{$now}', '{$source}')";
    }

    if (count($subscribersValues) > 0) {
      $this->connection->executeQuery('
        INSERT IGNORE INTO ' . $subscribersTable . ' (`is_woocommerce_user`, `email`, `status`, `created_at`, `last_subscribed_at`, `source`) VALUES
        ' . implode(',', $subscribersValues) . '
        ON DUPLICATE KEY UPDATE is_woocommerce_user = 1
      ');
    }

    return $insertedUsersEmails;
  }

  private function updateNames(array $emails): int {
    global $wpdb;
    if (!$emails) {
      return 0;
    }
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    // select latest order ID with emails
    $postIdsResult = $this->connection->executeQuery("
      SELECT MAX(post_id) AS post_id, meta_value AS email
      FROM {$wpdb->postmeta}
      WHERE meta_key = \"_billing_email\"
      AND meta_value IN (:emails)
      GROUP BY meta_value
    ", ['emails' => $emails], ['emails' => Connection::PARAM_STR_ARRAY])->fetchAllAssociative();

    $subscribersData = array_combine(array_column($postIdsResult, 'post_id'), $postIdsResult);
    $metaKeys = [
      '_billing_first_name',
      '_billing_last_name',
    ];
    $metaData = $this->connection->executeQuery("
      SELECT post_id, meta_key, meta_value
      FROM {$wpdb->postmeta}
      WHERE meta_key IN (:metaKeys) AND post_id IN (:postIds)
    ",
      ['metaKeys' => $metaKeys, 'postIds' => array_column($postIdsResult, 'post_id')],
      ['metaKeys' => Connection::PARAM_STR_ARRAY, 'postIds' => Connection::PARAM_INT_ARRAY]
    )->fetchAllAssociative();

    foreach ($metaData as $row) {
      if (!$row['meta_value']) {
        continue;
      }
      $subscribersData[$row['post_id']][$row['meta_key']] = $row['meta_value'];
    }

    $count = 0;
    $now = (Carbon::now())->format('Y-m-d H:i:s');
    foreach ($subscribersData as $subscriber) {
      $data = [];
      $data['is_woocommerce_synced'] = 1;
      $data['woocommerce_synced_at'] = $now;
      if (!empty($subscriber['_billing_first_name'])) $data['first_name'] = $subscriber['_billing_first_name'];
      if (!empty($subscriber['_billing_last_name'])) $data['last_name'] = $subscriber['_billing_last_name'];
      $this->connection->update($subscribersTable, $data, ['email' => $subscriber['email']]);
      $count++;
    }
    return $count;
  }

  private function insertUsersToSegment(): void {
    $wcSegment = $this->segmentsRepository->getWooCommerceSegment();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    // Subscribe WC users to segment
    $this->connection->executeQuery("
      INSERT IGNORE INTO {$subscriberSegmentsTable} (subscriber_id, segment_id, created_at)
      SELECT id, :segmentId, CURRENT_TIMESTAMP()
      FROM {$subscribersTable}
      WHERE is_woocommerce_user = 1
    ",
      ['segmentId' => $wcSegment->getId()],
      ['segmentId' => \PDO::PARAM_INT]
    );
  }

  private function unsubscribeUsersFromSegment(): void {
    $wcSegment = $this->segmentsRepository->getWooCommerceSegment();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    // Unsubscribe non-WC or invalid users from segment
    $this->connection->executeQuery("
      DELETE mpss FROM {$subscriberSegmentsTable} mpss
      LEFT JOIN {$subscribersTable} mps ON mpss.subscriber_id = mps.id
      WHERE mpss.segment_id = :segmentId AND (mps.is_woocommerce_user = 0 OR mps.email = '' OR mps.email IS NULL)
    ",
      ['segmentId' => $wcSegment->getId()],
      ['segmentId' => \PDO::PARAM_INT]
    );
  }

  private function updateGlobalStatus(): void {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $wcSegment = $this->segmentsRepository->getWooCommerceSegment();
    // Set global status unsubscribed to all woocommerce users without any segment
    $this->connection->executeQuery("
      UPDATE {$subscribersTable} mps
      LEFT JOIN {$subscriberSegmentsTable} mpss ON mpss.subscriber_id = mps.id
      SET mps.status = :statusUnsubscribed
      WHERE mpss.id IS NULL
        AND mps.is_woocommerce_user = 1
    ",
      ['statusUnsubscribed' => SubscriberEntity::STATUS_UNSUBSCRIBED],
      ['statusUnsubscribed' => \PDO::PARAM_STR]
    );
    // SET global status unsubscribed to all woocommerce users who have only 1 segment and it is woocommerce segment and they are not subscribed
    // You can't specify target table 'mps' for update in FROM clause
    $this->connection->executeQuery("
      UPDATE {$subscribersTable} mps
      JOIN {$subscriberSegmentsTable} mpss ON mps.id = mpss.subscriber_id AND mpss.segment_id = :segmentId AND mpss.status = :statusUnsubscribed
      SET mps.status = :statusUnsubscribed
      WHERE mps.id IN (
        SELECT s.id -- get all subscribers with exactly 1 segment
        FROM (SELECT id FROM {$subscribersTable} WHERE is_woocommerce_user = 1) s
        JOIN {$subscriberSegmentsTable} ss on s.id = ss.subscriber_id
        GROUP BY s.id
        HAVING COUNT(ss.id) = 1
      )
    ",
      ['statusUnsubscribed' => SubscriberEntity::STATUS_UNSUBSCRIBED, 'segmentId' => $wcSegment->getId()],
      ['statusUnsubscribed' => \PDO::PARAM_STR, 'segmentId' => \PDO::PARAM_INT]
    );
  }

  private function removeOrphanedSubscribers(): void {
    // Remove orphaned WooCommerce segment subscribers (not having a matching WC customer email),
    // e.g. if WC orders were deleted directly from the database
    // or a customer role was revoked and a user has no orders
    global $wpdb;

    $wcSegment = $this->segmentsRepository->getWooCommerceSegment();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();

    // Unmark registered customers

    // Insert WC customer IDs to a temporary table for left join to use an index
    $tmpTableName = Env::$dbPrefix . 'tmp_wc_ids';
    // Registered users with orders
    $this->connection->executeQuery("
      CREATE TEMPORARY TABLE {$tmpTableName}
        (`id` int(11) unsigned NOT NULL, UNIQUE(`id`)) AS
      SELECT DISTINCT wppm.meta_value AS id FROM {$wpdb->postmeta} wppm
        JOIN {$wpdb->posts} wpp ON wppm.post_id = wpp.ID
        AND wpp.post_type = 'shop_order'
        WHERE wppm.meta_key = '_customer_user'
    ");
    // Registered users with a customer role
    $this->connection->executeQuery("
      INSERT IGNORE INTO {$tmpTableName}
      SELECT DISTINCT wpum.user_id AS id FROM {$wpdb->usermeta} wpum
      WHERE wpum.meta_key = :capabilities AND wpum.meta_value LIKE '%\"customer\"%'
    ", ['capabilities' => $wpdb->prefix . 'capabilities']);

    // Unmark WC list registered users which aren't WC customers anymore
    $subQb = $this->connection->createQueryBuilder();
    $subQb->select('mps.id')
      ->from($subscribersTable, 'mps')
      ->join('mps', $subscriberSegmentsTable, 'mpss', 'mps.id = mpss.subscriber_id AND mpss.segment_id = :segmentId')
      ->leftJoin('mps', $tmpTableName, 'wctmp', 'mps.wp_user_id = wctmp.id')
      ->where('mps.is_woocommerce_user = 1')
      ->andWhere('wctmp.id IS NULL')
      ->andWhere('mps.wp_user_id IS NOT NULL');
    $qb = $this->connection->createQueryBuilder();
    $qb->update($subscribersTable)
      ->set('is_woocommerce_user', '0')
      ->where("id IN (SELECT id FROM ({$subQb->getSQL()}) AS sq) ")
      ->setParameter('segmentId', $wcSegment->getId());
    $qb->execute();

    $this->connection->executeQuery("DROP TABLE {$tmpTableName}");

    // Remove guest customers

    // Insert WC customer emails to a temporary table and ensure matching collations
    // between MailPoet and WooCommerce emails for left join to use an index
    $tmpTableName = Env::$dbPrefix . 'tmp_wc_emails';
    $collation = $this->mailpoetEmailCollation ? "COLLATE $this->mailpoetEmailCollation" : '';
    $this->connection->executeQuery("
      CREATE TEMPORARY TABLE {$tmpTableName}
        (`email` varchar(150) NOT NULL, UNIQUE(`email`)) {$collation}
      SELECT DISTINCT wppm.meta_value AS email FROM {$wpdb->postmeta} wppm
        JOIN {$wpdb->posts} wpp ON wppm.post_id = wpp.ID
        AND wpp.post_type = 'shop_order'
        WHERE wppm.meta_key = '_billing_email'
    ");

    // Remove WC list guest users which aren't WC customers anymore
    $subQb = $this->connection->createQueryBuilder();
    $subQb->select('mps.id')
      ->from($subscribersTable, 'mps')
      ->join('mps', $subscriberSegmentsTable, 'mpss', 'mps.id = mpss.subscriber_id AND mpss.segment_id = :segmentId')
      ->leftJoin('mps', $tmpTableName, 'wctmp', 'mps.email = wctmp.email')
      ->where('mps.is_woocommerce_user = 1')
      ->andWhere('wctmp.email IS NULL')
      ->andWhere('mps.wp_user_id IS NULL');
    $qb = $this->connection->createQueryBuilder();
    $qb->delete($subscribersTable)
      ->where("id IN (SELECT id FROM ({$subQb->getSQL()}) AS sq) ")
      ->setParameter('segmentId', $wcSegment->getId());
    $qb->execute();

    $this->connection->executeQuery("DROP TABLE {$tmpTableName}");
  }

  private function updateStatus(): void {
    $subscribeOldCustomers = $this->settings->get('mailpoet_subscribe_old_woocommerce_customers.enabled', false);
    if ($subscribeOldCustomers !== "1") {
      $status = SubscriberEntity::STATUS_UNSUBSCRIBED;
    } else {
      $status = SubscriberEntity::STATUS_SUBSCRIBED;
    }
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $subscriberSegmentsTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $wcSegment = $this->segmentsRepository->getWooCommerceSegment();

    $this->connection->executeQuery("
      UPDATE LOW_PRIORITY {$subscriberSegmentsTable} AS mpss
      JOIN {$subscribersTable} AS mps ON mpss.subscriber_id = mps.id
      SET mpss.status = :status
      WHERE
        mpss.segment_id = :segmentId
        AND mps.confirmed_at IS NULL
        AND mps.confirmed_ip IS NULL
        AND mps.is_woocommerce_user = 1
    ",
      ['status' => $status, 'segmentId' => $wcSegment->getId()],
      ['status' => \PDO::PARAM_STR, 'segmentId' => \PDO::PARAM_INT]
    );
  }
}
