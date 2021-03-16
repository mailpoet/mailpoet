<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;

class FilterHandlerTest extends \MailPoetTest {

  /** @var FilterHandler */
  private $filterHandler;

  /** @var SubscriberEntity */
  private $subscriber1;

  /** @var SubscriberEntity */
  private $subscriber2;

  public function _before() {
    $this->cleanWpUsers();
    $this->filterHandler = $this->diContainer->get(FilterHandler::class);
    $id = $this->tester->createWordPressUser('user-role-test1@example.com', 'editor');
    $this->tester->generateSubscriber([
      'email' => 'user-role-test1@example.com',
      'wp_user_id' => $id,
    ]);
    $id = $this->tester->createWordPressUser('user-role-test2@example.com', 'administrator');
    $this->tester->generateSubscriber([
      'email' => 'user-role-test2@example.com',
      'wp_user_id' => $id,
    ]);
    $id = $this->tester->createWordPressUser('user-role-test3@example.com', 'editor');
    $this->tester->generateSubscriber([
      'email' => 'user-role-test3@example.com',
      'wp_user_id' => $id,
    ]);

    // fetch entities
    /** @var SubscribersRepository $subscribersRepository */
    $subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber1 = $subscribersRepository->findOneBy(['email' => 'user-role-test1@example.com']);
    assert($subscriber1 instanceof SubscriberEntity);
    $this->subscriber1 = $subscriber1;
    $subscriber2 = $subscribersRepository->findOneBy(['email' => 'user-role-test2@example.com']);
    assert($subscriber2 instanceof SubscriberEntity);
    $this->subscriber2 = $subscriber2;
  }

  public function testItAppliesFilter() {
    $segment = $this->getSegment('editor');
    $statement = $this->filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect($result)->count(2);
    $subscriber1 = $this->entityManager->find(SubscriberEntity::class, $result[0]['id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $subscriber2 = $this->entityManager->find(SubscriberEntity::class, $result[1]['id']);
    assert($subscriber2 instanceof SubscriberEntity);
    expect($subscriber1->getEmail())->equals('user-role-test1@example.com');
    expect($subscriber2->getEmail())->equals('user-role-test3@example.com');
  }

  public function testItAppliesTwoFiltersWithoutSpecifyingConnection() {
    $segment = $this->getSegment('editor');
    $filter = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'administrator',
    ]);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filter);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    $this->entityManager->flush();
    $statement = $this->filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect($result)->count(3);
  }

  public function testItAppliesTwoFiltersWithOr() {
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $filterData = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'administrator',
      'connect' => 'or',
    ]);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    $filterData = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
      'connect' => 'or',
    ]);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filterData);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    $this->entityManager->flush();
    $statement = $this->filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect($result)->count(3);
  }

  public function testItAppliesTwoFiltersWithAnd() {
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    // filter user is an editor
    $editorData = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
      'connect' => 'and',
    ]);
    $filterEditor = new DynamicSegmentFilterEntity($segment, $editorData);
    $this->entityManager->persist($filterEditor);
    $segment->addDynamicFilter($filterEditor);
    // filter user opened an email
    $newsletter = new NewsletterEntity();
    $task = new ScheduledTaskEntity();
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $this->entityManager->persist($queue);
    $newsletter->getQueues()->add($queue);
    $newsletter->setSubject('newsletter 1');
    $newsletter->setStatus('sent');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($newsletter);
    $open = new StatisticsOpenEntity($newsletter, $queue, $this->subscriber1);
    $this->entityManager->persist($open);
    $open = new StatisticsOpenEntity($newsletter, $queue, $this->subscriber2);
    $this->entityManager->persist($open);
    $this->entityManager->flush();

    $openedData = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_EMAIL,
      'action' => EmailAction::ACTION_OPENED,
      'newsletter_id' => $newsletter->getId(),
      'connect' => 'and',
    ]);
    $filterOpened = new DynamicSegmentFilterEntity($segment, $openedData);
    $this->entityManager->persist($filterOpened);
    $segment->addDynamicFilter($filterOpened);
    $this->entityManager->flush();

    $statement = $this->filterHandler->apply($this->getQueryBuilder(), $segment)->execute();
    assert($statement instanceof Statement);
    $result = $statement->fetchAll();
    expect($result)->count(1);
  }

  private function getSegment(string $role): SegmentEntity {
    $filter = new DynamicSegmentFilterData([
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => $role,
    ]);
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $filter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicSegmentFilter);
    $this->entityManager->flush();
    return $segment;
  }

  private function getQueryBuilder() {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id")
      ->from($subscribersTable);
  }

  public function _after() {
    $this->cleanWpUsers();
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
  }

  private function cleanWpUsers() {
    $emails = ['user-role-test1@example.com', 'user-role-test2@example.com', 'user-role-test3@example.com'];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
  }
}
