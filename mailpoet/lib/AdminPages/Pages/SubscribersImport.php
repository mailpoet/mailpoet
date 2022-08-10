<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Form\Block;
use MailPoet\Models\ModelValidator;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;

class SubscribersImport {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var Block\Date */
  private $dateBlock;

  public function __construct(
    PageRenderer $pageRenderer,
    Block\Date $dateBlock
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->dateBlock = $dateBlock;
  }

  public function render() {
    $import = new ImportExportFactory(ImportExportFactory::IMPORT_ACTION);
    $data = $import->bootstrap();
    $data = array_merge($data, [
      'date_types' => $this->dateBlock->getDateTypes(),
      'date_formats' => $this->dateBlock->getDateFormats(),
      'month_names' => $this->dateBlock->getMonthNames(),
      'sub_menu' => 'mailpoet-subscribers',
      'role_based_emails' => json_encode(ModelValidator::ROLE_EMAILS),
    ]);
    $this->pageRenderer->displayPage('subscribers/importExport/import.html', $data);
  }
}
