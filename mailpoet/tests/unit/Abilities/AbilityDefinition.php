<?php declare(strict_types = 1);

namespace Automattic\WooCommerce\Abilities;

if (!interface_exists(AbilityDefinition::class)) {
  interface AbilityDefinition {
    public static function get_name(): string; // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Matches WooCommerce's AbilityDefinition interface.

    public static function get_registration_args(): array; // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Matches WooCommerce's AbilityDefinition interface.
  }
}
