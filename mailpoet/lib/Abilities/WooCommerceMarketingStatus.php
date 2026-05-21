<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedInCategory;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedProduct;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\AbandonedCart\AbandonedCartTrigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysAProductTrigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysFromACategoryTrigger;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\BuysFromATagTrigger;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Hooks;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\Subscription;
use MailPoet\WooCommerce\TransactionalEmails;

if (!defined('ABSPATH')) exit;

if (!interface_exists(AbilityDefinition::class)) {
  return;
}

class WooCommerceMarketingStatus implements AbilityDefinition {
  public static function get_name(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required by WooCommerce's AbilityDefinition interface.
    return 'mailpoet/get-woocommerce-marketing-status';
  }

  public static function get_registration_args(): array { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required by WooCommerce's AbilityDefinition interface.
    return [
      'label' => __('Get MailPoet WooCommerce marketing status', 'mailpoet'),
      'description' => __('Read MailPoet WooCommerce marketing, checkout opt-in, and email editor status.', 'mailpoet'),
      'category' => 'woocommerce',
      'output_schema' => self::getOutputSchema(),
      'execute_callback' => [self::class, 'execute'],
      'permission_callback' => [self::class, 'canReadStatus'],
      'meta' => [
        'show_in_rest' => true,
        'mcp' => [
          'public' => true,
          'type' => 'tool',
        ],
        'annotations' => [
          'readonly' => true,
          'destructive' => false,
          'idempotent' => true,
        ],
      ],
    ];
  }

  public static function execute(): array {
    $container = ContainerWrapper::getInstance();

    /** @var SettingsController $settings */
    $settings = $container->get(SettingsController::class);
    /** @var WooCommerceHelper $woocommerceHelper */
    $woocommerceHelper = $container->get(WooCommerceHelper::class);
    /** @var NewslettersRepository $newslettersRepository */
    $newslettersRepository = $container->get(NewslettersRepository::class);
    /** @var SegmentsRepository $segmentsRepository */
    $segmentsRepository = $container->get(SegmentsRepository::class);
    /** @var AutomationStorage $automationStorage */
    $automationStorage = $container->get(AutomationStorage::class);

    $checkoutSegmentIds = array_values(array_unique(array_map('absint', (array)$settings->get(Subscription::OPTIN_SEGMENTS_SETTING_NAME, []))));

    return [
      'checkout_optin' => [
        'enabled' => (bool)$settings->get(Subscription::OPTIN_ENABLED_SETTING_NAME, false),
        'message' => wp_strip_all_tags((string)$settings->get(Subscription::OPTIN_MESSAGE_SETTING_NAME, '')),
        'position' => (string)$settings->get(Subscription::OPTIN_POSITION_SETTING_NAME, Hooks::DEFAULT_OPTIN_POSITION),
        'segments' => self::formatSegments($segmentsRepository, $checkoutSegmentIds),
      ],
      'transactional_email_editor' => [
        'enabled' => (bool)$settings->get('woocommerce.use_mailpoet_editor', false),
        'template_configured' => absint($settings->get(TransactionalEmails::SETTING_EMAIL_ID, 0)) > 0,
      ],
      'legacy_automatic_emails' => [
        'active_counts' => [
          'abandoned_cart' => $newslettersRepository->getCountOfActiveAutomaticEmailsForEvent(AbandonedCart::SLUG),
          'first_purchase' => $newslettersRepository->getCountOfActiveAutomaticEmailsForEvent(FirstPurchase::SLUG),
          'purchased_in_category' => $newslettersRepository->getCountOfActiveAutomaticEmailsForEvent(PurchasedInCategory::SLUG),
          'purchased_product' => $newslettersRepository->getCountOfActiveAutomaticEmailsForEvent(PurchasedProduct::SLUG),
        ],
      ],
      'automations' => [
        'active_email_counts' => self::getAutomationEmailCounts($automationStorage),
      ],
      'measurement' => [
        'tracking_level' => (string)$settings->get('tracking.level', TrackingConfig::LEVEL_FULL),
        'analytics_enabled' => (bool)$settings->get('analytics.enabled', false),
        'revenue_attribution_order_statuses' => $woocommerceHelper->getPurchaseStates(),
      ],
    ];
  }

  public static function canReadStatus(): bool {
    return current_user_can(AccessControl::PERMISSION_MANAGE_SETTINGS);
  }

