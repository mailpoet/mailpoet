<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WP\Functions as WPFunctions;

class EmailTemplates {
  private AssetsController $assetsController;

  private PageRenderer $pageRenderer;

  private WPFunctions $wp;

  private UserFlagsController $userFlagsController;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    WPFunctions $wp,
    UserFlagsController $userFlagsController
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->userFlagsController = $userFlagsController;
  }

  public function render(): void {
    $this->assetsController->setupEmailTemplatesDependencies();
    $this->pageRenderer->displayPage('email-templates.html', [
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
      'classic_templates_first' => (bool)$this->userFlagsController->get('remember_email_editor_choice')
        && $this->userFlagsController->get('last_email_editor_choice') === 'classic',
    ]);
  }
}
