<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WP\Functions as WPFunctions;

class UserFlags extends APIEndpoint {

  /** @var UserFlagsController */
  private $userFlags;

  public $permissions = [
    'global' => AccessControl::ALL_ROLES_ACCESS,
  ];

  public function __construct(UserFlagsController $userFlags) {
    $this->userFlags = $userFlags;
  }

  public function set(array $flags = []) {
    if (empty($flags)) {
      return $this->badRequest(
        [
          APIError::BAD_REQUEST =>
            WPFunctions::get()->__('You have not specified any user flags to be saved.', 'mailpoet'),
        ]);
    } else {
      foreach ($flags as $name => $value) {
        $this->userFlags->set($name, $value);
      }
      return $this->successResponse([]);
    }
  }
}
