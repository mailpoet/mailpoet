<?php declare(strict_types = 1);

namespace MailPoet\Test\DataGenerator\Generators;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsNewsletterEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsWooCommercePurchaseEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Newsletter\Segment\NewsletterSegmentRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class WooCommercePastRevenues implements Generator {
  // Date range
  const MAX_DAYS_AGO = 800;
  const MIN_DAYS_AGO = 30;
  // Counts settings
  const SUBSCRIBERS_COUNT = 50;
  const SUBSCRIBERS_WITH_ORDERS_COUNT = 45;
  const PRODUCTS_COUNT = 50;
  const LOG_BATCH_SIZE = 10;
  const POST_NOTIFICATIONS_HISTORY = 30;
  const STANDARD_NEWSLETTER = 30;

  /** @var EntityManager */
  private $entityManager;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var NewsletterSegmentRepository */
  private $newsletterSegmentRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function __construct() {
    $this->entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
    $this->segmentsRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);
    $this->newsletterSegmentRepository = ContainerWrapper::getInstance()->get(NewsletterSegmentRepository::class);
    $this->scheduledTasksRepository = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class);
    $this->sendingQueuesRepository = ContainerWrapper::getInstance()->get(SendingQueuesRepository::class);
  }

  public function generate() {
    // Reset hooks to prevent revenues calculation during generating
    remove_all_actions('woocommerce_order_status_completed');
    remove_all_actions('woocommerce_order_status_processing');

    $minimalCreatedAtDate = (new Carbon())->subDays(self::MAX_DAYS_AGO)->toDateTimeString();

    // Create list
    $segmentFactory = new Segment();
    $subscribersList = $segmentFactory->withName('WC revenues load test ' . $this->getRandomString())->create();

    // Create subscribers
    $subscribersIds = [];
    $subscriberEmails = [];
    for ($i = 1; $i <= self::SUBSCRIBERS_COUNT; $i++) {
      $email = $this->getRandomString() . "address$i@email.com";
      $subscriber = $this->createSubscriber($email, "last_name_$i", $minimalCreatedAtDate, $subscribersList);
      $subscribersIds[] = $subscriber->getId();
      $subscriberEmails[$subscriber->getId()] = $email;
      $batchLog = $this->getBatchLog('Subscribers', count($subscribersIds));
      if ($batchLog) {
        yield $batchLog;
      }
    }
    yield "Subscribers done";

    // Products
    $productCategory = $this->createProductCategory('WC Revenues Test Category ' . $this->getRandomString(), 'revenues-test-cat-' . $this->getRandomString());
    $products = [];
    for ($i = 1; $i <= self::PRODUCTS_COUNT; $i++) {
      $products[] = $this->createProduct("Product $i " . $this->getRandomString(), 100, [$productCategory->term_id]); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    yield "Products done";

    // Newsletters
    $emailFactory = new Newsletter();
    // Create sent standard newsletters
    $sentStandardNewsletters = [];
    for ($i = 1; $i <= self::STANDARD_NEWSLETTER; $i++) {
      $sentAt = $this->getRandomDateInPast();
      $newsletter = $emailFactory
        ->withSubject("Standard $i " . $this->getRandomString())
        ->withSegments([$subscribersList])
        ->withCreatedAt($sentAt)
        ->create();
      $sentStandardNewsletters[] = $this->createSentEmailData($newsletter, $sentAt, $subscribersIds, $subscribersList->getId());
    }
    yield "Standard newsletters done";

    // Crate sent post notifications
    $emailFactory = new Newsletter();
    $postNotification = $emailFactory
      ->withSubject("Post Notification Parent")
      ->withPostNotificationsType()
      ->withActiveStatus()
      ->withSegments([$subscribersList])
      ->withCreatedAt($minimalCreatedAtDate)
      ->create();
    $sentPostNotifications = [];
    for ($i = 1; $i <= self::POST_NOTIFICATIONS_HISTORY; $i++) {
      $sentAt = $this->getRandomDateInPast();
      $newsletter = $emailFactory
        ->withSubject("Post notification history $i")
        ->withPostNotificationHistoryType()
        ->withSegments([$subscribersList])
        ->withCreatedAt($sentAt)
        ->withParent($postNotification)
        ->create();
      $sentPostNotifications[] = $this->createSentEmailData($newsletter, $sentAt, $subscribersIds, $subscribersList->getId());
    }

    yield "Post notifications done";

    // Welcome emails
    $emailFactory = new Newsletter();
    $welcomeEmail = $emailFactory
      ->withSubject("Welcome email" . $this->getRandomString())
      ->withActiveStatus()
      ->withWelcomeTypeForSegment($subscribersList->getId())
      ->withSegments([$subscribersList])
      ->withCreatedAt($minimalCreatedAtDate)
      ->create();
    $sentWelcomeEmails = [];
    foreach ($subscribersIds as $subscriberId) {
      $sentWelcomeEmails[$subscriberId] = $this->createSentEmailData($welcomeEmail, $minimalCreatedAtDate, [$subscriberId], $subscribersList->getId());
      $batchLog = $this->getBatchLog('Welcome emails sent', count($sentWelcomeEmails));
      if ($batchLog) {
        yield $batchLog;
      }
    }

    yield "Welcome emails done";

    // Automatic emails
    $automaticEmails = [];
    $emailFactory = new Newsletter();
    // First purchase
    $automaticEmails[] = $emailFactory
      ->withSubject("First Purchase" . $this->getRandomString())
      ->withActiveStatus()
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withSegments([])
      ->withCreatedAt($minimalCreatedAtDate)
      ->create();
    // Purchased product
    for ($i = 1; $i <= 2; $i++) {
      $product = [
        'id' => $products[$i]->get_name(),
        'name' => $products[$i]->get_id(),
      ];
      $automaticEmails[] = $emailFactory
        ->withSubject("Purchased Product $i " . $this->getRandomString())
        ->withActiveStatus()
        ->withAutomaticTypeWooCommerceProductPurchased([$product])
        ->withSegments([])
        ->withCreatedAt($minimalCreatedAtDate)
        ->create();
    }
    // Purchase in category emails
    for ($i = 1; $i <= 2; $i++) {
      $product = [
        'id' => $products[$i]->get_name(),
        'name' => $products[$i]->get_id(),
        'categories' => [[
          'id' => $productCategory->term_id, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          'name' => $productCategory->name,
        ]],
      ];
      $automaticEmails[] = $emailFactory
        ->withSubject("Purchased Product in Category $i " . $this->getRandomString())
        ->withActiveStatus()
        ->withAutomaticTypeWooCommerceProductInCategoryPurchased([$product])
        ->withSegments([])
        ->withCreatedAt($minimalCreatedAtDate)
        ->create();
    }

    // Send automatic emails
    $sentAutomaticEmails = [];
    foreach ($subscribersIds as $subscriberId) {
      $sentAutomaticEmails[$subscriberId] = [];
      // Pick random three automatic emails for each subscriber
      $emailsToSend = array_intersect_key(
        $automaticEmails,
        array_flip(array_rand($automaticEmails, 3))
      );
      foreach ($emailsToSend as $email) {
        $sentAutomaticEmails[$subscriberId][] = $this->createSentEmailData($email, $this->getRandomDateInPast(), [$subscriberId], $subscribersList->getId());
      }
      $batchLog = $this->getBatchLog('Automatic emails sent', count($sentAutomaticEmails));
      if ($batchLog) {
        yield $batchLog;
      }
    }
    yield "Automatic emails done";

    // Clicks and orders
    // Pick random subscribers which will have an order
    $subscribersWithOrders = array_flip(array_intersect_key(
      $subscribersIds,
      array_flip(array_rand($subscribersIds, self::SUBSCRIBERS_WITH_ORDERS_COUNT))
    ));
    $i = 0;
    $clicks = [];
    foreach ($subscribersIds as $subscriberId) {
      $i++;
      $subscriberClickTimes = [];
      $subscriberReceivedEmails = array_merge(
        $sentAutomaticEmails[$subscriberId],
        [$sentWelcomeEmails[$subscriberId]],
        $sentPostNotifications,
        $sentStandardNewsletters
      );
      // Pick amount of received emails and generate opens and clicks
      $openedCount = floor(count($subscriberReceivedEmails) / rand(2, 5));
      $emailsToClick = array_intersect_key(
        $subscriberReceivedEmails,
        array_flip(array_rand($subscriberReceivedEmails, intval($openedCount)))
      );
      $clicks[$subscriberId] = [];

      // Click and open selected emails
      foreach ($emailsToClick as $email) {
        $clickCreatedAt = (new Carbon())->setTimestamp($email['sent_at'])->addHours(1)->toDateTimeString();
        $this->openSentNewsletter($email, $subscriberId, $clickCreatedAt);
        $clicks[$subscriberId][] = [
          'click_id' => $this->clickSentNewsletter($email, $subscriberId, $clickCreatedAt),
          'newsletter_id' => $email['newsletter_id'],
          'queue_id' => $email['queue_id'],
          'time' => $clickCreatedAt,
        ];
      }
      // Create order
      if (isset($subscribersWithOrders[$subscriberId])) {
        // Create user account
        $email = $subscriberEmails[$subscriberId];
        $customerId = wc_create_new_customer($email, $email, $this->getRandomString(10));
        $orderCount = rand(1, 5);
        for ($i = 1; $i <= $orderCount; $i++) {
          // Pick a random logged click time and generate an order day after the click
          $clickData = $clicks[$subscriberId][array_rand($clicks[$subscriberId], 1)];
          $clickTime = $clickData['time'];
          $orderCompletedAt = (new Carbon($clickTime))->addDay();
          $order = $this->createCompletedWooCommerceOrder(
            $subscriberId,
            $subscriberEmails[$subscriberId],
            $customerId,
            [$products[array_rand($products)]],
            $orderCompletedAt
          );
          // Maybe track revenue
          if (rand(1, 10) <= 8 && $order instanceof \WC_Order) {
            $this->trackOrderRevenue($subscriberId, $clickData, $order);
          }
        }
      }
      $batchLog = $this->getBatchLog('Subscriber clicks and orders', $i);
      if ($batchLog) {
        yield $batchLog;
      }
    }
    yield "Clicks and Orders done";
  }

  private function getRandomDateInPast() {
    $daysAgo = mt_rand(self::MIN_DAYS_AGO, self::MAX_DAYS_AGO);
    return (new Carbon())->subDays($daysAgo)->toDateTimeString();
  }

  private function getBatchLog($dataType, $generatedCount) {
    if ($generatedCount % self::LOG_BATCH_SIZE !== 0) {
      return;
    }
    return "$dataType: $generatedCount";
  }

  public function runBefore() {
    $connection = $this->entityManager->getConnection();

    // Turn off CURRENT_TIMESTAMP to be able to save generated value
    $connection->executeStatement(
      "ALTER TABLE `" . $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName() . "`
      CHANGE `updated_at` `updated_at` timestamp NULL;"
    );

    // Disable keys
    global $wpdb;
    $prefix = $wpdb->prefix;
    $connection->executeStatement("ALTER TABLE `{$prefix}posts` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `{$prefix}postmeta` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName() . "` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName() . "` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName() . "` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName() . "` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName() . "` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName() . "` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName() . "` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName() . "` DISABLE KEYS");
    $connection->executeStatement("SET UNIQUE_CHECKS = 0;");
  }

  public function runAfter() {
    $connection = $this->entityManager->getConnection();

    $connection->executeStatement(
      "ALTER TABLE `" . $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName() . "`
      CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;"
    );

    // Enable keys
    global $wpdb;
    $prefix = $wpdb->prefix;
    $connection->executeStatement("ALTER TABLE `{$prefix}posts` ENABLE KEYS");
    $connection->executeStatement("ALTER TABLE `{$prefix}postmeta` DISABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName() . "` ENABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName() . "` ENABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(NewsletterLinkEntity::class)->getTableName() . "` ENABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(ScheduledTaskEntity::class)->getTableName() . "` ENABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName() . "` ENABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(SendingQueueEntity::class)->getTableName() . "` ENABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(StatisticsOpenEntity::class)->getTableName() . "` ENABLE KEYS");
    $connection->executeStatement("ALTER TABLE `" . $this->entityManager->getClassMetadata(StatisticsClickEntity::class)->getTableName() . "` ENABLE KEYS");
    $connection->executeStatement("SET UNIQUE_CHECKS = 1;");
  }

  private function createSubscriber(string $email, string $lastName, string $createdAtDate, SegmentEntity $segment, $status = SubscriberEntity::STATUS_SUBSCRIBED): SubscriberEntity {
    $subscriber = (new SubscriberFactory())
      ->withEmail($email)
      ->withStatus($status)
      ->withLastName($lastName)
      ->withCreatedAt(new Carbon($createdAtDate))
      ->create();

    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, $status);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();

    return $subscriber;
  }

  /**
   * @return \WC_Product
   */
  private function createProduct($name, $price, $categoryIds = [], $discount = 0) {
    $product = new \WC_Product();
    $product->set_name($name);
    $product->set_price($price - $discount);
    $product->set_status('publish');
    $product->set_regular_price($price);
    if ($categoryIds) {
      $product->set_category_ids($categoryIds);
    }
    $product->save();
    return $product;
  }

  /**
   * @return array
   */
  private function createSentEmailData(NewsletterEntity $newsletter, string $sentAt, array $subscribersIds, $segmentId) {
    $connection = $this->entityManager->getConnection();

    // Sending task
    $task = new ScheduledTaskEntity();
    $task->setType(Sending::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_COMPLETED);
    $task->setCreatedAt(new Carbon($sentAt));
    $task->setProcessedAt(new Carbon($sentAt));
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    // Sending queue
    $queue = new SendingQueueEntity();
    $queue->setTask($task);
    $queue->setNewsletter($newsletter);
    $queue->setCountTotal(count($subscribersIds));
    $queue->setCountProcessed(count($subscribersIds));
    $this->sendingQueuesRepository->persist($queue);
    $this->sendingQueuesRepository->flush();

    $this->entityManager->refresh($newsletter);

    // Add subscribers to task and stats sent
    $batchData = [];
    $statsBatchData = [];
    foreach ($subscribersIds as $subscriberId) {
      $batchData[] = "({$task->getId()}, $subscriberId, 1, '$sentAt')";
      if (count($batchData) % 1000 === 0) {
        $connection->executeStatement(
          "INSERT INTO " . $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName() . " (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ', $batchData)
        );
        $batchData = [];
      }
      $statsBatchData[] = "({$newsletter->getId()}, $subscriberId, {$queue->getId()}, '$sentAt')";
      if (count($statsBatchData) % 1000 === 0) {
        $connection->executeStatement(
          "INSERT INTO " . $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName() . " (`newsletter_id`, `subscriber_id`, `queue_id`, `sent_at`) VALUES " . implode(', ', $statsBatchData)
        );
        $statsBatchData = [];
      }
    }

    if ($batchData) {
      $connection->executeStatement(
        "INSERT INTO " . $this->entityManager->getClassMetadata(ScheduledTaskSubscriberEntity::class)->getTableName() . " (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ', $batchData)
      );
    }
    if ($statsBatchData) {
      $connection->executeStatement(
        "INSERT INTO " . $this->entityManager->getClassMetadata(StatisticsNewsletterEntity::class)->getTableName() . " (`newsletter_id`, `subscriber_id`, `queue_id`, `sent_at`) VALUES " . implode(', ', $statsBatchData)
      );
    }

    // Link
    $link = (new \MailPoet\Test\DataFactories\NewsletterLink($newsletter))
      ->withCreatedAt($sentAt)
      ->create();

    // Newsletter segment
    $segment = $this->segmentsRepository->findOneById($segmentId);
    $newsletterSegment = $this->newsletterSegmentRepository->findOneBy(['newsletter' => $newsletter, 'segment' => $segment]);

    if (!$newsletterSegment instanceof NewsletterSegmentEntity) {
      $newsletterSegment = new NewsletterSegmentEntity($newsletter, $segment);
      $this->segmentsRepository->persist($newsletterSegment);
      $this->segmentsRepository->flush();

      $this->entityManager->refresh($newsletter);
    }

    if ($newsletter->getStatus() === NewsletterEntity::STATUS_DRAFT) {
      $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
      $newsletter->setSentAt(Carbon::createFromFormat('Y-m-d H:i:s', $sentAt));
      $this->entityManager->flush();
    }

    return [
      'newsletter_id' => $newsletter->getId(),
      'task_id' => $task->getId(),
      'queue_id' => $queue->getId(),
      'sent_at' => strtotime($sentAt),
      'link_id' => $link->getId(),
    ];
  }

  private function openSentNewsletter(array $sentNewsletterData, $subscriberId, $createdAt) {
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $sentNewsletterData['newsletter_id']);
    $sendingQueueEntity = $this->entityManager->getReference(SendingQueueEntity::class, $sentNewsletterData['queue_id']);
    $subscriberEntity = $this->entityManager->getReference(SubscriberEntity::class, $subscriberId);

    $statisticsOpen = new StatisticsOpenEntity($newsletterEntity, $sendingQueueEntity, $subscriberEntity);
    $statisticsOpen->setCreatedAt(new Carbon($createdAt));

    $this->entityManager->persist($statisticsOpen);
    $this->entityManager->flush();
  }

  private function clickSentNewsletter(array $sentNewsletterData, $subscriberId, $createdAt): int {
    $newsletterEntity = $this->entityManager->getReference(NewsletterEntity::class, $sentNewsletterData['newsletter_id']);
    $sendingQueueEntity = $this->entityManager->getReference(SendingQueueEntity::class, $sentNewsletterData['queue_id']);
    $subscriberEntity = $this->entityManager->getReference(SubscriberEntity::class, $subscriberId);
    $newsletterLinkEntity = $this->entityManager->getReference(NewsletterLinkEntity::class, $sentNewsletterData['link_id']);

    $statisticsClick = new StatisticsClickEntity($newsletterEntity, $sendingQueueEntity, $subscriberEntity, $newsletterLinkEntity, 1);
    $statisticsClick->setCreatedAt(new Carbon($createdAt));
    $statisticsClick->setUpdatedAt(new Carbon($createdAt));

    $this->entityManager->persist($statisticsClick);
    $this->entityManager->flush();
    return $statisticsClick->getId();
  }

  /**
   * @return array|false|\WP_Term
   */
  private function createProductCategory($name, $slug, $description = '') {
    wp_insert_term(
      $name,
      'product_cat',
      ['description' => $description, 'slug' => $slug]
    );
    return get_term_by('slug', $slug, 'product_cat');
  }

  /**
   * @return \WC_Order|\WP_Error
   */
  private function createCompletedWooCommerceOrder($subscriberId, $email, $customerId = null, $products = [], Carbon $completedAt = null): \WC_Order {
    $random = $this->getRandomString();
    $countries = ['FR', 'GB', 'US', 'IE', 'IT'];
    $address = [
      'first_name' => "{$random}_name_{$subscriberId}",
      'last_name' => "{$random}_lastname_{$subscriberId}",
      'email' => $email,
      'phone' => '123-456-789',
      'address_1' => "{$random} {$subscriberId} Main st.",
      'city' => "City of {$random} {$subscriberId}",
      'postcode' => '92121',
      'country' => $countries[array_rand($countries)],
    ];

    $args = [];
    if ($customerId) {
      $args['customer_id'] = $customerId;
    }

    $order = wc_create_order($args);
    $order->set_address($address, 'billing');
    $order->set_address($address, 'shipping');
    foreach ($products as $product) {
      $order->add_product($product);
    }
    $order->calculate_totals();
    $order->update_status('completed', '', false);

    if ($completedAt) {
      $order->set_date_completed($completedAt->toDateTimeString());
      $order->set_date_paid($completedAt->toDateTimeString());
      $orderCreatedTime = $completedAt->subMinute()->toDateTimeString();
      $order->set_date_created(get_gmt_from_date( $orderCreatedTime ));
      $order->save();
    }
    return $order;
  }

  /**
   * @param int $subscriberId
   * @param array{newsletter_id: int, queue_id: int, click_id: int} $clickData
   * @param \WC_Order $order
   * @return void
   */
  private function trackOrderRevenue(int $subscriberId, array $clickData, \WC_Order $order): void {
    $newsletter = $this->entityManager->getReference(NewsletterEntity::class, $clickData['newsletter_id']);
    $queue = $this->entityManager->getReference(SendingQueueEntity::class, $clickData['queue_id']);
    $subscriber = $this->entityManager->getReference(SubscriberEntity::class, $subscriberId);
    $click = $this->entityManager->getReference(StatisticsClickEntity::class, $clickData['click_id']);
    $statisticsClick = new StatisticsWooCommercePurchaseEntity($newsletter, $queue, $click, $order->get_id(), $order->get_currency(), floatval($order->get_total()));
    $statisticsClick->setSubscriber($subscriber);
    $statisticsClick->setCreatedAt(new Carbon($order->get_date_modified()));
    $this->entityManager->persist($statisticsClick);
    $this->entityManager->flush();
  }

  private function getRandomString($length = 5) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }
}
