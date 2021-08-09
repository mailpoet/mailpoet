<?php

namespace MailPoet\Config;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\Subscription\Registration;
use MailPoet\WooCommerce\Settings as WooCommerceSettings;
use MailPoet\WooCommerce\Subscription as WooCommerceSubscription;

class HooksWooCommerce {
  /** @var WooCommerceSubscription */
  private $woocommerceSubscription;

  /** @var WooCommerceSegment */
  private $woocommerceSegment;

  /** @var WooCommerceSettings */
  private $woocommerceSettings;

  /** @var WooCommercePurchases */
  private $woocommercePurchases;

  /** @var Registration */
  private $subscriberRegistration;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct(
    WooCommerceSubscription $woocommerceSubscription,
    WooCommerceSegment $woocommerceSegment,
    WooCommerceSettings $woocommerceSettings,
    WooCommercePurchases $woocommercePurchases,
    Registration $subscriberRegistration,
    LoggerFactory $loggerFactory
  ) {
    $this->woocommerceSubscription = $woocommerceSubscription;
    $this->woocommerceSegment = $woocommerceSegment;
    $this->woocommerceSettings = $woocommerceSettings;
    $this->woocommercePurchases = $woocommercePurchases;
    $this->loggerFactory = $loggerFactory;
    $this->subscriberRegistration = $subscriberRegistration;
  }

  public function extendWooCommerceCheckoutForm() {
    try {
      $this->woocommerceSubscription->extendWooCommerceCheckoutForm();
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce Subscription');
    }
  }

  public function subscribeOnCheckout($orderId, $data) {
    try {
      $this->woocommerceSubscription->subscribeOnCheckout($orderId, $data);
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce Subscription');
    }
  }

  public function disableWooCommerceSettings() {
    try {
      $this->woocommerceSettings->disableWooCommerceSettings();
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce Settings');
    }
  }

  public function synchronizeRegisteredCustomer($wpUserId, $currentFilter = null) {
    try {
      $this->woocommerceSegment->synchronizeRegisteredCustomer($wpUserId, $currentFilter);
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce Sync');
    }
  }

  public function synchronizeGuestCustomer($orderId) {
    try {
      $this->woocommerceSegment->synchronizeGuestCustomer($orderId);
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce Sync');
    }
  }

  public function trackPurchase($id, $useCookies = true) {
    try {
      $this->woocommercePurchases->trackPurchase($id, $useCookies);
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce Purchases');
    }
  }

  public function extendForm() {
    try {
      $this->subscriberRegistration->extendForm();
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce Extend Form');
    }
  }

  public function onRegister($errors, string $userLogin, string $userEmail = null) {
    try {
      if (empty($errors->errors)) {
        $this->subscriberRegistration->onRegister($errors, $userLogin, $userEmail);
      }
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce on Register');
    }
    return $errors;
  }

  private function logError(\Throwable $e, $name) {
    $logger = $this->loggerFactory->getLogger($name);
    $logger->addError($e->getMessage(), [
      'file' => $e->getFile(),
      'line' => $e->getLine(),
    ]);
  }
}
