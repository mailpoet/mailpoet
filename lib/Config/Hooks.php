<?php

namespace MailPoet\Config;

use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Form;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WP\Functions as WPFunctions;

class Hooks {

  /** @var Form */
  private $subscription_form;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceSegment */
  private $woocommerce_segment;

  function __construct(
    Form $subscription_form,
    SettingsController $settings,
    WPFunctions $wp,
    WooCommerceSegment $woocommerce_segment
  ) {
    $this->subscription_form = $subscription_form;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->woocommerce_segment = $woocommerce_segment;
  }

  function init() {
    $this->setupWPUsers();
    $this->setupWooCommerceUsers();
    $this->setupImageSize();
    $this->setupListing();
    $this->setupSubscriptionEvents();
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
          '\MailPoet\Subscription\Comment::extendLoggedInForm'
        );
      } else {
        $this->wp->addAction(
          'comment_form_after_fields',
          '\MailPoet\Subscription\Comment::extendLoggedOutForm'
        );
      }

      $this->wp->addAction(
        'comment_post',
        '\MailPoet\Subscription\Comment::onSubmit',
        60,
        2
      );

      $this->wp->addAction(
        'wp_set_comment_status',
        '\MailPoet\Subscription\Comment::onStatusUpdate',
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
          '\MailPoet\Subscription\Registration::extendForm'
        );
        $this->wp->addAction(
          'wpmu_validate_user_signup',
          '\MailPoet\Subscription\Registration::onMultiSiteRegister',
          60,
          1
        );
      } else {
        $this->wp->addAction(
          'register_form',
          '\MailPoet\Subscription\Registration::extendForm'
        );
        $this->wp->addAction(
          'register_post',
          '\MailPoet\Subscription\Registration::onRegister',
          60,
          3
        );
      }
    }

    // Manage subscription
    $this->wp->addAction(
      'admin_post_mailpoet_subscription_update',
      '\MailPoet\Subscription\Manage::onSave'
    );
    $this->wp->addAction(
      'admin_post_nopriv_mailpoet_subscription_update',
      '\MailPoet\Subscription\Manage::onSave'
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

  function setupImageSize() {
    $this->wp->addFilter(
      'image_size_names_choose',
      array($this, 'appendImageSize'),
      10, 1
    );
  }

  function appendImageSize($sizes) {
    return array_merge($sizes, array(
      'mailpoet_newsletter_max' => __('MailPoet Newsletter', 'mailpoet')
    ));
  }

  function setupListing() {
    $this->wp->addFilter(
      'set-screen-option',
      array($this, 'setScreenOption'),
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
