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

class PurchasedInCategory {
  const SLUG = 'woocommerce_product_purchased_in_category';

  /** @var WCHelper */
  private $woocommerceHelper;

  /** @var AutomaticEmailScheduler */
  private $scheduler;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var AutomaticEmailsRepository */
  private $repository;

  public function __construct(
    WCHelper $woocommerceHelper = null
  ) {
    if ($woocommerceHelper === null) {
      $woocommerceHelper = new WCHelper();
    }
    $this->woocommerceHelper = $woocommerceHelper;
    $this->scheduler = new AutomaticEmailScheduler();
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->repository = ContainerWrapper::getInstance()->get(AutomaticEmailsRepository::class);
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
        'endpoint' => 'product_categories',
        'placeholder' => _x('Search category', 'Search input for product category (ecommerce)', 'mailpoet'),
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
        'id' => $category->term_id, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
      if ($product->get_type() === 'variation') {
        // WooCommerce returns a empty list when get_category_ids() is called for a product variation,
        // so we need to get the parent product
        $product = $this->woocommerceHelper->wcGetProduct($product->get_parent_id());
      }
      $orderedProductCategories = array_merge($orderedProductCategories, $product->get_category_ids());
    }

    $schedulingCondition = function($automaticEmail) use ($orderedProductCategories, $subscriber) {
      $matchedCategories = $this->getProductCategoryIdsMatchingNewsletterTrigger($automaticEmail, $orderedProductCategories);
      if (empty($matchedCategories)) {
        return false;
      }

      if ($this->repository->wasScheduledForSubscriber($automaticEmail->id, $subscriber->id)) {
        $sentAllProducts = $this->repository->alreadySentAllProducts($automaticEmail->id, $subscriber->id, 'orderedProductCategories', $matchedCategories);
        if ($sentAllProducts) return false;
      }

      return true;
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
      ['orderedProductCategories' => $orderedProductCategories],
      [$this, 'metaModifier']
    );
  }

  public function metaModifier(Newsletter $automaticEmail, array $meta): array {
    $orderedProductCategoryIds = $meta['orderedProductCategories'] ?? null;
    if (empty($orderedProductCategoryIds)) {
      return $meta;
    }
    $meta['orderedProductCategories'] = $this->getProductCategoryIdsMatchingNewsletterTrigger($automaticEmail, $orderedProductCategoryIds);

    return $meta;
  }

  private function getProductCategoryIdsMatchingNewsletterTrigger(Newsletter $automaticEmail, array $orderedCategoryIds): array {
    $automaticEmailMeta = $automaticEmail->getMeta();
    if (empty($automaticEmailMeta['option'])) {
      return [];
    }
    $emailTriggeringCategoryIds = array_column($automaticEmailMeta['option'], 'id');

    return array_intersect($emailTriggeringCategoryIds, $orderedCategoryIds);
  }
}
