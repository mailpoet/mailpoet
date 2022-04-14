<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Env;
use MailPoet\Newsletter\GutenbergFormatMapper;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterEditorV2 {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  /** @var NewslettersRepository */
  private $newsletterRepository;

  /** @var GutenbergFormatMapper */
  private $gutenbergMapper;

  public function __construct(
    PageRenderer $pageRenderer,
    WPFunctions $wp,
    NewslettersRepository $newsletterRepository,
    GutenbergFormatMapper $gutenbergFormatMapper
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->newsletterRepository = $newsletterRepository;
    $this->gutenbergMapper = $gutenbergFormatMapper;
  }

  public function render() {
    $newsletterId = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $newsletter = $this->newsletterRepository->findOneById($newsletterId);
    $newsletterBody = '';
    if ($newsletter) {
      $newsletterBody = $this->gutenbergMapper->map($newsletter->getBody() ?? []);
    }
    // Gutenberg styles
    $this->wp->wpEnqueueStyle('wp-edit-post' );
    $this->wp->wpEnqueueStyle('wp-format-library');
    $this->wp->wpEnqueueMedia();

    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor_v2',
      Env::$assetsUrl . '/dist/js/newsletter_editor_v2.js',
      [],
      Env::$version,
      true
    );

    $this->pageRenderer->displayPage('newsletter/editorv2.html', ['body' => $newsletterBody]);
  }
}
