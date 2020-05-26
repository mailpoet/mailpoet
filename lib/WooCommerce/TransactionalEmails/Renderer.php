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

  /** @var string */
  private $htmlBeforeContent;

  /** @var string */
  private $htmlAfterContent;

  public function __construct(csstidy $cssParser) {
    $this->cssParser = $cssParser;
    $this->htmlBeforeContent = '';
    $this->htmlAfterContent = '';
  }

  public function render(Newsletter $newsletter, NewsletterRenderer $renderer = null) {
    $renderer = $renderer ?: new NewsletterRenderer();
    $html = explode(Preprocessor::WC_CONTENT_PLACEHOLDER, $renderer->render($newsletter, true, 'html'));
    $this->htmlBeforeContent = $html[0];
    $this->htmlAfterContent = $html[1];
  }

  public function getHTMLBeforeContent($headingText) {
    if (empty($this->htmlBeforeContent)) {
      throw new \Exception("You should call 'render' before 'getHTMLBeforeContent'");
    }
    $html = str_replace(Preprocessor::WC_HEADING_PLACEHOLDER, $headingText, $this->htmlBeforeContent);
    return $html . '<div id="' . self::CONTENT_CONTAINER_ID . '"><div id="body_content_inner">';
  }

  public function getHTMLAfterContent() {
    if (empty($this->htmlAfterContent)) {
      throw new \Exception("You should call 'render' before 'getHTMLAfterContent'");
    }
    return '</div></div>' . $this->htmlAfterContent;
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
