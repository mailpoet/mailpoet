<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Fixtures;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SendingTaskSubscribers;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoetVendor\Idiorm\ORM;

class SendingTaskSubscribersTest extends \MailPoetTest {

  function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(SendingTaskSubscribers::class);
    $this->newsletter_id = Newsletter::createOrUpdate([
      'type' => Newsletter::TYPE_STANDARD,
      'subject' => 'My Standard Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
    ])->id;
    $this->task_id = ScheduledTask::createOrUpdate([
      'status' => ScheduledTask::STATUS_SCHEDULED,
    ])->id;
    SendingQueue::createOrUpdate([
      'task_id' => $this->task_id,
      'newsletter_id' => $this->newsletter_id,
    ]);
    $this->sent_subscriber = Subscriber::createOrUpdate([
      'last_name' => 'Test',
      'first_name' => 'Sent',
      'email' => 'sent@example.com',
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'failed' => 0,
      'processed' => 1,
      'task_id' => $this->task_id,
      'subscriber_id' => $this->sent_subscriber->id,
    ]);
    $this->failed_subscriber = Subscriber::createOrUpdate([
      'last_name' => 'Test',
      'first_name' => 'Failed',
      'email' => 'failed@example.com',
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'failed' => 1,
      'processed' => 1,
      'task_id' => $this->task_id,
      'error' => 'Something went wrong!',
      'subscriber_id' => $this->failed_subscriber->id,
    ]);
    $this->unprocessed_subscriber = Subscriber::createOrUpdate([
      'last_name' => 'Test',
      'first_name' => 'Unprocessed',
      'email' => 'unprocessed@example.com',
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'failed' => 0,
      'processed' => 0,
      'task_id' => $this->task_id,
      'subscriber_id' => $this->unprocessed_subscriber->id,
    ]);
  }

  function testListingReturnsErrorIfMissingNewsletter() {
    $res = $this->endpoint->listing([
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter_id + 1],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('This email has not been sent yet.');
  }

  function testListingReturnsErrorIfNewsletterNotBeingSent() {
    $newsletter = Newsletter::createOrUpdate([
      'type' => Newsletter::TYPE_STANDARD,
      'subject' => 'Draft',
      'body' => '',
    ]);
    $res = $this->endpoint->listing([
      'sort_by' => 'created_at',
      'params' => ['id' => $newsletter->id],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('This email has not been sent yet.');
  }

  function testItReturnsListing() {
    $sent_subscriber_status = [
      'error' => '',
      'failed' => 0,
      'processed' => 1,
      'taskId' => $this->task_id,
      'email' => $this->sent_subscriber->email,
      'subscriberId' => $this->sent_subscriber->id,
      'lastName' => $this->sent_subscriber->last_name,
      'firstName' => $this->sent_subscriber->first_name,
    ];
    $unprocessed_subscriber_status = [
      'error' => '',
      'failed' => 0,
      'processed' => 0,
      'taskId' => $this->task_id,
      'email' => $this->unprocessed_subscriber->email,
      'subscriberId' => $this->unprocessed_subscriber->id,
      'lastName' => $this->unprocessed_subscriber->last_name,
      'firstName' => $this->unprocessed_subscriber->first_name,
    ];
    $failed_subscriber_status = [
      'error' => 'Something went wrong!',
      'failed' => 1,
      'processed' => 1,
      'taskId' => $this->task_id,
      'email' => $this->failed_subscriber->email,
      'subscriberId' => $this->failed_subscriber->id,
      'lastName' => $this->failed_subscriber->last_name,
      'firstName' => $this->failed_subscriber->first_name,
    ];

    $res = $this->endpoint->listing([
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter_id],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $sent_subscriber_status,
      $failed_subscriber_status,
      $unprocessed_subscriber_status,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'sent',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter_id],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $sent_subscriber_status,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'failed',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter_id],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $failed_subscriber_status,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'unprocessed',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletter_id],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $unprocessed_subscriber_status,
    ]);
  }

  function testResendReturnsErrorIfWrongData() {
    $res = $this->endpoint->resend([
      'taskId' => $this->task_id + 1,
      'subscriberId' => $this->sent_subscriber->id,
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('Failed sending task not found!');

    $res = $this->endpoint->resend([
      'taskId' => $this->task_id,
      'subscriberId' => $this->sent_subscriber->id,
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('Failed sending task not found!');
  }

  function testItCanResend() {
    $res = $this->endpoint->resend([
      'taskId' => $this->task_id,
      'subscriberId' => $this->failed_subscriber->id,
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);

    $task_subscriber = ScheduledTaskSubscriber::where('task_id', $this->task_id)
      ->where('subscriber_id', $this->failed_subscriber->id)
      ->findOne();
    expect($task_subscriber->error)->equals('');
    expect($task_subscriber->failed)->equals(0);
    expect($task_subscriber->processed)->equals(0);

    $task = ScheduledTask::findOne($this->task_id);
    expect($task->status)->equals(null);

    $newsletter = Newsletter::findOne($this->newsletter_id);
    expect($newsletter->status)->equals(Newsletter::STATUS_SENDING);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
  }
}
