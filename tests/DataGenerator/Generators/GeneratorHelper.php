<?php

namespace MailPoet\Test\DataGenerator\Generators;

use Carbon\Carbon;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterSegment;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsClicks;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Tasks\Sending;
use MailPoet\Test\DataFactories\NewsletterLink;

class GeneratorHelper {
  public function createSubscriber($email, $last_name, $created_at_date, Segment $segment, $status = Subscriber::STATUS_SUBSCRIBED) {
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
  public function createProduct($name, $price, $category_ids = [], $discount = 0) {
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
  public function createSentEmailData(Newsletter $newsletter, $sent_at, $subscribers_ids, $segment_id) {
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
    foreach($subscribers_ids as $subscriber_id) {
      $batch_data[] = "({$task->id}, $subscriber_id, 1, '$sent_at')";
      if (count($batch_data) % 1000 === 0) {
        \ORM::rawExecute(
          "INSERT INTO " . ScheduledTaskSubscriber::$_table . " (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ',  $batch_data)
        );
        $batch_data = [];
      }
    }
    if ($batch_data) {
      \ORM::rawExecute(
        "INSERT INTO " . ScheduledTaskSubscriber::$_table . " (`task_id`, `subscriber_id`, `processed`, `created_at`) VALUES " . implode(', ',  $batch_data)
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
    $link = (new NewsletterLink($newsletter))
      ->withCreatedAt($sent_at)
      ->create()
      ->save();

    // Newsletter segment
    NewsletterSegment::createOrUpdate(['newsletter_id' => $newsletter->id, 'segment_id' => $segment_id])->save();

    if ($newsletter->status === Newsletter::STATUS_DRAFT) {
      $newsletter->status = Newsletter::STATUS_SENT;
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
  public function openSentNewsletter(array $sent_newsletter_data, $subscriber_id, $created_at) {
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
  public function clickSentNewsletter(array $sent_newsletter_data, $subscriber_id, $created_at) {
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
  public function createProductCategory($name, $slug, $description = '') {
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
  public function createCompletedWooCommerceOrder($subscriber_id, $email, $products = [], Carbon $completed_at = null) {
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
    foreach($products as $product) {
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
