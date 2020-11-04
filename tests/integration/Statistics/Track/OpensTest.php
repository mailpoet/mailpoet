<?php

namespace MailPoet\Test\Statistics\Track;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Statistics\Track\Opens;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Idiorm\ORM;

class OpensTest extends \MailPoetTest {
  public $opens;
  public $trackData;
  public $queue;
  public $subscriber;
  public $newsletter;

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
    $linkTokens = new LinkTokens;
    // build track data
    $this->trackData = (object)[
      'queue' => $queue,
      'subscriber' => $subscriber,
      'newsletter' => $newsletter,
      'subscriber_token' => $linkTokens->getToken(SubscriberModel::findOne('test@example.com')),
      'preview' => false,
    ];
    // instantiate class
    $this->opens = new Opens();
  }

  public function testItReturnsImageWhenTrackDataIsEmpty() {
    $opens = Stub::make($this->opens, [
      'returnResponse' => Expected::exactly(1),
    ], $this);
    $opens->track(false);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  public function testItDoesNotTrackOpenEventFromWpUserWhenPreviewIsEnabled() {
    $data = $this->trackData;
    $data->subscriber->setWpUserId(99);
    $data->preview = true;
    $opens = Stub::make($this->opens, [
      'returnResponse' => null,
    ], $this);
    $opens->track($data);
    expect(StatisticsOpens::findMany())->isEmpty();
  }

  public function testItReturnsNothingWhenImageDisplayIsDisabled() {
    expect($this->opens->track($this->trackData, $displayImage = false))->isEmpty();
  }

  public function testItTracksOpenEvent() {
    $opens = Stub::make($this->opens, [
      'returnResponse' => null,
    ], $this);
    $opens->track($this->trackData);
    expect(StatisticsOpens::findMany())->notEmpty();
  }

  public function testItDoesNotTrackRepeatedOpenEvents() {
    $opens = Stub::make($this->opens, [
      'returnResponse' => null,
    ], $this);
    for ($count = 0; $count <= 2; $count++) {
      $opens->track($this->trackData);
    }
    expect(count(StatisticsOpens::findMany()))->equals(1);
  }

  public function testItReturnsImageAfterTracking() {
    $opens = Stub::make($this->opens, [
      'returnResponse' => Expected::exactly(1),
    ], $this);
    $opens->track($this->trackData);
  }

  public function _after() {
    $this->cleanup();
  }

  public function cleanup() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . StatisticsOpens::$_table);
  }
}
