<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;

class SubscribersExport {
  /** @var PageRenderer */
  private $page_renderer;

  public function __construct(PageRenderer $page_renderer) {
    $this->page_renderer = $page_renderer;
  }

  public function render() {
    $export = new ImportExportFactory(ImportExportFactory::EXPORT_ACTION);
    $data = $export->bootstrap();
    $data['sub_menu'] = 'mailpoet-subscribers';
    $this->page_renderer->displayPage('subscribers/importExport/export.html', $data);
  }
}
