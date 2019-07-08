<?php

namespace MailPoet\Test\DataGenerator\Generators;

use Carbon\Carbon;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;

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

  /** @var GeneratorHelper */
  private $helper;

  function __construct(GeneratorHelper $generator_helper) {
    $this->helper = $generator_helper;
  }

  function generate() {
    $this->prepareDatabaseTables();
    // Reset hooks to prevent revenues calculation during generating
    remove_all_actions('woocommerce_order_status_completed');
    remove_all_actions('woocommerce_order_status_processing');

    $minimal_created_at_date = (new Carbon())->subDays(self::MAX_DAYS_AGO)->toDateTimeString();

    // Create list
    $segment_factory = new Segment();
    $subscribers_list = $segment_factory->withName('WC revenues load test')->create();

    // Create subscribers
    $subscribers_ids = [];
    $subscriber_emails = [];
    for ($i=1; $i <= self::SUBSCRIBERS_COUNT; $i++) {
      $email = "address$i@email.com";
      $subscriber = $this->helper->createSubscriber("address$i@email.com", "last_name_$i", $minimal_created_at_date, $subscribers_list);
      $subscribers_ids[] = $subscriber->id;
      $subscriber_emails[$subscriber->id] = $email;
      $batch_log = $this->getBatchLog('Subscribers', count($subscribers_ids));
      if ($batch_log) {
        yield $batch_log;
      }
    }
    yield "Subscribers done";

    // Products
    $product_category = $this->helper->createProductCategory('WC Revenues Test Category', 'revenues-test-cat');
    $products = [];
    for ($i=1; $i <= self::PRODUCTS_COUNT; $i++) {
      $products[] = $this->helper->createProduct("Product $i", 100, [$product_category->term_id]);
    }
    yield "Products done";

    // Newsletters
    $email_factory = new Newsletter();
    // Create sent standard newsletters
    $sent_standard_newsletters = [];
    for ($i = 1; $i <= self::STANDARD_NEWSLETTER; $i++) {
      $sent_at = $this->getRandomDateInPast();
      $newsletter = $email_factory
        ->withSubject("Standard $i")
        ->withSegments([$subscribers_list])
        ->withCreatedAt($sent_at)
        ->create();
      $sent_standard_newsletters[] = $this->helper->createSentEmailData($newsletter, $sent_at, $subscribers_ids, $subscribers_list->id);
    }
    yield "Standard newsletters done";

    // Crate sent post notifications
    $email_factory = new Newsletter();
    $post_notification = $email_factory
      ->withSubject("Post Notification Parent")
      ->withPostNotificationsType()
      ->withActiveStatus()
      ->withSegments([$subscribers_list])
      ->withCreatedAt($minimal_created_at_date)
      ->create();
    $sent_post_notifications = [];
    for ($i = 1; $i <= self::POST_NOTIFICATIONS_HISTORY; $i++) {
      $sent_at = $this->getRandomDateInPast();
      $newsletter = $email_factory
        ->withSubject("Post notification history $i")
        ->withPostNotificationHistoryType()
        ->withSegments([$subscribers_list])
        ->withCreatedAt($sent_at)
        ->withParentId($post_notification->id)
        ->create();
      $sent_post_notifications[] = $this->helper->createSentEmailData($newsletter, $sent_at, $subscribers_ids, $subscribers_list->id);
    }

    yield "Post notifications done";

    // Welcome emails
    $email_factory = new Newsletter();
    $welcome_email = $email_factory
      ->withSubject("Welcome email")
      ->withActiveStatus()
      ->withWelcomeTypeForSegment($subscribers_list->id())
      ->withSegments([$subscribers_list])
      ->withCreatedAt($minimal_created_at_date)
      ->create();
    $sent_welcome_emails = [];
    foreach ($subscribers_ids as $subscriber_id) {
      $sent_welcome_emails[$subscriber_id] = $this->helper->createSentEmailData($welcome_email, $minimal_created_at_date, [$subscriber_id], $subscribers_list->id);
      $batch_log = $this->getBatchLog('Welcome emails sent', count($sent_welcome_emails));
      if ($batch_log) {
        yield $batch_log;
      }
    }

    yield "Welcome emails done";

    // Automatic emails
    $automatic_emails = [];
    $email_factory = new Newsletter();
    // First purchase
    $automatic_emails[] = $email_factory
      ->withSubject("First Purchase")
      ->withActiveStatus()
      ->withAutomaticTypeWooCommerceFirstPurchase()
      ->withSegments([])
      ->withCreatedAt($minimal_created_at_date)
      ->create();
    // Purchased product
    for ($i = 1; $i <= 2; $i++) {
      $product = [
        'id' => $products[$i]->get_name(),
        'name' => $products[$i]->get_id(),
      ];
      $automatic_emails[] = $email_factory
        ->withSubject("Purchased Product $i")
        ->withActiveStatus()
        ->withAutomaticTypeWooCommerceProductPurchased([$product])
        ->withSegments([])
        ->withCreatedAt($minimal_created_at_date)
        ->create();
    }
    // Purchase in category emails
    for ($i = 1; $i <= 2; $i++) {
      $product = [
        'id' => $products[$i]->get_name(),
        'name' => $products[$i]->get_id(),
        'categories' => $products[$i]->get_category_ids(),
      ];
      $automatic_emails[] = $email_factory
        ->withSubject("Purchased Product in Category $i")
        ->withActiveStatus()
        ->withAutomaticTypeWooCommerceProductInCategoryPurchased([$product])
        ->withSegments([])
        ->withCreatedAt($minimal_created_at_date)
        ->create();
    }

    // Send automatic emails
    $sent_automatic_emails = [];
    foreach ($subscribers_ids as $subscriber_id) {
      $sent_automatic_emails[$subscriber_id] = [];
      // Pick random three automatic emails for each subscriber
      $emails_to_send = array_intersect_key(
        $automatic_emails,
        array_flip(array_rand($automatic_emails, 3))
      );
      foreach ($emails_to_send as $email) {
        $sent_automatic_emails[$subscriber_id][] = $this->helper->createSentEmailData($email, $this->getRandomDateInPast(), [$subscriber_id], $subscribers_list->id);
      }
      $batch_log = $this->getBatchLog('Automatic emails sent', count($sent_automatic_emails));
      if ($batch_log) {
        yield $batch_log;
      }
    }
    yield "Automatic emails done";

    // Clicks and orders
    // Pick random subscribers which will have an order
    $subscribers_with_orders = array_flip(array_intersect_key(
      $subscribers_ids,
      array_flip(array_rand($subscribers_ids, self::SUBSCRIBERS_WITH_ORDERS_COUNT))
    ));
    $i = 0;
    foreach ($subscribers_ids as $subscriber_id) {
      $i++;
      $subscriber_click_times = [];
      $subscriber_received_emails = array_merge(
        $sent_automatic_emails[$subscriber_id],
        [$sent_welcome_emails[$subscriber_id]],
        $sent_post_notifications,
        $sent_standard_newsletters
      );
      // Pick amount of received emails and generate opens and clicks
      $opened_count = floor(count($subscriber_received_emails)/rand(2,5));
      $emails_to_click = array_intersect_key(
        $subscriber_received_emails,
        array_flip(array_rand($subscriber_received_emails, $opened_count))
      );
      // Click and open selected emails
      foreach ($emails_to_click as $email) {
        $click_created_at = (new Carbon())->setTimestamp($email['sent_at'])->addHours(1)->toDateTimeString();
        $this->helper->openSentNewsletter($email, $subscriber_id, $click_created_at);
        $this->helper->clickSentNewsletter($email, $subscriber_id, $click_created_at);
        $subscriber_click_times[] = $click_created_at;
      }
      // Create order
      if (isset($subscribers_with_orders[$subscriber_id])) {
        // Pick a random logged click time and generate an order day after the click
        $click_time = $subscriber_click_times[array_rand($subscriber_click_times)];
        $order_completed_at = (new Carbon($click_time))->addDay();
        $this->helper->createCompletedWooCommerceOrder(
          $subscriber_id,
          $subscriber_emails[$subscriber_id],
          [$products[array_rand($products)]],
          $order_completed_at
        );
      }
      $batch_log = $this->getBatchLog('Subscriber clicks and orders', $i);
      if ($batch_log) {
        yield $batch_log;
      }
    }
    yield "Clicks and Orders done";
    $this->restoreDatabaseTables();
  }

  private function getRandomDateInPast() {
    $days_ago = mt_rand(self::MIN_DAYS_AGO, self::MAX_DAYS_AGO);
    return (new Carbon())->subDays($days_ago)->toDateTimeString();
  }

  private function getBatchLog($data_type, $generated_count) {
    if ($generated_count % self::LOG_BATCH_SIZE !== 0) {
      return;
    }
    return "$data_type: $generated_count";
  }

  private function prepareDatabaseTables() {
    // Turn off CURRENT_TIMESTAMP to be able to save generated value
    \ORM::rawExecute(
      "ALTER TABLE `" . StatisticsClicks::$_table . "`
      CHANGE `updated_at` `updated_at` timestamp NULL;"
    );

    // Disable keys
    global $wpdb;
    $prefix = $wpdb->prefix;
    \ORM::rawExecute("ALTER TABLE `{$prefix}posts` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `{$prefix}postmeta` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . Subscriber::$_table . "` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . SubscriberSegment::$_table . "` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . NewsletterLink::$_table . "` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . ScheduledTask::$_table . "` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . ScheduledTaskSubscriber::$_table . "` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . SendingQueue::$_table . "` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . StatisticsOpens::$_table . "` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . StatisticsClicks::$_table . "` DISABLE KEYS");
    \ORM::rawExecute("SET UNIQUE_CHECKS = 0;");
  }

  private function restoreDatabaseTables() {
    \ORM::rawExecute(
      "ALTER TABLE `" . StatisticsClicks::$_table . "`
      CHANGE `updated_at` `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;"
    );

    // Enable keys
    global $wpdb;
    $prefix = $wpdb->prefix;
    \ORM::rawExecute("ALTER TABLE `{$prefix}posts` ENABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `{$prefix}postmeta` DISABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . Subscriber::$_table . "` ENABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . SubscriberSegment::$_table . "` ENABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . NewsletterLink::$_table . "` ENABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . ScheduledTask::$_table . "` ENABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . ScheduledTaskSubscriber::$_table . "` ENABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . SendingQueue::$_table . "` ENABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . StatisticsOpens::$_table . "` ENABLE KEYS");
    \ORM::rawExecute("ALTER TABLE `" . StatisticsClicks::$_table . "` ENABLE KEYS");
    \ORM::rawExecute("SET UNIQUE_CHECKS = 1;");
  }
}
