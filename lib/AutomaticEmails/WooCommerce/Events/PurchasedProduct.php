<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

use function MailPoetVendor\array_column;

class PurchasedProduct {
  const SLUG = 'woocommerce_product_purchased';
  /**
   * @var \MailPoet\WooCommerce\Helper
   */
  private $helper;

  /** @var AutomaticEmailScheduler */
  private $scheduler;

  /** @var LoggerFactory */
  private $logger_factory;

  function __construct(WCHelper $helper = null) {
    if ($helper === null) {
      $helper = new WCHelper();
    }
    $this->helper = $helper;
    $this->scheduler = new AutomaticEmailScheduler();
    $this->logger_factory = LoggerFactory::getInstance();
  }

  function init() {
    WPFunctions::get()->removeAllFilters('woocommerce_product_purchased_get_products');
    WPFunctions::get()->addFilter(
      'woocommerce_product_purchased_get_products',
      [
        $this,
        'getProducts',
      ]
    );


    $accepted_order_states = WPFunctions::get()->applyFilters('mailpoet_first_purchase_order_states', ['completed', 'processing']);
    foreach ($accepted_order_states as $state) {
      WPFunctions::get()->addAction('woocommerce_order_status_' . $state, [
        $this,
        'scheduleEmailWhenProductIsPurchased',
      ], 10, 1);
    }
  }

  function getEventDetails() {
    return [
      'slug' => self::SLUG,
      'title' => WPFunctions::get()->__('Purchased This Product', 'mailpoet'),
      'description' => WPFunctions::get()->__('Let MailPoet send an email to customers who purchase a specific product.', 'mailpoet'),
      'listingScheduleDisplayText' => WPFunctions::get()->__('Email sent when a customer buys product: %s', 'mailpoet'),
      'listingScheduleDisplayTextPlural' => WPFunctions::get()->__('Email sent when a customer buys products: %s', 'mailpoet'),
      'options' => [
        'type' => 'remote',
        'multiple' => true,
        'remoteQueryMinimumInputLength' => 3,
        'remoteQueryFilter' => 'woocommerce_product_purchased_get_products',
        'placeholder' => WPFunctions::get()->__('Start typing to search for products...', 'mailpoet'),
      ],
    ];
  }

  function getProducts($product_search_query) {
    $args = [
      'post_type' => 'product',
      'post_status' => 'publish',
      's' => $product_search_query,
      'orderby' => 'title',
      'order' => 'ASC',
    ];
    $woocommerce_products = new \WP_Query($args);
    $woocommerce_products = $woocommerce_products->get_posts();
    if (empty($woocommerce_products)) {
      $this->logger_factory->getLogger(self::SLUG)->addInfo(
        'no products found', ['search_query' => $product_search_query]
      );
      return;
    }

    $woocommerce_products = array_map(function($product) {
      return [
        'id' => $product->ID,
        'name' => $product->post_title,
      ];
    }, $woocommerce_products);
    return $woocommerce_products;
  }

  function scheduleEmailWhenProductIsPurchased($order_id) {
    $order_details = $this->helper->wcGetOrder($order_id);
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

    $ordered_products = array_map(function($product) {
      return is_callable([$product, 'get_product_id']) ? $product->get_product_id() : null;
    }, $order_details->get_items());
    $ordered_products = array_values(array_filter($ordered_products));

    $scheduling_condition = function($automatic_email) use ($ordered_products, $subscriber) {
      $meta = $automatic_email->getMeta();

      if (empty($meta['option']) || $automatic_email->wasScheduledForSubscriber($subscriber->id)) return false;

      $meta_products = array_column($meta['option'], 'id');
      $matched_products = array_intersect($meta_products, $ordered_products);

      return !empty($matched_products);
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
