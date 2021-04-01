<?php

namespace MailPoet\Subscription;

use MailPoet\Models\SubscriberIP;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class Throttling {
  /** @var WPFunctions */
  private $wp;

  public function __construct(WPFunctions $wp) {
    $this->wp = $wp;
  }

  public function throttle() {
    $subscriptionLimitEnabled = $this->wp->applyFilters('mailpoet_subscription_limit_enabled', true);

    $subscriptionLimitWindow = $this->wp->applyFilters('mailpoet_subscription_limit_window', DAY_IN_SECONDS);
    $subscriptionLimitBase = $this->wp->applyFilters('mailpoet_subscription_limit_base', MINUTE_IN_SECONDS);

    $subscriberIp = Helpers::getIP();

    if ($subscriptionLimitEnabled && !$this->wp->isUserLoggedIn()) {
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

    $this->purge();

    return false;
  }

  public function purge() {
    $interval = $this->wp->applyFilters('mailpoet_subscription_purge_window', MONTH_IN_SECONDS);
    return SubscriberIP::whereRaw(
      '(`created_at` < NOW() - INTERVAL ? SECOND)',
      [$interval]
    )->deleteMany();
  }

  public function secondsToTimeString($seconds): string {
    $hrs = floor($seconds / 3600);
    $min = floor($seconds % 3600 / 60);
    $sec = $seconds % 3600 % 60;
    $result = [
      'hours' => $hrs ? sprintf(__('%d hours', 'mailpoet'), $hrs) : '',
      'minutes' => $min ? sprintf(__('%d minutes', 'mailpoet'), $min) : '',
      'seconds' => $sec ? sprintf(__('%d seconds', 'mailpoet'), $sec) : '',
    ];
    return join(' ', array_filter($result));
  }
}
