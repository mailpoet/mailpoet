<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing
// phpcs:ignoreFile - This file contains stubs for 3rd party functions and classes that might break our PHPCS rules

namespace {
  if (!function_exists('members_get_cap_group')) {
    function members_get_cap_group($name) {
    }
  }

  if (!class_exists(\WC_Subscription::class)) {
    class WC_Subscription extends WC_Order {
      public function get_id() {
        return 0;
      }

      public function get_last_order() {
        return 1;
      }

      public function get_customer_id() {
        return 1;
      }

      public function get_cancelled_email_sent() {
        return false;
      }

      public function get_failed_payment_count() {
        return 1;
      }

      public function get_payment_count() {
        return 1;
      }

      public function get_payment_interval() {
        return 1;
      }

      public function get_total_initial_payment() {
        return 1.00;
      }

      public function is_manual() {
        return false;
      }

      public function get_billing_period() {
        return 'day';
      }

      public function get_billing_interval() {
        return 1;
      }

      public function get_date(string $dateType, string $timeZone = 'gmt') {
        return 0;
      }
    }
  }

  if (!function_exists('wcs_create_subscription')) {
    function wcs_create_subscription($args) {
    }
  }

  if (!class_exists(\WP_HTML_Tag_Processor::class)) {
    class WP_HTML_Tag_Processor {
      public function __construct($content) {
      }

      public function next_tag($tag) {
      }

      public function get_attribute($attribute) {
      }

      public function get_updated_html() {
      }

      public function set_attribute($attribute, $value) {
      }
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

// Temporary stubs for marketing channels
// I have no idea why PhpStorm is complaining, but the woocommerce-stubs library has the latest updates
namespace Automattic\WooCommerce\Admin\Marketing {
  interface MarketingChannelInterface {
    public const PRODUCT_LISTINGS_NOT_APPLICABLE   = 'not-applicable';
    public const PRODUCT_LISTINGS_SYNC_IN_PROGRESS = 'sync-in-progress';
    public const PRODUCT_LISTINGS_SYNC_FAILED      = 'sync-failed';
    public const PRODUCT_LISTINGS_SYNCED           = 'synced';
    public function get_slug(): string;
    public function get_name(): string;
    public function get_description(): string;
    public function get_icon_url(): string;
    public function is_setup_completed(): bool;
    public function get_setup_url(): string;
    public function get_product_listings_status(): string;
    public function get_errors_count(): int;
    public function get_supported_campaign_types(): array;
    public function get_campaigns(): array;
  }

  class MarketingCampaign {
    /**
     * MarketingCampaign constructor.
     *
     * @param string                $id         The marketing campaign's unique identifier.
     * @param MarketingCampaignType $type       The marketing campaign type.
     * @param string                $title      The title of the marketing campaign.
     * @param string                $manage_url The URL to the channel's campaign management page.
     * @param Price|null            $cost       The cost of the marketing campaign with the currency.
     */
    public function __construct( string $id, MarketingCampaignType $type, string $title, string $manage_url, Price $cost = null ) {}
  }

  class MarketingCampaignType {
    /**
     * MarketingCampaignType constructor.
     *
     * @param string                    $id          A unique identifier for the campaign type.
     * @param MarketingChannelInterface $channel     The marketing channel that this campaign type belongs to.
     * @param string                    $name        Name of the marketing campaign type.
     * @param string                    $description Description of the marketing campaign type.
     * @param string                    $create_url  The URL to the create campaign page.
     * @param string                    $icon_url    The URL to an image/icon for the campaign type.
     */
    public function __construct( string $id, MarketingChannelInterface $channel, string $name, string $description, string $create_url, string $icon_url ) {}
  }

  class Price {
    /**
     * Price constructor.
     *
     * @param string $value    The value of the price.
     * @param string $currency The currency of the price.
     */
    public function __construct( string $value, string $currency ) {}
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
