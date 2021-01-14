<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\AutomaticEmailsRepository;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

class PurchasedProduct {
  const SLUG = 'woocommerce_product_purchased';
  /**
   * @var \MailPoet\WooCommerce\Helper
   */
  private $helper;

  /** @var AutomaticEmailScheduler */
  private $scheduler;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var AutomaticEmailsRepository */
  private $repository;

  public function __construct(WCHelper $helper = null) {
    if ($helper === null) {
      $helper = new WCHelper();
    }
    $this->helper = $helper;
    $this->scheduler = new AutomaticEmailScheduler();
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->repository = ContainerWrapper::getInstance()->get(AutomaticEmailsRepository::class);
  }

  public function init() {
    WPFunctions::get()->removeAllFilters('woocommerce_product_purchased_get_products');
    WPFunctions::get()->addFilter(
      'woocommerce_product_purchased_get_products',
      [
        $this,
        'getProducts',
      ]
    );


    $acceptedOrderStates = WPFunctions::get()->applyFilters('mailpoet_first_purchase_order_states', ['completed', 'processing']);
    foreach ($acceptedOrderStates as $state) {
      WPFunctions::get()->addAction('woocommerce_order_status_' . $state, [
        $this,
        'scheduleEmailWhenProductIsPurchased',
      ], 10, 1);
    }
  }

  public function getEventDetails() {
    return [
      'slug' => self::SLUG,
      'title' => WPFunctions::get()->__('Purchased This Product', 'mailpoet'),
      'description' => WPFunctions::get()->__('Let MailPoet send an email to customers who purchase a specific product for the first time.', 'mailpoet'),
      'listingScheduleDisplayText' => WPFunctions::get()->__('Email sent when a customer buys product: %s', 'mailpoet'),
      'listingScheduleDisplayTextPlural' => WPFunctions::get()->__('Email sent when a customer buys products: %s', 'mailpoet'),
      'options' => [
        'multiple' => true,
        'endpoint' => 'products',
        'placeholder' => __('Search products', 'mailpoet'),
      ],
    ];
  }

  public function getProducts($productSearchQuery) {
    $args = [
      'post_type' => 'product',
      'post_status' => 'publish',
      's' => $productSearchQuery,
      'orderby' => 'title',
      'order' => 'ASC',
    ];
    $woocommerceProducts = new \WP_Query($args);
    $woocommerceProducts = $woocommerceProducts->get_posts();
    /** @var \WP_Post[] $woocommerceProducts */
    if (empty($woocommerceProducts)) {
      $this->loggerFactory->getLogger(self::SLUG)->addInfo(
        'no products found', ['search_query' => $productSearchQuery]
      );
      return;
    }

    $woocommerceProducts = array_map(function($product) {
      return [
        'id' => $product->ID,
        'name' => $product->post_title, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ];
    }, $woocommerceProducts);
    return $woocommerceProducts;
  }

  public function scheduleEmailWhenProductIsPurchased($orderId) {
    $orderDetails = $this->helper->wcGetOrder($orderId);
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

    $orderedProducts = array_map(function($product) {
      return ($product instanceof \WC_Order_Item_Product) ? $product->get_product_id() : null;
    }, $orderDetails->get_items());
    $orderedProducts = array_values(array_filter($orderedProducts));

    $schedulingCondition = function(Newsletter $automaticEmail) use ($orderedProducts, $subscriber) {
      $meta = $automaticEmail->getMeta();

      if (empty($meta['option'])) return false;
      if ($this->repository->wasScheduledForSubscriber($automaticEmail->id, $subscriber->id)) {
        $sentAllProducts = $this->repository->alreadySentAllProducts($automaticEmail->id, $subscriber->id, 'orderedProducts', $orderedProducts);
        if ($sentAllProducts) return false;
      }

      $metaProducts = array_column($meta['option'], 'id');
      $matchedProducts = array_intersect($metaProducts, $orderedProducts);

      return !empty($matchedProducts);
    };

    $this->loggerFactory->getLogger(self::SLUG)->addInfo(
      'Email scheduled', [
        'order_id' => $orderId,
        'customer_email' => $customerEmail,
        'subscriber_id' => $subscriber->id,
      ]
    );
    return $this->scheduler->scheduleAutomaticEmail(
      WooCommerce::SLUG,
      self::SLUG,
      $schedulingCondition,
      $subscriber->id,
      ['orderedProducts' => $orderedProducts]
    );
  }
}
