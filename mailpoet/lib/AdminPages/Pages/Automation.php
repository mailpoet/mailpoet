<?php declare(strict_types=1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Migrations\Migrator;

class Automation {
  /** @var Migrator */
  private $migrator;

  /** @var PageRenderer */
  private $pageRenderer;

  public function __construct(
    Migrator $migrator,
    PageRenderer $pageRenderer
  ) {
    $this->migrator = $migrator;
    $this->pageRenderer = $pageRenderer;
  }

  public function render() {
    if (!$this->migrator->hasSchema()) {
      $this->migrator->createSchema();
    }
    $this->pageRenderer->displayPage('automation.html', []);
  }
}
