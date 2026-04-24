<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\API;

use MailPoet\API\REST\Endpoint as MailPoetEndpoint;
use MailPoet\Automation\Engine\Engine;
use MailPoet\WP\Functions as WPFunctions;

abstract class Endpoint extends MailPoetEndpoint {
  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(Engine::CAPABILITY_MANAGE_AUTOMATIONS);
  }
}
