<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Settings\SettingsController;

class Homepage {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var SettingsController */
  private $settingsController;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settingsController
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settingsController = $settingsController;
  }

  public function render() {
    $data = [
      'mta_log' => $this->settingsController->get('mta_log'),
    ];
    $this->pageRenderer->displayPage('homepage.html', $data);
  }
}
