<?php
namespace MailPoet\Config;
use MailPoet\Models\Setting;

class Hooks {
  function __construct() {
  }

  function init() {
    $this->setupWPUsers();
    $this->setupImageSize();
    $this->setupListing();
    $this->setupSubscriptionEvents();
    $this->setupPostNotifications();
  }

  function setupSubscriptionEvents() {
    $subscribe = Setting::getValue('subscribe', array());
    // Subscribe in comments
    if(
      isset($subscribe['on_comment']['enabled'])
      &&
      (bool)$subscribe['on_comment']['enabled']
    ) {
      if(is_user_logged_in()) {
        add_action(
          'comment_form_field_comment',
          '\MailPoet\Subscription\Comment::extendLoggedInForm'
        );
      } else {
        add_action(
          'comment_form_after_fields',
          '\MailPoet\Subscription\Comment::extendLoggedOutForm'
        );
      }

      add_action(
        'comment_post',
        '\MailPoet\Subscription\Comment::onSubmit',
        60,
        2
      );

      add_action(
        'wp_set_comment_status',
        '\MailPoet\Subscription\Comment::onStatusUpdate',
        60,
        2
      );
    }

    // Subscribe in registration form
    if(
      isset($subscribe['on_register']['enabled'])
      &&
      (bool)$subscribe['on_register']['enabled']
    ) {
      if(is_multisite()) {
        add_action(
          'signup_extra_fields',
          '\MailPoet\Subscription\Registration::extendForm'
        );
        add_action(
          'wpmu_validate_user_signup',
          '\MailPoet\Subscription\Registration::onMultiSiteRegister',
          60,
          1
        );
      } else {
        add_action(
          'register_form',
          '\MailPoet\Subscription\Registration::extendForm'
        );
        add_action(
          'register_post',
          '\MailPoet\Subscription\Registration::onRegister',
          60,
          3
        );
      }
    }

    // Manage subscription
    add_action(
      'admin_post_mailpoet_subscription_update',
      '\MailPoet\Subscription\Manage::onSave'
    );
    add_action(
      'admin_post_nopriv_mailpoet_subscription_update',
      '\MailPoet\Subscription\Manage::onSave'
    );

    // Subscription form
    add_action(
      'admin_post_mailpoet_subscription_form',
      '\MailPoet\Subscription\Form::onSubmit'
    );
    add_action(
      'admin_post_nopriv_mailpoet_subscription_form',
      '\MailPoet\Subscription\Form::onSubmit'
    );
  }

  function setupWPUsers() {
    // WP Users synchronization
    add_action(
      'user_register',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    add_action(
      'added_existing_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    add_action(
      'profile_update',
      '\MailPoet\Segments\WP::synchronizeUser',
      1, 2
    );
    add_action(
      'delete_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    // multisite
    add_action(
      'deleted_user',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
    add_action(
      'remove_user_from_blog',
      '\MailPoet\Segments\WP::synchronizeUser',
      1
    );
  }

  function setupImageSize() {
    add_filter(
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
    add_filter(
      'set-screen-option',
      array($this, 'setScreenOption'),
      10, 3
    );
  }

  function setScreenOption($status, $option, $value) {
    if(preg_match('/^mailpoet_(.*)_per_page$/', $option)) {
      return $value;
    } else {
      return $status;
    }
  }

  function setupPostNotifications() {
    $post_types = get_post_types();
    foreach($post_types as $post_type) {
      add_filter(
        'publish_' . $post_type,
        '\MailPoet\Newsletter\Scheduler\Scheduler::schedulePostNotification',
        10, 1
      );
    }
  }
}