  private static function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'checkout_optin' => [
          'type' => 'object',
          'properties' => [
            'enabled' => ['type' => 'boolean'],
            'message' => ['type' => 'string'],
            'position' => [
              'type' => 'string',
              'enum' => array_keys(Hooks::OPTIN_HOOKS),
            ],
            'segments' => [
              'type' => 'array',
              'items' => [
                'type' => 'object',
                'properties' => [
                  'id' => ['type' => 'integer'],
                  'name' => ['type' => 'string'],
                ],
                'required' => ['id', 'name'],
                'additionalProperties' => false,
              ],
            ],
          ],
          'required' => ['enabled', 'message', 'position', 'segments'],
          'additionalProperties' => false,
        ],
        'transactional_email_editor' => [
          'type' => 'object',
          'properties' => [
            'enabled' => ['type' => 'boolean'],
            'template_configured' => ['type' => 'boolean'],
          ],
          'required' => ['enabled', 'template_configured'],
          'additionalProperties' => false,
        ],
        'legacy_automatic_emails' => [
          'type' => 'object',
          'properties' => [
            'active_counts' => [
              'type' => 'object',
              'properties' => [
                'abandoned_cart' => ['type' => 'integer'],
                'first_purchase' => ['type' => 'integer'],
                'purchased_in_category' => ['type' => 'integer'],
                'purchased_product' => ['type' => 'integer'],
              ],
              'required' => ['abandoned_cart', 'first_purchase', 'purchased_in_category', 'purchased_product'],
              'additionalProperties' => false,
            ],
          ],
          'required' => ['active_counts'],
          'additionalProperties' => false,
        ],
        'automations' => [
          'type' => 'object',
          'properties' => [
            'active_email_counts' => [
              'type' => 'object',
              'properties' => [
                'abandoned_cart' => ['type' => 'integer'],
                'order_completed' => ['type' => 'integer'],
                'order_created' => ['type' => 'integer'],
                'order_status_changed' => ['type' => 'integer'],
                'purchased_in_category' => ['type' => 'integer'],
                'purchased_product' => ['type' => 'integer'],
                'purchased_with_tag' => ['type' => 'integer'],
              ],
              'required' => ['abandoned_cart', 'order_completed', 'order_created', 'order_status_changed', 'purchased_in_category', 'purchased_product', 'purchased_with_tag'],
              'additionalProperties' => false,
            ],
          ],
          'required' => ['active_email_counts'],
          'additionalProperties' => false,
        ],
        'measurement' => [
          'type' => 'object',
          'properties' => [
            'tracking_level' => [
              'type' => 'string',
              'enum' => [TrackingConfig::LEVEL_FULL, TrackingConfig::LEVEL_PARTIAL, TrackingConfig::LEVEL_BASIC],
            ],
            'analytics_enabled' => ['type' => 'boolean'],
            'revenue_attribution_order_statuses' => [
              'type' => 'array',
              'items' => ['type' => 'string'],
            ],
          ],
          'required' => ['tracking_level', 'analytics_enabled', 'revenue_attribution_order_statuses'],
          'additionalProperties' => false,
        ],
      ],
      'required' => ['checkout_optin', 'transactional_email_editor', 'legacy_automatic_emails', 'automations', 'measurement'],
      'additionalProperties' => false,
    ];
  }

  private static function formatSegments(SegmentsRepository $segmentsRepository, array $segmentIds): array {
    if (!$segmentIds) {
      return [];
    }

    $segmentsById = [];
    foreach ($segmentsRepository->findByIds($segmentIds) as $segment) {
      if (!$segment instanceof SegmentEntity || $segment->getId() === null) {
        continue;
      }
      $segmentsById[(int)$segment->getId()] = [
        'id' => (int)$segment->getId(),
        'name' => $segment->getName(),
      ];
    }

    $selectedSegments = [];
    foreach ($segmentIds as $segmentId) {
      if (isset($segmentsById[$segmentId])) {
        $selectedSegments[] = $segmentsById[$segmentId];
      }
    }

    return $selectedSegments;
  }

  private static function getAutomationEmailCounts(AutomationStorage $automationStorage): array {
    return [
      'abandoned_cart' => $automationStorage->getCountOfActiveByTriggerKeysAndAction([AbandonedCartTrigger::KEY], SendEmailAction::KEY),
      'order_completed' => $automationStorage->getCountOfActiveByTriggerKeysAndAction(['woocommerce:order-completed'], SendEmailAction::KEY),
      'order_created' => $automationStorage->getCountOfActiveByTriggerKeysAndAction(['woocommerce:order-created'], SendEmailAction::KEY),
      'order_status_changed' => $automationStorage->getCountOfActiveByTriggerKeysAndAction(['woocommerce:order-status-changed'], SendEmailAction::KEY),
      'purchased_in_category' => $automationStorage->getCountOfActiveByTriggerKeysAndAction([BuysFromACategoryTrigger::KEY], SendEmailAction::KEY),
      'purchased_product' => $automationStorage->getCountOfActiveByTriggerKeysAndAction([BuysAProductTrigger::KEY], SendEmailAction::KEY),
      'purchased_with_tag' => $automationStorage->getCountOfActiveByTriggerKeysAndAction([BuysFromATagTrigger::KEY], SendEmailAction::KEY),
    ];
  }
}
