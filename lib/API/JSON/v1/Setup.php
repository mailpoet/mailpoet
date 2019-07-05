<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Activator;

if (!defined('ABSPATH')) exit;

class Setup extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  /** @var WPFunctions */
  private $wp;

  /** @var Activator */
  private $activator;

  function __construct(WPFunctions $wp, Activator $activator) {
    $this->wp = $wp;
    $this->activator = $activator;
  }

  function reset() {
    try {
      $this->activator->deactivate();
      $this->activator->activate();
      $this->wp->doAction('mailpoet_setup_reset');
      return $this->successResponse();
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}
