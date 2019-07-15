<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\MP2Migrator;

if (!defined('ABSPATH')) exit;

class MP2Migration {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var MP2Migrator */
  private $mp2_migrator;

  function __construct(PageRenderer $page_renderer, MP2Migrator $mp2_migrator) {
    $this->page_renderer = $page_renderer;
    $this->mp2_migrator = $mp2_migrator;
  }

  function render() {
    $this->mp2_migrator->init();
    $data = [
      'log_file_url' => $this->mp2_migrator->log_file_url,
      'progress_url' => $this->mp2_migrator->progressbar->url,
    ];
    $this->page_renderer->displayPage('mp2migration.html', $data);
  }
}
