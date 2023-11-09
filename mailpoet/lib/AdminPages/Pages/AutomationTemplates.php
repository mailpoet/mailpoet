<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\AssetsController;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Data\AutomationTemplate;
use MailPoet\Automation\Engine\Data\AutomationTemplateCategory;
use MailPoet\Automation\Engine\Registry;
use MailPoet\WP\Functions as WPFunctions;

class AutomationTemplates {
  /** @var AssetsController */
  private $assetsController;

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var Registry  */
  private $registry;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    AssetsController $assetsController,
    PageRenderer $pageRenderer,
    Registry $registry,
    WPFunctions $wp
  ) {
    $this->assetsController = $assetsController;
    $this->pageRenderer = $pageRenderer;
    $this->registry = $registry;
    $this->wp = $wp;
  }

  public function render() {
    $this->assetsController->setupAutomationTemplatesDependencies();

    $this->pageRenderer->displayPage(
      'automation/templates.html',
      [
        'locale_full' => $this->wp->getLocale(),
        'api' => [
          'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
          'nonce' => $this->wp->wpCreateNonce('wp_rest'),
        ],
        'templates' => array_map(
          function(AutomationTemplate $template): array {
            return $template->toArray();
          },
          array_values($this->registry->getTemplates())
        ),
        'template_categories' => array_map(
          function (AutomationTemplateCategory $category): array {
            return [
              'slug' => $category->getSlug(),
              'name' => $category->getName(),
            ];
          },
          array_values($this->registry->getTemplateCategories())
        ),
      ]
    );
  }
}
