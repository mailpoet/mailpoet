<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Config\Activator;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class Setup extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  /** @var WPFunctions */
  private $wp;

  /** @var Activator */
  private $activator;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    WPFunctions $wp,
    Activator $activator,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->activator = $activator;
    $this->settings = $settings;
  }

  public function reset() {
    try {
      $this->activator->deactivate();
      $this->settings->resetCache();
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
