<?php

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\Subscription\Comment;
use MailPoet\Subscription\Form;
use MailPoet\Subscription\Manage;
use MailPoet\Subscription\Registration;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Subscription as WooCommerceSubscription;
use MailPoet\WP\Functions as WPFunctions;

class Hooks {

  /** @var Form */
  private $subscription_form;

  /** @var Comment */
  private $subscription_comment;

  /** @var Manage */
  private $subscription_manage;

  /** @var Registration */
  private $subscription_registration;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceSubscription */
  private $woocommerce_subscription;

  /** @var WooCommerceSegment */
  private $woocommerce_segment;

  /** @var WooCommercePurchases */
  private $woocommerce_purchases;

  function __construct(
    Form $subscription_form,
    Comment $subscription_comment,
    Manage $subscription_manage,
    Registration $subscription_registration,
    SettingsController $settings,
    WPFunctions $wp,
    WooCommerceSubscription $woocommerce_subscription,
    WooCommerceSegment $woocommerce_segment,
    WooCommercePurchases $woocommerce_purchases
  ) {
    $this->subscription_form = $subscription_form;
    $this->subscription_comment = $subscription_comment;
    $this->subscription_manage = $subscription_manage;
    $this->subscription_registration = $subscription_registration;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->woocommerce_subscription = $woocommerce_subscription;
    $this->woocommerce_segment = $woocommerce_segment;
    $this->woocommerce_purchases = $woocommerce_purchases;
  }

  function init() {
    $this->setupWPUsers();
    $this->setupWooCommerceUsers();
    $this->setupWooCommercePurchases();
    $this->setupImageSize();
    $this->setupListing();
    $this->setupSubscriptionEvents();
    $this->setupWooCommerceSubscriptionEvents();
    $this->setupPostNotifications();
  }

  function setupSubscriptionEvents() {

    $subscribe = $this->settings->get('subscribe', []);
    // Subscribe in comments
    if (
      isset($subscribe['on_comment']['enabled'])
      &&
      (bool)$subscribe['on_comment']['enabled']
    ) {
      if ($this->wp->isUserLoggedIn()) {
        $this->wp->addAction(
          'comment_form_field_comment',
          [$this->subscription_comment, 'extendLoggedInForm']
        );
      } else {
        $this->wp->addAction(
          'comment_form_after_fields',
          [$this->subscription_comment, 'extendLoggedOutForm']
        );
      }

      $this->wp->addAction(
        'comment_post',
        [$this->subscription_comment, 'onSubmit'],
        60,
        2
      );

      $this->wp->addAction(
        'wp_set_comment_status',
        [$this->subscription_comment, 'onStatusUpdate'],
        60,
        2
      );
    }

    // Subscribe in registration form
    if (
      isset($subscribe['on_register']['enabled'])
      &&
      (bool)$subscribe['on_register']['enabled']
    ) {
      if (is_multisite()) {
        $this->wp->addAction(
          'signup_extra_fields',
          [$this->subscription_registration, 'extendForm']
        );
        $this->wp->addAction(
          'wpmu_validate_user_signup',
          [$this->subscription_registration, 'onMultiSiteRegister'],
          60,
          1
        );
      } else {
        $this->wp->addAction(
          'register_form',
          [$this->subscription_registration, 'extendForm']
        );
        // we need to process new users while they are registered.
        // We used `register_post` before but that is too soon
        //   because if registration fails during `registration_errors` we will keep the user as subscriber.
        // So we are hooking to `registration_error` with a low priority.
        $this->wp->addFilter(
          'registration_errors',
          [$this->subscription_registration, 'onRegister'],
          60,
          3
        );
      }
    }

    // Manage subscription
    $this->wp->addAction(
      'admin_post_mailpoet_subscription_update',
      [$this->subscription_manage, 'onSave']
    );
    $this->wp->addAction(
      'admin_post_nopriv_mailpoet_subscription_update',
      [$this->subscription_manage, 'onSave']
    );

    // Subscription form
    $this->wp->addAction(
      'admin_post_mailpoet_subscription_form',
      [$this->subscription_form, 'onSubmit']
    );
    $this->wp->addAction(
      'admin_post_nopriv_mailpoet_subscription_form',
      [$this->subscription_form, 'onSubmit']
    );
  }

  function setupWooCommerceSubscriptionEvents() {
    $woocommerce = $this->settings->get('woocommerce', []);
    // WooCommerce: subscribe on checkout
    if (!empty($woocommerce['optin_on_checkout']['enabled'])) {
      $this->wp->addAction(
        'woocommerce_checkout_before_terms_and_conditions',
        [$this->woocommerce_subscription, 'extendWooCommerceCheckoutForm']
      );
    }

    $this->wp->addAction(
      'woocommerce_checkout_update_order_meta',
      [$this->woocommerce_subscription, 'subscribeOnCheckout'],
      10, // this should execute after the WC sync call on the same hook
      2
    );
  }

  function setupWPUsers() {
    // WP Users synchronization
    $this->wp->addAction(
      'user_register',
      '\MailPoet\Segments\WP::synchronizeUser',
      6
    );
    $this->wp->addAction(
      'added_existing_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      6
    );
    $this->wp->addAction(
      'profile_update',
      '\MailPoet\Segments\WP::synchronizeUser',
      6, 2
    );
    $this->wp->addAction(
      'delete_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    // multisite
    $this->wp->addAction(
      'deleted_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    $this->wp->addAction(
      'remove_user_from_blog',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
  }

  function setupWooCommerceUsers() {
    // WooCommerce Customers synchronization
    $this->wp->addAction(
      'woocommerce_new_customer',
      [$this->woocommerce_segment, 'synchronizeRegisteredCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_update_customer',
      [$this->woocommerce_segment, 'synchronizeRegisteredCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_delete_customer',
      [$this->woocommerce_segment, 'synchronizeRegisteredCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_checkout_update_order_meta',
      [$this->woocommerce_segment, 'synchronizeGuestCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_process_shop_order_meta',
      [$this->woocommerce_segment, 'synchronizeGuestCustomer'],
      7
    );
  }

  function setupWooCommercePurchases() {
    // use both 'processing' and 'completed' states since payment hook and 'processing' status
    // may be skipped with some payment methods (cheque) or when state transitioned manually
    $accepted_order_states = WPFunctions::get()->applyFilters(
      'mailpoet_purchase_order_states',
      ['processing', 'completed']
    );

    foreach ($accepted_order_states as $status) {
      WPFunctions::get()->addAction(
        'woocommerce_order_status_' . $status,
        [$this->woocommerce_purchases, 'trackPurchase'],
        10,
        1
      );
    }
  }

  function setupImageSize() {
    $this->wp->addFilter(
      'image_size_names_choose',
      [$this, 'appendImageSize'],
      10, 1
    );
  }

  function appendImageSize($sizes) {
    return array_merge($sizes, [
      'mailpoet_newsletter_max' => WPFunctions::get()->__('MailPoet Newsletter', 'mailpoet'),
    ]);
  }

  function setupListing() {
    $this->wp->addFilter(
      'set-screen-option',
      [$this, 'setScreenOption'],
      10, 3
    );
  }

  function setScreenOption($status, $option, $value) {
    if (preg_match('/^mailpoet_(.*)_per_page$/', $option)) {
      return $value;
    } else {
      return $status;
    }
  }

  function setupPostNotifications() {
    $this->wp->addAction(
      'transition_post_status',
      '\MailPoet\Newsletter\Scheduler\Scheduler::transitionHook',
      10, 3
    );
  }
}
