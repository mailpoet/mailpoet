<?php
namespace MailPoet\Router\Endpoints;

use MailPoet\Subscription as UserSubscription;

if(!defined('ABSPATH')) exit;

class Subscription {
  const ENDPOINT = 'subscription';

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