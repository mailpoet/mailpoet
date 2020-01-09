<?php

namespace MailPoet\Subscription;

use MailPoet\Models\SubscriberIP;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class Throttling {
  public static function throttle() {
    $wp = new WPFunctions;
    $subscriptionLimitEnabled = $wp->applyFilters('mailpoet_subscription_limit_enabled', true);

    $subscriptionLimitWindow = $wp->applyFilters('mailpoet_subscription_limit_window', DAY_IN_SECONDS);
    $subscriptionLimitBase = $wp->applyFilters('mailpoet_subscription_limit_base', MINUTE_IN_SECONDS);

    $subscriberIp = Helpers::getIP();

    if ($subscriptionLimitEnabled && !$wp->isUserLoggedIn()) {
      if (!empty($subscriberIp)) {
        $subscriptionCount = SubscriberIP::where('ip', $subscriberIp)
          ->whereRaw(
            '(`created_at` >= NOW() - INTERVAL ? SECOND)',
            [(int)$subscriptionLimitWindow]
          )->count();

        if ($subscriptionCount > 0) {
          $timeout = $subscriptionLimitBase * pow(2, $subscriptionCount - 1);
          $existingUser = SubscriberIP::where('ip', $subscriberIp)
            ->whereRaw(
              '(`created_at` >= NOW() - INTERVAL ? SECOND)',
              [(int)$timeout]
            )->findOne();

          if (!empty($existingUser)) {
            return $timeout;
          }
        }
      }
    }

    $ip = SubscriberIP::create();
    $ip->ip = $subscriberIp;
    $ip->save();

    self::purge();

    return false;
  }

  public static function purge() {
    $wp = new WPFunctions;
    $interval = $wp->applyFilters('mailpoet_subscription_purge_window', MONTH_IN_SECONDS);
    return SubscriberIP::whereRaw(
      '(`created_at` < NOW() - INTERVAL ? SECOND)',
      [$interval]
    )->deleteMany();
  }

  public static function secondsToTimeString($seconds) {
    $wp = new WPFunctions;
    $hrs = floor($seconds / 3600);
    $min = floor($seconds % 3600 / 60);
    $sec = $seconds % 3600 % 60;
    $result = [
      'hours' => $hrs ? sprintf($wp->__('%d hours', 'mailpoet'), $hrs) : '',
      'minutes' => $min ? sprintf($wp->__('%d minutes', 'mailpoet'), $min) : '',
      'seconds' => $sec ? sprintf($wp->__('%d seconds', 'mailpoet'), $sec) : '',
    ];
    return join(' ', array_filter($result));
  }
}
