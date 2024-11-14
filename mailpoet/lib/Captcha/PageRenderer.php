<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Form\AssetsController;
use MailPoet\WP\Functions as WPFunction;

class PageRenderer {
  private array $data = [];
  private WPFunction $wp;
  private CaptchaFormRenderer $formRenderer;
  private AssetsController $assetsController;

  public function __construct(
    WPFunction $wp,
    CaptchaFormRenderer $formRenderer,
    AssetsController $assetsController
  ) {
    $this->wp = $wp;
    $this->formRenderer = $formRenderer;
    $this->assetsController = $assetsController;
  }

  public function render($data) {
    $this->data = $data;
    $this->wp->addFilter('wp_title', [$this, 'setWindowTitle'], 10, 3);
    $this->wp->addFilter('document_title_parts', [$this, 'setWindowTitleParts']);
    $this->wp->removeAction('wp_head', 'noindex', 1);
    $this->wp->addAction('wp_head', [$this, 'setMetaRobots'], 1);
    $this->wp->addFilter('the_title', [$this, 'setPageTitle']);
    $this->wp->addFilter('the_content', [$this, 'setPageContent']);
  }

  public function setWindowTitle($title, $separator, $separatorLocation = 'right') {
    $titleParts = explode(" $separator ", $title);
    if (!is_array($titleParts)) {
      return $title;
    }

    if ($separatorLocation === 'right') {
      // first part
      $titleParts[0] = $this->setPageTitle();
    } else {
      // last part
      $lastIndex = count($titleParts) - 1;
      $titleParts[$lastIndex] = $this->setPageTitle();
    }

    return implode(" $separator ", $titleParts);
  }

  public function setWindowTitleParts($meta = []) {
    $meta['title'] = $this->setPageTitle();
    return $meta;
  }

  public function setMetaRobots() {
    echo '<meta name="robots" content="noindex,nofollow">';
  }

  public function setPageTitle() {
    return __("Confirm you’re not a robot", 'mailpoet');
  }

  public function setPageContent($pageContent) {
    $this->assetsController->setupFrontEndDependencies();

    $content = $this->formRenderer->render($this->data);
    if (!$content) {
      return false;
    }

    return str_replace('[mailpoet_page]', trim($content), $pageContent);
  }
}
