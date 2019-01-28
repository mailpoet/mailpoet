<?php
namespace MailPoet\Subscription;

use MailPoet\Models\SubscriberIP;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class Throttling {
  static function throttle() {
    $wp = new WPFunctions;
    $subscription_limit_enabled = $wp->applyFilters('mailpoet_subscription_limit_enabled', true);

    $subscription_limit_window = $wp->applyFilters('mailpoet_subscription_limit_window', DAY_IN_SECONDS);
    $subscription_limit_base = $wp->applyFilters('mailpoet_subscription_limit_base', MINUTE_IN_SECONDS);

    $subscriber_ip = Helpers::getIP();
    $wp = new WPFunctions;

    if($subscription_limit_enabled && !$wp->isUserLoggedIn()) {
      if(!empty($subscriber_ip)) {
        $subscription_count = SubscriberIP::where('ip', $subscriber_ip)
          ->whereRaw(
            '(`created_at` >= NOW() - INTERVAL ? SECOND)',
            array((int)$subscription_limit_window)
          )->count();

        if($subscription_count > 0) {
          $timeout = $subscription_limit_base * pow(2, $subscription_count - 1);
          $existing_user = SubscriberIP::where('ip', $subscriber_ip)
            ->whereRaw(
              '(`created_at` >= NOW() - INTERVAL ? SECOND)',
              array((int)$timeout)
            )->findOne();

          if(!empty($existing_user)) {
            return $timeout;
          }
        }
      }
    }

    $ip = SubscriberIP::create();
    $ip->ip = $subscriber_ip;
    $ip->save();

    self::purge($subscription_limit_window);

    return false;
  }

  static function purge($interval) {
    return SubscriberIP::whereRaw(
      '(`created_at` < NOW() - INTERVAL ? SECOND)',
      array($interval)
    )->deleteMany();
  }
}
