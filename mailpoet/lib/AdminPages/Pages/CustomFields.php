<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Form\Block\Date;
use MailPoet\WP\Functions as WPFunctions;

class CustomFields {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  /** @var Date */
  private $dateBlock;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    WPFunctions $wp,
    Date $dateBlock
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->dateBlock = $dateBlock;
  }

  public function render(): void {
    $this->assetsController->setupCustomFieldsDependencies();
    $dateTypes = $this->dateBlock->getDateTypes();
    $this->pageRenderer->displayPage('subscribers/custom_fields.html', [
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'date_types' => array_map(function ($label, $value) {
        return [
          'label' => $label,
          'value' => $value,
        ];
      }, $dateTypes, array_keys($dateTypes)),
      'date_formats' => $this->dateBlock->getDateFormats(),
      'subscribers_listing_url' => $this->wp->escUrlRaw($this->wp->adminUrl('admin.php?page=mailpoet-subscribers')),
    ]);
  }
}
