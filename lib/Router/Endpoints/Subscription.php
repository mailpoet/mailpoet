<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;
use MailPoet\Subscription as UserSubscription;

if(!defined('ABSPATH')) exit;

class Subscription {
  const ENDPOINT = 'subscription';
  const ACTION_CONFIRM = 'confirm';
  const ACTION_MANAGE = 'manage';
  const ACTION_UNSUBSCRIBE = 'unsubscribe';
  public $allowed_actions = array(
    self::ACTION_CONFIRM,
    self::ACTION_MANAGE,
    self::ACTION_UNSUBSCRIBE
  );
  public $data;
  public $permissions = array(
    'global' => AccessControl::NO_ACCESS_RESTRICTION
  );

  function __construct($data) {
    $this->data = $data;
  }

  function confirm() {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_CONFIRM);
    $subscription->confirm();
  }

  function manage() {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_MANAGE);
  }

  function unsubscribe() {
    $subscription = $this->initSubscriptionPage(UserSubscription\Pages::ACTION_UNSUBSCRIBE);
    $subscription->unsubscribe();
  }

  private function initSubscriptionPage($action) {
    return new UserSubscription\Pages($action, $this->data, true, true);
  }
}