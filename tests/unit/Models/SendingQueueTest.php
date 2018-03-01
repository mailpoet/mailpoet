<?php

namespace MailPoet\Test\Models;

use AspectMock\Test as Mock;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\Util\Helpers;

class SendingQueueTest extends \MailPoetTest {
  function _before() {
    $this->queue = SendingQueue::create();
    $this->queue->task_id = 0;
    $this->queue->newsletter_id = 1;
    $this->queue->save();

    $this->rendered_body = array(
      'html' => 'some html',
      'text' => 'some text'
    );
  }

  function testItCanEncodeEmojisInBody() {
    $mock = Mock::double('MailPoet\WP\Emoji', [
      'encodeForUTF8Column' => function($params) {
        return $params;
      }
    ]);
    $this->queue->encodeEmojisInBody($this->rendered_body);
    $mock->verifyInvokedMultipleTimes('encodeForUTF8Column', 2);
  }

  function testItCanDecodeEmojisInBody() {
    $mock = Mock::double('MailPoet\WP\Emoji', [
      'decodeEntities' => function($params) {
        return $params;
      }
    ]);
    $this->queue->decodeEmojisInBody($this->rendered_body);
    $mock->verifyInvokedMultipleTimes('decodeEntities', 2);
  }

  function testItChecksProcessedSubscribersForOldQueues() {
    $subscriber_id = 123;
    expect($this->queue->isSubscriberProcessed($subscriber_id))->false();
    $this->queue->subscribers = array('processed' => array($subscriber_id));
    expect($this->queue->isSubscriberProcessed($subscriber_id))->true();
  }

  function testItChecksProcessedSubscribersForNewQueues() {
    $subscriber_id = 123;
    $queue = SendingTask::create();
    $queue->setSubscribers(array($subscriber_id));
    $queue->save();
    expect($queue->isSubscriberProcessed($subscriber_id))->false();
    $queue->updateProcessedSubscribers(array($subscriber_id));
    expect($queue->isSubscriberProcessed($subscriber_id))->true();
  }

  function testItReadsSerializedRenderedNewsletterBody() {
    $queue = $this->queue;
    $data = array(
      'html' => 'html',
      'text' => 'text'
    );
    $queue->newsletter_rendered_body = serialize($data);
    expect($queue->getNewsletterRenderedBody())->equals($data);
  }

  function testItReadsJsonEncodedRenderedNewsletterBody() {
    $queue = $this->queue;
    $data = array(
      'html' => 'html',
      'text' => 'text'
    );
    $queue->newsletter_rendered_body = json_encode($data);
    expect($queue->getNewsletterRenderedBody())->equals($data);
  }

  function testItJsonEncodesRenderedNewsletterBodyWhenSaving() {
    $queue = SendingQueue::create();
    $data = array(
      'html' => 'html',
      'text' => 'text'
    );
    $queue->task_id = 0;
    $queue->newsletter_id = 1;
    $queue->newsletter_rendered_body = $data;
    $queue->save();

    $queue = SendingQueue::findOne($queue->id);

    expect(Helpers::isJson($queue->newsletter_rendered_body))->true();
    expect(json_decode($queue->newsletter_rendered_body, true))->equals($data);
  }

  function testItReencodesSerializedObjectToJsonEncoded() {
    $queue = $this->queue;
    $newsletter_rendered_body = $this->rendered_body;

    // update queue with a serialized rendered newsletter body
    \ORM::rawExecute(
      'UPDATE `' . SendingQueue::$_table . '` SET `newsletter_rendered_body` = ? WHERE `id` = ?',
      array(
        serialize($newsletter_rendered_body),
        $queue->id
      )
    );
    $sending_queue = SendingQueue::findOne($queue->id);
    expect($sending_queue->newsletter_rendered_body)->equals(serialize($newsletter_rendered_body));

    // re-saving the queue will re-rencode the body using json_encode()
    $sending_queue->save();
    $sending_queue = SendingQueue::findOne($queue->id);
    expect($sending_queue->newsletter_rendered_body)->equals(json_encode($newsletter_rendered_body));
  }

  function _after() {
    Mock::clean();
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}