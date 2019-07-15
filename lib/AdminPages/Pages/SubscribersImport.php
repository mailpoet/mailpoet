<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Form\Block;
use MailPoet\Models\ModelValidator;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\Util\Installation;

if (!defined('ABSPATH')) exit;

class SubscribersImport {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var Installation */
  private $installation;

  function __construct(PageRenderer $page_renderer, Installation $installation) {
    $this->page_renderer = $page_renderer;
    $this->installation = $installation;
  }

  function render() {
    $import = new ImportExportFactory(ImportExportFactory::IMPORT_ACTION);
    $data = $import->bootstrap();
    $data = array_merge($data, [
      'date_types' => Block\Date::getDateTypes(),
      'date_formats' => Block\Date::getDateFormats(),
      'month_names' => Block\Date::getMonthNames(),
      'sub_menu' => 'mailpoet-subscribers',
      'role_based_emails' => json_encode(ModelValidator::ROLE_EMAILS),
    ]);

    $data['is_new_user'] = $this->installation->isNewInstallation();

    $this->page_renderer->displayPage('subscribers/importExport/import.html', $data);
  }
}
