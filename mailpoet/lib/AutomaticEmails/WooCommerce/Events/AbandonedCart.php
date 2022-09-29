<?php declare(strict_types = 1);

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Scheduler\AutomaticEmailScheduler;
use MailPoet\Statistics\Track\SubscriberActivityTracker;
use MailPoet\Statistics\Track\SubscriberCookie;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class AbandonedCart {
  const SLUG = 'woocommerce_abandoned_shopping_cart';
  const TASK_META_NAME = 'cart_product_ids';

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  /** @var SubscriberCookie */
  private $subscriberCookie;

  /** @var AutomaticEmailScheduler */
  private $scheduler;

  /** @var SubscriberActivityTracker */
  private $subscriberActivityTracker;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    WPFunctions $wp,
    WooCommerceHelper $wooCommerceHelper,
    SubscriberCookie $subscriberCookie,
    SubscriberActivityTracker $subscriberActivityTracker,
    AutomaticEmailScheduler $scheduler,
    SubscribersRepository $subscribersRepository
  ) {
    $this->wp = $wp;
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->subscriberCookie = $subscriberCookie;
    $this->subscriberActivityTracker = $subscriberActivityTracker;
    $this->scheduler = $scheduler;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function getEventDetails() {
    return [
      'slug' => self::SLUG,
      'title' => _x('Abandoned Shopping Cart', 'This is the name of a type of automatic email for ecommerce. Those emails are sent automatically when a customer adds product to his shopping cart but never complete the checkout process.', 'mailpoet'),
      'description' => __('Send an email to logged-in visitors who have items in their shopping carts but left your website without checking out. Can convert up to 5% of abandoned carts.', 'mailpoet'),
      'listingScheduleDisplayText' => _x('Email sent when a customer abandons his cart.', 'Description of Abandoned Shopping Cart email', 'mailpoet'),
      'badge' => [
        'text' => __('Must-have', 'mailpoet'),
        'style' => 'red',
      ],
      'timeDelayValues' => [
        'minutes' => [
          'text' => _x('30 minutes after last page loaded', 'This is a trigger setting. It means that we will send an automatic email to a visitor 30 minutes after this visitor had left the website.', 'mailpoet'),
          'displayAfterTimeNumberField' => false,
        ],
        'hours' => [
          'text' => __('hour(s) later', 'mailpoet'),
          'displayAfterTimeNumberField' => true,
        ],
        'days' => [
          'text' => __('day(s) later', 'mailpoet'),
          'displayAfterTimeNumberField' => true,
        ],
        'weeks' => [
          'text' => __('week(s) later', 'mailpoet'),
          'displayAfterTimeNumberField' => true,
        ],
      ],
      'defaultAfterTimeType' => 'minutes',
      'schedulingReadMoreLink' => [
        'link' => 'https://www.mailpoet.com/blog/abandoned-cart-woocommerce',
        'text' => __('We recommend setting up 3 abandoned cart emails. Here’s why.', 'mailpoet'),
      ],
    ];
  }

  public function init() {
    if (!$this->wooCommerceHelper->isWooCommerceActive()) {
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

    $this->subscriberActivityTracker->registerCallback(
      'mailpoet_abandoned_cart',
      [$this, 'handleSubscriberActivity']
    );
  }

  public function handleCartChange() {
    $cart = $this->wooCommerceHelper->WC()->cart;
    if (current_action() !== 'woocommerce_cart_emptied' && $cart && !$cart->is_empty()) {
      $this->scheduleAbandonedCartEmail($this->getCartProductIds($cart));
    } else {
      $this->cancelAbandonedCartEmail();
    }
  }

  public function handleSubscriberActivity(SubscriberEntity $subscriber) {
    // on subscriber activity on site reschedule all currently scheduled (not yet sent) emails for given subscriber
    // (it tracks at most once per minute to avoid processing many calls at the same time, i.e. AJAX)
    $this->rescheduleAbandonedCartEmail($subscriber);
  }

  private function getCartProductIds($cart) {
    $cartItems = $cart->get_cart() ?: [];
    return array_column($cartItems, 'product_id');
  }

  private function scheduleAbandonedCartEmail(array $cartProductIds = []) {
    $subscriber = $this->getSubscriber();
    if (!$subscriber || $subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      return;
    }

    $meta = [self::TASK_META_NAME => $cartProductIds];
    $this->scheduler->scheduleOrRescheduleAutomaticEmail(WooCommerceEmail::SLUG, self::SLUG, $subscriber, $meta);
  }

  private function rescheduleAbandonedCartEmail(SubscriberEntity $subscriber) {
    $this->scheduler->rescheduleAutomaticEmail(WooCommerceEmail::SLUG, self::SLUG, $subscriber);
  }

  private function cancelAbandonedCartEmail() {
    $subscriber = $this->getSubscriber();
    if (!$subscriber) {
      return;
    }
    $this->scheduler->cancelAutomaticEmail(WooCommerceEmail::SLUG, self::SLUG, $subscriber);
  }

  private function getSubscriber(): ?SubscriberEntity {
    $wpUser = $this->wp->wpGetCurrentUser();
    if ($wpUser->exists()) {
      return $this->subscribersRepository->findOneBy(['wpUserId' => $wpUser->ID]);
    }

    // if user not logged in, try to find subscriber by cookie
    $subscriberId = $this->subscriberCookie->getSubscriberId();
    if ($subscriberId) {
      return $this->subscribersRepository->findOneById($subscriberId);
    }
    return null;
  }
}
