<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

use function MailPoetVendor\array_column;

class PurchasedInCategory {
  const SLUG = 'woocommerce_product_purchased_in_category';

  /** @var WCHelper */
  private $woocommerce_helper;

  /** @var AutomaticEmailScheduler */
  private $scheduler;

  /** @var LoggerFactory */
  private $logger_factory;

  public function __construct(WCHelper $woocommerce_helper = null) {
    if ($woocommerce_helper === null) {
      $woocommerce_helper = new WCHelper();
    }
    $this->woocommerce_helper = $woocommerce_helper;
    $this->scheduler = new AutomaticEmailScheduler();
    $this->logger_factory = LoggerFactory::getInstance();
  }

  public function getEventDetails() {
    return [
      'slug' => self::SLUG,
      'title' => _x('Purchased In This Category', 'This is the name of a type for automatic email for ecommerce. Those emails are sent automatically every time a customer buys for the first time a product in a given category', 'mailpoet'),
      'description' => __('Let MailPoet send an email to customers who purchase a product from a specific category.', 'mailpoet'),
      'listingScheduleDisplayText' => __('Email sent when a customer buys a product in category: %s', 'mailpoet'),
      'listingScheduleDisplayTextPlural' => __('Email sent when a customer buys a product in categories: %s', 'mailpoet'),
      'options' => [
        'multiple' => true,
        'type' => 'remote',
        'remoteQueryMinimumInputLength' => 3,
        'remoteQueryFilter' => 'woocommerce_product_purchased_get_categories',
        'placeholder' => _x('Start typing to search for categories…', 'Search input for product category (ecommerce)', 'mailpoet'),
      ],
    ];
  }

  public function init() {
    WPFunctions::get()->removeAllFilters('woocommerce_product_purchased_get_categories');
    WPFunctions::get()->addFilter(
      'woocommerce_product_purchased_get_categories',
      [$this, 'getCategories']
    );

    $accepted_order_states = WPFunctions::get()->applyFilters('mailpoet_first_purchase_order_states', ['completed', 'processing']);
    foreach ($accepted_order_states as $state) {
      WPFunctions::get()->addAction(
        'woocommerce_order_status_' . $state,
        [$this, 'scheduleEmail'],
        10,
        1
      );
    }
  }

  public function getCategories($search_query) {
    $args = [
      'taxonomy' => 'product_cat',
      'search' => $search_query,
      'orderby' => 'name',
      'hierarchical' => 0,
      'hide_empty' => 1,
      'order' => 'ASC',
    ];
    $all_categories = get_categories($args);

    return array_map(function($category) {
      return [
        'id' => $category->term_id,
        'name' => $category->name,
      ];
    }, $all_categories);
  }

  public function scheduleEmail($order_id) {
    $order_details = $this->woocommerce_helper->wcGetOrder($order_id);
    if (!$order_details || !$order_details->get_billing_email()) {
      $this->logger_factory->getLogger(self::SLUG)->addInfo(
        'Email not scheduled because the order customer was not found',
        ['order_id' => $order_id]
      );
      return;
    }
    $customer_email = $order_details->get_billing_email();

    $subscriber = Subscriber::getWooCommerceSegmentSubscriber($customer_email);

    if (!$subscriber instanceof Subscriber) {
      $this->logger_factory->getLogger(self::SLUG)->addInfo(
        'Email not scheduled because the customer was not found as WooCommerce list subscriber',
        ['order_id' => $order_id, 'customer_email' => $customer_email]
      );
      return;
    }

    $ordered_product_categories = [];
    foreach ($order_details->get_items() as $order_item_product) {
      $product = $order_item_product->get_product();
      if (!$product instanceof \WC_Product) {
        continue;
      }
      $ordered_product_categories = array_merge($ordered_product_categories, $product->get_category_ids());
    }

    $scheduling_condition = function($automatic_email) use ($ordered_product_categories, $subscriber) {
      $meta = $automatic_email->getMeta();
      if (empty($meta['option']) || $automatic_email->wasScheduledForSubscriber($subscriber->id)) return false;

      $meta_categories = array_column($meta['option'], 'id');
      $matched_categories = array_intersect($meta_categories, $ordered_product_categories);

      return !empty($matched_categories);
    };

    $this->logger_factory->getLogger(self::SLUG)->addInfo(
      'Email scheduled', [
        'order_id' => $order_id,
        'customer_email' => $customer_email,
        'subscriber_id' => $subscriber->id,
      ]
    );
    $this->scheduler->scheduleAutomaticEmail(WooCommerce::SLUG, self::SLUG, $scheduling_condition, $subscriber->id);
  }
}
