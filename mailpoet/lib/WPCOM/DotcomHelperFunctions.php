<?php declare(strict_types = 1);

namespace MailPoet\WPCOM;

use MailPoet\WP\Functions;

/**
 * Plan detection documentation:
 * https://github.com/Automattic/wc-calypso-bridge#active-plan-detection
 */
class DotcomHelperFunctions {

  private Functions $wp;

  public function __construct(
    Functions $wp
  ) {
    $this->wp = $wp;
  }

  /**
   * Returns true if in the context of WordPress.com Atomic platform.
   *
   * @return bool
   */
  public function isAtomicPlatform(): bool {
    // ATOMIC_CLIENT_ID === '2' corresponds to WordPress.com client on the Atomic platform
    $is_atomic_platform = defined('IS_ATOMIC') && IS_ATOMIC && defined('ATOMIC_CLIENT_ID') && (ATOMIC_CLIENT_ID === '2');
    return (bool)$this->wp->applyFilters('mailpoet_is_atomic_platform', $is_atomic_platform);
  }

  /**
   * Returns true if the site is on WordPress.com.
   */
  public function isDotcom(): bool {
    return $this->isAtomicPlatform();
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

  public function isEcommerceWPCom(): bool {
    return function_exists('wc_calypso_bridge_is_wpcom_ecommerce_plan') && wc_calypso_bridge_is_wpcom_ecommerce_plan();
  }

  public function isEcommerce(): bool {
    return function_exists('wc_calypso_bridge_is_ecommerce_plan') && wc_calypso_bridge_is_ecommerce_plan();
  }

  public function isGarden(): bool {
    return defined('IS_COMMERCE_GARDEN') && IS_COMMERCE_GARDEN;
  }

  protected function getWpcloudConfig(string $key): ?string {
    if (!function_exists('garden_get_wpcloud_config')) {
      return null;
    }
    $value = \garden_get_wpcloud_config($key);
    return is_string($value) && $value !== '' ? $value : null;
  }

  protected function getSiteMetaValue(string $meta_key): ?string {
    if (function_exists('get_site_meta')) {
      $blog_id = \get_current_blog_id();
      $value = \get_site_meta($blog_id, $meta_key, true);
      if (is_string($value) && $value !== '') {
        return $value;
      }
    }

    if ($this->isGarden()) {
      return $this->getWpcloudConfig($meta_key);
    }

    return null;
  }

  public function gardenName(): ?string {
    if (!$this->isGarden()) {
      return null;
    }
    return $this->getSiteMetaValue('garden_name');
  }

  public function gardenPartner(): ?string {
    if (!$this->isGarden()) {
      return null;
    }
    return $this->getSiteMetaValue('garden_partner');
  }

  /**
   * Returns the plan name for the current site if hosted on WordPress.com.
   * Empty otherwise.
   */
  public function getDotcomPlan(): string {
    if ($this->isWooExpressPerformance()) {
      return 'performance';
    } elseif ($this->isWooExpressEssential()) {
      return 'essential';
    } elseif ($this->isBusiness()) {
      return 'business';
    } elseif ($this->isEcommerceTrial()) {
      return 'ecommerce_trial';
    } elseif ($this->isEcommerceWPCom()) {
      return 'ecommerce_wpcom';
    } elseif ($this->isEcommerce()) {
      return 'ecommerce';
    }

    // Garden plan detection via WP Cloud persistent data
    if ($this->isGarden()) {
      $planInfo = $this->getWpcloudConfig('plan_info');
      if ($planInfo !== null) {
        $decoded = json_decode($planInfo, true);
        if (is_array($decoded) && isset($decoded['plan_type']) && is_string($decoded['plan_type']) && $decoded['plan_type'] !== '') {
          return $decoded['plan_type'];
        }
      }
    }

    return '';
  }
}
