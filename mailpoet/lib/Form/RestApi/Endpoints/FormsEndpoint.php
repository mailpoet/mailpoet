<?php declare(strict_types = 1);

namespace MailPoet\Form\RestApi\Endpoints;

use MailPoet\API\REST\Endpoint;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

abstract class FormsEndpoint extends Endpoint {
  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_FORMS);
  }
}
