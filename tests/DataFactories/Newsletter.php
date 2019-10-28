<?php

namespace MailPoet\Test\DataFactories;

use Carbon\Carbon;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending as SendingTask;

class Newsletter {

  /** @var array */
  private $data;

  /** @var array */
  private $options;

  /** @var array */
  private $segments;

  /** @var array */
  private $queue_options;

  /** @var array */
  private $task_subscribers;

  public function __construct() {
    $this->data = [
      'subject' => 'Some subject',
      'preheader' => 'Some preheader',
      'type' => 'standard',
      'status' => 'draft',
      'sender_address' => null,
      ];
    $this->options = [];
    $this->segments = [];
    $this->queue_options = [];
    $this->task_subscribers = [];
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
    $this->data['status'] = \MailPoet\Models\Newsletter::STATUS_ACTIVE;
    return $this;
  }

  public function withSentStatus() {
    $this->data['status'] = \MailPoet\Models\Newsletter::STATUS_SENT;
    return $this;
  }

  public function withSenderAddress($address) {
    $this->data['sender_address'] = $address;
    return $this;
  }

  /**
   * @param string $created_at in format Y-m-d H:i:s
   * @return Newsletter
   */
  public function withCreatedAt($created_at) {
    $this->data['created_at'] = $created_at;
    return $this;
  }

  public function withParentId($parent_id) {
    $this->data['parent_id'] = $parent_id;
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
  public function withPostNotificationHistoryType() {
    $this->data['type'] = \MailPoet\Models\Newsletter::TYPE_NOTIFICATION_HISTORY;
    $this->withOptions([]);
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withWelcomeTypeForSegment($segment_id = 2) {
    $this->data['type'] = 'welcome';
    $this->withOptions([
      3 => 'segment', // event
      4 => $segment_id, // segment
      5 => 'subscriber', // role
      6 => '1', // afterTimeNumber
      7 => 'immediate', // afterTimeType
    ]);
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withAutomaticTypeWooCommerceFirstPurchase() {
    $this->data['type'] = 'automatic';
    $this->withOptions([
      14 => 'woocommerce', // group
      15 => 'woocommerce_first_purchase',
      16 => 'user', // sendTo
      19 => 'immediate', // afterTimeType
    ]);
    return $this;
  }

  /**
   * @param array $products Array of products.
   *  $products = [
   *    [
   *      'id' => (int) Product Id,
   *      'name' => (string) Product name,
   *    ], ...
   *  ]
   *  You can pass an array of products created by WooCommerceProduct factory
   * @return Newsletter
   */
  public function withAutomaticTypeWooCommerceProductPurchased(array $products = []) {
    $this->data['type'] = 'automatic';

    $products_option = array_map(function ($product) {
      return ['id' => $product['id'], 'name' => $product['name']];
    }, $products);

    $this->withOptions([
      14 => 'woocommerce', // group
      15 => 'woocommerce_product_purchased',
      16 => 'user', // sendTo
      19 => 'immediate', // afterTimeType
      20 => json_encode(['option' => $products_option]),
    ]);
    return $this;
  }

  /**
   * @param array $products Array of products.
   *  $products = [
   *    [
   *      'id' => (int) Product Id,
   *      'name' => (string) Product name,
   *    ], ...
   *  ]
   *  You can pass an array of products created by WooCommerceProduct factory
   * @return Newsletter
   */
  public function withAutomaticTypeWooCommerceProductInCategoryPurchased(array $products = []) {
    $this->data['type'] = 'automatic';

    $options = [];
    foreach ($products as $product) {
      foreach ($product['categories'] as $category) {
        $options[] = ['id' => $category['id'], 'name' => $category['name']];
      }
    }

    $this->withOptions([
      14 => 'woocommerce', // group
      15 => 'woocommerce_product_purchased_in_category',
      16 => 'user', // sendTo
      19 => 'immediate', // afterTimeType
      20 => json_encode(['option' => $options]),
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
    foreach ($segments as $segment) {
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

  public function withSubscriber($subscriber, array $data = []) {
    $this->task_subscribers[] = array_merge([
      'subscriber_id' => $subscriber->id,
      'processed' => 1,
      'failed' => 0,
      'error' => '',
    ], $data);
    return $this;
  }

  /**
   * @return \MailPoet\Models\Newsletter
   */
  public function create() {
    $newsletter = \MailPoet\Models\Newsletter::createOrUpdate($this->data);
    foreach ($this->options as $option_id => $option_value) {
      \MailPoet\Models\NewsletterOption::createOrUpdate(
        [
          'newsletter_id' => $newsletter->id,
          'option_field_id' => $option_id,
          'value' => $option_value,
        ]
      );
    }
    if ($this->data['sender_address']) {
      $newsletter->sender_address = $this->data['sender_address'];
      $newsletter->save();
    }
    foreach ($this->segments as $segment_id) {
      NewsletterSegment::createOrUpdate([
        'newsletter_id' => $newsletter->id,
        'segment_id' => $segment_id,
      ]);
    }
    if ($this->queue_options) {
      $sending_task = SendingTask::create();
      $sending_task->newsletter_id = $newsletter->id;
      $sending_task->status = $this->queue_options['status'];
      $sending_task->count_processed = $this->queue_options['count_processed'];
      $sending_task->count_total = $this->queue_options['count_total'];
      $sending_task->save();

      foreach ($this->task_subscribers as $data) {
        $task_subscriber = ScheduledTaskSubscriber::createOrUpdate([
          'subscriber_id' => $data['subscriber_id'],
          'task_id' => $sending_task->task_id,
          'error' => $data['error'],
          'failed' => $data['failed'],
          'processed' => $data['processed'],
        ]);
      }
    }
    return $newsletter;
  }
}
