<?php declare(strict_types = 1);

namespace MailPoet\API\REST;

use MailPoet\Validator\Schema;
use MailPoet\WP\Functions as WPFunctions;

abstract class Endpoint {
  abstract public function handle(Request $request): Response;

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan('admin');
  }

  /** @return array<string, Schema> */
  public static function getRequestSchema(): array {
    return [];
  }
}
