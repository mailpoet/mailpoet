<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\UserAgentEntity;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;

class EmailActionTest extends \MailPoetTest {
  /** @var EmailAction */
  private $emailAction;

  /** @var NewsletterEntity */
  private $newsletter;
  /** @var NewsletterEntity */
  private $newsletter2;
  /** @var NewsletterEntity */
  private $newsletter3;

  public $subscriberOpenedNotClicked;
  public $subscriberNotSent;
  public $subscriberNotOpened;
  public $subscriberOpenedClicked;

  public function _before() {
    $this->cleanData();
    $this->emailAction = $this->diContainer->get(EmailAction::class);
    $this->newsletter = new NewsletterEntity();
    $this->newsletter2 = new NewsletterEntity();
    $this->newsletter3 = new NewsletterEntity();
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $task2 = new ScheduledTaskEntity();
    $this->entityManager->persist($task2);
    $task3 = new ScheduledTaskEntity();
    $this->entityManager->persist($task3);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($this->newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $queue2 = new SendingQueueEntity();
    $queue2->setNewsletter($this->newsletter2);
    $queue2->setTask($task2);
    $this->entityManager->persist($queue2);
    $queue3 = new SendingQueueEntity();
    $queue3->setNewsletter($this->newsletter);
    $queue3->setTask($task3);
    $this->entityManager->persist($queue3);

    $this->newsletter->getQueues()->add($queue);
    $this->newsletter->setSubject('newsletter 1');
    $this->newsletter->setStatus('sent');
    $this->newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($this->newsletter);
    $this->newsletter2->getQueues()->add($queue2);
    $this->newsletter2->setSubject('newsletter 2');
    $this->newsletter2->setStatus('sent');
    $this->newsletter2->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($this->newsletter2);
    $this->newsletter3->getQueues()->add($queue3);
    $this->newsletter3->setSubject('newsletter 3');
    $this->newsletter3->setStatus('sent');
    $this->newsletter3->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($this->newsletter3);
    $this->entityManager->flush();

    $this->subscriberOpenedClicked = $this->createSubscriber('opened_clicked@example.com');
    $this->subscriberOpenedNotClicked = $this->createSubscriber('opened_not_clicked@example.com');
    $subscriberOpenedNotClicked2 = $this->createSubscriber('opened_clicked2@example.com');
    $subscriberOpenedNotClicked3 = $this->createSubscriber('opened_clicked3@example.com');
    $this->subscriberNotOpened = $this->createSubscriber('not_opened@example.com');
    $this->subscriberNotSent = $this->createSubscriber('not_sent@example.com');

    $this->createStatsNewsletter($this->subscriberOpenedClicked, $this->newsletter);
    $this->createStatsNewsletter($this->subscriberOpenedNotClicked, $this->newsletter);
    $this->createStatsNewsletter($this->subscriberNotOpened, $this->newsletter);
    $this->createStatsNewsletter($subscriberOpenedNotClicked2, $this->newsletter2);
    $this->createStatsNewsletter($subscriberOpenedNotClicked3, $this->newsletter3);

    $this->createStatisticsOpens($this->subscriberOpenedClicked, $this->newsletter);
    $this->createStatisticsOpens($this->subscriberOpenedNotClicked, $this->newsletter);
    $this->createStatisticsOpens($subscriberOpenedNotClicked2, $this->newsletter2);
    $this->createStatisticsOpens($subscriberOpenedNotClicked3, $this->newsletter3);

    $this->addClickedToLink('http://example.com', $this->newsletter, $this->subscriberOpenedClicked);
  }

  public function testGetOpened() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_OPENED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
    expect($subscriber2->getEmail())->equals('opened_not_clicked@example.com');
  }

  public function testNotOpened() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_OPENED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('not_opened@example.com');
  }

  public function testGetClickedWithoutLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWithLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, (int)$this->newsletter->getId(), 1);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWrongLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, (int)$this->newsletter->getId(), 2);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(0);
  }

  public function testGetNotClickedWithLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, (int)$this->newsletter->getId(), 1);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber2->getEmail())->equals('not_opened@example.com');
  }

  public function testGetNotClickedWithWrongLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, (int)$this->newsletter->getId(), 2);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(3);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $subscriber3 = $this->entityManager->find(SubscriberEntity::class, $result[2]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
    expect($subscriber2->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber3->getEmail())->equals('not_opened@example.com');
  }

  public function testGetNotClickedWithoutLink() {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_NOT_CLICKED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber2->getEmail())->equals('not_opened@example.com');
  }

  public function testOpensNotIncludeMachineOpens() {
    $subscriberOpenedMachine = $this->createSubscriber('opened_machine@example.com');
    $this->createStatsNewsletter($subscriberOpenedMachine, $this->newsletter);
    $open = $this->createStatisticsOpens($subscriberOpenedMachine, $this->newsletter);
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $userAgent = new UserAgentEntity(UserAgentEntity::MACHINE_USER_AGENTS[0]);
    $this->entityManager->persist($userAgent);
    $open->setUserAgent($userAgent);
    $this->entityManager->flush();

    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_OPENED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->rowCount();
    expect($result)->equals(2);
  }

  public function testMachineOpens() {
    $subscriberOpenedMachine = $this->createSubscriber('opened_machine@example.com');
    $this->createStatsNewsletter($subscriberOpenedMachine, $this->newsletter);
    $open = $this->createStatisticsOpens($subscriberOpenedMachine, $this->newsletter);
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $userAgent = new UserAgentEntity(UserAgentEntity::MACHINE_USER_AGENTS[0]);
    $this->entityManager->persist($userAgent);
    $open->setUserAgent($userAgent);
    $this->entityManager->flush();

    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_MACHINE_OPENED, (int)$this->newsletter->getId());
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->rowCount();
    expect($result)->equals(1);
  }

  private function getQueryBuilder() {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  /**
   * @param string $action
   * @param int|int[] $newsletterId
   * @param int|null $linkId
   * @param string|null $operator
   * @return DynamicSegmentFilterEntity
   */
  private function getSegmentFilter(string $action, $newsletterId = null, int $linkId = null, string $operator = null): DynamicSegmentFilterEntity {
    $data = [
      'newsletter_id' => $newsletterId,
      'link_id' => $linkId,
    ];
    if ($operator) {
      $data['operator'] = $operator;
    }
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $action, $data);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $segmentFilterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
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

  private function createStatsNewsletter(SubscriberEntity $subscriber, NewsletterEntity $newsletter) {
    $queue = $this->newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $stats = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($stats);
    $this->entityManager->flush();
    return $stats;
  }

  private function createStatisticsOpens(SubscriberEntity $subscriber, NewsletterEntity $newsletter) {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    return $open;
  }

  private function addClickedToLink(string $link, NewsletterEntity $newsletter, SubscriberEntity $subscriber) {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $link = new NewsletterLinkEntity($this->newsletter, $queue, $link, uniqid());
    $this->entityManager->persist($link);
    $this->entityManager->flush();
    $click = new StatisticsClickEntity(
      $newsletter,
      $queue,
      $subscriber,
      $link,
      1
    );
    $this->entityManager->persist($click);
    $this->entityManager->flush();
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
    $this->truncateEntity(UserAgentEntity::class);
  }
}
