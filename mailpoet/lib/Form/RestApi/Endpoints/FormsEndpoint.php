<?php declare(strict_types = 1);

namespace MailPoet\Form\RestApi\Endpoints;

use MailPoet\API\REST\Endpoint;
use MailPoet\Config\AccessControl;

abstract class FormsEndpoint extends Endpoint {
  public function checkPermissions(): bool {
    return current_user_can(AccessControl::PERMISSION_MANAGE_FORMS);
  }
}
