<?php

namespace MailPoet\Test\Models;

use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Helpers;
use MailPoetVendor\Idiorm\ORM;

class SendingQueueTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->queue = SendingQueue::create();
    $this->queue->task_id = 0;
    $this->queue->newsletter_id = 1;
    $this->queue->save();

    $this->rendered_body = [
      'html' => 'some html',
      'text' => 'some text',
    ];
  }

  function testItChecksProcessedSubscribersForOldQueues() {
    $subscriber_id = 123;
    expect($this->queue->isSubscriberProcessed($subscriber_id))->false();
    $this->queue->subscribers = ['processed' => [$subscriber_id]];
    expect($this->queue->isSubscriberProcessed($subscriber_id))->true();
  }

  function testItChecksProcessedSubscribersForNewQueues() {
    $subscriber_id = 123;
    $queue = SendingTask::create();
    $queue->setSubscribers([$subscriber_id]);
    $queue->save();
    expect($queue->isSubscriberProcessed($subscriber_id))->false();
    $queue->updateProcessedSubscribers([$subscriber_id]);
    expect($queue->isSubscriberProcessed($subscriber_id))->true();
  }

  function testItReadsSerializedRenderedNewsletterBody() {
    $queue = $this->queue;
    $data = [
      'html' => 'html',
      'text' => 'text',
    ];
    $queue->newsletter_rendered_body = serialize($data);
    expect($queue->getNewsletterRenderedBody())->equals($data);
  }

  function testItReadsJsonEncodedRenderedNewsletterBody() {
    $queue = $this->queue;
    $data = [
      'html' => 'html',
      'text' => 'text',
    ];
    $queue->newsletter_rendered_body = json_encode($data);
    expect($queue->getNewsletterRenderedBody())->equals($data);
  }

  function testItJsonEncodesRenderedNewsletterBodyWhenSaving() {
    $queue = SendingQueue::create();
    $data = [
      'html' => 'html',
      'text' => 'text',
    ];
    $queue->task_id = 0;
    $queue->newsletter_id = 1;
    $queue->newsletter_rendered_body = $data;
    $queue->save();

    $queue = SendingQueue::findOne($queue->id);

    expect(Helpers::isJson($queue->newsletter_rendered_body))->true();
    expect(json_decode($queue->newsletter_rendered_body, true))->equals($data);
  }

  function testItJsonEncodesMetaWhenSaving() {
    $queue = SendingQueue::create();
    $meta = [
      'some' => 'value',
    ];
    $queue->task_id = 0;
    $queue->newsletter_id = 1;
    $queue->meta = $meta;
    $queue->save();

    $queue = SendingQueue::findOne($queue->id);

    expect(Helpers::isJson($queue->meta))->true();
    expect(json_decode($queue->meta, true))->equals($meta);
  }

  function testItDoesNotJsonEncodesMetaEqualToNull() {
    $queue = SendingQueue::create();
    $meta = null;
    $queue->task_id = 0;
    $queue->newsletter_id = 1;
    $queue->meta = $meta;
    $queue->save();

    $queue = SendingQueue::findOne($queue->id);

    expect(Helpers::isJson($queue->meta))->false();
    expect($queue->meta)->equals($meta);
  }

  function testItReencodesSerializedObjectToJsonEncoded() {
    $queue = $this->queue;
    $newsletter_rendered_body = $this->rendered_body;

    // update queue with a serialized rendered newsletter body
    ORM::rawExecute(
      'UPDATE `' . SendingQueue::$_table . '` SET `newsletter_rendered_body` = ? WHERE `id` = ?',
      [
        serialize($newsletter_rendered_body),
        $queue->id,
      ]
    );
    $sending_queue = SendingQueue::findOne($queue->id);
    expect($sending_queue->newsletter_rendered_body)->equals(serialize($newsletter_rendered_body));

    // re-saving the queue will re-rencode the body using json_encode()
    $sending_queue->save();
    $sending_queue = SendingQueue::findOne($queue->id);
    expect($sending_queue->newsletter_rendered_body)->equals(json_encode($newsletter_rendered_body));
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
