<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Features\FeatureFlagsController;
use MailPoet\Features\FeaturesController;

if (!defined('ABSPATH')) exit;

class FeatureFlags extends APIEndpoint {

  /** @var FeatureFlagsController */
  private $feature_flags_controller;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_FEATURES,
  ];

  function __construct(FeatureFlagsController $feature_flags) {
    $this->feature_flags_controller = $feature_flags;
  }

  function getAll() {
    $feature_flags = $this->feature_flags_controller->getAll();
    return $this->successResponse($feature_flags);
  }

  function set(array $flags) {
    foreach ($flags as $name => $value) {
      if (!isset(FeaturesController::$defaults[$name])) {
        return $this->badRequest([
          APIError::BAD_REQUEST => "Feature '$name' does not exist'",
        ]);
      }
    }

    foreach ($flags as $name => $value) {
      $this->feature_flags_controller->set($name, (bool)$value);
    }
    return $this->successResponse([]);
  }
}
