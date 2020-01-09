<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\MP2Migrator;

class MP2Migration {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var MP2Migrator */
  private $mp2_migrator;

  public function __construct(PageRenderer $pageRenderer, MP2Migrator $mp2Migrator) {
    $this->pageRenderer = $pageRenderer;
    $this->mp2Migrator = $mp2Migrator;
  }

  public function render() {
    $this->mp2Migrator->init();
    $data = [
      'log_file_url' => $this->mp2Migrator->log_file_url,
      'progress_url' => $this->mp2Migrator->progressbar->url,
    ];
    $this->pageRenderer->displayPage('mp2migration.html', $data);
  }
}
