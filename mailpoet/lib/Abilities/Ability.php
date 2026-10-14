<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

if (!defined('ABSPATH')) exit;

interface Ability {
  public function getName(): string;

  /**
   * Arguments for wp_register_ability() without the category, which the registrar adds.
   */
  public function getRegistrationArgs(): array;
}
