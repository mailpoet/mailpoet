<?php declare(strict_types = 1);

namespace Automattic\WooCommerce\Abilities {
  if (!interface_exists(AbilityDefinition::class)) {
    interface AbilityDefinition {
      public static function get_name(): string;

      public static function get_registration_args(): array;
    }
  }
}

namespace MailPoet\Test\Abilities {

use MailPoet\Abilities\WooCommerceMarketingStatus;

require_once __DIR__ . '/../../../lib/Abilities/WooCommerceMarketingStatus.php';

class WooCommerceMarketingStatusTest extends \MailPoetUnitTest {
  public function testItRegistersReadOnlyWooCommerceAbility() {
    $args = WooCommerceMarketingStatus::get_registration_args();

    verify(WooCommerceMarketingStatus::get_name())->equals('mailpoet/get-woocommerce-marketing-status');
    verify($args['category'])->equals('woocommerce');
    verify($args['meta']['show_in_rest'])->true();
    verify($args['meta']['mcp']['public'])->true();
    verify($args['meta']['mcp']['type'])->equals('tool');
    verify($args['meta']['annotations']['readonly'])->true();
    verify($args['meta']['annotations']['destructive'])->false();
    verify($args['meta']['annotations']['idempotent'])->true();
  }
}
}
