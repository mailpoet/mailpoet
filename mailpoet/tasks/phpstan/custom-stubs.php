<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing
// phpcs:ignoreFile - This file contains stubs for 3rd party functions and classes that might break our PHPCS rules

namespace {
  if (!function_exists('members_get_cap_group')) {
    function members_get_cap_group($name) {
    }
  }

  if (!class_exists(\WC_Subscription::class)) {
    class WC_Subscription extends WC_Product {
    }
  }

  if (!function_exists('wcs_create_subscription')) {
    function wcs_create_subscription($args) {
    }
  }
}

// Temporary stubs for Woo Custom Tables.
// We can remove them after the functionality is officially released and added into php-stubs/woocommerce-stubs
namespace Automattic\WooCommerce\Internal\DataStores\Orders {

  if (!class_exists(\Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class)) {
    class DataSynchronizer {
      function create_database_tables() {}
    }
  }
}
namespace Automattic\WooCommerce\Internal\Features {

  if (!class_exists(\Automattic\WooCommerce\Internal\Features\FeaturesController::class)) {
    class FeaturesController {
      function change_feature_enable(string $feature_id, bool $enable) {}
    }
  }
}

namespace WP_CLI\Utils {
  if (!function_exists('format_items')) {
    /** @param array|string $fields */
    function format_items(string $format, array $items, $fields): void {
    }
  }
}

namespace AutomateWoo {
  if (!class_exists(\AutomateWoo\Customer::class)) {
    class Customer {
      public function opt_out() {
      }
    }
  }
  if (!class_exists(\AutomateWoo\Customer_Factory::class)) {
    class Customer_Factory {
      public static function get_by_email(string $customer_email, bool $create_if_not_found = true) {
      }
    }
  }
}
