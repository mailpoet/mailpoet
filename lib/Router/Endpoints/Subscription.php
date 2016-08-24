<?php
namespace MailPoet\Router\Endpoints;

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

  function confirm($data) {
    $subscription = new UserSubscription\Pages('confirm', $data);
    $subscription->confirm();
  }

  function manage($data) {
    $subscription = new UserSubscription\Pages('manage', $data);
  }

  function unsubscribe($data) {
    $subscription = new UserSubscription\Pages('unsubscribe', $data);
    $subscription->unsubscribe();
  }
}