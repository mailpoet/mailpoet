<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use Automattic\WooCommerce\Abilities\AbilityDefinition;
use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Config\AccessControl;
use MailPoet\DI\ContainerWrapper;

if (!defined('ABSPATH')) exit;

if (!interface_exists(AbilityDefinition::class)) {
  return;
}

class WooCommerceAutomationTemplates implements AbilityDefinition {
  private const CATEGORIES = [
    'abandoned-cart',
    'bookings',
    'purchase',
    'review',
    'subscriptions',
  ];

  public static function get_name(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required by WooCommerce's AbilityDefinition interface.
    return 'mailpoet/list-woocommerce-automation-templates';
  }

  public static function get_registration_args(): array { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required by WooCommerce's AbilityDefinition interface.
    return [
      'label' => __('List MailPoet WooCommerce automation templates', 'mailpoet'),
      'description' => __('List MailPoet automation templates for WooCommerce marketing workflows.', 'mailpoet'),
      'category' => 'woocommerce',
      'input_schema' => self::getInputSchema(),
      'output_schema' => self::getOutputSchema(),
      'execute_callback' => [self::class, 'execute'],
      'permission_callback' => [self::class, 'canReadTemplates'],
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

  public static function execute($input = []): array {
    $category = is_array($input) && isset($input['category']) && is_string($input['category']) ? $input['category'] : '';
    if ($category !== '' && !in_array($category, self::CATEGORIES, true)) {
      return ['templates' => []];
    }

    $container = ContainerWrapper::getInstance();

    /** @var Registry $registry */
    $registry = $container->get(Registry::class);

    $templates = $registry->getTemplates($category !== '' ? $category : null);
    $templates = array_filter($templates, function (AutomationTemplate $template): bool {
      return in_array($template->getCategory(), self::CATEGORIES, true);
    });

    return [
      'templates' => array_values(array_map([self::class, 'formatTemplate'], $templates)),
    ];
  }

  public static function canReadTemplates(): bool {
    return current_user_can(AccessControl::PERMISSION_MANAGE_AUTOMATIONS);
  }

  private static function formatTemplate(AutomationTemplate $template): array {
    return [
      'slug' => $template->getSlug(),
      'name' => $template->getName(),
      'description' => $template->getDescription(),
      'category' => $template->getCategory(),
      'type' => $template->getType(),
      'required_capabilities' => $template->getRequiredCapabilities(),
      'is_recommended' => $template->isRecommended(),
    ];
  }

  private static function getInputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'category' => [
          'type' => 'string',
          'enum' => self::CATEGORIES,
        ],
      ],
      'additionalProperties' => false,
    ];
  }

  private static function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'templates' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'slug' => ['type' => 'string'],
              'name' => ['type' => 'string'],
              'description' => ['type' => 'string'],
              'category' => [
                'type' => 'string',
                'enum' => self::CATEGORIES,
              ],
              'type' => [
                'type' => 'string',
                'enum' => [
                  AutomationTemplate::TYPE_DEFAULT,
                  AutomationTemplate::TYPE_PREMIUM,
                  AutomationTemplate::TYPE_COMING_SOON,
                ],
              ],
              'required_capabilities' => [
                'type' => 'object',
                'additionalProperties' => [
                  'type' => ['boolean', 'integer'],
                ],
              ],
              'is_recommended' => ['type' => 'boolean'],
            ],
            'required' => ['slug', 'name', 'description', 'category', 'type', 'required_capabilities', 'is_recommended'],
            'additionalProperties' => false,
          ],
        ],
      ],
      'required' => ['templates'],
      'additionalProperties' => false,
    ];
  }
}
