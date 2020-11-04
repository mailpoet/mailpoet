<?php

namespace MailPoet\Test\Router\Endpoints;

use Codeception\Stub;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Router\Endpoints\Track;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Tasks\Sending as SendingTask;

class TrackTest extends \MailPoetTest {
  public $track;
  public $trackData;
  public $link;
  public $queue;
  public $subscriber;
  public $newsletter;

  public function _before() {
    parent::_before();
    $this->cleanup();
    // create newsletter
    $newsletter = new NewsletterEntity();
    $newsletter->setType('type');
    $newsletter->setSubject('Subject');
    $this->newsletter = $newsletter;
    $this->entityManager->persist($newsletter);
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
    $task->setType('sending');
    $this->entityManager->persist($task);
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($newsletter);
    $queue->setSubscribers((string)$subscriber->getId());
    $this->queue = $queue;
    $this->entityManager->persist($queue);
    // create link
    $link = new NewsletterLinkEntity($newsletter, $queue, 'url', 'hash');
    $this->link = $link;
    $this->entityManager->persist($link);
    $scheduledTaskSubscriber = new ScheduledTaskSubscriberEntity($task, $subscriber, 1);
    $this->entityManager->persist($scheduledTaskSubscriber);
    $this->entityManager->flush();
    $subscriberModel = Subscriber::findOne($subscriber->getId());
    $linkTokens = new LinkTokens;
    // build track data
    $this->trackData = [
      'queue_id' => $queue->getId(),
      'subscriber_id' => $subscriber->getId(),
      'newsletter_id' => $newsletter->getId(),
      'subscriber_token' => $linkTokens->getToken($subscriberModel),
      'link_hash' => $link->getHash(),
      'preview' => false,
    ];
    $queue = SendingQueue::findOne($queue->getId());
    assert($queue instanceof SendingQueue);
    $queue = SendingTask::createFromQueue($queue);
    $queue->updateProcessedSubscribers([$subscriberModel->id]);
    // instantiate class
    $this->track = $this->diContainer->get(Track::class);
  }

  public function testItReturnsFalseWhenTrackDataIsMissing() {
    // queue ID is required
    $data = $this->trackData;
    unset($data['queue_id']);
    expect($this->track->_processTrackData($data))->false();
    // subscriber ID is required
    $data = $this->trackData;
    unset($data['subscriber_id']);
    expect($this->track->_processTrackData($data))->false();
    // subscriber token is required
    $data = $this->trackData;
    unset($data['subscriber_token']);
    expect($this->track->_processTrackData($data))->false();
  }

  public function testItFailsWhenSubscriberTokenDoesNotMatch() {
    $data = (object)array_merge(
      $this->trackData,
      [
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter,
      ]
    );
    $data->subscriber->setEmail('random@email.com');
    $this->entityManager->flush();
    $track = Stub::make(Track::class, [
      'linkTokens' => new LinkTokens,
      'terminate' => function($code) {
        expect($code)->equals(403);
      },
    ]);
    $track->_validateTrackData($data);
  }

  public function testItFailsWhenSubscriberIsNotOnProcessedList() {
    $data = (object)array_merge(
      $this->trackData,
      [
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter,
        'subscriber_token' => $this->subscriber->getLinkToken(),
      ]
    );
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('test1@example.com');
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $subscriber->setLinkToken($this->subscriber->getLinkToken());
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    $data->subscriber->setId($subscriber->getId());
    expect($this->track->_validateTrackData($data))->false();
  }

  public function testItDoesNotRequireWpUsersToBeOnProcessedListWhenPreviewIsEnabled() {
    $data = (object)array_merge(
      $this->trackData,
      [
        'queue' => $this->queue,
        'subscriber' => $this->subscriber,
        'newsletter' => $this->newsletter,
      ]
    );
    $this->subscriber->setWpUserId(99);
    $this->entityManager->flush();
    $data->preview = true;
    expect($this->track->_validateTrackData($data))->equals($data);
  }

  public function testItRequiresValidQueueToGetNewsletter() {
    $data = $this->trackData;
    $data['newsletter_id'] = false;
    $data['queue_id'] = 99;
    $processedData = $this->track->_processTrackData($data);
    expect($processedData)->false();
  }

  public function testItGetsNewsletterFromQueue() {
    $data = $this->trackData;
    $data['newsletter_id'] = false;
    $processedData = $this->track->_processTrackData($data);
    expect($processedData->newsletter->getId())->equals($this->newsletter->getId());
  }

  public function testItProcessesTrackData() {
    $processedData = $this->track->_processTrackData($this->trackData);
    expect($processedData->queue->getId())->equals($this->queue->getId());
    expect($processedData->subscriber->getId())->equals($this->subscriber->getId());
    expect($processedData->newsletter->getId())->equals($this->newsletter->getId());
    expect($processedData->link->getId())->equals($this->link->getId());
  }

  public function testItGetsProperHashWhenDuplicateHashesExist() {
    // create another newsletter and queue
    $newsletter = Newsletter::create();
    $newsletter->type = 'type';
    $newsletter = $newsletter->save();
    $queue = SendingTask::create();
    $queue->newsletterId = $newsletter->id;
    $queue->setSubscribers([$this->subscriber->getId()]);
    $queue->updateProcessedSubscribers([$this->subscriber->getId()]);
    $queue->save();
    $trackData = $this->trackData;
    $trackData['queue_id'] = $queue->id;
    $trackData['newsletter_id'] = $newsletter->id;
    // create another link with the same hash but different queue ID
    $link = NewsletterLink::create();
    $link->hash = $this->link->getHash();
    $link->url = $this->link->getUrl();
    $link->newsletterId = $trackData['newsletter_id'];
    $link->queueId = $trackData['queue_id'];
    $link = $link->save();
    // assert that 2 links with identical hash exist
    $newsletterLink = NewsletterLink::where('hash', $link->hash)->findMany();
    expect($newsletterLink)->count(2);

    // assert that the fetched link ID belong to the newly created link
    $processedData = $this->track->_processTrackData($trackData);
    expect($processedData->link->getId())->equals($link->id);
  }

  public function _after() {
    $this->cleanup();
  }

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(NewsletterLinkEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
  }
}
