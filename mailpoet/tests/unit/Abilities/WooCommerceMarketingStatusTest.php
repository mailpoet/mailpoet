<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\WooCommerceMarketingStatus;
use MailPoet\Settings\TrackingConfig;

require_once __DIR__ . '/AbilityDefinition.php';
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

  public function testItConstrainsTrackingLevelToRuntimeValues() {
    $args = WooCommerceMarketingStatus::get_registration_args();

    verify($args['output_schema']['properties']['tracking']['properties']['level']['enum'])->equals([
      TrackingConfig::LEVEL_FULL,
      TrackingConfig::LEVEL_PARTIAL,
      TrackingConfig::LEVEL_BASIC,
    ]);
  }
}
