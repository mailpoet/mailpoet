<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine;

use MailPoet\Validator\Builder;

class EmailApiController {
  /** @var SettingsController */
  private $settingsController;

  public function __construct(
    SettingsController $settingsController
  ) {
    $this->settingsController = $settingsController;
  }

  /**
   * @return array - Email specific data such styles.
   */
  public function getEmailData(): array {
    return [
      'layout_styles' => $this->settingsController->getEmailStyles(),
    ];
  }

  /**
   * Update Email specific data we store.
   */
  public function saveEmailData(array $data, \WP_Post $emailPost): void {
    // Here comes code saving of Email specific data that will be passed on 'email_data' attribute
  }

  public function getEmailDataSchema(): array {
    return Builder::object([
      'layout_styles' => Builder::object([
        'width' => Builder::string(),
        'background' => Builder::string(),
        'padding' => Builder::object([
          'bottom' => Builder::string(),
          'left' => Builder::string(),
          'right' => Builder::string(),
          'top' => Builder::string(),
        ]),
      ]),
    ])->toArray();
  }
}
