<?php
namespace MailPoet\API\Endpoints;

use MailPoet\Subscription as UserSubscription;

if(!defined('ABSPATH')) exit;

class Subscription {
  const ENDPOINT = 'subscription';

  static function confirm($data) {
    $subscription = new UserSubscription\Pages('confirm', $data);
    $subscription->confirm();
  }

  static function manage($data) {
    $subscription = new UserSubscription\Pages('manage', $data);
  }

  static function unsubscribe($data) {
    $subscription = new UserSubscription\Pages('unsubscribe', $data);
    $subscription->unsubscribe();
  }
}