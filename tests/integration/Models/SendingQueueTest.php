<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Helpers;
use MailPoetVendor\Idiorm\ORM;

class SendingQueueTest extends \MailPoetTest {
  public $rendered_body;
  public $queue;
  public function _before() {
    parent::_before();
    $this->queue = SendingQueue::create();
    $this->queue->task_id = 0;
    $this->queue->newsletter_id = 1;
    $this->queue->save();

    $this->renderedBody = [
      'html' => 'some html',
      'text' => 'some text',
    ];
  }

  public function testItChecksProcessedSubscribersForOldQueues() {
    $subscriberId = 123;
    expect($this->queue->isSubscriberProcessed($subscriberId))->false();
    $this->queue->subscribers = ['processed' => [$subscriberId]];
    expect($this->queue->isSubscriberProcessed($subscriberId))->true();
  }

  public function testItChecksProcessedSubscribersForNewQueues() {
    $subscriberId = 123;
    $queue = SendingTask::create();
    $queue->setSubscribers([$subscriberId]);
    $queue->save();
    expect($queue->isSubscriberProcessed($subscriberId))->false();
    $queue->updateProcessedSubscribers([$subscriberId]);
    expect($queue->isSubscriberProcessed($subscriberId))->true();
  }

  public function testItReadsSerializedRenderedNewsletterBody() {
    $queue = $this->queue;
    $data = [
      'html' => 'html',
      'text' => 'text',
    ];
    $queue->newsletterRenderedBody = serialize($data);
    expect($queue->getNewsletterRenderedBody())->equals($data);
  }

  public function testItReadsJsonEncodedRenderedNewsletterBody() {
    $queue = $this->queue;
    $data = [
      'html' => 'html',
      'text' => 'text',
    ];
    $queue->newsletterRenderedBody = json_encode($data);
    expect($queue->getNewsletterRenderedBody())->equals($data);
  }

  public function testItJsonEncodesRenderedNewsletterBodyWhenSaving() {
    $queue = SendingQueue::create();
    $data = [
      'html' => 'html',
      'text' => 'text',
    ];
    $queue->taskId = 0;
    $queue->newsletterId = 1;
    $queue->newsletterRenderedBody = $data;
    $queue->save();

    $queue = SendingQueue::findOne($queue->id);

    /** @var string queue_newsletter_rendered_body */
    $queueNewsletterRenderedBody = $queue->newsletterRenderedBody;
    expect(Helpers::isJson($queueNewsletterRenderedBody))->true();
    expect(json_decode($queueNewsletterRenderedBody, true))->equals($data);
  }

  public function testItJsonEncodesMetaWhenSaving() {
    $queue = SendingQueue::create();
    $meta = [
      'some' => 'value',
    ];
    $queue->taskId = 0;
    $queue->newsletterId = 1;
    $queue->meta = $meta;
    $queue->save();

    $queue = SendingQueue::findOne($queue->id);

    expect(Helpers::isJson($queue->meta))->true();
    expect(json_decode((string)$queue->meta, true))->equals($meta);
  }

  public function testItDoesNotJsonEncodesMetaEqualToNull() {
    $queue = SendingQueue::create();
    $meta = null;
    $queue->taskId = 0;
    $queue->newsletterId = 1;
    $queue->meta = $meta;
    $queue->save();

    $queue = SendingQueue::findOne($queue->id);

    expect(Helpers::isJson($queue->meta))->false();
    expect($queue->meta)->equals($meta);
  }

  public function testItReencodesSerializedObjectToJsonEncoded() {
    $queue = $this->queue;
    $newsletterRenderedBody = $this->renderedBody;

    // update queue with a serialized rendered newsletter body
    ORM::rawExecute(
      'UPDATE `' . SendingQueue::$_table . '` SET `newsletter_rendered_body` = ? WHERE `id` = ?',
      [
        serialize($newsletterRenderedBody),
        $queue->id,
      ]
    );
    $sendingQueue = SendingQueue::findOne($queue->id);
    expect($sendingQueue->newsletterRenderedBody)->equals(serialize($newsletterRenderedBody));

    // re-saving the queue will re-rencode the body using json_encode()
    $sendingQueue->save();
    $sendingQueue = SendingQueue::findOne($queue->id);
    expect($sendingQueue->newsletterRenderedBody)->equals(json_encode($newsletterRenderedBody));
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
