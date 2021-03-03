<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;

class EmailActionTest extends \MailPoetTest {
  /** @var EmailAction */
  private $emailAction;

  /** @var NewsletterEntity */
  private $newsletter;
  public $subscriberOpenedNotClicked;
  public $subscriberNotSent;
  public $subscriberNotOpened;
  public $subscriberOpenedClicked;

  public function _before() {
    $this->cleanData();
    $this->emailAction = $this->diContainer->get(EmailAction::class);
    $this->newsletter = new NewsletterEntity();
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setNewsletter($this->newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $this->newsletter->getQueues()->add($queue);
    $this->newsletter->setSubject('newsletter 1');
    $this->newsletter->setStatus('sent');
    $this->newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($this->newsletter);
    $this->entityManager->flush();

    $this->subscriberOpenedClicked = $this->createSubscriber('opened_clicked@example.com');
    $this->subscriberOpenedNotClicked = $this->createSubscriber('opened_not_clicked@example.com');
    $this->subscriberNotOpened = $this->createSubscriber('not_opened@example.com');
    $this->subscriberNotSent = $this->createSubscriber('not_sent@example.com');

    $this->createStatsNewsletter($this->subscriberOpenedClicked);
    $this->createStatsNewsletter($this->subscriberOpenedNotClicked);
    $this->createStatsNewsletter($this->subscriberNotOpened);

    $this->createStatisticsOpens($this->subscriberOpenedClicked);
    $this->createStatisticsOpens($this->subscriberOpenedNotClicked);


    $link = new NewsletterLinkEntity($this->newsletter, $queue, 'http://example.com', 'asdfgh');
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    $click = new StatisticsClickEntity(
      $this->newsletter,
      $queue,
      $this->subscriberOpenedClicked,
      $link,
      1
    );
    $this->entityManager->persist($click);
    $this->entityManager->flush();
  }

  public function testGetOpened() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_OPENED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
    expect($subscriber2->getEmail())->equals('opened_not_clicked@example.com');
  }

  public function testNotOpened() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_OPENED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('not_opened@example.com');
  }

  public function testGetClickedWithoutLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWithLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, (int)$this->newsletter->getId(), 1);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWrongLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, (int)$this->newsletter->getId(), 2);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(0);
  }

  public function testGetNotClickedWithLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, (int)$this->newsletter->getId(), 1);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber2->getEmail())->equals('not_opened@example.com');
  }

  public function testGetNotClickedWithWrongLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, (int)$this->newsletter->getId(), 2);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
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
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
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

  private function getSegmentFilter(string $action, int $newsletterId, int $linkId = null): DynamicSegmentFilterData {
    return new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => $action,
      'newsletter_id' => $newsletterId,
      'link_id' => $linkId,
    ]);
  }

  private function createSubscriber(string $email) {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setLastName('Last');
    $subscriber->setFirstName('First');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createStatsNewsletter(SubscriberEntity $subscriber) {
    $queue = $this->newsletter->getLatestQueue();
    assert($queue instanceof SendingQueueEntity);
    $stats = new StatisticsNewsletterEntity($this->newsletter, $queue, $subscriber);
    $this->entityManager->persist($stats);
    $this->entityManager->flush();
    return $stats;
  }

  private function createStatisticsOpens(SubscriberEntity $subscriber) {
    $queue = $this->newsletter->getLatestQueue();
    assert($queue instanceof SendingQueueEntity);
    $open = new StatisticsOpenEntity($this->newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    return $open;
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
    $this->truncateEntity(StatisticsClickEntity::class);
    $this->truncateEntity(StatisticsNewsletterEntity::class);
    $this->truncateEntity(NewsletterLinkEntity::class);
  }
}
