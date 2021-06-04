<?php

namespace MailPoet\WooCommerce\TransactionalEmails;

use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\Newsletter\Renderer\Renderer as NewsletterRenderer;
use MailPoetVendor\csstidy;
use MailPoetVendor\csstidy_print;

class Renderer {
  const CONTENT_CONTAINER_ID = 'mailpoet_woocommerce_container';

  /** @var csstidy */
  private $cssParser;

  /** @var NewsletterRenderer */
  private $renderer;

  /** @var string */
  private $htmlBeforeContent;

  /** @var string */
  private $htmlAfterContent;

  public function __construct(
    csstidy $cssParser,
    NewsletterRenderer $renderer
  ) {
    $this->cssParser = $cssParser;
    $this->htmlBeforeContent = '';
    $this->htmlAfterContent = '';
    $this->renderer = $renderer;
  }

  public function render(Newsletter $newsletter, ?string $subject = null) {
    $html = explode(Preprocessor::WC_CONTENT_PLACEHOLDER, $this->renderer->renderAsPreview($newsletter, 'html', $subject));
    $this->htmlBeforeContent = $html[0];
    $this->htmlAfterContent = $html[1];
  }

  public function getHTMLBeforeContent($headingText) {
    if (empty($this->htmlBeforeContent)) {
      throw new \Exception("You should call 'render' before 'getHTMLBeforeContent'");
    }
    $html = str_replace(Preprocessor::WC_HEADING_PLACEHOLDER, $headingText, $this->htmlBeforeContent);
    return $html . '<div id="' . self::CONTENT_CONTAINER_ID . '"><div id="body_content"><div id="body_content_inner"><table style="width: 100%"><tr><td>';
  }

  public function getHTMLAfterContent() {
    if (empty($this->htmlAfterContent)) {
      throw new \Exception("You should call 'render' before 'getHTMLAfterContent'");
    }
    return '</td></tr></table></div></div></div>' . $this->htmlAfterContent;
  }

  public function prefixCss($css) {
    $this->cssParser->settings['compress_colors'] = false;
    $this->cssParser->parse($css);
    foreach ($this->cssParser->css as $index => $rules) {
      $this->cssParser->css[$index] = [];
      foreach ($rules as $selectors => $properties) {
        $selectors = explode(',', $selectors);
        $selectors = array_map(function($selector) {
          return '#' . self::CONTENT_CONTAINER_ID . ' ' . $selector;
        }, $selectors);
        $selectors = implode(',', $selectors);
        $this->cssParser->css[$index][$selectors] = $properties;
      }
    }
    /** @var csstidy_print */
    $print = $this->cssParser->print;
    return $print->plain();
  }
}
