<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\AutomaticEmailsRepository;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

class FirstPurchase {
  const SLUG = 'woocommerce_first_purchase';
  const ORDER_TOTAL_SHORTCODE = '[woocommerce:order_total]';
  const ORDER_DATE_SHORTCODE = '[woocommerce:order_date]';
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
    WPFunctions::get()->addFilter('mailpoet_newsletter_shortcode', [
      $this,
      'handleOrderTotalShortcode',
    ], 10, 4);
    WPFunctions::get()->addFilter('mailpoet_newsletter_shortcode', [
      $this,
      'handleOrderDateShortcode',
    ], 10, 4);

    // We have to use a set of states because an order state after checkout differs for different payment methods
    $acceptedOrderStates = WPFunctions::get()->applyFilters('mailpoet_first_purchase_order_states', ['completed', 'processing']);

    foreach ($acceptedOrderStates as $state) {
      WPFunctions::get()->addAction('woocommerce_order_status_' . $state, [
        $this,
        'scheduleEmailWhenOrderIsPlaced',
      ], 10, 1);
    }
  }

  public function getEventDetails() {
    return [
      'slug' => self::SLUG,
      'title' => WPFunctions::get()->__('First Purchase', 'mailpoet'),
      'description' => WPFunctions::get()->__('Let MailPoet send an email to customers who make their first purchase.', 'mailpoet'),
      'listingScheduleDisplayText' => WPFunctions::get()->__('Email sent when a customer makes their first purchase.', 'mailpoet'),
      'badge' => [
        'text' => WPFunctions::get()->__('Must-have', 'mailpoet'),
        'style' => 'red',
      ],
      'shortcodes' => [
        [
          'text' => WPFunctions::get()->__('Order amount', 'mailpoet'),
          'shortcode' => self::ORDER_TOTAL_SHORTCODE,
        ],
        [
          'text' => WPFunctions::get()->__('Order date', 'mailpoet'),
          'shortcode' => self::ORDER_DATE_SHORTCODE,
        ],
      ],
    ];
  }

  public function handleOrderDateShortcode($shortcode, $newsletter, $subscriber, $queue) {
    $result = $shortcode;
    if ($shortcode === self::ORDER_DATE_SHORTCODE) {
      $defaultValue = WPFunctions::get()->dateI18n(get_option('date_format'));
      if (!$queue) {
        $result = $defaultValue;
      } else {
        $meta = $queue->getMeta();
        $result = (!empty($meta['order_date'])) ? WPFunctions::get()->dateI18n(get_option('date_format'), $meta['order_date']) : $defaultValue;
      }
    }
    $this->loggerFactory->getLogger(self::SLUG)->addInfo(
      'handleOrderDateShortcode called', [
        'newsletter_id' => ($newsletter instanceof Newsletter) ? $newsletter->id : null,
        'subscriber_id' => ($subscriber instanceof Subscriber) ? $subscriber->id : null,
        'task_id' => ($queue instanceof SendingQueue) ? $queue->taskId : null,
        'shortcode' => $shortcode,
        'result' => $result,
      ]
    );
    return $result;
  }

  public function handleOrderTotalShortcode($shortcode, $newsletter, $subscriber, $queue) {
    $result = $shortcode;
    if ($shortcode === self::ORDER_TOTAL_SHORTCODE) {
      $defaultValue = $this->helper->wcPrice(0);
      if (!$queue) {
        $result = $defaultValue;
      } else {
        $meta = $queue->getMeta();
        $result = (!empty($meta['order_amount'])) ? $this->helper->wcPrice($meta['order_amount']) : $defaultValue;
      }
    }
    $this->loggerFactory->getLogger(self::SLUG)->addInfo(
      'handleOrderTotalShortcode called', [
        'newsletter_id' => ($newsletter instanceof Newsletter) ? $newsletter->id : null,
        'subscriber_id' => ($subscriber instanceof Subscriber) ? $subscriber->id : null,
        'task_id' => ($queue instanceof SendingQueue) ? $queue->taskId : null,
        'shortcode' => $shortcode,
        'result' => $result,
      ]
    );
    return $result;
  }

  public function scheduleEmailWhenOrderIsPlaced($orderId) {
    $orderDetails = $this->helper->wcGetOrder($orderId);
    if (!$orderDetails || !$orderDetails->get_billing_email()) {
      $this->loggerFactory->getLogger(self::SLUG)->addInfo(
        'Email not scheduled because the order customer was not found',
        ['order_id' => $orderId]
      );
      return;
    }

    $customerEmail = $orderDetails->get_billing_email();
    $customerOrderCount = $this->getCustomerOrderCount($customerEmail);
    if ($customerOrderCount > 1) {
      $this->loggerFactory->getLogger(self::SLUG)->addInfo(
        'Email not scheduled because this is not the first order of the customer', [
          'order_id' => $orderId,
          'customer_email' => $customerEmail,
          'order_count' => $customerOrderCount,
        ]
      );
      return;
    }

    $meta = [
      'order_amount' => $orderDetails->get_total(),
      'order_date' => $orderDetails->get_date_created()->getTimestamp(),
      'order_id' => $orderDetails->get_id(),
    ];

    $subscriber = Subscriber::getWooCommerceSegmentSubscriber($customerEmail);

    if (!$subscriber instanceof Subscriber) {
      $this->loggerFactory->getLogger(self::SLUG)->addInfo(
        'Email not scheduled because the customer was not found as WooCommerce list subscriber',
        ['order_id' => $orderId, 'customer_email' => $customerEmail]
      );
      return;
    }

    $checkEmailWasNotScheduled = function (Newsletter $newsletter) use ($subscriber) {
      return !$this->repository->wasScheduledForSubscriber($newsletter->id, $subscriber->id);
    };

    $this->loggerFactory->getLogger(self::SLUG)->addInfo(
      'Email scheduled', [
        'order_id' => $orderId,
        'customer_email' => $customerEmail,
        'subscriber_id' => $subscriber->id,
      ]
    );
    $this->scheduler->scheduleAutomaticEmail(WooCommerce::SLUG, self::SLUG, $checkEmailWasNotScheduled, $subscriber->id, $meta);
  }

  public function getCustomerOrderCount($customerEmail) {
    // registered user
    $user = WPFunctions::get()->getUserBy('email', $customerEmail);
    if ($user) {
      return $this->helper->wcGetCustomerOrderCount($user->ID);
    }
    // guest user
    return $this->getGuestCustomerOrderCountByEmail($customerEmail);
  }

  private function getGuestCustomerOrderCountByEmail($customerEmail) {
    global $wpdb;
    $count = $wpdb->get_var( "SELECT COUNT(*)
        FROM $wpdb->posts as posts
        LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
        WHERE   meta.meta_key = '_billing_email'
        AND     posts.post_type = 'shop_order'
        AND     meta_value = '" . WPFunctions::get()->escSql($customerEmail) . "'
    " );
    return (int)$count;
  }
}
