<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Env;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterEditorV2 {
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
    $this->wp->wpEnqueueStyle( 'wp-edit-post' );
    $this->wp->wpEnqueueStyle('wp-format-library');
    $this->wp->wpEnqueueMedia();

    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor_v2',
      Env::$assetsUrl . '/dist/js/newsletter_editor_v2.js',
      [],
      Env::$version,
      true
    );


    $this->pageRenderer->displayPage('newsletter/editorv2.html', []);
  }
}
