<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing
// phpcs:ignoreFile - This file contains stubs for 3rd party functions and classes that might break our PHPCS rules

namespace {
  if (!function_exists('members_get_cap_group')) {
    function members_get_cap_group($name) {
    }
  }

  if (!class_exists(\WP_HTML_Tag_Processor::class)) {
    class WP_HTML_Tag_Processor {

      /** @var int */
      const MAX_BOOKMARKS = 10;

      /** @var string */
      protected $html;

      /** @var WP_HTML_Span[] */
      protected $bookmarks = array();

      /** @var WP_HTML_Text_Replacement[]  */
      protected $lexical_updates = array();

      public function __construct($content) {
      }

      public function next_tag($tag = null) {
      }

      public function get_attribute($attribute) {
      }

      public function get_updated_html() {
      }

      public function set_attribute($attribute, $value) {
      }

      public function next_token() {}

      public function get_modifiable_text() {}

      public function get_token_type() {}

      public function remove_attribute($name) {}

      public function get_tag() {}

      public function set_modifiable_text($plaintext_content ) {}

      public function set_bookmark($name) {}

      /** @return bool */
      public function is_tag_closer() {}

      /** @return string[]|null */
      public function get_attribute_names_with_prefix($prefix) {}

      /** @return bool */
      public function paused_at_incomplete_token() {}
    }
  }

  if (!class_exists(\WP_HTML_Span::class)) {
    class WP_HTML_Span {
      /** @var int */
      public $start;

      /** @var int */
      public $length;

      /**
       * @param int $start  Byte offset into document where replacement span begins.
       * @param int $length Byte length of span.
       */
      public function __construct( int $start, int $length ) {}
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

// Stub for the order attribution controller, which is internal and not included in php-stubs/woocommerce-stubs
namespace Automattic\WooCommerce\Internal\Orders {

  if (!class_exists(\Automattic\WooCommerce\Internal\Orders\OrderAttributionController::class)) {
    class OrderAttributionController {
      public function get_field_names(): array {
        return [];
      }
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
