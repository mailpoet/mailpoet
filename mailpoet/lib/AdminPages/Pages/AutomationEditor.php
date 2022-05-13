<?php declare(strict_types=1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\WP\Functions as WPFunctions;

class AutomationEditor {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    PageRenderer $pageRenderer,
    WPFunctions $wp
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
  }

  public function render() {
    // Gutenberg styles
    $this->wp->wpEnqueueStyle('wp-edit-post');
    $this->wp->wpEnqueueStyle('wp-format-library');
    $this->wp->wpEnqueueMedia();

    $this->pageRenderer->displayPage('automation/editor.html', [
      'sub_menu' => 'mailpoet-automation',
      'api' => [
        'root' => rtrim($this->wp->escUrlRaw($this->wp->restUrl()), '/'),
        'nonce' => $this->wp->wpCreateNonce('wp_rest'),
      ],
    ]);
  }
}
