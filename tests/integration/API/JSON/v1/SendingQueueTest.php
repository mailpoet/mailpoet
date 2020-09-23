<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Fixtures;
use Codeception\Util\Stub;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SendingQueue as SendingQueueAPI;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Segments\SubscribersFinder;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoetVendor\Idiorm\ORM;

class SendingQueueTest extends \MailPoetTest {
  public $newsletter;

  public function _before() {
    parent::_before();
    $this->newsletter = Newsletter::createOrUpdate(
      [
        'subject' => 'My Standard Newsletter',
        'body' => Fixtures::get('newsletter_body_template'),
        'type' => Newsletter::TYPE_STANDARD,
      ]
    );
    $settings = SettingsController::getInstance();
    $settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
  }

  public function testItCreatesNewScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_SCHEDULED;
    $newsletter->save();
    $newletterOptions = [
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00',
    ];
    $this->_createOrUpdateNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_STANDARD,
      $newletterOptions
    );

    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->id]);
    $scheduledTask = ScheduledTask::findOne($result->data['task_id']);
    expect($scheduledTask->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    expect($scheduledTask->scheduledAt)->equals($newletterOptions['scheduledAt']);
    expect($scheduledTask->type)->equals(Sending::TASK_TYPE);
  }

  public function testItReturnsErrorIfSubscribersLimitReached() {
    $sendingQueue = new SendingQueueAPI(
      Stub::make(SubscribersFeature::class, [
        'check' => true,
      ]),
      $this->diContainer->get(SubscribersFinder::class)
    );
    $res = $sendingQueue->add(['newsletter_id' => $this->newsletter->id]);
    expect($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
    $res = $sendingQueue->resume(['newsletter_id' => $this->newsletter->id]);
    expect($res->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItReschedulesScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_SCHEDULED;
    $newsletter->save();
    $newletterOptions = [
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00',
    ];
    $this->_createOrUpdateNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_STANDARD,
      $newletterOptions
    );
    $sendingQueue = $this->diContainer->get(SendingQueueAPI::class);

    // add scheduled task
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->id]);
    $scheduledTask = ScheduledTask::findOne($result->data['task_id']);
    expect($scheduledTask->scheduledAt)->equals('2018-10-10 10:00:00');

    // update scheduled time
    $newletterOptions = [
      'scheduledAt' => '2018-11-11 11:00:00',
    ];
    $this->_createOrUpdateNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_STANDARD,
      $newletterOptions
    );
    $result = $sendingQueue->add(['newsletter_id' => $newsletter->id]);
    $rescheduledTask = ScheduledTask::findOne($result->data['task_id']);
    // new task was not created
    expect($rescheduledTask->id)->equals($scheduledTask->id);
    // scheduled time was updated
    expect($rescheduledTask->scheduledAt)->equals('2018-11-11 11:00:00');
  }

  private function _createOrUpdateNewsletterOptions($newsletterId, $newsletterType, $options) {
    foreach ($options as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::where('name', $option)->findOne();
      if (!$newsletterOptionField) {
        $newsletterOptionField = NewsletterOptionField::create();
        $newsletterOptionField->name = $option;
        $newsletterOptionField->newsletterType = $newsletterType;
        $newsletterOptionField->save();
        expect($newsletterOptionField->getErrors())->false();
      }

      $newsletterOption = NewsletterOption::where('newsletter_id', $newsletterId)
        ->where('option_field_id', $newsletterOptionField->id)
        ->findOne();
      if (!$newsletterOption) {
        $newsletterOption = NewsletterOption::create();
        $newsletterOption->optionFieldId = (int)$newsletterOptionField->id;
        $newsletterOption->newsletterId = $newsletterId;
      }
      $newsletterOption->value = $value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . SendingQueueModel::$_table);
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}
