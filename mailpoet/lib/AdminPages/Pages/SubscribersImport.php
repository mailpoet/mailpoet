<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Form\Block;
use MailPoet\Services\Validator;
use MailPoet\Subscribers\ImportExport\ImportExportFactory;
use MailPoet\WP\Functions as WPFunctions;

class SubscribersImport {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var Block\Date */
  private $dateBlock;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    PageRenderer $pageRenderer,
    Block\Date $dateBlock,
    WPFunctions $wp
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->dateBlock = $dateBlock;
    $this->wp = $wp;
  }

  public function render() {
    $import = new ImportExportFactory(ImportExportFactory::IMPORT_ACTION);
    $data = $import->bootstrap();
    $dateTypes = $this->dateBlock->getDateTypes();
    $data = array_merge($data, [
      'date_types' => $dateTypes,
      'custom_fields_date_types' => array_map(function ($label, $value) {
        return [
          'label' => $label,
          'value' => $value,
        ];
      }, $dateTypes, array_keys($dateTypes)),
      'date_formats' => $this->dateBlock->getDateFormats(),
      'month_names' => $this->dateBlock->getMonthNames(),
      'role_based_emails' => json_encode(Validator::ROLE_EMAILS),
      'custom_fields_api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
    ]);
    $this->pageRenderer->displayPage('subscribers/importExport/import.html', $data);
  }
}
