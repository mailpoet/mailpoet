<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\API;

use function current_user_can;

abstract class Endpoint {
  abstract public function handle(Request $request): Response;

  public function checkPermissions(): bool {
    return current_user_can('administrator');
  }
}
