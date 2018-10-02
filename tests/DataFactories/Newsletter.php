<?php
namespace MailPoet\Test\DataFactories;

use Carbon\Carbon;

class Newsletter {

  /** @var array */
  private $data;

  /** @var array */
  private $options;

  public function __construct() {
    $this->data = [
      'subject' => 'Some subject',
      'preheader' => 'Some preheader',
      'type' => 'standard',
      'status' => 'draft',
      ];
    $this->options = [];
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

  /**
   * @return Newsletter
   */
  public function withStatus($status) {
    $this->data['status'] = $status;
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withType($type) {
    $this->data['type'] = $type;
    return $this;
  }

  /**
   * @param array $options
   *
   * @return Newsletter
   */
  public function withPostNotificationOptions(array $options = [
    8 => 'daily', # intervalType
    9 => '0', # timeOfDay
    10 => '1', # intervalType
    11 => '0', # monthDay
    12 => '1', # nthWeekDay
    13 => '0 0 * * *', # schedule
  ]) {
    return $this->withOptions($options);
  }

  /**
   * @param array $options
   *
   * @return Newsletter
   */
  public function withOptions(array $options) {
    $this->options = array_merge($this->options, $options);
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
    return $newsletter;
  }
}
