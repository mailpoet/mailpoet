<?php

namespace MailPoet\Test\Statistics\Track;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;

class OpensTest extends \MailPoetTest {
  public $opens;
  public $trackData;
  public $queue;
  public $subscriber;
  public $newsletter;

  /** @var StatisticsOpensRepository */
  private $statisticsOpensRepository;

  public function _before() {
    parent::_before();
    $this->cleanup();
    // create newsletter
    $newsletter = new NewsletterEntity();
    $newsletter->setType('type');
    $newsletter->setSubject('subject');
    $this->entityManager->persist($newsletter);
    $this->newsletter = $newsletter;
    // create subscriber
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test@example.com');
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $subscriber->setLinkToken('token');
    $this->subscriber = $subscriber;
    $this->entityManager->persist($subscriber);
    // create queue
    $task = new ScheduledTaskEntity();
    $task->setType(SendingTask::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $this->entityManager->persist($task);

    $queue = new SendingQueueEntity();
    $queue->setNewsletter($newsletter);
    $queue->setTask($task);
    $queue->setSubscribers((string)$subscriber->getId());
    $newsletter->getQueues()->add($queue);
    $this->entityManager->persist($queue);
    $this->entityManager->flush();

    $this->queue = $queue;
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    // build track data
    $this->trackData = (object)[
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => $linkTokens->getToken($subscriber),
      'preview' => false,
    ];
    // instantiate class
    $this->statisticsOpensRepository = $this->diContainer->get(StatisticsOpensRepository::class);
    $this->opens = new Opens($this->statisticsOpensRepository);
  }

  public function testItReturnsImageWhenTrackDataIsEmpty() {
    $opens = Stub::construct($this->opens, [$this->diContainer->get(StatisticsOpensRepository::class)], [
      'returnResponse' => Expected::exactly(1),
    ], $this);
    $opens->track(false);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  public function testItDoesNotTrackOpenEventFromWpUserWhenPreviewIsEnabled() {
    $data = $this->trackData;
    $data->subscriber->setWpUserId(99);
    $data->preview = true;
    $opens = Stub::construct($this->opens, [$this->diContainer->get(StatisticsOpensRepository::class)], [
      'returnResponse' => null,
    ], $this);
    $opens->track($data);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  public function testItReturnsNothingWhenImageDisplayIsDisabled() {
    expect($this->opens->track($this->trackData, $displayImage = false))->isEmpty();
  }

  public function testItTracksOpenEvent() {
    $opens = Stub::construct($this->opens, [$this->diContainer->get(StatisticsOpensRepository::class)], [
      'returnResponse' => null,
    ], $this);
    $opens->track($this->trackData);
    expect(StatisticsOpens::findMany())->notEmpty();
  }

  public function testItDoesNotTrackRepeatedOpenEvents() {
    $opens = Stub::construct($this->opens, [$this->diContainer->get(StatisticsOpensRepository::class)], [
      'returnResponse' => null,
    ], $this);
    for ($count = 0; $count <= 2; $count++) {
      $opens->track($this->trackData);
    }
    expect(count(StatisticsOpens::findMany()))->equals(1);
  }

  public function testItReturnsImageAfterTracking() {
    $opens = Stub::construct($this->opens, [$this->diContainer->get(StatisticsOpensRepository::class)], [
      'returnResponse' => Expected::exactly(1),
    ], $this);
    $opens->track($this->trackData);
  }

  public function testItSavesNewUserAgent() {
    $this->trackData->userAgent = 'User agent';
    $opens = Stub::construct($this->opens, [$this->diContainer->get(StatisticsOpensRepository::class)], [
      'returnResponse' => null,
    ], $this);
    $opens->track($this->trackData);
    $opens = $this->statisticsOpensRepository->findAll();
    expect($opens)->count(1);
    $open = $opens[0];
    $userAgent = $open->getUserAgent();
    expect($userAgent)->notNull();
  }

  public function testItSavesOpenWithExistingUserAgent() {

  }

  public function testItOverridesOldUserAgent() {

  }

  public function _after() {
    $this->cleanup();
  }

  public function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(StatisticsOpenEntity::class);
  }
}
