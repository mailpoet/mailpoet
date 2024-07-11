<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\NewsletterTemplates;

use MailPoet\WP\Functions as WPFunctions;

class TemplateImageLoader {
  const TIMEOUT = 30; // seconds

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  /**
   * Acts as a proxy for html2canvas
   */
  public function loadExternalImage(string $url) {
    if (!$this->isUrlAllowed($url)) {
      // URL not allowed
      return false;
    }
    $image = $this->downloadUrl($url);
    if ($this->wp->isWpError($image)) {
      // Failed to load the image
      return false;
    }
    $mime = $this->wp->wpGetImageMime($image);
    if (!$this->isTypeAllowed($image, $mime)) {
      // Wrong file type
      @unlink($image);
      return false;
    }
    header('Content-Type: ' . $mime);
    readfile($image);
    @unlink($image);
    return true;
  }

  protected function downloadUrl($url) {
    require_once ABSPATH . '/wp-admin/includes/file.php';
    return download_url($url, self::TIMEOUT);
  }

  private function isUrlAllowed($url) {
    $urlParts = $this->wp->wpParseUrl($url);
    $allowedExtensions = ['gif', 'png', 'jpg', 'jpeg'];
    if (
      !isset($urlParts['path'])
      || !preg_match('/\.(' . join('|', $allowedExtensions) . ')$/i', $urlParts['path'])
    ) {
      return false;
    }
    /** @var string[] */
    $allowedUrls = (array)$this->wp->applyFilters('mailpoet_template_image_allowed_urls', [
      'https://ps.w.org/mailpoet/assets/newsletter-templates/',
    ]);
    foreach ($allowedUrls as $allowedUrl) {
      $allowedUrlParts = $this->wp->wpParseUrl($allowedUrl);
      if (
        isset($urlParts['host'], $urlParts['scheme'])
        && isset($allowedUrlParts['host'], $allowedUrlParts['scheme'], $allowedUrlParts['path'])
        && $urlParts['host'] === $allowedUrlParts['host']
        && $urlParts['scheme'] === $allowedUrlParts['scheme']
        && strpos($urlParts['path'], $allowedUrlParts['path']) === 0
      ) {
        return true;
      }
    }
    return false;
  }

  private function isTypeAllowed($image, $mime) {
    $allowedMimeTypes = [
      'image/gif',
      'image/jpeg',
      'image/png',
    ];
    return $mime && in_array($mime, $allowedMimeTypes);
  }
}
