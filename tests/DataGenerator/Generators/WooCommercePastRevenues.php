<?php

namespace MailPoet\Test\DataGenerator\Generators;

use MailPoet\Models\NewsletterLink;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WooCommercePastRevenues {

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

  public function generate() {
    $this->prepareDatabaseTables();
    // Reset hooks to prevent revenues calculation during generating
    remove_all_actions('woocommerce_order_status_completed');
    remove_all_actions('woocommerce_order_status_processing');

    $minimalCreatedAtDate = (new Carbon())->subDays(self::MAX_DAYS_AGO)->toDateTimeString();

    // Create list
    $segmentFactory = new Segment();
    $subscribersList = $segmentFactory->withName('WC revenues load test')->create();

    // Create subscribers
    $subscribersIds = [];
    $subscriberEmails = [];
    for ($i = 1; $i <= self::SUBSCRIBERS_COUNT; $i++) {
      $email = "address$i@email.com";
      $subscriber = $this->createSubscriber("address$i@email.com", "last_name_$i", $minimalCreatedAtDate, $subscribersList);
      $subscribersIds[] = $subscriber->id;
      $subscriberEmails[$subscriber->id] = $email;
      $batchLog = $this->getBatchLog('Subscribers', count($subscribersIds));
      if ($batchLog) {
        yield $batchLog;
      }
    }
    yield "Subscribers done";

    // Products
    $productCategory = $this->createProductCategory('WC Revenues Test Category', 'revenues-test-cat');
    $products = [];
    for ($i = 1; $i <= self::PRODUCTS_COUNT; $i++) {
      $products[] = $this->createProduct("Product $i", 100, [$productCategory->term_id]); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    }
    yield "Products done";

    // Newsletters
    $emailFactory = new Newsletter();
    // Create sent standard newsletters
    $sentStandardNewsletters = [];
    for ($i = 1; $i <= self::STANDARD_NEWSLETTER; $i++) {
      $sentAt = $this->getRandomDateInPast();
      $newsletter = $emailFactory
        ->withSubject("Standard $i")
        ->withSegments([$subscribersList])
        ->withCreatedAt($sentAt)
        ->create();
      $sentStandardNewsletters[] = $this->createSentEmailData($newsletter, $sentAt, $subscribersIds, $subscribersList->id);
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
        ->withParentId($postNotification->id)
        ->create();
      $sentPostNotifications[] = $this->createSentEmailData($newsletter, $sentAt, $subscribersIds, $subscribersList->id);
    }

    yield "Post notifications done";

    // Welcome emails
    $emailFactory = new Newsletter();
    $welcomeEmail = $emailFactory
      ->withSubject("Welcome email")
      ->withActiveStatus()
      ->withWelcomeTypeForSegment($subscribersList->id())
      ->withSegments([$subscribersList])
      ->withCreatedAt($minimalCreatedAtDate)
      ->create();
    $sentWelcomeEmails = [];
    foreach ($subscribersIds as $subscriberId) {
      $sentWelcomeEmails[$subscriberId] = $this->createSentEmailData($welcomeEmail, $minimalCreatedAtDate, [$subscriberId], $subscribersList->id);
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
      ->withSubject("First Purchase")
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
        ->withSubject("Purchased Product $i")
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
          'id' => $productCategory->term_id, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
          'name' => $productCategory->name,
        ]],
      ];
      $automaticEmails[] = $emailFactory
        ->withSubject("Purchased Product in Category $i")
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
        $sentAutomaticEmails[$subscriberId][] = $this->createSentEmailData($email, $this->getRandomDateInPast(), [$subscriberId], $subscribersList->id);
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
        array_flip(array_rand($subscriberReceivedEmails, $openedCount))
      );
      // Click and open selected emails
      foreach ($emailsToClick as $email) {
        $clickCreatedAt = (new Carbon())->setTimestamp($email['sent_at'])->addHours(1)->toDateTimeString();
        $this->openSentNewsletter($email, $subscriberId, $clickCreatedAt);
        $this->clickSentNewsletter($email, $subscriberId, $clickCreatedAt);
        $subscriberClickTimes[] = $clickCreatedAt;
      }
      // Create order
      if (isset($subscribersWithOrders[$subscriberId])) {
        // Pick a random logged click time and generate an order day after the click
        $clickTime = $subscriberClickTimes[array_rand($subscriberClickTimes)];
        $orderCompletedAt = (new Carbon($clickTime))->addDay();
        $this->createCompletedWooCommerceOrder(
          $subscriberId,
          $subscriberEmails[$subscriberId],
          [$products[array_rand($products)]],
          $orderCompletedAt
        );
      }
      $batchLog = $this->getBatchLog('Subscriber clicks and orders', $i);
      if ($batchLog) {
        yield $batchLog;
      }
    }
    yield "Clicks and Orders done";
    $this->restoreDatabaseTables();
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

  private function prepareDatabaseTables() {
    // Turn off CURRENT_TIMESTAMP to be able to save generated value
    ORM::rawExecute(
      "ALTER TABLE `" . StatisticsClicks::$_table . "`
      CHANGE `updated_at` `updated_at` timestamp NULL;"
    );

    // Disable keys
    global $wpdb;
    $prefix = $wpdb->prefix;
    ORM::rawExecute("ALTER TABLE `{$prefix}posts` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `{$prefix}postmeta` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . Subscriber::$_table . "` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . SubscriberSegment::$_table . "` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . NewsletterLink::$_table . "` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . ScheduledTask::$_table . "` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . ScheduledTaskSubscriber::$_table . "` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . SendingQueue::$_table . "` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . StatisticsOpens::$_table . "` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . StatisticsClicks::$_table . "` DISABLE KEYS");
    ORM::rawExecute("SET UNIQUE_CHECKS = 0;");
  }

  private function restoreDatabaseTables() {
    ORM::rawExecute(
      "ALTER TABLE `" . StatisticsClicks::$_table . "`
      CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;"
    );

    // Enable keys
    global $wpdb;
    $prefix = $wpdb->prefix;
    ORM::rawExecute("ALTER TABLE `{$prefix}posts` ENABLE KEYS");
    ORM::rawExecute("ALTER TABLE `{$prefix}postmeta` DISABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . Subscriber::$_table . "` ENABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . SubscriberSegment::$_table . "` ENABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . NewsletterLink::$_table . "` ENABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . ScheduledTask::$_table . "` ENABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . ScheduledTaskSubscriber::$_table . "` ENABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . SendingQueue::$_table . "` ENABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . StatisticsOpens::$_table . "` ENABLE KEYS");
    ORM::rawExecute("ALTER TABLE `" . StatisticsClicks::$_table . "` ENABLE KEYS");
    ORM::rawExecute("SET UNIQUE_CHECKS = 1;");
  }

  private function createSubscriber($email, $lastName, $createdAtDate, \MailPoet\Models\Segment $segment, $status = Subscriber::STATUS_SUBSCRIBED) {
    $subscriber = Subscriber::createOrUpdate([
      'email' => $email,
      'status' => $status,
      'last_name' => $lastName,
      'created_at' => $createdAtDate,
    ]);
    $subscriber->save();
    $segment->addSubscriber($subscriber->id);
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
  private function createSentEmailData(\MailPoet\Models\Newsletter $newsletter, $sentAt, $subscribersIds, $segmentId) {
    // Sending task
    $task = ScheduledTask::createOrUpdate([
      'type' => Sending::TASK_TYPE,
      'status' => ScheduledTask::STATUS_COMPLETED,
      'created_at' => $sentAt,
      'processed_at' => $sentAt,
    ]);
    $task->save();

    // Add subscribers to task
    $batchData = [];
    foreach ($subscribersIds as $subscriberId) {
      $batchData[] = "({$task->id}, $subscriberId, 1, '$sentAt')";
      if (count($batchData) % 1000 === 0) {
        ORM::rawExecute(
          "INSERT INTO " . ScheduledTaskSubscriber::$_table . " (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ', $batchData)
        );
        $batchData = [];
      }
    }
    if ($batchData) {
      ORM::rawExecute(
        "INSERT INTO " . ScheduledTaskSubscriber::$_table . " (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ', $batchData)
      );
    }

    // Sending queue
    $queue = SendingQueue::createOrUpdate([
      'task_id' => $task->id,
      'newsletter_id' => $newsletter->id,
      'count_total' => count($subscribersIds),
      'count_processed' => count($subscribersIds),
    ]);
    $queue->save();

    // Link
    $link = (new \MailPoet\Test\DataFactories\NewsletterLink($newsletter))
      ->withCreatedAt($sentAt)
      ->create()
      ->save();

    // Newsletter segment
    NewsletterSegment::createOrUpdate(['newsletter_id' => $newsletter->id, 'segment_id' => $segmentId])->save();

    if ($newsletter->status === \MailPoet\Models\Newsletter::STATUS_DRAFT) {
      $newsletter->status = \MailPoet\Models\Newsletter::STATUS_SENT;
      $newsletter->sentAt = $sentAt;
      $newsletter->save();
    }

    return [
      'newsletter_id' => $newsletter->id,
      'task_id' => $task->id,
      'queue_id' => $queue->id,
      'sent_at' => strtotime($sentAt),
      'link_id' => $link->id,
    ];
  }

  /**
   * @return StatisticsOpens
   */
  private function openSentNewsletter(array $sentNewsletterData, $subscriberId, $createdAt) {
    StatisticsOpens::createOrUpdate([
      'subscriber_id' => $subscriberId,
      'newsletter_id' => $sentNewsletterData['newsletter_id'],
      'queue_id' => $sentNewsletterData['queue_id'],
      'created_at' => $createdAt,
    ])->save();
  }

  /**
   * @return array
   */
  private function clickSentNewsletter(array $sentNewsletterData, $subscriberId, $createdAt) {
    StatisticsClicks::createOrUpdate([
      'subscriber_id' => $subscriberId,
      'newsletter_id' => $sentNewsletterData['newsletter_id'],
      'queue_id' => $sentNewsletterData['queue_id'],
      'link_id' => $sentNewsletterData['link_id'],
      'count' => 1,
      'created_at' => $createdAt,
      'updated_at' => $createdAt,
    ])->save();
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
  private function createCompletedWooCommerceOrder($subscriberId, $email, $products = [], Carbon $completedAt = null) {
    $address = [
      'first_name' => "name_$subscriberId",
      'last_name' => "lastname_$subscriberId",
      'email' => $email,
      'phone' => '123-456-789',
      'address_1' => "$subscriberId Main st.",
      'city' => "City of $subscriberId",
      'postcode' => '92121',
      'country' => 'France',
    ];

    $order = wc_create_order();
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
      $order->save();
      $orderCreatedTime = $completedAt->subMinute()->toDateTimeString();
      wp_update_post([
        'ID' => $order->get_id(),
        'post_date' => $orderCreatedTime,
        'post_date_gmt' => get_gmt_from_date( $orderCreatedTime ),
      ]);
    }
    return $order;
  }
}
