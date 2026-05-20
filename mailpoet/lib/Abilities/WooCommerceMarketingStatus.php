<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\Events\FirstPurchase;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedInCategory;
use MailPoet\AutomaticEmails\WooCommerce\Events\PurchasedProduct;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Env;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\Subscription;
use MailPoet\WooCommerce\TransactionalEmails;

if (!defined('ABSPATH')) exit;

if (!interface_exists(AbilityDefinition::class)) {
  return;
}

class WooCommerceMarketingStatus implements AbilityDefinition {
  public static function get_name(): string {
    return 'mailpoet/get-woocommerce-marketing-status';
  }

  public static function get_registration_args(): array {
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

    return [
      'woocommerce' => [
        'active' => $woocommerceHelper->isWooCommerceActive(),
        'version' => (string)($woocommerceHelper->getWooCommerceVersion() ?? ''),
        'currency' => $woocommerceHelper->isWooCommerceActive() ? (string)$woocommerceHelper->getWoocommerceCurrency() : '',
        'custom_orders_table_enabled' => $woocommerceHelper->isWooCommerceCustomOrdersTableEnabled(),
        'blocks_active' => $woocommerceHelper->isWooCommerceBlocksActive('8.0.0'),
      ],
      'checkout_optin' => [
        'enabled' => (bool)$settings->get(Subscription::OPTIN_ENABLED_SETTING_NAME, false),
        'message' => wp_strip_all_tags((string)$settings->get(Subscription::OPTIN_MESSAGE_SETTING_NAME, '')),
        'position' => (string)$settings->get(Subscription::OPTIN_POSITION_SETTING_NAME, ''),
        'segment_ids' => array_values(array_map('absint', (array)$settings->get(Subscription::OPTIN_SEGMENTS_SETTING_NAME, []))),
      ],
      'transactional_email_editor' => [
        'enabled' => (bool)$settings->get('woocommerce.use_mailpoet_editor', false),
        'template_newsletter_id' => absint($settings->get(TransactionalEmails::SETTING_EMAIL_ID, 0)),
      ],
      'automatic_emails' => [
        'active_counts' => [
          'abandoned_cart' => $newslettersRepository->getCountOfActiveAutomaticEmailsForEvent(AbandonedCart::SLUG),
          'first_purchase' => $newslettersRepository->getCountOfActiveAutomaticEmailsForEvent(FirstPurchase::SLUG),
          'purchased_in_category' => $newslettersRepository->getCountOfActiveAutomaticEmailsForEvent(PurchasedInCategory::SLUG),
          'purchased_product' => $newslettersRepository->getCountOfActiveAutomaticEmailsForEvent(PurchasedProduct::SLUG),
        ],
      ],
      'tracking' => [
        'level' => (string)$settings->get('tracking.level', ''),
        'analytics_enabled' => (bool)$settings->get('analytics.enabled', false),
        'purchase_states' => $woocommerceHelper->getPurchaseStates(),
      ],
      'plugin_version' => (string)Env::$version,
    ];
  }

  public static function canReadStatus(): bool {
    return current_user_can(AccessControl::PERMISSION_MANAGE_SETTINGS);
  }

  private static function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'woocommerce' => [
          'type' => 'object',
          'properties' => [
            'active' => ['type' => 'boolean'],
            'version' => ['type' => 'string'],
            'currency' => ['type' => 'string'],
            'custom_orders_table_enabled' => ['type' => 'boolean'],
            'blocks_active' => ['type' => 'boolean'],
          ],
          'required' => ['active', 'version', 'currency', 'custom_orders_table_enabled', 'blocks_active'],
          'additionalProperties' => false,
        ],
        'checkout_optin' => [
          'type' => 'object',
          'properties' => [
            'enabled' => ['type' => 'boolean'],
            'message' => ['type' => 'string'],
            'position' => ['type' => 'string'],
            'segment_ids' => [
              'type' => 'array',
              'items' => ['type' => 'integer'],
            ],
          ],
          'required' => ['enabled', 'message', 'position', 'segment_ids'],
          'additionalProperties' => false,
        ],
        'transactional_email_editor' => [
          'type' => 'object',
          'properties' => [
            'enabled' => ['type' => 'boolean'],
            'template_newsletter_id' => ['type' => 'integer'],
          ],
          'required' => ['enabled', 'template_newsletter_id'],
          'additionalProperties' => false,
        ],
        'automatic_emails' => [
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
        'tracking' => [
          'type' => 'object',
          'properties' => [
            'level' => ['type' => 'string'],
            'analytics_enabled' => ['type' => 'boolean'],
            'purchase_states' => [
              'type' => 'array',
              'items' => ['type' => 'string'],
            ],
          ],
          'required' => ['level', 'analytics_enabled', 'purchase_states'],
          'additionalProperties' => false,
        ],
        'plugin_version' => ['type' => 'string'],
      ],
      'required' => ['woocommerce', 'checkout_optin', 'transactional_email_editor', 'automatic_emails', 'tracking', 'plugin_version'],
      'additionalProperties' => false,
    ];
  }
}
