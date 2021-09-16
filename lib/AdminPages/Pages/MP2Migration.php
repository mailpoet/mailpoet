<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\MP2Migrator;

class MP2Migration {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var MP2Migrator */
  private $mp2Migrator;

  public function __construct(
      PageRenderer $pageRenderer,
      MP2Migrator $mp2Migrator
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->mp2Migrator = $mp2Migrator;
  }

  public function render() {
    $this->mp2Migrator->init();
    $data = [
      'log_file_url' => $this->mp2Migrator->logFileUrl,
      'progress_url' => $this->mp2Migrator->progressbar->url,
    ];
    $this->pageRenderer->displayPage('mp2migration.html', $data);
  }
}
