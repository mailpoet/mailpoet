<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsNewsletters;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;

class EmailActionTest extends \MailPoetTest {
  /** @var EmailAction */
  private $emailAction;

  public $subscriberOpenedNotClicked;
  public $subscriberNotSent;
  public $subscriberNotOpened;
  public $subscriberOpenedClicked;
  public $newsletter;

  public function _before() {
    $this->emailAction = $this->diContainer->get(EmailAction::class);
    $this->newsletter = Newsletter::createOrUpdate([
      'subject' => 'newsletter 1',
      'status' => 'sent',
      'type' => Newsletter::TYPE_NOTIFICATION,
    ]);
    $this->subscriberOpenedClicked = Subscriber::createOrUpdate([
      'email' => 'opened_clicked@example.com',
    ]);
    $this->subscriberOpenedNotClicked = Subscriber::createOrUpdate([
      'email' => 'opened_not_clicked@example.com',
    ]);
    $this->subscriberNotOpened = Subscriber::createOrUpdate([
      'email' => 'not_opened@example.com',
    ]);
    $this->subscriberNotSent = Subscriber::createOrUpdate([
      'email' => 'not_sent@example.com',
    ]);
    StatisticsNewsletters::createMultiple([
      ['newsletter_id' => $this->newsletter->id, 'subscriber_id' => $this->subscriberOpenedClicked->id, 'queue_id' => 1],
      ['newsletter_id' => $this->newsletter->id, 'subscriber_id' => $this->subscriberOpenedNotClicked->id, 'queue_id' => 1],
      ['newsletter_id' => $this->newsletter->id, 'subscriber_id' => $this->subscriberNotOpened->id, 'queue_id' => 1],
    ]);
    StatisticsOpens::getOrCreate($this->subscriberOpenedClicked->id, $this->newsletter->id, 1);
    StatisticsOpens::getOrCreate($this->subscriberOpenedNotClicked->id, $this->newsletter->id, 1);
    StatisticsClicks::createOrUpdateClickCount(1, $this->subscriberOpenedClicked->id, $this->newsletter->id, 1);
  }

  public function testGetOpened() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_OPENED, $this->newsletter->id);
    $result = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute()->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
    expect($subscriber2->getEmail())->equals('opened_not_clicked@example.com');
  }

  public function testNotOpened() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_OPENED, $this->newsletter->id);
    $result = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute()->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('not_opened@example.com');
  }

  public function testGetClickedWithoutLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, $this->newsletter->id);
    $result = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute()->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWithLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, $this->newsletter->id, 1);
    $result = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute()->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWrongLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, $this->newsletter->id, 2);
    $result = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute()->fetchAll();
    expect(count($result))->equals(0);
  }

  public function testGetNotClickedWithLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, $this->newsletter->id, 1);
    $result = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute()->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber2->getEmail())->equals('not_opened@example.com');
  }

  public function testGetNotClickedWithWrongLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, $this->newsletter->id, 2);
    $result = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute()->fetchAll();
    expect(count($result))->equals(3);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    $subscriber3 = $this->entityManager->find(SubscriberEntity::class, $result[2]['id']);
    assert($subscriber3 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
    expect($subscriber2->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber3->getEmail())->equals('not_opened@example.com');
  }

  public function testGetNotClickedWithoutLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, $this->newsletter->id);
    $result = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute()->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber2->getEmail())->equals('not_opened@example.com');
  }

  private function getQueryBuilder() {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(string $action, int $newsletterId, int $linkId = null): DynamicSegmentFilterEntity {
    return new DynamicSegmentFilterEntity(
      new SegmentEntity('segment', SegmentEntity::TYPE_DYNAMIC),
      [
        'segmentType' => DynamicSegmentFilterEntity::TYPE_EMAIL,
        'action' => $action,
        'newsletter_id' => $newsletterId,
        'link_id' => $linkId,
      ]
    );
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    StatisticsClicks::where('newsletter_id', $this->newsletter->id)->findResultSet()->delete();
    StatisticsNewsletters::where('newsletter_id', $this->newsletter->id)->findResultSet()->delete();
    StatisticsOpens::where('newsletter_id', $this->newsletter->id)->findResultSet()->delete();
    $this->newsletter->delete();
    $this->subscriberOpenedClicked->delete();
    $this->subscriberOpenedNotClicked->delete();
    $this->subscriberNotOpened->delete();
    $this->subscriberNotSent->delete();
  }
}
