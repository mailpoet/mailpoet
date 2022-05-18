<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\API;

use MailPoet\Config\AccessControl;
use MailPoet\Validator\Schema;

use function current_user_can;

abstract class Endpoint {
  abstract public function handle(Request $request): Response;

  public function checkPermissions(): bool {
    return current_user_can(AccessControl::PERMISSION_MANAGE_AUTOMATIONS);
  }

  /** @return array<string, Schema> */
  public static function getRequestSchema(): array {
    return [];
  }
}
