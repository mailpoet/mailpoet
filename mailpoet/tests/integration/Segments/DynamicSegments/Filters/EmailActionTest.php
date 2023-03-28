<?php declare(strict_types = 1);

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
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class EmailActionTest extends \MailPoetTest {
  /** @var EmailAction */
  private $emailAction;

  /** @var NewsletterEntity */
  private $newsletter;
  /** @var NewsletterEntity */
  private $newsletter2;
  /** @var NewsletterEntity */
  private $newsletter3;

  /** @var SubscriberEntity */
  public $subscriberOpenedNotClicked;
  /** @var SubscriberEntity */
  public $subscriberNotSent;
  /** @var SubscriberEntity */
  public $subscriberNotOpened;
  /** @var SubscriberEntity */
  public $subscriberOpenedClicked;

  public function _before(): void {
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
    $subscriberOpenedNotClicked2 = $this->createSubscriber('opened_not_clicked2@example.com');
    $subscriberOpenedNotClicked3 = $this->createSubscriber('opened_not_clicked3@example.com');
    $subscriberOpenedNotClicked4 = $this->createSubscriber('opened_not_clicked4@example.com');
    $this->subscriberNotOpened = $this->createSubscriber('not_opened@example.com');
    $this->subscriberNotSent = $this->createSubscriber('not_sent@example.com');

    $this->createStatsNewsletter($this->subscriberOpenedClicked, $this->newsletter);
    $this->createStatsNewsletter($this->subscriberOpenedNotClicked, $this->newsletter);
    $this->createStatsNewsletter($this->subscriberNotOpened, $this->newsletter);
    $this->createStatsNewsletter($subscriberOpenedNotClicked2, $this->newsletter2);
    $this->createStatsNewsletter($subscriberOpenedNotClicked4, $this->newsletter2);
    $this->createStatsNewsletter($subscriberOpenedNotClicked3, $this->newsletter3);
    $this->createStatsNewsletter($subscriberOpenedNotClicked4, $this->newsletter3);

    $this->createStatisticsOpens($this->subscriberOpenedClicked, $this->newsletter);
    $this->createStatisticsOpens($this->subscriberOpenedNotClicked, $this->newsletter);
    $this->createStatisticsOpens($subscriberOpenedNotClicked2, $this->newsletter2);
    $this->createStatisticsOpens($subscriberOpenedNotClicked4, $this->newsletter2);
    $this->createStatisticsOpens($subscriberOpenedNotClicked3, $this->newsletter3);
    $this->createStatisticsOpens($subscriberOpenedNotClicked4, $this->newsletter3);
  }

  public function testGetOpened(): void {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_OPENED, [
      'newsletters' => [$this->newsletter->getId()],
    ]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
    expect($subscriber2->getEmail())->equals('opened_not_clicked@example.com');
  }

  public function testGetOpenedOperatorAny(): void {
    $segmentFilter = $this->getSegmentFilter(
      EmailAction::ACTION_OPENED,
      [
        'newsletters' => [(int)$this->newsletter->getId(), (int)$this->newsletter2->getId()],
        'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
      ]
    );
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(4);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $subscriber3 = $this->entityManager->find(SubscriberEntity::class, $result[2]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    $subscriber4 = $this->entityManager->find(SubscriberEntity::class, $result[3]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber4);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
    expect($subscriber2->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber3->getEmail())->equals('opened_not_clicked2@example.com');
    expect($subscriber4->getEmail())->equals('opened_not_clicked4@example.com');
  }

  public function testGetOpenedOperatorAll(): void {
    $segmentFilter = $this->getSegmentFilter(
      EmailAction::ACTION_OPENED,
      [
        'newsletters' => [(int)$this->newsletter2->getId(), (int)$this->newsletter3->getId()],
        'operator' => DynamicSegmentFilterData::OPERATOR_ALL,
      ]
    );
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened_not_clicked4@example.com');
  }

  public function testGetOpenedOperatorNone(): void {
    $segmentFilter = $this->getSegmentFilter(
      EmailAction::ACTION_OPENED,
      [
        'newsletters' => [(int)$this->newsletter->getId()],
        'operator' => DynamicSegmentFilterData::OPERATOR_NONE,
      ]
    );
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('not_opened@example.com');
  }

  public function testGetClickedWithoutSavedLinks(): void {
    $this->createClickedLink('http://example.com', $this->newsletter, $this->subscriberOpenedClicked); // id 1
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, [
      'newsletter_id' => (int)$this->newsletter->getId(),
    ]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(1);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWithAnyOfLinks(): void {
    // 2 Links each clicked by a different subscriber
    $link1 = $this->createClickedLink('http://example.com', $this->newsletter, $this->subscriberOpenedClicked); // id 1
    $subscriberClickedOther = $this->createSubscriber('second_click@example.com');
    $link2 = $this->createClickedLink('http://example2.com', $this->newsletter, $subscriberClickedOther); // id 2
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, [
      'newsletter_id' => (int)$this->newsletter->getId(),
      'link_ids' => [$link1->getId(), $link2->getId()],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
    ]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWithAllOfLinks(): void {
    // 2 Links both clicked by $this->subscriberOpenedClicked and second one clicked only by other subscriber
    $link1 = $this->createClickedLink('http://example.com', $this->newsletter, $this->subscriberOpenedClicked);
    $link2 = $this->createClickedLink('http://example2.com', $this->newsletter, $this->subscriberOpenedClicked);
    $subscriberClickedOther = $this->createSubscriber('second_click@example.com');
    $this->addClickToLink($link2, $subscriberClickedOther);

    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, [
      'newsletter_id' => (int)$this->newsletter->getId(),
      'link_ids' => [$link1->getId(), $link2->getId()],
      'operator' => DynamicSegmentFilterData::OPERATOR_ALL,
    ]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(1); // Only $this->subscriberOpenedClicked clicked all
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWithAllOfAndNoSavedLinks(): void {
    // 2 Links both clicked by $this->subscriberOpenedClicked and second one clicked only by other subscriber
    $this->createClickedLink('http://example.com', $this->newsletter, $this->subscriberOpenedClicked);
    $link2 = $this->createClickedLink('http://example2.com', $this->newsletter, $this->subscriberOpenedClicked);
    $subscriberClickedOther = $this->createSubscriber('second_click@example.com');
    $this->addClickToLink($link2, $subscriberClickedOther);

    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, [
      'newsletter_id' => (int)$this->newsletter->getId(),
      'link_ids' => [],
      'operator' => DynamicSegmentFilterData::OPERATOR_ALL,
    ]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(1); // Only $this->subscriberOpenedClicked clicked all
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('opened_clicked@example.com');
  }

  public function testGetClickedWrongLink(): void {
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, [
      'newsletter_id' => (int)$this->newsletter->getId(),
      'link_ids' => [2],
      'operator' => DynamicSegmentFilterData::OPERATOR_ANY,
    ]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(0);
  }

  public function testGetClickedWithNoneOfLinks(): void {
    $link = $this->createClickedLink('http://example.com', $this->newsletter, $this->subscriberOpenedClicked);
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, [
      'newsletter_id' => (int)$this->newsletter->getId(),
      'link_ids' => [$link->getId(), 2],
      'operator' => DynamicSegmentFilterData::OPERATOR_NONE,
    ]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber2->getEmail())->equals('not_opened@example.com');
  }

  public function testGetClickedWithNoneAndNoSavedLinks(): void {
    $this->createClickedLink('http://example.com', $this->newsletter, $this->subscriberOpenedClicked);
    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_CLICKED, [
      'newsletter_id' => (int)$this->newsletter->getId(),
      'link_ids' => [],
      'operator' => DynamicSegmentFilterData::OPERATOR_NONE,
    ]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->fetchAllAssociative();
    expect(count($result))->equals(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber1->getEmail())->equals('opened_not_clicked@example.com');
    expect($subscriber2->getEmail())->equals('not_opened@example.com');
  }

  public function testOpensNotIncludeMachineOpens(): void {
    $subscriberOpenedMachine = $this->createSubscriber('opened_machine@example.com');
    $this->createStatsNewsletter($subscriberOpenedMachine, $this->newsletter);
    $open = $this->createStatisticsOpens($subscriberOpenedMachine, $this->newsletter);
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $userAgent = new UserAgentEntity(UserAgentEntity::MACHINE_USER_AGENTS[0]);
    $this->entityManager->persist($userAgent);
    $open->setUserAgent($userAgent);
    $this->entityManager->flush();

    $segmentFilter = $this->getSegmentFilter(EmailAction::ACTION_OPENED, ['newsletters' => [(int)$this->newsletter->getId()]]);
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->rowCount();
    expect($result)->equals(2);
  }

  public function testMachineOpensAny(): void {
    $subscriberOpenedMachine = $this->createSubscriber('opened_machine@example.com');
    $this->createStatsNewsletter($subscriberOpenedMachine, $this->newsletter);
    $open = $this->createStatisticsOpens($subscriberOpenedMachine, $this->newsletter);
    $open->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $userAgent = new UserAgentEntity(UserAgentEntity::MACHINE_USER_AGENTS[0]);
    $this->entityManager->persist($userAgent);
    $open->setUserAgent($userAgent);
    $this->entityManager->flush();

    $segmentFilter = $this->getSegmentFilter(
      EmailAction::ACTION_MACHINE_OPENED,
      ['newsletters' => [
        (int)$this->newsletter->getId(),
        (int)$this->newsletter2->getId(),
      ]]
    );
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->rowCount();
    expect($result)->equals(1);
  }

  public function testMachineOpensAll(): void {
    $subscriberOpenedMachine = $this->createSubscriber('opened_machine@example.com');
    $this->createStatsNewsletter($subscriberOpenedMachine, $this->newsletter);
    $this->createStatsNewsletter($subscriberOpenedMachine, $this->newsletter2);
    $open1 = $this->createStatisticsOpens($subscriberOpenedMachine, $this->newsletter);
    $open1->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $open2 = $this->createStatisticsOpens($subscriberOpenedMachine, $this->newsletter2);
    $open2->setUserAgentType(UserAgentEntity::USER_AGENT_TYPE_MACHINE);
    $userAgent = new UserAgentEntity(UserAgentEntity::MACHINE_USER_AGENTS[0]);
    $this->entityManager->persist($userAgent);
    $open1->setUserAgent($userAgent);
    $open2->setUserAgent($userAgent);
    $this->entityManager->flush();

    $segmentFilter = $this->getSegmentFilter(
      EmailAction::ACTION_MACHINE_OPENED,
      [
        'newsletters' => [
          (int)$this->newsletter->getId(),
          (int)$this->newsletter2->getId(),
        ],
        'operator' => DynamicSegmentFilterData::OPERATOR_ALL,
      ]
    );
    $statement = $this->emailAction->apply($this->getQueryBuilder(), $segmentFilter)->execute();
    $this->assertInstanceOf(Statement::class, $statement);
    $result = $statement->rowCount();
    expect($result)->equals(1);
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id")
      ->from($subscribersTable);
  }

  private function getSegmentFilter(string $action, array $data): DynamicSegmentFilterEntity {
    $segmentFilterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_EMAIL, $action, $data);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $segmentFilterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setLastName('Last');
    $subscriber->setFirstName('First');
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createStatsNewsletter(SubscriberEntity $subscriber, NewsletterEntity $newsletter): StatisticsNewsletterEntity {
    $queue = $this->newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $stats = new StatisticsNewsletterEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($stats);
    $this->entityManager->flush();
    return $stats;
  }

  private function createStatisticsOpens(SubscriberEntity $subscriber, NewsletterEntity $newsletter): StatisticsOpenEntity {
    $queue = $newsletter->getLatestQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $open = new StatisticsOpenEntity($newsletter, $queue, $subscriber);
    $this->entityManager->persist($open);
    $this->entityManager->flush();
    return $open;
  }

  private function createClickedLink(string $link, NewsletterEntity $newsletter, SubscriberEntity $subscriber): NewsletterLinkEntity {
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
    return $link;
  }

  private function addClickToLink(NewsletterLinkEntity $link, SubscriberEntity $subscriberEntity): StatisticsClickEntity {
    $newsletter = $link->getNewsletter();
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $queue = $link->getQueue();
    $this->assertInstanceOf(SendingQueueEntity::class, $queue);
    $click = new StatisticsClickEntity(
      $newsletter,
      $queue,
      $subscriberEntity,
      $link,
      1
    );
    $this->entityManager->persist($click);
    $this->entityManager->flush();
    return $click;
  }
}
