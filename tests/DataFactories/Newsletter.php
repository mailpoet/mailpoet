<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoetVendor\Carbon\Carbon;

class Newsletter {

  /** @var array */
  private $data;

  /** @var array */
  private $options;

  /** @var array */
  private $segments;

  /** @var array */
  private $queueOptions;

  /** @var array */
  private $taskSubscribers;

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
    $this->queueOptions = [];
    $this->taskSubscribers = [];
    $this->loadBodyFrom('newsletterWithALC.json');
  }

  /**
   * @return Newsletter
   */
  public function loadBodyFrom($filename) {
    $body = file_get_contents(__DIR__ . '/../_data/' . $filename);
    assert(is_string($body));
    $this->data['body'] = json_decode($body, true);
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

  public function withDraftStatus() {
    $this->data['status'] = \MailPoet\Models\Newsletter::STATUS_DRAFT;
    return $this;
  }

  public function withScheduledStatus() {
    $this->data['status'] = \MailPoet\Models\Newsletter::STATUS_SCHEDULED;
    return $this;
  }

  public function withSenderAddress($address) {
    $this->data['sender_address'] = $address;
    return $this;
  }

  /**
   * @param string $createdAt in format Y-m-d H:i:s
   * @return Newsletter
   */
  public function withCreatedAt($createdAt) {
    $this->data['created_at'] = $createdAt;
    return $this;
  }

  public function withParentId($parentId) {
    $this->data['parent_id'] = $parentId;
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
  public function withWelcomeTypeForSegment($segmentId = 2) {
    $this->data['type'] = 'welcome';
    $this->withOptions([
      3 => 'segment', // event
      4 => $segmentId, // segment
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

    $productsOption = array_map(function ($product) {
      return ['id' => $product['id'], 'name' => $product['name']];
    }, $products);

    $this->withOptions([
      14 => 'woocommerce', // group
      15 => 'woocommerce_product_purchased',
      16 => 'user', // sendTo
      19 => 'immediate', // afterTimeType
      20 => json_encode(['option' => $productsOption]),
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
   * @param SegmentEntity[] $segments
   * @return Newsletter
   */
  public function withSegments(array $segments) {
    foreach ($segments as $segment) {
      $this->segments[] = $segment->getId();
    }
    return $this;
  }

  public function withSendingQueue(array $options = []) {
    $this->queueOptions = [
      'status' => ScheduledTask::STATUS_COMPLETED,
      'count_processed' => 1,
      'count_total' => 1,
    ];
    $this->queueOptions = array_merge($this->queueOptions, $options);
    return $this;
  }

  public function withScheduledQueue(array $options = []) {
    $this->queueOptions = [
      'status' => ScheduledTask::STATUS_SCHEDULED,
      'count_processed' => 0,
      'count_total' => 1,
    ];
    $this->queueOptions = array_merge($this->queueOptions, $options);
    return $this;
  }

  public function withSubscriber($subscriber, array $data = []) {
    $this->taskSubscribers[] = array_merge([
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
    foreach ($this->options as $optionId => $optionValue) {
      \MailPoet\Models\NewsletterOption::createOrUpdate(
        [
          'newsletter_id' => $newsletter->id,
          'option_field_id' => $optionId,
          'value' => $optionValue,
        ]
      );
    }
    if ($this->data['sender_address']) {
      $newsletter->senderAddress = $this->data['sender_address'];
      $newsletter->save();
    }
    foreach ($this->segments as $segmentId) {
      NewsletterSegment::createOrUpdate([
        'newsletter_id' => $newsletter->id,
        'segment_id' => $segmentId,
      ]);
    }
    if ($this->queueOptions) {
      $sendingTask = SendingTask::create();
      $sendingTask->newsletterId = $newsletter->id;
      $sendingTask->status = $this->queueOptions['status'];
      $sendingTask->countProcessed = $this->queueOptions['count_processed'];
      $sendingTask->countTotal = $this->queueOptions['count_total'];
      $sendingTask->newsletterRenderedSubject = $this->queueOptions['subject'] ?? $this->data['subject'];
      $sendingTask->save();

      foreach ($this->taskSubscribers as $data) {
        $taskSubscriber = ScheduledTaskSubscriber::createOrUpdate([
          'subscriber_id' => $data['subscriber_id'],
          'task_id' => $sendingTask->taskId,
          'error' => $data['error'],
          'failed' => $data['failed'],
          'processed' => $data['processed'],
        ]);
      }
    }
    return $newsletter;
  }
}
