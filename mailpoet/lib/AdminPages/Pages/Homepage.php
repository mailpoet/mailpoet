<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Homepage\HomepageDataController;
use MailPoet\Settings\SettingsController;

class Homepage {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var SettingsController */
  private $settingsController;

  /** @var HomepageDataController */
  private $homepageDataController;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settingsController,
    HomepageDataController $homepageDataController
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settingsController = $settingsController;
    $this->homepageDataController = $homepageDataController;
  }

  public function render() {
    $data = [
      'mta_log' => $this->settingsController->get('mta_log'),
      'homepage' => $this->homepageDataController->getPageData(),
    ];
    $this->pageRenderer->displayPage('homepage.html', $data);
  }
}
