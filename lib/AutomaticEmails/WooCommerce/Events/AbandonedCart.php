<?php

namespace MailPoet\Premium\AutomaticEmails\WooCommerce\Events;

use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\Premium\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Statistics\Track\Clicks;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class AbandonedCart {
  const SLUG = 'woocommerce_abandoned_shopping_cart';
  const LAST_VISIT_TIMESTAMP_OPTION_NAME = 'mailpoet_last_visit_timestamp';

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $woo_commerce_helper;

  /** @var Cookies */
  private $cookies;

  /** @var AbandonedCartPageVisitTracker */
  private $page_visit_tracker;

  /** @var AutomaticEmailScheduler */
  private $scheduler;

  function __construct() {
    $this->wp = WPFunctions::get();
    $this->woo_commerce_helper = new WooCommerceHelper();
    $this->cookies = new Cookies();
    $this->page_visit_tracker = new AbandonedCartPageVisitTracker($this->wp, $this->woo_commerce_helper, $this->cookies);
    $this->scheduler = new AutomaticEmailScheduler();
  }

  function getEventDetails() {
    return [
      'slug' => self::SLUG,
      'title' => WPFunctions::get()->_x('Abandoned Shopping Cart', 'This is the name of a type of automatic email for ecommerce. Those emails are sent automatically when a customer adds product to his shopping cart but never complete the checkout process.', 'mailpoet-premium'),
      'description' => WPFunctions::get()->__('Send an email to logged-in visitors who have items in their shopping carts but left your website without checking out. Can convert up to 5% of abandoned carts.', 'mailpoet-premium'),
      'listingScheduleDisplayText' => WPFunctions::get()->_x('Email sent when a customer abandons his cart.', 'Description of Abandoned Shopping Cart email', 'mailpoet-premium'),
      'badge' => [
        'text' => WPFunctions::get()->__('Must-have', 'mailpoet-premium'),
        'style' => 'red',
      ],
      'timeDelayValues' => [
        'minutes' => [
          'text' => _x('30 minutes after last page loaded', 'This is a trigger setting. It means that we will send an automatic email to a visitor 30 minutes after this visitor had left the website.', 'mailpoet-premium'),
          'displayAfterTimeNumberField' => false,
        ],
        'hours' => [
          'text' => __('hour(s) later', 'mailpoet-premium'),
          'displayAfterTimeNumberField' => true,
        ],
        'days' => [
          'text' => __('day(s) later', 'mailpoet-premium'),
          'displayAfterTimeNumberField' => true,
        ],
        'weeks' => [
          'text' => __('week(s) later', 'mailpoet-premium'),
          'displayAfterTimeNumberField' => true,
        ],
      ],
      'defaultAfterTimeType' => 'minutes',
      'schedulingReadMoreLink' => [
        'link' => 'https://www.mailpoet.com/blog/abandoned-cart-woocommerce',
        'text' => __('We recommend setting up 3 abandoned cart emails. Here’s why.', 'mailpoet-premium'),
      ],
    ];
  }

  function init() {
    if (!$this->woo_commerce_helper->isWooCommerceActive()) {
      return;
    }

    // item added to cart (not fired on quantity changes)
    $this->wp->addAction(
      'woocommerce_add_to_cart',
      [$this, 'handleCartChange'],
      10
    );

    // item removed from cart (not fired on quantity changes, not even change to zero)
    $this->wp->addAction(
      'woocommerce_cart_item_removed',
      [$this, 'handleCartChange'],
      10
    );

    // item quantity updated (not fired when quantity updated to zero)
    $this->wp->addAction(
      'woocommerce_after_cart_item_quantity_update',
      [$this, 'handleCartChange'],
      10
    );

    // item quantity set to zero (it removes the item but does not fire remove event)
    $this->wp->addAction(
      'woocommerce_before_cart_item_quantity_zero',
      [$this, 'handleCartChange'],
      10
    );

    // cart emptied (not called when all items removed)
    $this->wp->addAction(
      'woocommerce_cart_emptied',
      [$this, 'handleCartChange'],
      10
    );

    // undo removal of item from cart or cart emptying (does not fire any other cart change hook)
    $this->wp->addAction(
      'woocommerce_cart_item_restored',
      [$this, 'handleCartChange'],
      10
    );

    $this->wp->addAction(
      'wp',
      [$this, 'trackPageVisit'],
      10
    );
  }

  function handleCartChange() {
    $cart = $this->woo_commerce_helper->WC()->cart;
    if ($cart && !$cart->is_empty()) {
      $this->scheduleAbandonedCartEmail();
    } else {
      $this->cancelAbandonedCartEmail();
      $this->page_visit_tracker->stopTracking();
    }
  }

  function trackPageVisit() {
    // on page visit reschedule all currently scheduled (not yet sent) emails for given subscriber
    // (it tracks at most once per minute to avoid processing many calls at the same time, i.e. AJAX)
    $this->page_visit_tracker->trackVisit(function () {
      $this->rescheduleAbandonedCartEmail();
    });
  }

  private function scheduleAbandonedCartEmail() {
    $subscriber = $this->getSubscriber();
    if (!$subscriber || $subscriber->status !== Subscriber::STATUS_SUBSCRIBED) {
      return;
    }

    $this->scheduler->scheduleOrRescheduleAutomaticEmail(WooCommerceEmail::SLUG, self::SLUG, $subscriber->id);

    // start tracking page visits to detect inactivity
    $this->page_visit_tracker->startTracking();
  }

  private function rescheduleAbandonedCartEmail() {
    $subscriber = $this->getSubscriber();
    if (!$subscriber) {
      return;
    }
    $this->scheduler->rescheduleAutomaticEmail(WooCommerceEmail::SLUG, self::SLUG, $subscriber->id);
  }

  private function cancelAbandonedCartEmail() {
    $subscriber = $this->getSubscriber();
    if (!$subscriber) {
      return;
    }
    $this->scheduler->cancelAutomaticEmail(WooCommerceEmail::SLUG, self::SLUG, $subscriber->id);
  }

  private function getSubscriber() {
    $wp_user = $this->wp->wpGetCurrentUser();
    if ($wp_user->exists()) {
      return Subscriber::where('wp_user_id', $wp_user->ID)->findOne() ?: null;
    }

    // if user not logged in, try to find subscriber by cookie
    $cookie_data = $this->cookies->get(Clicks::ABANDONED_CART_COOKIE_NAME);
    if ($cookie_data && isset($cookie_data['subscriber_id'])) {
      return Subscriber::findOne($cookie_data['subscriber_id']) ?: null;
    }
    return null;
  }
}
