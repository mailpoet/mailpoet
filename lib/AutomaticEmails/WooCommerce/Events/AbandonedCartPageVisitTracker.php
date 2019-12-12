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
  private $woo_commerce_helper;

  /** @var Cookies */
  private $cookies;

  function __construct(WPFunctions $wp, WooCommerceHelper $woo_commerce_helper, Cookies $cookies) {
    $this->wp = $wp;
    $this->woo_commerce_helper = $woo_commerce_helper;
    $this->cookies = $cookies;
  }

  function startTracking() {
    $this->saveLastVisitTimestamp();
  }

  function trackVisit(callable $onTrackCallback = null) {
    // track at most once per minute to avoid processing many calls at the same time, i.e. AJAX
    $last_visit_timestamp = $this->loadLastVisitTimestamp();
    $minute_ago_timestamp = Carbon::now()->getTimestamp() - 60;
    if ($last_visit_timestamp && $last_visit_timestamp < $minute_ago_timestamp && $this->isPageVisit()) {
      if ($onTrackCallback) {
        $onTrackCallback();
      }
      $this->saveLastVisitTimestamp();
    }
  }

  function stopTracking() {
    $this->removeLastVisitTimestamp();
  }

  private function saveLastVisitTimestamp() {
    $woo_commerce_session = $this->woo_commerce_helper->WC()->session;
    if (!$woo_commerce_session) {
      return;
    }
    $woo_commerce_session->set(self::LAST_VISIT_TIMESTAMP_OPTION_NAME, Carbon::now()->getTimestamp());
  }

  private function loadLastVisitTimestamp() {
    $woo_commerce_session = $this->woo_commerce_helper->WC()->session;
    if (!$woo_commerce_session) {
      return;
    }
    return $woo_commerce_session->get(self::LAST_VISIT_TIMESTAMP_OPTION_NAME);
  }

  private function removeLastVisitTimestamp() {
    $woo_commerce_session = $this->woo_commerce_helper->WC()->session;
    if (!$woo_commerce_session) {
      return;
    }
    $woo_commerce_session->__unset(self::LAST_VISIT_TIMESTAMP_OPTION_NAME);
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
