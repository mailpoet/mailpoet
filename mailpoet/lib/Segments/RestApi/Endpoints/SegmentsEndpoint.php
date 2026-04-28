<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\REST\Endpoint;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;

abstract class SegmentsEndpoint extends Endpoint {
  use SegmentRequestValidationTrait;

  public function checkPermissions(): bool {
    return WPFunctions::get()->currentUserCan(AccessControl::PERMISSION_MANAGE_SEGMENTS);
  }
}
