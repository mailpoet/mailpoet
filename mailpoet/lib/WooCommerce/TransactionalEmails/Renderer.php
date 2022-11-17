<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\WooCommerce\TransactionalEmails;

use MailPoet\Entities\NewsletterEntity;
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

  public function render(NewsletterEntity $newsletter, ?string $subject = null) {
    $renderedHtml = $this->renderer->renderAsPreview($newsletter, 'html', $subject);
    $headingText = $subject ?? '';
    $renderedHtml = str_replace(ContentPreprocessor::WC_HEADING_PLACEHOLDER, $headingText, $renderedHtml);
    $html = explode(ContentPreprocessor::WC_CONTENT_PLACEHOLDER, $renderedHtml);
    $this->htmlBeforeContent = $html[0];
    $this->htmlAfterContent = $html[1];
  }

  public function getHTMLBeforeContent() {
    if (empty($this->htmlBeforeContent)) {
      throw new \Exception("You should call 'render' before 'getHTMLBeforeContent'");
    }
    return $this->htmlBeforeContent . '<div id="' . self::CONTENT_CONTAINER_ID . '"><div id="body_content"><div id="body_content_inner"><table style="width: 100%"><tr><td style="padding: 10px 20px">';
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
