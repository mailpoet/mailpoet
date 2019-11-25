<?php

namespace MailPoet\Test\DataGenerator\Generators;

use Carbon\Carbon;
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
    for ($i = 1; $i <= self::SUBSCRIBERS_COUNT; $i++) {
      $email = "address$i@email.com";
      $subscriber = $this->createSubscriber("address$i@email.com", "last_name_$i", $minimal_created_at_date, $subscribers_list);
      $subscribers_ids[] = $subscriber->id;
      $subscriber_emails[$subscriber->id] = $email;
      $batch_log = $this->getBatchLog('Subscribers', count($subscribers_ids));
      if ($batch_log) {
        yield $batch_log;
      }
    }
    yield "Subscribers done";

    // Products
    $product_category = $this->createProductCategory('WC Revenues Test Category', 'revenues-test-cat');
    $products = [];
    for ($i = 1; $i <= self::PRODUCTS_COUNT; $i++) {
      $products[] = $this->createProduct("Product $i", 100, [$product_category->term_id]);
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
      $sent_standard_newsletters[] = $this->createSentEmailData($newsletter, $sent_at, $subscribers_ids, $subscribers_list->id);
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
      $sent_post_notifications[] = $this->createSentEmailData($newsletter, $sent_at, $subscribers_ids, $subscribers_list->id);
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
      $sent_welcome_emails[$subscriber_id] = $this->createSentEmailData($welcome_email, $minimal_created_at_date, [$subscriber_id], $subscribers_list->id);
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
        $sent_automatic_emails[$subscriber_id][] = $this->createSentEmailData($email, $this->getRandomDateInPast(), [$subscriber_id], $subscribers_list->id);
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
      $opened_count = floor(count($subscriber_received_emails) / rand(2, 5));
      $emails_to_click = array_intersect_key(
        $subscriber_received_emails,
        array_flip(array_rand($subscriber_received_emails, $opened_count))
      );
      // Click and open selected emails
      foreach ($emails_to_click as $email) {
        $click_created_at = (new Carbon())->setTimestamp($email['sent_at'])->addHours(1)->toDateTimeString();
        $this->openSentNewsletter($email, $subscriber_id, $click_created_at);
        $this->clickSentNewsletter($email, $subscriber_id, $click_created_at);
        $subscriber_click_times[] = $click_created_at;
      }
      // Create order
      if (isset($subscribers_with_orders[$subscriber_id])) {
        // Pick a random logged click time and generate an order day after the click
        $click_time = $subscriber_click_times[array_rand($subscriber_click_times)];
        $order_completed_at = (new Carbon($click_time))->addDay();
        $this->createCompletedWooCommerceOrder(
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

  private function createSubscriber($email, $last_name, $created_at_date, \MailPoet\Models\Segment $segment, $status = Subscriber::STATUS_SUBSCRIBED) {
    $subscriber = Subscriber::createOrUpdate([
      'email' => $email,
      'status' => $status,
      'last_name' => $last_name,
      'created_at' => $created_at_date,
    ]);
    $subscriber->save();
    $segment->addSubscriber($subscriber->id);
    return $subscriber;
  }

  /**
   * @return \WC_Product
   */
  private function createProduct($name, $price, $category_ids = [], $discount = 0) {
    $product = new \WC_Product();
    $product->set_name($name);
    $product->set_price($price - $discount);
    $product->set_status('publish');
    $product->set_regular_price($price);
    if ($category_ids) {
      $product->set_category_ids($category_ids);
    }
    $product->save();
    return $product;
  }

  /**
   * @return array
   */
  private function createSentEmailData(\MailPoet\Models\Newsletter $newsletter, $sent_at, $subscribers_ids, $segment_id) {
    // Sending task
    $task = ScheduledTask::createOrUpdate([
      'type' => Sending::TASK_TYPE,
      'status' => ScheduledTask::STATUS_COMPLETED,
      'created_at' => $sent_at,
      'processed_at' => $sent_at,
    ]);
    $task->save();

    // Add subscribers to task
    $batch_data = [];
    foreach ($subscribers_ids as $subscriber_id) {
      $batch_data[] = "({$task->id}, $subscriber_id, 1, '$sent_at')";
      if (count($batch_data) % 1000 === 0) {
        ORM::rawExecute(
          "INSERT INTO " . ScheduledTaskSubscriber::$_table . " (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ', $batch_data)
        );
        $batch_data = [];
      }
    }
    if ($batch_data) {
      ORM::rawExecute(
        "INSERT INTO " . ScheduledTaskSubscriber::$_table . " (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ', $batch_data)
      );
    }

    // Sending queue
    $queue = SendingQueue::createOrUpdate([
      'task_id' => $task->id,
      'newsletter_id' => $newsletter->id,
      'count_total' => count($subscribers_ids),
      'count_processed' => count($subscribers_ids),
    ]);
    $queue->save();

    // Link
    $link = (new \MailPoet\Test\DataFactories\NewsletterLink($newsletter))
      ->withCreatedAt($sent_at)
      ->create()
      ->save();

    // Newsletter segment
    NewsletterSegment::createOrUpdate(['newsletter_id' => $newsletter->id, 'segment_id' => $segment_id])->save();

    if ($newsletter->status === \MailPoet\Models\Newsletter::STATUS_DRAFT) {
      $newsletter->status = \MailPoet\Models\Newsletter::STATUS_SENT;
      $newsletter->sent_at = $sent_at;
      $newsletter->save();
    }

    return [
      'newsletter_id' => $newsletter->id,
      'task_id' => $task->id,
      'queue_id' => $queue->id,
      'sent_at' => strtotime($sent_at),
      'link_id' => $link->id,
    ];
  }

  /**
   * @return StatisticsOpens
   */
  private function openSentNewsletter(array $sent_newsletter_data, $subscriber_id, $created_at) {
    StatisticsOpens::createOrUpdate([
      'subscriber_id' => $subscriber_id,
      'newsletter_id' => $sent_newsletter_data['newsletter_id'],
      'queue_id' => $sent_newsletter_data['queue_id'],
      'created_at' => $created_at,
    ])->save();
  }

  /**
   * @return array
   */
  private function clickSentNewsletter(array $sent_newsletter_data, $subscriber_id, $created_at) {
    StatisticsClicks::createOrUpdate([
      'subscriber_id' => $subscriber_id,
      'newsletter_id' => $sent_newsletter_data['newsletter_id'],
      'queue_id' => $sent_newsletter_data['queue_id'],
      'link_id' => $sent_newsletter_data['link_id'],
      'count' => 1,
      'created_at' => $created_at,
      'updated_at' => $created_at,
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
  private function createCompletedWooCommerceOrder($subscriber_id, $email, $products = [], Carbon $completed_at = null) {
    $address = [
      'first_name' => "name_$subscriber_id",
      'last_name' => "lastname_$subscriber_id",
      'email' => $email,
      'phone' => '123-456-789',
      'address_1' => "$subscriber_id Main st.",
      'city' => "City of $subscriber_id",
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

    if ($completed_at) {
      $order->set_date_completed($completed_at->toDateTimeString());
      $order->set_date_paid($completed_at->toDateTimeString());
      $order->save();
      $order_created_time = $completed_at->subMinute()->toDateTimeString();
      wp_update_post([
        'ID' => $order->get_id(),
        'post_date' => $order_created_time,
        'post_date_gmt' => get_gmt_from_date( $order_created_time ),
      ]);
    }
    return $order;
  }
}
