<?php declare(strict_types = 1);

namespace MailPoet\Test\Segments;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\WP;
use MailPoet\Segments\WPUserDeleteNotice;

class WPUserDeleteNoticeTest extends \MailPoetTest {
  /** @var WPUserDeleteNotice */
  private $notice;

  /** @var WP */
  private $wpSegment;

  public function _before(): void {
    parent::_before();
    $this->notice = $this->diContainer->get(WPUserDeleteNotice::class);
    $this->wpSegment = $this->diContainer->get(WP::class);
    $this->cleanData();
  }

  public function testItWarnsThatTheSubscriberIsKept(): void {
    $userId = $this->insertUser();
    $this->wpSegment->synchronizeUsers();

    $output = $this->renderNotice([$userId]);

    $this->assertStringContainsString('MailPoet', $output);
    $this->assertStringContainsString('does not delete their MailPoet subscriber', $output);
    // Only one user selected, so no count. WordPress already says "this user" above.
    $this->assertStringContainsString('This user is also a MailPoet subscriber', $output);
    $this->assertStringNotContainsString('of the users you are deleting', $output);
  }

  public function testItCountsOnlyUsersThatHaveASubscriber(): void {
    $userWithSubscriber = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    // Created after the sync, so it has no linked subscriber yet.
    $userWithoutSubscriber = $this->insertUser();

    $output = $this->renderNotice([$userWithSubscriber, $userWithoutSubscriber]);

    // Two users selected, one affected, so the count earns its place.
    $this->assertStringContainsString('1 of the users you are deleting is also a MailPoet subscriber', $output);
  }

  public function testItUsesThePluralFormForSeveralSubscribers(): void {
    $firstUserId = $this->insertUser();
    $secondUserId = $this->insertUser();
    $this->wpSegment->synchronizeUsers();

    $output = $this->renderNotice([$firstUserId, $secondUserId]);

    $this->assertStringContainsString('2 of the users you are deleting are also MailPoet subscribers', $output);
  }

  public function testItRendersNothingWhenNoUserHasASubscriber(): void {
    $userId = $this->insertUser();

    $this->assertSame('', $this->renderNotice([$userId]));
  }

  public function testItRendersNothingWhenTheHardDeleteFilterIsEnabled(): void {
    $userId = $this->insertUser();
    $this->wpSegment->synchronizeUsers();

    add_filter('mailpoet_delete_subscriber_on_wp_user_delete', '__return_true');
    try {
      $output = $this->renderNotice([$userId]);
    } finally {
      remove_filter('mailpoet_delete_subscriber_on_wp_user_delete', '__return_true');
    }

    $this->assertSame('', $output);
  }

  public function testItRendersNothingWithoutUserIds(): void {
    $this->assertSame('', $this->renderNotice([]));
    $this->assertSame('', $this->renderNotice(null));
  }

  public function testItHandlesTheRawPostValuesPassedByNetworkAdmin(): void {
    $userId = $this->insertUser();
    $this->wpSegment->synchronizeUsers();

    // confirm_delete_users() in wp-admin/includes/ms.php passes (array) $_POST['allusers'],
    // so the ids arrive as strings and the list can contain empty entries.
    $output = $this->renderNotice(['', (string)$userId, '0']);

    // The empty entries are dropped before the count, so this is a single-user delete.
    $this->assertStringContainsString('This user is also a MailPoet subscriber', $output);
  }

  public function testItCountsEachSubscriberOnce(): void {
    $userId = $this->insertUser();
    $this->wpSegment->synchronizeUsers();

    $output = $this->renderNotice([$userId, $userId]);

    // Deduplicated first, so this counts as one user, not two.
    $this->assertStringContainsString('This user is also a MailPoet subscriber', $output);
  }

  /**
   * A subscriber who was only on the WP Users list gets trashed, and the subscribers
   * listing hides trashed rows outside the "trash" group. Without this pointer the notice
   * sends admins to a screen where they will not find the subscriber.
   */
  public function testItTellsTheAdminWhereToLookForTheSubscriber(): void {
    $firstUserId = $this->insertUser();
    $secondUserId = $this->insertUser();
    $this->wpSegment->synchronizeUsers();

    $single = $this->renderNotice([$firstUserId]);
    $plural = $this->renderNotice([$firstUserId, $secondUserId]);

    $this->assertStringContainsString('look for them on their other lists or in the Trash', $single);
    $this->assertStringContainsString('look for them on their other lists or in the Trash', $plural);
  }

  public function testItDropsTheCountOnlyWhenASingleUserIsSelected(): void {
    $firstUserId = $this->insertUser();
    $secondUserId = $this->insertUser();
    $this->wpSegment->synchronizeUsers();

    $single = $this->renderNotice([$firstUserId]);
    $both = $this->renderNotice([$firstUserId, $secondUserId]);

    $this->assertStringContainsString('This user is also a MailPoet subscriber', $single);
    $this->assertStringNotContainsString('This user is also a MailPoet subscriber', $both);
    $this->assertStringContainsString('2 of the users you are deleting are also MailPoet subscribers', $both);
  }

  public function testItLinksToTheKnowledgeBaseArticle(): void {
    $userId = $this->insertUser();
    $this->wpSegment->synchronizeUsers();

    $output = $this->renderNotice([$userId]);

    $this->assertStringContainsString(WPUserDeleteNotice::KB_ARTICLE_URL, $output);
  }

  /**
   * @param mixed $userIds
   */
  private function renderNotice($userIds): string {
    ob_start();
    $this->notice->displayNotice(null, $userIds);
    return (string)ob_get_clean();
  }

  private function insertUser(): int {
    global $wpdb;

    $this->connection->executeStatement(
      "INSERT INTO {$wpdb->users} (user_login, user_nicename, user_email, user_registered)
        VALUES (
          CONCAT('user-delete-notice-test', rand()),
          CONCAT('user-delete-notice-test', rand()),
          CONCAT('user-delete-notice-test', rand(), '@example.com'),
          '2017-01-02 12:31:12'
        )"
    );

    return (int)$this->connection->lastInsertId();
  }

  private function cleanData(): void {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);

    global $wpdb;
    $this->entityManager->getConnection()->executeQuery(
      "DELETE FROM {$wpdb->users} WHERE user_login != 'admin'"
    );
    $this->entityManager->getConnection()->executeQuery(
      "DELETE FROM {$wpdb->usermeta} WHERE user_id NOT IN (SELECT id FROM {$wpdb->users})"
    );
  }

  public function _after(): void {
    parent::_after();
    $this->cleanData();
  }
}
