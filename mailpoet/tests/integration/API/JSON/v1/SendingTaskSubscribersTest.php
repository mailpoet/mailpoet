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
  public $unprocessedSubscriber;
  public $failedSubscriber;
  public $sentSubscriber;
  public $taskId;
  public $newsletterId;
  public $endpoint;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(SendingTaskSubscribers::class);
    $this->newsletterId = Newsletter::createOrUpdate([
      'type' => Newsletter::TYPE_STANDARD,
      'subject' => 'My Standard Newsletter',
      'body' => Fixtures::get('newsletter_body_template'),
    ])->id;
    $this->taskId = ScheduledTask::createOrUpdate([
      'status' => ScheduledTask::STATUS_SCHEDULED,
    ])->id;
    SendingQueue::createOrUpdate([
      'task_id' => $this->taskId,
      'newsletter_id' => $this->newsletterId,
    ]);
    $this->sentSubscriber = Subscriber::createOrUpdate([
      'last_name' => 'Test',
      'first_name' => 'Sent',
      'email' => 'sent@example.com',
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'failed' => 0,
      'processed' => 1,
      'task_id' => $this->taskId,
      'subscriber_id' => $this->sentSubscriber->id,
    ]);
    $this->failedSubscriber = Subscriber::createOrUpdate([
      'last_name' => 'Test',
      'first_name' => 'Failed',
      'email' => 'failed@example.com',
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'failed' => 1,
      'processed' => 1,
      'task_id' => $this->taskId,
      'error' => 'Something went wrong!',
      'subscriber_id' => $this->failedSubscriber->id,
    ]);
    $this->unprocessedSubscriber = Subscriber::createOrUpdate([
      'last_name' => 'Test',
      'first_name' => 'Unprocessed',
      'email' => 'unprocessed@example.com',
    ]);
    ScheduledTaskSubscriber::createOrUpdate([
      'failed' => 0,
      'processed' => 0,
      'task_id' => $this->taskId,
      'subscriber_id' => $this->unprocessedSubscriber->id,
    ]);
  }

  public function testListingReturnsErrorIfMissingNewsletter() {
    $res = $this->endpoint->listing([
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletterId + 1],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('This email has not been sent yet.');
  }

  public function testListingReturnsErrorIfNewsletterNotBeingSent() {
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

  public function testItReturnsListing() {
    $sentSubscriberStatus = [
      'error' => '',
      'failed' => 0,
      'processed' => 1,
      'taskId' => $this->taskId,
      'email' => $this->sentSubscriber->email,
      'subscriberId' => $this->sentSubscriber->id,
      'lastName' => $this->sentSubscriber->last_name,
      'firstName' => $this->sentSubscriber->first_name,
    ];
    $unprocessedSubscriberStatus = [
      'error' => '',
      'failed' => 0,
      'processed' => 0,
      'taskId' => $this->taskId,
      'email' => $this->unprocessedSubscriber->email,
      'subscriberId' => $this->unprocessedSubscriber->id,
      'lastName' => $this->unprocessedSubscriber->last_name,
      'firstName' => $this->unprocessedSubscriber->first_name,
    ];
    $failedSubscriberStatus = [
      'error' => 'Something went wrong!',
      'failed' => 1,
      'processed' => 1,
      'taskId' => $this->taskId,
      'email' => $this->failedSubscriber->email,
      'subscriberId' => $this->failedSubscriber->id,
      'lastName' => $this->failedSubscriber->last_name,
      'firstName' => $this->failedSubscriber->first_name,
    ];

    $res = $this->endpoint->listing([
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletterId],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $sentSubscriberStatus,
      $failedSubscriberStatus,
      $unprocessedSubscriberStatus,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'sent',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletterId],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $sentSubscriberStatus,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'failed',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletterId],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $failedSubscriberStatus,
    ]);

    $res = $this->endpoint->listing([
      'group' => 'unprocessed',
      'sort_by' => 'created_at',
      'params' => ['id' => $this->newsletterId],
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);
    expect($res->data)->equals([
      $unprocessedSubscriberStatus,
    ]);
  }

  public function testResendReturnsErrorIfWrongData() {
    $res = $this->endpoint->resend([
      'taskId' => $this->taskId + 1,
      'subscriberId' => $this->sentSubscriber->id,
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('Failed sending task not found!');

    $res = $this->endpoint->resend([
      'taskId' => $this->taskId,
      'subscriberId' => $this->sentSubscriber->id,
    ]);
    expect($res->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($res->errors[0]['message'])
      ->equals('Failed sending task not found!');
  }

  public function testItCanResend() {
    $res = $this->endpoint->resend([
      'taskId' => $this->taskId,
      'subscriberId' => $this->failedSubscriber->id,
    ]);
    expect($res->status)->equals(APIResponse::STATUS_OK);

    $taskSubscriber = ScheduledTaskSubscriber::where('task_id', $this->taskId)
      ->where('subscriber_id', $this->failedSubscriber->id)
      ->findOne();
    assert($taskSubscriber instanceof ScheduledTaskSubscriber);
    expect($taskSubscriber->error)->equals('');
    expect($taskSubscriber->failed)->equals(0);
    expect($taskSubscriber->processed)->equals(0);

    $task = ScheduledTask::findOne($this->taskId);
    assert($task instanceof ScheduledTask);
    expect($task->status)->equals(null);

    $newsletter = Newsletter::findOne($this->newsletterId);
    assert($newsletter instanceof Newsletter);
    expect($newsletter->status)->equals(Newsletter::STATUS_SENDING);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTaskSubscriber::$_table);
  }
}
