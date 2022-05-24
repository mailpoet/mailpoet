<?php

namespace MailPoet\Test\DataFactories;

use Codeception\Util\Fixtures;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\Security;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

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
      'type' => NewsletterEntity::TYPE_STANDARD,
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

  public function withDefaultBody() {
    return $this->withBody(json_decode(Fixtures::get('newsletter_body_template'), true));
  }

  /**
   * @return Newsletter
   */
  public function withBody($body) {
    $this->data['body'] = $body;
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withType(string $type) {
    $this->data['type'] = $type;
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
    $this->data['status'] = NewsletterEntity::STATUS_ACTIVE;
    return $this;
  }

  public function withSentStatus() {
    $this->data['status'] = NewsletterEntity::STATUS_SENT;
    return $this;
  }

  public function withDraftStatus() {
    $this->data['status'] = NewsletterEntity::STATUS_DRAFT;
    return $this;
  }

  public function withScheduledStatus() {
    $this->data['status'] = NewsletterEntity::STATUS_SCHEDULED;
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

  public function withParent(NewsletterEntity $parent) {
    $this->data['parent'] = $parent;
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

  public function withReengagementType() {
    $this->data['type'] = NewsletterEntity::TYPE_RE_ENGAGEMENT;
    return $this;
  }

  /**
   * @return Newsletter
   */
  public function withPostNotificationHistoryType() {
    $this->data['type'] = NewsletterEntity::TYPE_NOTIFICATION_HISTORY;
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
      $this->segments[$segment->getId()] = $segment;
    }
    return $this;
  }

  public function withSendingQueue(array $options = []) {
    $this->queueOptions = [
      'status' => ScheduledTaskEntity::STATUS_COMPLETED,
      'count_processed' => 1,
      'count_total' => 1,
    ];
    $this->queueOptions = array_merge($this->queueOptions, $options);
    return $this;
  }

  public function withScheduledQueue(array $options = []) {
    $this->queueOptions = [
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
      'count_processed' => 0,
      'count_total' => 1,
    ];
    $this->queueOptions = array_merge($this->queueOptions, $options);
    return $this;
  }

  public function withSubscriber(SubscriberEntity $subscriber, array $data = []) {
    $this->taskSubscribers[$subscriber->getId()] = array_merge([
      'subscriber' => $subscriber,
      'processed' => 1,
      'failed' => 0,
      'error' => '',
    ], $data);
    return $this;
  }

  public function create(): NewsletterEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $newsletter = $this->createNewsletter();
    $entityManager->persist($newsletter);

    foreach ($this->options as $optionId => $optionValue) {
      $newsletterOption = $this->createOption($newsletter, $optionId, $optionValue);
      $entityManager->persist($newsletterOption);
    }

    foreach ($this->segments as $segment) {
      $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
      $entityManager->persist($newsletterSegment);
    }

    if ($this->queueOptions) {
      $this->createQueue($newsletter);
    }

    $entityManager->flush();
    return $newsletter;
  }

  private function createNewsletter(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject($this->data['subject']);
    $newsletter->setPreheader($this->data['preheader']);
    $newsletter->setType($this->data['type']);
    $newsletter->setStatus($this->data['status']);
    $newsletter->setBody($this->data['body']);
    if (isset($this->data['sender_address'])) {
      $newsletter->setSenderAddress($this->data['sender_address']);
    } else {
      $newsletter->setSenderAddress('john.doe@example.com');
      $newsletter->setSenderName('John Doe');
    }
    $newsletter->setHash(Security::generateHash());
    if (isset($this->data['parent'])) $newsletter->setParent($this->data['parent']);
    if (isset($this->data['deleted_at'])) $newsletter->setDeletedAt($this->data['deleted_at']);
    return $newsletter;
  }

  private function createOption(NewsletterEntity $newsletter, int $optionId, string $optionValue): NewsletterOptionEntity {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $newsletterOptionField = $entityManager->getReference(NewsletterOptionFieldEntity::class, $optionId);
    $newsletterOption = new NewsletterOptionEntity($newsletter, $newsletterOptionField);
    $newsletterOption->setValue($optionValue);
    return $newsletterOption;
  }

  private function createQueue(NewsletterEntity $newsletter) {
    $entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $scheduledTask = new ScheduledTaskEntity();
    $entityManager->persist($scheduledTask);
    $sendingQueue = new SendingQueueEntity();
    $sendingQueue->setTask($scheduledTask);
    $entityManager->persist($sendingQueue);
    $sendingQueue->setNewsletter($newsletter);
    $scheduledTask->setStatus($this->queueOptions['status']);
    $sendingQueue->setCountProcessed($this->queueOptions['count_processed']);
    $sendingQueue->setCountTotal($this->queueOptions['count_total']);
    $sendingQueue->setNewsletterRenderedSubject($this->queueOptions['subject'] ?? $this->data['subject']);
    $newsletter->getQueues()->add($sendingQueue);

    foreach ($this->taskSubscribers as $data) {
      $taskSubscriber = new ScheduledTaskSubscriberEntity(
        $scheduledTask,
        $data['subscriber'],
        $data['processed'],
        $data['failed'],
        $data['error']
      );
      $entityManager->persist($taskSubscriber);
    }
  }
}
