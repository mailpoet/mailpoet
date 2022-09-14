<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Automation\Engine\Data\WorkflowTemplate;
use MailPoet\Automation\Engine\Storage\WorkflowTemplateStorage;
use MailPoet\WP\Functions as WPFunctions;

class AutomationTemplates {

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WorkflowTemplateStorage  */
  private $templateStorage;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    PageRenderer $pageRenderer,
    WorkflowTemplateStorage $templateStorage,
    WPFunctions $wp
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->templateStorage = $templateStorage;
    $this->wp = $wp;
  }

  public function render() {

    $this->pageRenderer->displayPage(
      'automation/templates.html',
      [
        'sub_menu' => 'mailpoet-automation',
        'api' => [
          'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
          'nonce' => $this->wp->wpCreateNonce('wp_rest'),
        ],
        'templates' => array_map(
          function(WorkflowTemplate $template): array {
            return $template->toArray();
          },
          $this->templateStorage->getTemplates()
        ),
      ]
    );
  }
}
