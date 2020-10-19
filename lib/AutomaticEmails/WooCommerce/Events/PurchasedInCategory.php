<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

class PurchasedInCategory {
  const SLUG = 'woocommerce_product_purchased_in_category';

  /** @var WCHelper */
  private $woocommerceHelper;

  /** @var AutomaticEmailScheduler */
  private $scheduler;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct(WCHelper $woocommerceHelper = null) {
    if ($woocommerceHelper === null) {
      $woocommerceHelper = new WCHelper();
    }
    $this->woocommerceHelper = $woocommerceHelper;
    $this->scheduler = new AutomaticEmailScheduler();
    $this->loggerFactory = LoggerFactory::getInstance();
  }

  public function getEventDetails() {
    return [
      'slug' => self::SLUG,
      'title' => _x('Purchased In This Category', 'This is the name of a type for automatic email for ecommerce. Those emails are sent automatically every time a customer buys for the first time a product in a given category', 'mailpoet'),
      'description' => __('Let MailPoet send an email to customers who purchase a product for the first time in a specific category.', 'mailpoet'),
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

    $acceptedOrderStates = WPFunctions::get()->applyFilters('mailpoet_first_purchase_order_states', ['completed', 'processing']);
    foreach ($acceptedOrderStates as $state) {
      WPFunctions::get()->addAction(
        'woocommerce_order_status_' . $state,
        [$this, 'scheduleEmail'],
        10,
        1
      );
    }
  }

  public function getCategories($searchQuery) {
    $args = [
      'taxonomy' => 'product_cat',
      'search' => $searchQuery,
      'orderby' => 'name',
      'hierarchical' => 0,
      'hide_empty' => 1,
      'order' => 'ASC',
    ];
    $allCategories = get_categories($args);

    return array_map(function($category) {
      return [
        'id' => $category->term_id, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        'name' => $category->name,
      ];
    }, $allCategories);
  }

  public function scheduleEmail($orderId) {
    $orderDetails = $this->woocommerceHelper->wcGetOrder($orderId);
    if (!$orderDetails || !$orderDetails->get_billing_email()) {
      $this->loggerFactory->getLogger(self::SLUG)->addInfo(
        'Email not scheduled because the order customer was not found',
        ['order_id' => $orderId]
      );
      return;
    }
    $customerEmail = $orderDetails->get_billing_email();

    $subscriber = Subscriber::getWooCommerceSegmentSubscriber($customerEmail);

    if (!$subscriber instanceof Subscriber) {
      $this->loggerFactory->getLogger(self::SLUG)->addInfo(
        'Email not scheduled because the customer was not found as WooCommerce list subscriber',
        ['order_id' => $orderId, 'customer_email' => $customerEmail]
      );
      return;
    }

    $orderedProductCategories = [];
    foreach ($orderDetails->get_items() as $orderItemProduct) {
      $product = $orderItemProduct->get_product();
      if (!$product instanceof \WC_Product) {
        continue;
      }
      $orderedProductCategories = array_merge($orderedProductCategories, $product->get_category_ids());
    }

    $schedulingCondition = function($automaticEmail) use ($orderedProductCategories, $subscriber) {
      $meta = $automaticEmail->getMeta();

      if (empty($meta['option'])) return false;
      if ($automaticEmail->wasScheduledForSubscriber($subscriber->id)) return false;

      $metaCategories = array_column($meta['option'], 'id');
      $matchedCategories = array_intersect($metaCategories, $orderedProductCategories);

      return !empty($matchedCategories);
    };

    $this->loggerFactory->getLogger(self::SLUG)->addInfo(
      'Email scheduled', [
        'order_id' => $orderId,
        'customer_email' => $customerEmail,
        'subscriber_id' => $subscriber->id,
      ]
    );
    $this->scheduler->scheduleAutomaticEmail(
      WooCommerce::SLUG,
      self::SLUG,
      $schedulingCondition,
      $subscriber->id,
      ['orderedProducts' => $orderedProductCategories]
    );
  }
}
