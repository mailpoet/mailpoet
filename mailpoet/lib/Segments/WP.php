<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Segments;

use MailPoet\Config\SubscriberChangesNotifier;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Services\Validator;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SegmentsCountRecalculator;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\DBCollationChecker;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WP {

  /** @var WPFunctions */
  private $wp;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var WooCommerceHelper */
  private $wooHelper;

  /**
   * Per-request, keyed by WP user id: whether this sync created the subscriber
   * row or found one already there. See wasSubscriberCreatedBySync().
   *
   * @var array<int, bool>
   */
  private $syncCreatedSubscriber = [];

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberChangesNotifier */
  private $subscriberChangesNotifier;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var Validator */
  private $validator;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var EntityManager */
  private $entityManager;

  /** @var DBCollationChecker */
  private $collationChecker;

  /** @var string */
  private $subscribersTable;

  /** @var \MailPoetVendor\Doctrine\DBAL\Connection */
  private $databaseConnection;

  /** @var SegmentsCountRecalculator */
  private $segmentsCountRecalculator;

  public function __construct(
    WPFunctions $wp,
    WelcomeScheduler $welcomeScheduler,
    WooCommerceHelper $wooHelper,
    SubscribersRepository $subscribersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    SubscriberChangesNotifier $subscriberChangesNotifier,
    Validator $validator,
    SegmentsRepository $segmentsRepository,
    EntityManager $entityManager,
    DBCollationChecker $collationChecker,
    SegmentsCountRecalculator $segmentsCountRecalculator
  ) {
    $this->wp = $wp;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->wooHelper = $wooHelper;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->subscriberChangesNotifier = $subscriberChangesNotifier;
    $this->validator = $validator;
    $this->segmentsRepository = $segmentsRepository;
    $this->entityManager = $entityManager;
    $this->collationChecker = $collationChecker;
    $this->segmentsCountRecalculator = $segmentsCountRecalculator;
    $this->databaseConnection = $this->entityManager->getConnection();
    $this->subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
  }

  /**
   * @param int $wpUserId
   * @param array|false $oldWpUserData
   */

  /**
   * Whether this request's sync created the subscriber row for the given WP
   * user, rather than finding one that was already there. Defaults to false,
   * meaning "treat as pre-existing", for a user this sync never saw: a missing
   * signal must never cause an unearned overwrite of an earlier consent choice.
   *
   * Needed because wp_insert_user() calls set_user_role() before it fires
   * user_register, and set_user_role is itself hooked to synchronizeUser. So by
   * the time anything runs on user_register — at any priority — the row already
   * exists, and a later lookup cannot tell a brand new registrant from someone
   * who was already on the list.
   */
  public function wasSubscriberCreatedBySync(int $wpUserId): bool {
    return $this->syncCreatedSubscriber[$wpUserId] ?? false;
  }

  public function synchronizeUser(int $wpUserId, $oldWpUserData = false): void {
    $wpUser = $this->wp->getUserdata($wpUserId);
    if ($wpUser === false) return;

    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $wpUserId]);
    if (!isset($this->syncCreatedSubscriber[$wpUserId])) {
      $this->syncCreatedSubscriber[$wpUserId] = !$subscriber instanceof SubscriberEntity
        && $this->subscribersRepository->findOneBy(['email' => $wpUser->user_email]) === null; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }

    $currentFilter = $this->wp->currentFilter();
    // Delete
    if (in_array($currentFilter, ['delete_user', 'deleted_user', 'remove_user_from_blog'])) {
      if ($subscriber instanceof SubscriberEntity) {
        $this->unlinkSubscriberFromWpUser($subscriber, $wpUser);
      }
      return;
    }
    $this->handleCreatingOrUpdatingSubscriber($currentFilter, $wpUser, $subscriber, $oldWpUserData);

    // In WP::synchronizeUser, after the subscriber is created
    $this->wp->doAction('mailpoet_user_registered', $wpUserId, $subscriber);
  }

  private function deleteSubscriber(SubscriberEntity $subscriber): void {
    $this->entityManager->wrapInTransaction(function() use ($subscriber): void {
      $this->subscriberSegmentRepository->deleteAllBySubscriber($subscriber);
      $this->subscribersRepository->remove($subscriber);
      $this->subscribersRepository->flush();
    });
  }

  private function unlinkSubscriberFromWpUser(SubscriberEntity $subscriber, \WP_User $wpUser): void {
    // Backwards-compat escape hatch for sites that need the legacy hard-delete behavior
    // (e.g. GDPR-driven account deletion flows). Returning true reproduces pre-STOMAIL-8018 behavior.
    $hardDelete = (bool)$this->wp->applyFilters(
      'mailpoet_delete_subscriber_on_wp_user_delete',
      false,
      $subscriber,
      (int)$wpUser->ID // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    );
    if ($hardDelete) {
      $this->deleteSubscriber($subscriber);
      return;
    }

    $this->entityManager->wrapInTransaction(function() use ($subscriber, $wpUser): void {
      $wpSegment = $this->segmentsRepository->getWPUsersSegment();

      // Remove only the WP-Users segment membership; other list subscriptions stay intact.
      $this->entityManager->createQueryBuilder()
        ->delete(SubscriberSegmentEntity::class, 'ss')
        ->where('ss.subscriber = :subscriber AND ss.segment = :segment')
        ->setParameter('subscriber', $subscriber)
        ->setParameter('segment', $wpSegment)
        ->getQuery()
        ->execute();

      $subscriber->setWpUserId(null);
      $subscriber->setSource(Source::WORDPRESS_USER_DELETED);

      // If the subscriber was only on the WP-Users list and is not a WC customer,
      // they had no list of their own to remain on — trash them instead of leaving a floating row.
      // Skip when the subscriber was already trashed (e.g. manually by an admin) so we keep
      // the original deleted_at and status as audit information.
      $hasOtherActiveSegments = $this->hasOtherActiveSegments($subscriber);
      $isWooCustomer = $this->wooHelper->isWooCommerceActive() && in_array('customer', $wpUser->roles, true); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      if (!$hasOtherActiveSegments && !$isWooCustomer && $subscriber->getDeletedAt() === null) {
        $subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
        $subscriber->setDeletedAt(Carbon::now()->millisecond(0));
      }

      $this->subscribersRepository->persist($subscriber);
      $this->subscribersRepository->flush();
    });
    $this->segmentsCountRecalculator->recalculateForSubscribers([(int)$subscriber->getId()]);
  }

  private function hasOtherActiveSegments(SubscriberEntity $subscriber): bool {
    $subscriberId = $subscriber->getId();
    if ($subscriberId === null) {
      return false;
    }

    $count = $this->entityManager->createQueryBuilder()
      ->select('COUNT(segment.id)')
      ->from(SubscriberSegmentEntity::class, 'subscriberSegment')
      ->innerJoin('subscriberSegment.segment', 'segment')
      ->where('subscriberSegment.subscriber = :subscriber')
      ->andWhere('segment.type != :wpType')
      ->andWhere('segment.deletedAt IS NULL')
      ->setParameter('subscriber', $subscriber)
      ->setParameter('wpType', SegmentEntity::TYPE_WP_USERS)
      ->getQuery()
      ->getSingleScalarResult();

    return is_numeric($count) && (int)$count > 0;
  }

  /**
   * @param string $currentFilter
   * @param \WP_User $wpUser
   * @param ?SubscriberEntity $subscriber
   * @param array|false $oldWpUserData
   */
  private function handleCreatingOrUpdatingSubscriber(string $currentFilter, \WP_User $wpUser, ?SubscriberEntity $subscriber = null, $oldWpUserData = false): void {
    // Add or update
    $wpSegment = $this->segmentsRepository->getWPUsersSegment();

    // find subscriber by email when is null
    if (is_null($subscriber)) {
      $subscriber = $this->subscribersRepository->findOneBy(['email' => $wpUser->user_email]); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }

    // get first name & last name
    $firstName = $this->decodeUserName($wpUser->first_name); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $lastName = $this->decodeUserName($wpUser->last_name); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if (empty($wpUser->first_name) && empty($wpUser->last_name)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $firstName = $this->decodeUserName($wpUser->display_name); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    $signupConfirmationEnabled = SettingsController::getInstance()->get('signup_confirmation.enabled');
    $status = $signupConfirmationEnabled ? SubscriberEntity::STATUS_UNCONFIRMED : SubscriberEntity::STATUS_SUBSCRIBED;
    // we want to mark a new subscriber as unsubscribe when the checkbox from registration is unchecked
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- type narrowing only, value is read as bool
    $mailpoetPost = isset($_POST['mailpoet']) && is_array($_POST['mailpoet']) ? $_POST['mailpoet'] : [];
    if (isset($mailpoetPost['subscribe_on_register_active']) && (bool)$mailpoetPost['subscribe_on_register_active'] === true) {
      $status = SubscriberEntity::STATUS_UNSUBSCRIBED;
    }

    // subscriber data
    $wpUserId = (int)$wpUser->ID; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $data = [
      'wp_user_id' => $wpUserId,
      'email' => $wpUser->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'first_name' => $firstName,
      'last_name' => $lastName,
      'status' => $status,
      'source' => Source::WORDPRESS_USER,
    ];

    $isRelinkingDeletedWpUser = $subscriber !== null
      && $subscriber->getSource() === Source::WORDPRESS_USER_DELETED
      && $subscriber->getWpUserId() !== $wpUserId;
    if (!is_null($subscriber)) {
      $data['id'] = $subscriber->getId();
      unset($data['status']); // don't override status for existing users
      unset($data['source']); // don't override source for existing users
      if ($isRelinkingDeletedWpUser) {
        // Restore the live WP-user source on any re-link, not only the auto-trashed case.
        // Only revive deleted_at when the subscriber was actually trashed by the unlink
        // (subscribers kept on other lists were never trashed and must keep deleted_at IS NULL).
        $data['source'] = Source::WORDPRESS_USER;
        if ($subscriber->getDeletedAt() !== null) {
          $data['deleted_at'] = null;
        }
      }
    }

    $addingNewUserToDisabledWPSegment = $wpSegment->getDeletedAt() !== null && $currentFilter === 'user_register';

    $otherActiveSegments = [];
    if ($subscriber) {
      $otherActiveSegments = array_filter($subscriber->getSegments()->toArray() ?? [], function (SegmentEntity $segment) {
          return $segment->getType() !== SegmentEntity::TYPE_WP_USERS && $segment->getDeletedAt() === null;
      });
    }
    $isWooCustomer = $this->wooHelper->isWooCommerceActive() && in_array('customer', $wpUser->roles, true);
    // When WP Segment is disabled force trashed state and unconfirmed status for new WPUsers without active segment
    // or who are not WooCommerce customers at the same time since customers are added to the WooCommerce list
    if ($addingNewUserToDisabledWPSegment && !$isRelinkingDeletedWpUser && !$otherActiveSegments && !$isWooCustomer) {
      $data['deleted_at'] = Carbon::now()->millisecond(0);
      $data['status'] = SubscriberEntity::STATUS_UNCONFIRMED;
    }

    // Apply filter to allow modifying subscriber data before save
    $data = $this->wp->applyFilters('mailpoet_subscriber_data_before_save', $data);

    // Ensure data is an array
    if (!is_array($data)) {
      // If the filter returned a non-array, log it and use the original data
      $logger = LoggerFactory::getInstance()->getLogger();
      $logger->error(
        'Filter mailpoet_subscriber_data_before_save returned non-array data.',
        ['data_type' => gettype($data)]
      );
      return;
    }

    // When updating an existing subscriber's email, remove any other subscriber
    // that already holds the new email to avoid a unique constraint violation.
    // This can happen when a WP user registers with email A, checks out with
    // email B (creating a second subscriber), then changes their account email
    // from A to B.
    // Uses bulkDelete() to properly clean up related data (segments, tags,
    // custom fields) and to restrict deletion to safe duplicates (non-WP,
    // non-WooCommerce subscribers).
    if ($subscriber !== null && $subscriber->getEmail() !== $data['email']) {
      $existingSubscriber = $this->subscribersRepository->findOneBy(['email' => $data['email']]);
      if ($existingSubscriber !== null && $existingSubscriber->getId() !== $subscriber->getId()) {
        $duplicateId = $existingSubscriber->getId();
        $this->entityManager->detach($existingSubscriber);
        $deletedCount = $this->subscribersRepository->bulkDelete([$duplicateId]);
        if ($deletedCount === 0) {
          // The duplicate is a WP user or WooCommerce customer and cannot be
          // safely removed. Skip the email update to avoid a constraint violation.
          $logger = LoggerFactory::getInstance()->getLogger();
          $logger->warning(
            'Cannot update subscriber email: duplicate subscriber is a WP user or WooCommerce customer',
            ['subscriber_id' => $subscriber->getId(), 'duplicate_id' => $duplicateId, 'email' => $data['email']]
          );
          return;
        }
      }
    }

    try {
      $subscriber = $this->createOrUpdateSubscriber($data, $subscriber);
    } catch (\Exception $e) {
      return; // fails silently as this was the behavior before the Doctrine refactor.
    }

    // add subscriber to the WP Users segment
    $this->subscriberSegmentRepository->subscribeToSegments(
      $subscriber,
      [$wpSegment]
    );

    if (!$signupConfirmationEnabled && $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED && $currentFilter === 'user_register') {
      $subscriberSegment = $this->subscriberSegmentRepository->findOneBy([
        'subscriber' => $subscriber->getId(),
        'segment' => $wpSegment->getId(),
      ]);

      if (!is_null($subscriberSegment)) {
        $this->wp->doAction('mailpoet_segment_subscribed', $subscriberSegment);
      }
    }

    $subscribeOnRegisterEnabled = SettingsController::getInstance()->get('subscribe.on_register.enabled');
    $sendConfirmationEmail =
      $signupConfirmationEnabled
      && $subscribeOnRegisterEnabled
      && $currentFilter !== 'profile_update'
      && !$addingNewUserToDisabledWPSegment;

    if ($sendConfirmationEmail && ($subscriber->getStatus() === SubscriberEntity::STATUS_UNCONFIRMED)) {
      /** @var ConfirmationEmailMailer $confirmationEmailMailer */
      $confirmationEmailMailer = ContainerWrapper::getInstance()->get(ConfirmationEmailMailer::class);
      try {
        // Per-list confirmation settings are not resolved here because this path
        // subscribes to the WordPress Users segment (TYPE_WP_USERS),
        // which does not support custom confirmation overrides.
        $confirmationEmailMailer->sendConfirmationEmailOnce($subscriber);
      } catch (\Exception $e) {
        // ignore errors
      }
    }

    // welcome email
    $scheduleWelcomeNewsletter = false;
    if (in_array($currentFilter, ['profile_update', 'user_register', 'add_user_role', 'set_user_role'])) {
      $scheduleWelcomeNewsletter = true;
    }
    if ($scheduleWelcomeNewsletter === true) {
      $this->welcomeScheduler->scheduleWPUserWelcomeNotification(
        $subscriber->getId(),
        (array)$wpUser,
        (array)$oldWpUserData
      );
    }
  }

  /**
   * WordPress stores user names entity-encoded (see the `pre_user_first_name`,
   * `pre_user_last_name` and `pre_user_display_name` filters). We decode them so a
   * name such as "Family & friends" is stored the way it was written, then run the
   * same text sanitizer the other subscriber write paths use, so decoding can never
   * turn encoded markup back into markup.
   *
   * @param mixed $name
   */
  private function decodeUserName($name): string {
    $decoded = html_entity_decode(is_string($name) ? $name : '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);

    return $this->wp->sanitizeTextField($decoded);
  }

  private function createOrUpdateSubscriber(array $data, ?SubscriberEntity $subscriber = null): SubscriberEntity {
    if (is_null($subscriber)) {
      $subscriber = new SubscriberEntity();
    }

    $subscriber->setWpUserId($data['wp_user_id']);
    $subscriber->setEmail($data['email']);

    // Only set first_name if it's present in the data array
    if (isset($data['first_name'])) {
      $subscriber->setFirstName($data['first_name']);
    }

    // Only set last_name if it's present in the data array
    if (isset($data['last_name'])) {
      $subscriber->setLastName($data['last_name']);
    }

    if (isset($data['status'])) {
      $subscriber->setStatus($data['status']);
    }

    if (isset($data['source'])) {
      $subscriber->setSource($data['source']);
    }

    if (array_key_exists('deleted_at', $data)) {
      $subscriber->setDeletedAt($data['deleted_at']);
    }

    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();

    return $subscriber;
  }

  public function synchronizeUsers(): bool {
    // Temporarily skip synchronization in WP Playground.
    // Some of the queries are not yet supported by the SQLite integration.
    if (Connection::isSQLite()) {
      return true;
    }

    // Save timestamp about changes and update before insert
    $this->subscriberChangesNotifier->subscribersBatchCreate();
    $this->subscriberChangesNotifier->subscribersBatchUpdate();

    $updatedUsersEmails = $this->updateSubscribersEmails();
    $insertedUsersEmails = $this->insertSubscribers();
    $this->removeUpdatedSubscribersWithInvalidEmail(array_merge($updatedUsersEmails, $insertedUsersEmails));
    // There is high chance that an update will be made
    $this->subscriberChangesNotifier->subscribersBatchUpdate();
    unset($updatedUsersEmails);
    unset($insertedUsersEmails);
    $this->updateFirstNames();
    $this->updateLastNames();
    $this->updateFirstNameIfMissing();
    $this->insertUsersToSegment();
    $this->removeOrphanedSubscribers();
    // insertUsersToSegment adds WP users to the WP-Users segment via raw SQL,
    // so refresh segments_count for that segment's members.
    // recalculateForSegment() only sees subscribers that still have a membership
    // row. Orphans that are hard-deleted by removeOrphanedSubscribers() are fine
    // (row gone, count moot). Orphans whose membership is deleted but who survive
    // (soft-trashed or still on other lists) are recalculated explicitly inside
    // removeOrphanedSubscribersFromWpSegment() before the membership DELETE.
    $this->segmentsCountRecalculator->recalculateForSegment((int)$this->segmentsRepository->getWPUsersSegment()->getId());
    $this->subscribersRepository->invalidateTotalSubscribersCache();
    $this->subscribersRepository->refreshAll();

    return true;
  }

  private function removeUpdatedSubscribersWithInvalidEmail(array $updatedEmails): void {
    $invalidWpUserIds = array_map(function($item) {
      return $item['id'];
    },
    array_filter($updatedEmails, function($updatedEmail) {
      return !$this->validator->validateEmail($updatedEmail['email']) && $updatedEmail['id'] !== null;
    }));
    if (!$invalidWpUserIds) {
      return;
    }

    $this->subscribersRepository->removeByWpUserIds($invalidWpUserIds);
  }

  private function updateSubscribersEmails(): array {
    global $wpdb;

    $stmt = $this->databaseConnection->executeQuery('SELECT NOW();');
    $startTime = $stmt->fetchOne();

    if (!is_string($startTime)) {
      throw new \RuntimeException("Failed to fetch the current time.");
    }

    $updateSql =
      "UPDATE IGNORE {$this->subscribersTable} s
        INNER JOIN {$wpdb->users} as wu ON s.wp_user_id = wu.id
        SET s.email = wu.user_email";
    $this->databaseConnection->executeStatement($updateSql);

    $selectSql =
      "SELECT wp_user_id as id, email FROM {$this->subscribersTable}
        WHERE updated_at >= '{$startTime}'";
    $updatedEmails = $this->databaseConnection->fetchAllAssociative($selectSql);

    return $updatedEmails;
  }

  private function insertSubscribers(): array {
    global $wpdb;
    $wpSegment = $this->segmentsRepository->getWPUsersSegment();

    if ($wpSegment->getDeletedAt() !== null) {
      $subscriberStatus = SubscriberEntity::STATUS_UNCONFIRMED;
      $deletedAt = 'CURRENT_TIMESTAMP()';
    } else {
      $signupConfirmationEnabled = SettingsController::getInstance()->get('signup_confirmation.enabled');
      $subscriberStatus = $signupConfirmationEnabled ? SubscriberEntity::STATUS_UNCONFIRMED : SubscriberEntity::STATUS_SUBSCRIBED;
      $deletedAt = 'null';
    }

    // Fetch users that are not in the subscribers table
    $selectSql =
      "SELECT u.id, u.user_email as email
        FROM {$wpdb->users} u
        LEFT JOIN {$this->subscribersTable} AS s ON s.wp_user_id = u.id
        WHERE s.wp_user_id IS NULL AND u.user_email != ''";
    $insertedUserIds = $this->databaseConnection->fetchAllAssociative($selectSql);

    // Insert new users into the subscribers table.
    // The email-based join can cross a collation boundary when wp_users.user_email and
    // mailpoet_subscribers.email use different (but compatible) collations — force a
    // matching collation to avoid "Illegal mix of collations" errors.
    $emailCollation = $this->collationChecker->getCollateIfNeeded(
      $wpdb->users,
      'user_email',
      $this->subscribersTable,
      'email'
    );
    $insertSql =
      "INSERT IGNORE INTO {$this->subscribersTable} (wp_user_id, email, status, created_at, `source`, deleted_at)
        SELECT wu.id, wu.user_email, :subscriberStatus, CURRENT_TIMESTAMP(), :source, {$deletedAt}
        FROM {$wpdb->users} wu
        LEFT JOIN {$this->subscribersTable} s ON wu.id = s.wp_user_id
        LEFT JOIN {$this->subscribersTable} existingSubscriber ON wu.user_email = existingSubscriber.email {$emailCollation}
        WHERE s.wp_user_id IS NULL AND wu.user_email != ''
        ON DUPLICATE KEY UPDATE
          wp_user_id = wu.id,
          deleted_at = IF(
            existingSubscriber.`source` = :deletedSource AND existingSubscriber.deleted_at IS NOT NULL,
            NULL,
            existingSubscriber.deleted_at
          ),
          `source` = IF(
            existingSubscriber.`source` = :deletedSource,
            :source,
            existingSubscriber.`source`
          )";
    $stmt = $this->databaseConnection->prepare($insertSql);
    $stmt->bindValue('subscriberStatus', $subscriberStatus);
    $stmt->bindValue('source', Source::WORDPRESS_USER);
    $stmt->bindValue('deletedSource', Source::WORDPRESS_USER_DELETED);
    $stmt->executeStatement();

    return $insertedUserIds;
  }

  private function updateFirstNames(): void {
    global $wpdb;

    $sql =
      "UPDATE {$this->subscribersTable} s
        JOIN {$wpdb->usermeta} as wpum ON s.wp_user_id = wpum.user_id AND wpum.meta_key = 'first_name'
        SET s.first_name = SUBSTRING(wpum.meta_value, 1, 255)
        WHERE s.first_name = ''
        AND s.wp_user_id IS NOT NULL
        AND wpum.meta_value IS NOT NULL";

    $this->databaseConnection->executeStatement($sql);
  }

  private function updateLastNames(): void {
    global $wpdb;

    $sql =
      "UPDATE {$this->subscribersTable} s
        JOIN {$wpdb->usermeta} as wpum ON s.wp_user_id = wpum.user_id AND wpum.meta_key = 'last_name'
        SET s.last_name = SUBSTRING(wpum.meta_value, 1, 255)
        WHERE s.last_name = ''
        AND s.wp_user_id IS NOT NULL
        AND wpum.meta_value IS NOT NULL";

    $this->databaseConnection->executeStatement($sql);
  }

  private function updateFirstNameIfMissing(): void {
    global $wpdb;

    $sql =
      "UPDATE {$this->subscribersTable} s
        JOIN {$wpdb->users} wu ON s.wp_user_id = wu.id
        SET s.first_name = wu.display_name
        WHERE s.first_name = ''
        AND s.wp_user_id IS NOT NULL";

    $this->databaseConnection->executeStatement($sql);
  }

  private function insertUsersToSegment(): void {
    $wpSegment = $this->segmentsRepository->getWPUsersSegment();
    $subscribersSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();

    $sql =
      "INSERT IGNORE INTO {$subscribersSegmentTable} (subscriber_id, segment_id, created_at)
        SELECT s.id, '{$wpSegment->getId()}', CURRENT_TIMESTAMP() FROM {$this->subscribersTable} s
        WHERE s.wp_user_id > 0";

    $this->databaseConnection->executeStatement($sql);
  }

  private function removeOrphanedSubscribers(): void {
    $this->subscribersRepository->removeOrphanedSubscribersFromWpSegment();
  }
}
