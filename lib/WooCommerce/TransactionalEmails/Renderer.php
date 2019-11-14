<?php

namespace MailPoet\WooCommerce\TransactionalEmails;

use csstidy;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\Newsletter\Renderer\Renderer as NewsletterRenderer;

class Renderer {
  const CONTENT_CONTAINER_ID = 'mailpoet_woocommerce_container';

  /** @var string */
  private $html_before_content;

  /** @var string */
  private $html_after_content;

  function __construct() {
    $this->html_before_content = '';
    $this->html_after_content = '';
  }

  public function render(Newsletter $newsletter) {
    $renderer = new NewsletterRenderer($newsletter, true);
    $html = explode(Preprocessor::WC_CONTENT_PLACEHOLDER, $renderer->render('html'));
    $this->html_before_content = $html[0];
    $this->html_after_content = $html[1];
  }

  public function getHTMLBeforeContent($heading_text) {
    if (empty($this->html_before_content)) {
      throw new \Exception("You should call 'render' before 'getHTMLBeforeContent'");
    }
    $html = str_replace(Preprocessor::WC_HEADING_PLACEHOLDER, $heading_text, $this->html_before_content);
    return $html . '<div id="' . self::CONTENT_CONTAINER_ID . '">';
  }

  public function getHTMLAfterContent() {
    if (empty($this->html_after_content)) {
      throw new \Exception("You should call 'render' before 'getHTMLAfterContent'");
    }
    return '</div>' . $this->html_after_content;
  }

  public function prefixCss($css) {
    $parser = new csstidy();
    $parser->settings['compress_colors'] = false;
    $parser->parse($css);
    foreach ($parser->css as $index => $rules) {
      $parser->css[$index] = [];
      foreach ($rules as $selectors => $properties) {
        $selectors = explode(',', $selectors);
        $selectors = array_map(function($selector) {
          return '#' . self::CONTENT_CONTAINER_ID . ' ' . $selector;
        }, $selectors);
        $selectors = implode(',', $selectors);
        $parser->css[$index][$selectors] = $properties;
      }
    }
    return $parser->print->plain();
  }
}