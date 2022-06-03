<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\AutomaticEmailsRepository;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Helpers;
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

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    WCHelper $helper = null
  ) {
    if ($helper === null) {
      $helper = new WCHelper();
    }
    $this->helper = $helper;
    $this->scheduler = ContainerWrapper::getInstance()->get(AutomaticEmailScheduler::class);
    $this->loggerFactory = LoggerFactory::getInstance();
    $this->repository = ContainerWrapper::getInstance()->get(AutomaticEmailsRepository::class);
    $this->subscribersRepository = ContainerWrapper::getInstance()->get(SubscribersRepository::class);
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
      $this->loggerFactory->getLogger(self::SLUG)->info(
        'no products found', ['search_query' => $productSearchQuery]
      );
      return;
    }

    $woocommerceProducts = array_map(function($product) {
      return [
        'id' => $product->ID,
        'name' => $product->post_title, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ];
    }, $woocommerceProducts);
    return $woocommerceProducts;
  }

  public function scheduleEmailWhenProductIsPurchased($orderId) {
    $orderDetails = $this->helper->wcGetOrder($orderId);
    if (!$orderDetails || !$orderDetails->get_billing_email()) {
      $this->loggerFactory->getLogger(self::SLUG)->info(
        'Email not scheduled because the order customer was not found',
        ['order_id' => $orderId]
      );
      return;
    }
    $customerEmail = $orderDetails->get_billing_email();

    $subscriber = $this->subscribersRepository->getWooCommerceSegmentSubscriber($customerEmail);

    if (!$subscriber instanceof SubscriberEntity) {
      $this->loggerFactory->getLogger(self::SLUG)->info(
        'Email not scheduled because the customer was not found as WooCommerce list subscriber',
        ['order_id' => $orderId, 'customer_email' => $customerEmail]
      );
      return;
    }

    $orderedProducts = array_map(function($product) {
      return ($product instanceof \WC_Order_Item_Product) ? $product->get_product_id() : null;
    }, $orderDetails->get_items());
    $orderedProducts = array_values(array_filter($orderedProducts));

    $schedulingCondition = function(NewsletterEntity $automaticEmail) use ($orderedProducts, $subscriber) {
      $matchedProducts = $this->getProductIdsMatchingNewsletterTrigger($automaticEmail, $orderedProducts);
      if (empty($matchedProducts)) {
        return false;
      }

      if ($this->repository->wasScheduledForSubscriber((int)$automaticEmail->getId(), (int)$subscriber->getId())) {
        $sentAllProducts = $this->repository->alreadySentAllProducts((int)$automaticEmail->getId(), (int)$subscriber->getId(), 'orderedProducts', $matchedProducts);
        if ($sentAllProducts) return false;
      }

      return true;
    };

    $this->loggerFactory->getLogger(self::SLUG)->info(
      'Email scheduled', [
        'order_id' => $orderId,
        'customer_email' => $customerEmail,
        'subscriber_id' => $subscriber->getId(),
      ]
    );
    return $this->scheduler->scheduleAutomaticEmail(
      WooCommerce::SLUG,
      self::SLUG,
      $schedulingCondition,
      $subscriber->getId(),
      ['orderedProducts' => $orderedProducts],
      [$this, 'metaModifier']
    );
  }

  public function metaModifier(NewsletterEntity $newsletter, array $meta): array {
    $orderedProductIds = $meta['orderedProducts'] ?? null;
    if (empty($orderedProductIds)) {
      return $meta;
    }
    $meta['orderedProducts'] = $this->getProductIdsMatchingNewsletterTrigger($newsletter, $orderedProductIds);

    return $meta;
  }

  private function getProductIdsMatchingNewsletterTrigger(NewsletterEntity $automaticEmail, array $orderedProductIds): array {
    $automaticEmailMetaValue = $automaticEmail->getOptionValue(NewsletterOptionFieldEntity::NAME_META);
    $optionValue = (is_string($automaticEmailMetaValue) && Helpers::isJson($automaticEmailMetaValue)) ? json_decode($automaticEmailMetaValue, true) : $automaticEmailMetaValue;

    if (!is_array($optionValue) || empty($optionValue['option'])) {
      return [];
    }
    $emailTriggeringProductIds = array_column($optionValue['option'], 'id');

    return array_intersect($emailTriggeringProductIds, $orderedProductIds);
  }
}
