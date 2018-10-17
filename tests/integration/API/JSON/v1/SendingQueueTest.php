<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Fixtures;
use MailPoet\API\JSON\v1\SendingQueue as SendingQueueAPI;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\SendingQueue as SendingQueueModel;
use MailPoet\Models\Setting;
use MailPoet\Tasks\Sending;

class SendingQueueTest extends \MailPoetTest {
  function _before() {
    $this->newsletter = Newsletter::createOrUpdate(
      array(
        'subject' => 'My Standard Newsletter',
        'body' => Fixtures::get('newsletter_body_template'),
        'type' => Newsletter::TYPE_STANDARD
      )
    );
    Setting::setValue('sender', array(
      'name' => 'John Doe',
      'address' => 'john.doe@example.com'
    ));
  }

  function testItCreatesNewScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_SCHEDULED;
    $newsletter->save();
    $newletter_options = array(
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00'
    );
    $this->_createOrUpdateNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_STANDARD,
      $newletter_options
    );

    $sending_queue = new SendingQueueAPI();
    $result = $sending_queue->add(array('newsletter_id' => $newsletter->id));
    $scheduled_task = ScheduledTask::findOne($result->data['task_id']);
    expect($scheduled_task->status)->equals(ScheduledTask::STATUS_SCHEDULED);
    expect($scheduled_task->scheduled_at)->equals($newletter_options['scheduledAt']);
    expect($scheduled_task->type)->equals(Sending::TASK_TYPE);
  }

  function testItReschedulesScheduledSendingQueueTask() {
    $newsletter = $this->newsletter;
    $newsletter->status = Newsletter::STATUS_SCHEDULED;
    $newsletter->save();
    $newletter_options = array(
      'isScheduled' => 1,
      'scheduledAt' => '2018-10-10 10:00:00'
    );
    $this->_createOrUpdateNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_STANDARD,
      $newletter_options
    );
    $sending_queue = new SendingQueueAPI();

    // add scheduled task
    $result = $sending_queue->add(array('newsletter_id' => $newsletter->id));
    $scheduled_task = ScheduledTask::findOne($result->data['task_id']);
    expect($scheduled_task->scheduled_at)->equals('2018-10-10 10:00:00');

    // update scheduled time
    $newletter_options = array(
      'scheduledAt' => '2018-11-11 11:00:00'
    );
    $this->_createOrUpdateNewsletterOptions(
      $newsletter->id,
      Newsletter::TYPE_STANDARD,
      $newletter_options
    );
    $result = $sending_queue->add(array('newsletter_id' => $newsletter->id));
    $rescheduled_task = ScheduledTask::findOne($result->data['task_id']);
    // new task was not created
    expect($rescheduled_task->id)->equals($scheduled_task->id);
    // scheduled time was updated
    expect($rescheduled_task->scheduled_at)->equals('2018-11-11 11:00:00');
  }

  private function _createOrUpdateNewsletterOptions($newsletter_id, $newsletter_type, $options) {
    foreach($options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::where('name', $option)->findOne();
      if(!$newsletter_option_field) {
        $newsletter_option_field = NewsletterOptionField::create();
        $newsletter_option_field->name = $option;
        $newsletter_option_field->newsletter_type = $newsletter_type;
        $newsletter_option_field->save();
        expect($newsletter_option_field->getErrors())->false();
      }

      $newsletter_option = NewsletterOption::where('newsletter_id', $newsletter_id)
        ->where('option_field_id', $newsletter_option_field->id)
        ->findOne();
      if(!$newsletter_option) {
        $newsletter_option = NewsletterOption::create();
        $newsletter_option->option_field_id = $newsletter_option_field->id;
        $newsletter_option->newsletter_id = $newsletter_id;
      }
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueueModel::$_table);
    \ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
  }
}