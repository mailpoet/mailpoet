<?php
namespace MailPoet\Test\DataFactories;

use Carbon\Carbon;
use MailPoet\Models\NewsletterSegment;

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
    return $newsletter;
  }
}
