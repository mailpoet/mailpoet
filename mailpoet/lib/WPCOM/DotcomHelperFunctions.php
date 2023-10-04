<?php declare(strict_types = 1);

namespace MailPoet\WPCOM;

/**
 * Plan detection documentation:
 * https://github.com/Automattic/wc-calypso-bridge#active-plan-detection
 */
class DotcomHelperFunctions {
  /**
   * Returns true if in the context of WordPress.com Atomic platform.
   *
   * @return bool
   */
  public function isAtomicPlatform(): bool {
    // ATOMIC_CLIENT_ID === '2' corresponds to WordPress.com client on the Atomic platform
    return defined('IS_ATOMIC') && IS_ATOMIC && defined('ATOMIC_CLIENT_ID') && (ATOMIC_CLIENT_ID === '2');
  }

  /**
   * Returns true if the site is on WordPress.com.
   */
  public function isDotcom(): bool {
    return $this->isAtomicPlatform() ;
  }

  public function isWooExpressPerformance(): bool {
    return function_exists('wc_calypso_bridge_is_woo_express_performance_plan') && wc_calypso_bridge_is_woo_express_performance_plan();
  }

  public function isWooExpressEssential(): bool {
    return function_exists('wc_calypso_bridge_is_woo_express_essential_plan') && wc_calypso_bridge_is_woo_express_essential_plan();
  }

  public function isBusiness(): bool {
    return function_exists('wc_calypso_bridge_is_business_plan') && wc_calypso_bridge_is_business_plan();
  }

  public function isEcommerceTrial(): bool {
    return function_exists('wc_calypso_bridge_is_ecommerce_trial_plan') && wc_calypso_bridge_is_ecommerce_trial_plan();
  }

  /**
   * Returns the plan name for the current site if hosted on WordPress.com.
   * Empty otherwise.
   */
  public function getDotcomPlan(): string {
    if ($this->isWooExpressPerformance()) {
      return 'performance';
    }

    if ($this->isWooExpressEssential()) {
      return 'essential';
    }

    if ($this->isBusiness()) {
      return 'business';
    }

    if ($this->isEcommerceTrial()) {
      return 'ecommerce_trial';
    }

    return '';
  }
}
