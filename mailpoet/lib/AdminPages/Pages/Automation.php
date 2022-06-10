<?php declare(strict_types=1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Migrations\Migrator;
use MailPoet\WP\Functions as WPFunctions;

class Automation {
  /** @var Migrator */
  private $migrator;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    Migrator $migrator,
    PageRenderer $pageRenderer,
    WPFunctions $wp
  ) {
    $this->migrator = $migrator;
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
  }

  public function render() {
    $this->wp->wpEnqueueStyle('wp-components');

    if (!$this->migrator->hasSchema()) {
      $this->migrator->createSchema();
    }
    $this->pageRenderer->displayPage('automation.html', [
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
    ]);
  }
}
