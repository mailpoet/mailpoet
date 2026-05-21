<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\Abilities;
use MailPoet\Abilities\WooCommerceAutomationTemplates;
use MailPoet\Abilities\WooCommerceMarketingStatus;
use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Config\Hooks;
use MailPoet\Settings\TrackingConfig;

require_once __DIR__ . '/AbilityDefinition.php';
require_once __DIR__ . '/../../../lib/Abilities/Abilities.php';
require_once __DIR__ . '/../../../lib/Abilities/WooCommerceAutomationTemplates.php';
require_once __DIR__ . '/../../../lib/Abilities/WooCommerceMarketingStatus.php';

class WooCommerceMarketingStatusTest extends \MailPoetUnitTest {
  public function testItRegistersAbilityDefinitionClasses() {
    $this->assertSame([
      WooCommerceAutomationTemplates::class,
      WooCommerceMarketingStatus::class,
    ], Abilities::addAbilityDefinitionClasses([]));
  }

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

  public function testItRegistersReadOnlyWooCommerceAutomationTemplatesAbility() {
    $args = WooCommerceAutomationTemplates::get_registration_args();

    verify(WooCommerceAutomationTemplates::get_name())->equals('mailpoet/list-woocommerce-automation-templates');
    verify($args['category'])->equals('woocommerce');
    verify($args['meta']['show_in_rest'])->true();
    verify($args['meta']['mcp']['public'])->true();
    verify($args['meta']['mcp']['type'])->equals('tool');
    verify($args['meta']['annotations']['readonly'])->true();
    verify($args['meta']['annotations']['destructive'])->false();
    verify($args['meta']['annotations']['idempotent'])->true();
  }

  public function testItLimitsMarketingStatusToMailPoetWooCommerceProperties() {
    $args = WooCommerceMarketingStatus::get_registration_args();
    $schema = $args['output_schema'];

    $this->assertArrayNotHasKey('woocommerce', $schema['properties']);
    $this->assertArrayNotHasKey('plugin_version', $schema['properties']);
    $this->assertSame([
      'checkout_optin',
      'transactional_email_editor',
      'legacy_automatic_emails',
      'automations',
      'measurement',
    ], $schema['required']);
  }

  public function testItExposesUsefulCheckoutOptinSegmentData() {
    $args = WooCommerceMarketingStatus::get_registration_args();
    $checkoutOptin = $args['output_schema']['properties']['checkout_optin'];

    verify($checkoutOptin['properties']['position']['enum'])->equals(array_keys(Hooks::OPTIN_HOOKS));
    $this->assertArrayHasKey('segments', $checkoutOptin['properties']);
    $this->assertSame(['id', 'name'], $checkoutOptin['properties']['segments']['items']['required']);
    $this->assertArrayNotHasKey('segment_ids', $checkoutOptin['properties']);
  }

  public function testItConstrainsTrackingLevelToRuntimeValues() {
    $args = WooCommerceMarketingStatus::get_registration_args();

    verify($args['output_schema']['properties']['measurement']['properties']['tracking_level']['enum'])->equals([
      TrackingConfig::LEVEL_FULL,
      TrackingConfig::LEVEL_PARTIAL,
      TrackingConfig::LEVEL_BASIC,
    ]);
  }

  public function testItConstrainsWooCommerceAutomationTemplateProperties() {
    $args = WooCommerceAutomationTemplates::get_registration_args();

    verify($args['input_schema']['properties']['category']['enum'])->equals([
      'abandoned-cart',
      'bookings',
      'purchase',
      'review',
      'subscriptions',
    ]);
    verify($args['output_schema']['properties']['templates']['items']['properties']['type']['enum'])->equals([
      AutomationTemplate::TYPE_DEFAULT,
      AutomationTemplate::TYPE_PREMIUM,
      AutomationTemplate::TYPE_COMING_SOON,
    ]);
  }
}
