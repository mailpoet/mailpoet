<?php declare(strict_types = 1);

namespace integration\Analytics;

use MailPoet\Analytics\UnsubscribeReporter;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

class UnsubscribeAnalyticsTest extends \MailPoetTest {

  /*** @var UnsubscribeReporter */
  private $unsubscribeReporter;

  public function _before() {
    parent::_before();
    $this->unsubscribeReporter = $this->diContainer->get(UnsubscribeReporter::class);
  }

  public function testItExposesUnsubscriptionProperties() {

    $createdAt = Carbon::now();
    $this->createStatisticsUnsubscribe($createdAt, StatisticsUnsubscribeEntity::METHOD_LINK);

    $createdAt = (new Carbon())->subMonths(3);
    $this->createStatisticsUnsubscribe($createdAt, StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);

    $createdAt = (new Carbon())->subMonths(4);
    $this->createStatisticsUnsubscribe($createdAt, StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);

    $createdAt = (new Carbon())->subMonths(5);
    $this->createStatisticsUnsubscribe($createdAt, StatisticsUnsubscribeEntity::METHOD_ONE_CLICK);

    $createdAt = (new Carbon())->subMonths(10);
    $this->createStatisticsUnsubscribe($createdAt, StatisticsUnsubscribeEntity::METHOD_LINK);

    $properties = $this->unsubscribeReporter->getProperties();
    expect($properties[UnsubscribeReporter::TOTAL])->equals(4);
    expect($properties[sprintf(UnsubscribeReporter::COUNT_PER_METHOD_PATTERN, '1 Click')])->equals(3);
    expect($properties[sprintf(UnsubscribeReporter::COUNT_PER_METHOD_PATTERN, 'Link')])->equals(1);
  }

  private function createStatisticsUnsubscribe(\DateTimeInterface $createdAt, $method): StatisticsUnsubscribeEntity {
    $subscriber = (new Subscriber())->create();
    $newsletter = (new Newsletter())->create();
    $task = (new ScheduledTask())->create(Sending::TASK_TYPE, null, (new Carbon())->subMonths(random_int(0, 12)));
    $queue = (new SendingQueue())->create($task);

    $entity = new StatisticsUnsubscribeEntity($newsletter, $queue, $subscriber);
    $entity->setCreatedAt($createdAt);
    $entity->setMethod($method);
    $this->entityManager->persist($entity);
    $this->entityManager->flush();
    return $entity;
  }
}
