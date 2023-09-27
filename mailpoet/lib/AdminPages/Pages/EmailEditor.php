<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Env;
use MailPoet\WP\Functions as WPFunctions;

class EmailEditor {
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
    $assetsParams = require_once Env::$assetsPath . '/dist/js/email_editor_custom/email_editor.asset.php';
    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email_editor_custom/email_editor.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );
    $this->wp->wpEnqueueStyle(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email_editor_custom/email_editor.css',
      [],
      $assetsParams['version']
    );
    $this->wp->wpEnqueueStyle('wp-components');
    $this->wp->wpEnqueueStyle('wp-block-editor');
    $this->wp->wpEnqueueStyle('wp-block-editor-content');
    $this->wp->wpEnqueueStyle('wp-edit-post');
    $this->wp->wpEnqueueStyle('wp-editor');
    $this->wp->wpEnqueueStyle('wp-block-library');
    $this->wp->wpEnqueueStyle('wp-format-library');
    $this->wp->wpEnqueueStyle('wp-interface');

    $this->pageRenderer->displayPage('email_editor.html', []);
  }
}
