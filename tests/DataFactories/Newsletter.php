<?php
namespace MailPoet\Test\DataFactories;

use Carbon\Carbon;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Tasks\Sending as SendingTask;

class Newsletter {

  /** @var array */
  private $data;

  /** @var array */
  private $options;

  /** @var array */
  private $segments;

  public function __construct() {
    $this->data = [
      'subject' => 'Some subject',
      'preheader' => 'Some preheader',
      'type' => 'standard',
      'status' => 'draft',
      ];
    $this->options = [];
    $this->segments = [];
    $this->queue_options = [];
    $this->loadBodyFrom('newsletterWithALC.json');
  }

  /**
   * @return Newsletter
   */
  public function loadBodyFrom($filename) {
    $this->data['body'] = json_decode(file_get_contents(__DIR__ . '/../_data/' . $filename), true);
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withSubject($subject) {
    $this->data['subject'] = $subject;
    return $this;
  }

  public function withActiveStatus() {
    $this->data['status'] = 'active';
    return $this;
  }

  public function withSentStatus() {
    $this->data['status'] = 'sent';
    return $this;
  }

  public function withImmediateSendingSettings() {
    $this->withOptions([
      8 => 'immediately', # intervalType
      9 => '0', # timeOfDay
      10 => '1', # intervalType
      11 => '0', # monthDay
      12 => '1', # nthWeekDay
      13 => '* * * * *', # schedule
    ]);
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withPostNotificationsType() {
    $this->data['type'] = 'notification';
    $this->withOptions([
      8 => 'daily', # intervalType
      9 => '0', # timeOfDay
      10 => '1', # intervalType
      11 => '0', # monthDay
      12 => '1', # nthWeekDay
      13 => '0 0 * * *', # schedule
    ]);
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withWelcomeType() {
    $this->data['type'] = 'welcome';
    $this->withOptions([
      3 => 'segment', // event
      4 => '2', // segment
      5 => 'subscriber', // role
      6 => '1', // afterTimeNumber
      7 => 'immediate', // afterTimeType
    ]);
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withWcAutomaticType($event = 'woocommerce_first_purchase') {
    $this->data['type'] = 'automatic';
    $this->withOptions([
      14 => 'woocommerce', // group
      15 => $event,
      16 => 'user', // sendTo
      19 => 'immediate', // afterTimeType
    ]);
    return $this;
  }

  /**
   * @param array $options
   *
   * @return Newsletter
   */
  private function withOptions(array $options) {
    $this->options = $options;
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withDeleted() {
    $this->data['deleted_at'] = Carbon::now();
    return $this;
  }

  /**
   * @param \MailPoet\Models\Segment[] $segments
   * @return Newsletter
   */
  public function withSegments(array $segments) {
    foreach($segments as $segment) {
      $this->segments[] = $segment->id();
    }
    return $this;
  }

  public function withSendingQueue(array $options = []) {
    $this->queue_options = [
      'status' => ScheduledTask::STATUS_COMPLETED,
      'count_processed' => 1,
      'count_total' => 1,
    ];
    $this->queue_options = array_merge($this->queue_options, $options);
    return $this;
  }

  /**
   * @return \MailPoet\Models\Newsletter
   */
  public function create() {
    $newsletter = \MailPoet\Models\Newsletter::createOrUpdate($this->data);
    foreach($this->options as $option_id => $option_value) {
      \MailPoet\Models\NewsletterOption::createOrUpdate(
        [
          'newsletter_id' => $newsletter->id,
          'option_field_id' => $option_id,
          'value' => $option_value,
        ]
      );
    }
    foreach($this->segments as $segment_id) {
      NewsletterSegment::createOrUpdate([
        'newsletter_id' => $newsletter->id,
        'segment_id' => $segment_id,
      ]);
    }
    if($this->queue_options) {
      $sending_task = SendingTask::create();
      $sending_task->newsletter_id = $newsletter->id;
      $sending_task->status = $this->queue_options['status'];
      $sending_task->count_processed = $this->queue_options['count_processed'];
      $sending_task->count_total = $this->queue_options['count_total'];
      $sending_task->save();
    }
    return $newsletter;
  }
}
