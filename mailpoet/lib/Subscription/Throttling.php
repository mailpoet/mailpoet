<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberIPEntity;
use MailPoet\Subscribers\SubscriberIPsRepository;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Throttling {
  /** @var SubscriberIPsRepository */
  private $subscriberIPsRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    SubscriberIPsRepository $subscriberIPsRepository,
    WPFunctions $wp
  ) {
    $this->wp = $wp;
    $this->subscriberIPsRepository = $subscriberIPsRepository;
  }

  /**
   * Returns the seconds left, rounded up to whole minutes, before the visitor's IP may subscribe again,
   * or 0 when it may subscribe now. Unlike throttle(), it never records a subscription.
   */
  public function getWaitTime(): int {
    $subscriptionLimitEnabled = $this->wp->applyFilters('mailpoet_subscription_limit_enabled', true);
    if (!$subscriptionLimitEnabled || $this->isUserExemptFromThrottling()) {
      return 0;
    }

    $subscriberIp = Helpers::getIP();
    if (empty($subscriberIp)) {
      return 0;
    }

    $window = $this->wp->applyFilters('mailpoet_subscription_limit_window', DAY_IN_SECONDS);
    $subscriptionLimitWindow = is_numeric($window) ? (int)$window : DAY_IN_SECONDS;
    $base = $this->wp->applyFilters('mailpoet_subscription_limit_base', MINUTE_IN_SECONDS);
    $subscriptionLimitBase = is_numeric($base) ? (int)$base : MINUTE_IN_SECONDS;

    $subscriptionCount = $this->subscriberIPsRepository->getCountByIPAndCreatedAtAfterTimeInSeconds($subscriberIp, $subscriptionLimitWindow);
    if ($subscriptionCount === 0) {
      return 0;
    }
    $timeout = $subscriptionLimitBase * pow(2, $subscriptionCount - 1);
    // Cap timeout and avoid float numbers
    $timeout = (int)min($timeout, $subscriptionLimitWindow);

    $latestSubscription = $this->subscriberIPsRepository->findOneByIPAndCreatedAtAfterTimeInSeconds($subscriberIp, $timeout);
    if (!$latestSubscription) {
      return 0;
    }
    $secondsLeft = $latestSubscription->getCreatedAt()->getTimestamp() + $timeout - Carbon::now()->getTimestamp();
    if ($secondsLeft <= 0) {
      return 0;
    }
    return (int)ceil($secondsLeft / MINUTE_IN_SECONDS) * MINUTE_IN_SECONDS;
  }

  /**
   * Returns the seconds to wait when the visitor's IP must wait before subscribing again.
   * Otherwise records the subscription and returns false.
   *
   * @return int|false
   */
  public function throttle() {
    $waitTime = $this->getWaitTime();
    if ($waitTime > 0) {
      return $waitTime;
    }

    $subscriberIp = Helpers::getIP();
    if ($subscriberIp !== null) {
      $ip = new SubscriberIPEntity($subscriberIp);
      $existingIp = $this->subscriberIPsRepository->findOneBy(['ip' => $ip->getIP(), 'createdAt' => $ip->getCreatedAt()]);
      if (!$existingIp) {
        $this->subscriberIPsRepository->persist($ip);
        $this->subscriberIPsRepository->flush();
      }
    }

    $this->purge();

    return false;
  }

  public function purge(): void {
    $interval = $this->wp->applyFilters('mailpoet_subscription_purge_window', MONTH_IN_SECONDS);
    $purgeWindow = is_numeric($interval) ? (int)$interval : MONTH_IN_SECONDS;
    $this->subscriberIPsRepository->deleteCreatedAtBeforeTimeInSeconds($purgeWindow);
  }

  public function secondsToTimeString($seconds): string {
    $hrs = floor($seconds / 3600);
    $min = floor($seconds % 3600 / 60);
    $sec = $seconds % 3600 % 60;
    $result = [
      // translators: %s is the number of hours
      'hours' => $hrs ? sprintf(__('%d hours', 'mailpoet'), $hrs) : '',
      // translators: %s is the number of minutes
      'minutes' => $min ? sprintf(__('%d minutes', 'mailpoet'), $min) : '',
      // translators: %s is the number of seconds
      'seconds' => $sec ? sprintf(__('%d seconds', 'mailpoet'), $sec) : '',
    ];
    return join(' ', array_filter($result));
  }

  private function isUserExemptFromThrottling(): bool {
    if (!$this->wp->isUserLoggedIn()) {
      return false;
    }
    $user = $this->wp->wpGetCurrentUser();
    $roles = $this->wp->applyFilters('mailpoet_subscription_throttling_exclude_roles', ['administrator', 'editor']);
    if (!is_array($roles)) {
      $roles = ['administrator', 'editor'];
    }
    $roles = array_values(array_filter($roles, 'is_string'));
    $userRoles = array_values(array_filter((array)$user->roles, 'is_string'));
    return !empty(array_intersect($roles, $userRoles));
  }
}
