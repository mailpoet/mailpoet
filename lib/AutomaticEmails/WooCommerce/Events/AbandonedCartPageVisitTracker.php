<?php

namespace MailPoet\AutomaticEmails\WooCommerce\Events;

use MailPoet\Statistics\Track\Clicks;
use MailPoet\Util\Cookies;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class AbandonedCartPageVisitTracker {
  const LAST_VISIT_TIMESTAMP_OPTION_NAME = 'mailpoet_last_visit_timestamp';

  /** @var WPFunctions */
  private $wp;

  /** @var WooCommerceHelper */
  private $wooCommerceHelper;

  /** @var Cookies */
  private $cookies;

  public function __construct(
      WPFunctions $wp,
      WooCommerceHelper $wooCommerceHelper,
      Cookies $cookies
  ) {
    $this->wp = $wp;
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->cookies = $cookies;
  }

  public function startTracking() {
    $this->saveLastVisitTimestamp();
  }

  public function trackVisit(callable $onTrackCallback = null) {
    // track at most once per minute to avoid processing many calls at the same time, i.e. AJAX
    $lastVisitTimestamp = $this->loadLastVisitTimestamp();
    $minuteAgoTimestamp = Carbon::now()->getTimestamp() - 60;
    if ($lastVisitTimestamp && $lastVisitTimestamp < $minuteAgoTimestamp && $this->isPageVisit()) {
      if ($onTrackCallback) {
        $onTrackCallback();
      }
      $this->saveLastVisitTimestamp();
    }
  }

  public function stopTracking() {
    $this->removeLastVisitTimestamp();
  }

  private function saveLastVisitTimestamp() {
    $wooCommerceSession = $this->wooCommerceHelper->WC()->session;
    if (!$wooCommerceSession) {
      return;
    }
    $wooCommerceSession->set(self::LAST_VISIT_TIMESTAMP_OPTION_NAME, Carbon::now()->getTimestamp());
  }

  private function loadLastVisitTimestamp() {
    $wooCommerceSession = $this->wooCommerceHelper->WC()->session;
    if (!$wooCommerceSession) {
      return;
    }
    return $wooCommerceSession->get(self::LAST_VISIT_TIMESTAMP_OPTION_NAME);
  }

  private function removeLastVisitTimestamp() {
    $wooCommerceSession = $this->wooCommerceHelper->WC()->session;
    if (!$wooCommerceSession) {
      return;
    }
    $wooCommerceSession->__unset(self::LAST_VISIT_TIMESTAMP_OPTION_NAME);
  }

  private function isPageVisit() {
    if ($this->wp->isAdmin()) {
      return false;
    }

    // when we have logged-in user or a tracking cookie we consider it a page visit
    // (we can't exclude AJAX since some shops may be AJAX-only)
    return $this->wp->wpGetCurrentUser()->exists() || $this->cookies->get(Clicks::ABANDONED_CART_COOKIE_NAME);
  }
}
