<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
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

  function __construct(WCHelper $helper = null) {
    if ($helper === null) {
      $helper = new WCHelper();
    }
    $this->helper = $helper;
    $this->scheduler = new AutomaticEmailScheduler();
  }

  function init() {
    WPFunctions::get()->addFilter('mailpoet_newsletter_shortcode', [
      $this,
      'handleOrderTotalShortcode',
    ], 10, 4);
    WPFunctions::get()->addFilter('mailpoet_newsletter_shortcode', [
      $this,
      'handleOrderDateShortcode',
    ], 10, 4);

    // We have to use a set of states because an order state after checkout differs for different payment methods
    $accepted_order_states = WPFunctions::get()->applyFilters('mailpoet_first_purchase_order_states', ['completed', 'processing']);

    foreach ($accepted_order_states as $state) {
      WPFunctions::get()->addAction('woocommerce_order_status_' . $state, [
        $this,
        'scheduleEmailWhenOrderIsPlaced',
      ], 10, 1);
    }
  }

  function getEventDetails() {
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

  function handleOrderDateShortcode($shortcode, $newsletter, $subscriber, $queue) {
    $result = $shortcode;
    if ($shortcode === self::ORDER_DATE_SHORTCODE) {
      $default_value = WPFunctions::get()->dateI18n(get_option('date_format'));
      if (!$queue) {
        $result = $default_value;
      } else {
        $meta = $queue->getMeta();
        $result = (!empty($meta['order_date'])) ? WPFunctions::get()->dateI18n(get_option('date_format'), $meta['order_date']) : $default_value;
      }
    }
    LoggerFactory::getLogger(self::SLUG)->addInfo(
      'handleOrderDateShortcode called', [
        'newsletter_id' => ($newsletter instanceof Newsletter) ? $newsletter->id : null,
        'subscriber_id' => ($subscriber instanceof Subscriber) ? $subscriber->id : null,
        'task_id' => ($queue instanceof SendingQueue) ? $queue->task_id : null,
        'shortcode' => $shortcode,
        'result' => $result,
      ]
    );
    return $result;
  }

  function handleOrderTotalShortcode($shortcode, $newsletter, $subscriber, $queue) {
    $result = $shortcode;
    if ($shortcode === self::ORDER_TOTAL_SHORTCODE) {
      $default_value = $this->helper->wcPrice(0);
      if (!$queue) {
        $result = $default_value;
      } else {
        $meta = $queue->getMeta();
        $result = (!empty($meta['order_amount'])) ? $this->helper->wcPrice($meta['order_amount']) : $default_value;
      }
    }
    LoggerFactory::getLogger(self::SLUG)->addInfo(
      'handleOrderTotalShortcode called', [
        'newsletter_id' => ($newsletter instanceof Newsletter) ? $newsletter->id : null,
        'subscriber_id' => ($subscriber instanceof Subscriber) ? $subscriber->id : null,
        'task_id' => ($queue instanceof SendingQueue) ? $queue->task_id : null,
        'shortcode' => $shortcode,
        'result' => $result,
      ]
    );
    return $result;
  }

  function scheduleEmailWhenOrderIsPlaced($order_id) {
    $order_details = $this->helper->wcGetOrder($order_id);
    if (!$order_details || !$order_details->get_billing_email()) {
      LoggerFactory::getLogger(self::SLUG)->addInfo(
        'Email not scheduled because the order customer was not found',
        ['order_id' => $order_id]
      );
      return;
    }

    $customer_email = $order_details->get_billing_email();
    $customer_order_count = $this->getCustomerOrderCount($customer_email);
    if ($customer_order_count > 1) {
      LoggerFactory::getLogger(self::SLUG)->addInfo(
        'Email not scheduled because this is not the first order of the customer', [
          'order_id' => $order_id,
          'customer_email' => $customer_email,
          'order_count' => $customer_order_count,
        ]
      );
      return;
    }

    $meta = [
      'order_amount' => $order_details->get_total(),
      'order_date' => $order_details->get_date_created()->getTimestamp(),
      'order_id' => $order_details->get_id(),
    ];

    $subscriber = Subscriber::getWooCommerceSegmentSubscriber($customer_email);

    if (!$subscriber instanceof Subscriber) {
      LoggerFactory::getLogger(self::SLUG)->addInfo(
        'Email not scheduled because the customer was not found as WooCommerce list subscriber',
        ['order_id' => $order_id, 'customer_email' => $customer_email]
      );
      return;
    }

    $check_email_was_not_scheduled = function (Newsletter $newsletter) use ($subscriber) {
      return !$newsletter->wasScheduledForSubscriber($subscriber->id);
    };

    LoggerFactory::getLogger(self::SLUG)->addInfo(
      'Email scheduled', [
        'order_id' => $order_id,
        'customer_email' => $customer_email,
        'subscriber_id' => $subscriber->id,
      ]
    );
    $this->scheduler->scheduleAutomaticEmail(WooCommerce::SLUG, self::SLUG, $check_email_was_not_scheduled, $subscriber->id, $meta);
  }

  function getCustomerOrderCount($customer_email) {
    // registered user
    $user = WPFunctions::get()->getUserBy('email', $customer_email);
    if ($user) {
      return $this->helper->wcGetCustomerOrderCount($user->ID);
    }
    // guest user
    return $this->getGuestCustomerOrderCountByEmail($customer_email);
  }

  private function getGuestCustomerOrderCountByEmail($customer_email) {
    global $wpdb;
    $count = $wpdb->get_var( "SELECT COUNT(*)
        FROM $wpdb->posts as posts
        LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
        WHERE   meta.meta_key = '_billing_email'
        AND     posts.post_type = 'shop_order'
        AND     meta_value = '" . WPFunctions::get()->escSql($customer_email) . "'
    " );
    return (int)$count;
  }
}
