<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\WooCommerce\TransactionalEmails;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\Renderer as NewsletterRenderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
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

  /** @var Shortcodes */
  private $shortcodes;

  public function __construct(
    csstidy $cssParser,
    NewsletterRenderer $renderer,
    Shortcodes $shortcodes
  ) {
    $this->cssParser = $cssParser;
    $this->htmlBeforeContent = '';
    $this->htmlAfterContent = '';
    $this->renderer = $renderer;
    $this->shortcodes = $shortcodes;
  }

  public function render(NewsletterEntity $newsletter, ?string $subject = null) {
    $renderedNewsletter = $this->renderer->renderAsPreview($newsletter, 'html', $subject);
    $headingText = $subject ?? '';

    $renderedHtml = $this->processShortcodes($newsletter, $renderedNewsletter);

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

  /**
   * In this method we alter CSS that is later inlined into the WooCommerce email template. WooCommerce use Emogrifier to inline CSS.
   * - We prefix the original selectors to avoid inlining those rules into content added int the MailPoet's editor.
   * - We update the font-family in the original CSS if it's set in the editor.
   */
  public function enhanceCss(string $css, NewsletterEntity $newsletter): string {
    // We allow setting global font family in the editor. The global font is saved in text.fontFamily
    $fontFamily = $newsletter->getGlobalStyle('text', 'fontFamily');
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
        // Update font family if it's set in the editor
        if ($fontFamily && !empty($properties['font-family'])) {
          $properties['font-family'] = $fontFamily;
        }
        $this->cssParser->css[$index][$selectors] = $properties;
      }
    }

    /** @var csstidy_print */
    $print = $this->cssParser->print;
    return $print->plain();
  }

  private function processShortcodes(NewsletterEntity $newsletter, $content) {
    $this->shortcodes->setQueue(null);
    $this->shortcodes->setSubscriber(null);
    $this->shortcodes->setNewsletter($newsletter);
    return $this->shortcodes->replace($content);
  }
}
