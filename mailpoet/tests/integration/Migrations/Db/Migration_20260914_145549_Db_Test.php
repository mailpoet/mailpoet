<?php declare(strict_types = 1);

namespace MailPoet\Migrations\Db;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;

require_once __DIR__ . '/../../../../lib/Migrations/Db/Migration_20260914_145549_Db.php';

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20260914_145549_Db_Test extends \MailPoetTest {
  /** @var Migration_20260914_145549_Db */
  private $migration;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->settings->delete('sent_with_tracking_backfill');
    $this->migration = new Migration_20260914_145549_Db($this->diContainer);
  }

  public function _after() {
    parent::_after();
    $this->settings->delete('sent_with_tracking_backfill');
  }

  public function testItMarksSendsMadeAfterTheRecipientOptedOut() {
    $newsletter = $this->createNewsletterWithLink();
    $subscriber = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days');
    $afterOptOut = $this->createRow($newsletter, $subscriber, '-1 day');
    $beforeOptOut = $this->createRow($newsletter, $subscriber, '-5 days');

    $this->migration->run();

    verify($this->getStoredValue($afterOptOut))->equals(0);
    verify($this->getStoredValue($beforeOptOut))->equals(1);
  }

  public function testItLeavesRecipientsWhoDidNotOptOutTracked() {
    $newsletter = $this->createNewsletterWithLink();
    $granted = $this->createRow($newsletter, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_GRANTED, '-3 days'), '-1 day');
    $neverAsked = $this->createRow($newsletter, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_UNKNOWN, null), '-1 day');

    $this->migration->run();

    verify($this->getStoredValue($granted))->equals(1);
    verify($this->getStoredValue($neverAsked))->equals(1);
  }

  public function testItMarksEveryRowOfAQueueSentWithoutTrackedLinks() {
    $withoutLinks = (new Newsletter())->withSendingQueue()->create();
    $withLinks = $this->createNewsletterWithLink();
    $subscriber = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_GRANTED, null);
    $untracked = $this->createRow($withoutLinks, $subscriber, '-1 day');
    $tracked = $this->createRow($withLinks, $subscriber, '-1 day');

    $this->migration->run();

    verify($this->getStoredValue($untracked))->equals(0);
    verify($this->getStoredValue($tracked))->equals(1);
  }

  public function testItLeavesAnOptedOutRecipientWhoOpenedOrClickedThatEmailTracked() {
    $newsletter = $this->createNewsletterWithLink();
    $opened = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days');
    $clicked = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days');
    $openedRow = $this->createRow($newsletter, $opened, '-1 day');
    $clickedRow = $this->createRow($newsletter, $clicked, '-1 day');
    (new StatisticsOpens($newsletter, $opened))->create();
    (new StatisticsClicks((new NewsletterLink($newsletter))->withHash(uniqid())->create(), $clicked))->create();

    $this->migration->run();

    verify($this->getStoredValue($openedRow))->equals(1);
    verify($this->getStoredValue($clickedRow))->equals(1);
  }

  public function testItLeavesAQueueWithoutLinksTrackedWhenItHasOpens() {
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $opener = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_GRANTED, null);
    $other = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_GRANTED, null);
    $openerRow = $this->createRow($newsletter, $opener, '-1 day');
    $otherRow = $this->createRow($newsletter, $other, '-1 day');
    (new StatisticsOpens($newsletter, $opener))->create();

    $this->migration->run();

    verify($this->getStoredValue($openerRow))->equals(1);
    verify($this->getStoredValue($otherRow))->equals(1);
  }

  public function testItKeepsTheSendDate() {
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $row = $this->createRow($newsletter, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days'), '-1 day');
    $before = $this->getStoredSentAt($row);

    $this->migration->run();

    verify($this->getStoredValue($row))->equals(0);
    verify($this->getStoredSentAt($row))->equals($before);
  }

  public function testItWorksAcrossBatches() {
    $migration = new class($this->diContainer) extends Migration_20260914_145549_Db {
      protected function getBatchSize(): int {
        return 1;
      }
    };
    $rows = [];
    for ($i = 0; $i < 3; $i++) {
      $newsletter = (new Newsletter())->withSendingQueue()->create();
      $rows[] = $this->createRow($newsletter, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_GRANTED, null), '-1 day');
      $optedOut = $this->createNewsletterWithLink();
      $rows[] = $this->createRow($optedOut, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days'), '-1 day');
    }

    $migration->run();

    foreach ($rows as $row) {
      verify($this->getStoredValue($row))->equals(0);
    }
  }

  public function testItCanRunTwice() {
    $newsletter = $this->createNewsletterWithLink();
    $row = $this->createRow($newsletter, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days'), '-1 day');

    $this->migration->run();
    $this->migration->run();

    verify($this->getStoredValue($row))->equals(0);
  }

  public function testItWorksAcrossIdWindows() {
    $migration = $this->createMigration(1000, 1);
    $rows = [];
    for ($i = 0; $i < 3; $i++) {
      $newsletter = (new Newsletter())->withSendingQueue()->create();
      $rows[] = $this->createRow($newsletter, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_GRANTED, null), '-1 day');
      $optedOut = $this->createNewsletterWithLink();
      $rows[] = $this->createRow($optedOut, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days'), '-1 day');
    }

    $migration->run();

    foreach ($rows as $row) {
      verify($this->getStoredValue($row))->equals(0);
    }
  }

  public function testItClearsItsProgressWhenItFinishes() {
    $newsletter = $this->createNewsletterWithLink();
    $this->createRow($newsletter, $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days'), '-1 day');

    $this->migration->run();

    verify($this->settings->get('sent_with_tracking_backfill'))->null();
  }

  public function testItCarriesOnFromWhereAStoppedRunLeftOff() {
    $alreadyDone = (new Newsletter())->withSendingQueue()->create();
    $subscriber = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_GRANTED, null);
    $skippedRow = $this->createRow($alreadyDone, $subscriber, '-1 day');
    $doneQueue = $alreadyDone->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $doneQueue);
    $laterNewsletter = (new Newsletter())->withSendingQueue()->create();
    $laterRow = $this->createRow($laterNewsletter, $subscriber, '-1 day');
    $this->settings->set('sent_with_tracking_backfill', ['step' => 'links', 'lastId' => (int)$doneQueue->getId()]);

    $this->migration->run();

    verify($this->getStoredValue($skippedRow))->equals(1);
    verify($this->getStoredValue($laterRow))->equals(0);
  }

  public function testItCarriesOnWithinTheOptOutStep() {
    $newsletter = $this->createNewsletterWithLink();
    $early = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days');
    $earlyRow = $this->createRow($newsletter, $early, '-1 day');
    $later = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days');
    $laterRow = $this->createRow($newsletter, $later, '-1 day');
    $this->settings->set('sent_with_tracking_backfill', ['step' => 'opt-out', 'lastId' => (int)$early->getId()]);

    $this->migration->run();

    verify($this->getStoredValue($earlyRow))->equals(1);
    verify($this->getStoredValue($laterRow))->equals(0);
  }

  public function testItStartsOverWhenTheStoredStepIsNotOneOfItsOwn() {
    $newsletter = $this->createNewsletterWithLink();
    $subscriber = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_DENIED, '-3 days');
    $row = $this->createRow($newsletter, $subscriber, '-1 day');
    $this->settings->set('sent_with_tracking_backfill', ['step' => 'nonsense', 'lastId' => PHP_INT_MAX]);

    $this->migration->run();

    verify($this->getStoredValue($row))->equals(0);
  }

  public function testItPicksUpQueuesThatAStoppedRunDidNotReach() {
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $subscriber = $this->createSubscriber(SubscriberEntity::TRACKING_CONSENT_GRANTED, null);
    $row = $this->createRow($newsletter, $subscriber, '-1 day');
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $this->settings->set('sent_with_tracking_backfill', ['step' => 'links', 'lastId' => (int)$queue->getId() - 1]);

    $this->migration->run();

    verify($this->getStoredValue($row))->equals(0);
  }

  private function createMigration(int $batchSize, int $windowSize): Migration_20260914_145549_Db {
    return new class($this->diContainer, $batchSize, $windowSize) extends Migration_20260914_145549_Db {
      /** @var int */
      private $batchSize;

      /** @var int */
      private $windowSize;

      public function __construct(
        \MailPoet\DI\ContainerWrapper $container,
        int $batchSize,
        int $windowSize
      ) {
        parent::__construct($container);
        $this->batchSize = $batchSize;
        $this->windowSize = $windowSize;
      }

      protected function getBatchSize(): int {
        return $this->batchSize;
      }

      protected function getWindowSize(): int {
        return $this->windowSize;
      }
    };
  }

  private function createNewsletterWithLink(): NewsletterEntity {
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    (new NewsletterLink($newsletter))->withHash(uniqid())->create();
    return $newsletter;
  }

  private function createSubscriber(string $consent, ?string $consentChanged): SubscriberEntity {
    $subscriber = (new Subscriber())->withTrackingConsent($consent)->create();
    $table = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement(
      "UPDATE `{$table}` SET tracking_consent_updated_at = ? WHERE id = ?",
      [$consentChanged ? (new \DateTimeImmutable($consentChanged))->format('Y-m-d H:i:s') : null, $subscriber->getId()]
    );
    return $subscriber;
  }

  private function createRow(NewsletterEntity $newsletter, SubscriberEntity $subscriber, string $sentAt): int {
    $row = (new StatisticsNewsletters($newsletter, $subscriber))
      ->withSentAt(new \DateTimeImmutable($sentAt))
      ->create();
    return (int)$row->getId();
  }

  private function getStoredValue(int $rowId): int {
    $value = $this->entityManager->getConnection()->fetchOne(
      "SELECT sent_with_tracking FROM `{$this->getStatisticsTable()}` WHERE id = ?",
      [$rowId]
    );
    $this->assertIsNumeric($value);
    return (int)$value;
  }

  private function getStoredSentAt(int $rowId): string {
    $value = $this->entityManager->getConnection()->fetchOne(
      "SELECT sent_at FROM `{$this->getStatisticsTable()}` WHERE id = ?",
      [$rowId]
    );
    $this->assertIsString($value);
    return $value;
  }

  private function getStatisticsTable(): string {
    return $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName();
  }
}
