<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Statistics\Track\WooCommercePurchases;
use MailPoet\Subscription\Registration;
use MailPoet\WooCommerce\MailPoetTask;
use MailPoet\WooCommerce\Settings as WooCommerceSettings;
use MailPoet\WooCommerce\SubscriberEngagement;
use MailPoet\WooCommerce\Subscription as WooCommerceSubscription;
use MailPoet\WooCommerce\Tracker;

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

  /** @var SubscriberEngagement */
  private $subscriberEngagement;

  /** @var Tracker */
  private $tracker;

  public function __construct(
    WooCommerceSubscription $woocommerceSubscription,
    WooCommerceSegment $woocommerceSegment,
    WooCommerceSettings $woocommerceSettings,
    WooCommercePurchases $woocommercePurchases,
    Registration $subscriberRegistration,
    LoggerFactory $loggerFactory,
    Tracker $tracker,
    SubscriberEngagement $subscriberEngagement
  ) {
    $this->woocommerceSubscription = $woocommerceSubscription;
    $this->woocommerceSegment = $woocommerceSegment;
    $this->woocommerceSettings = $woocommerceSettings;
    $this->woocommercePurchases = $woocommercePurchases;
    $this->loggerFactory = $loggerFactory;
    $this->subscriberRegistration = $subscriberRegistration;
    $this->tracker = $tracker;
    $this->subscriberEngagement = $subscriberEngagement;
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

  public function subscribeOnOrderPay($orderId) {
    try {
      $this->woocommerceSubscription->subscribeOnOrderPay($orderId);
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

  public function updateSubscriberEngagement($orderId) {
    try {
      $this->subscriberEngagement->updateSubscriberEngagement($orderId);
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce Update Subscriber Engagement');
    }
  }

  public function declareHposCompatibility() {
    try {
      if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', Env::$pluginPath);
      }
    } catch (\Throwable $e) {
      $this->logError($e, 'WooCommerce HPOS Compatibility');
    }
  }

  public function addTrackingData($data) {
    if (!is_array($data)) {
      return $data;
    }
    return $this->tracker->addTrackingData($data);
  }

  public function addMailPoetTaskToWooHomePage() {
    try {
      if (class_exists(TaskLists::class) && class_exists(Task::class)) {
        TaskLists::add_task('extended', new MailPoetTask());
      }
    } catch (\Throwable $e) {
      $this->logError($e, 'Unable to add MailPoet task to WooCommerce homepage');
    }
  }

  private function logError(\Throwable $e, $name) {
    $logger = $this->loggerFactory->getLogger($name);
    $logger->error($e->getMessage(), [
      'file' => $e->getFile(),
      'line' => $e->getLine(),
    ]);
  }
}
