<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sharing;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Util\pQuery\pQuery;
use MailPoet\WP\Functions as WPFunctions;

class ShareMetadataBuilder {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function injectMetadata(string $html, NewsletterEntity $newsletter, string $canonicalUrl): string {
    $metadata = $this->buildMetadata($html, $newsletter, $canonicalUrl);
    if (stripos($html, '</head>') === false) {
      return $metadata . "\n" . $html;
    }

    return preg_replace('/<\/head>/i', $metadata . "\n</head>", $html, 1) ?: $html;
  }

  public function buildMetadata(string $html, NewsletterEntity $newsletter, string $canonicalUrl): string {
    $tags = [
      $this->metaTag('name', 'robots', 'noindex, nofollow'),
    ];

    $title = $newsletter->getCampaignNameOrSubject();
    if ($title === '') {
      return implode("\n", $tags);
    }

    $description = trim($newsletter->getPreheader());
    $image = $this->findFirstContentImage($html);

    $tags[] = $this->metaTag('property', 'og:title', $title);
    $tags[] = $this->metaTag('property', 'og:type', 'website');
    $tags[] = $this->metaTag('property', 'og:url', $canonicalUrl);
    $tags[] = $this->metaTag('name', 'twitter:card', $image ? 'summary_large_image' : 'summary');
    $tags[] = $this->metaTag('name', 'twitter:title', $title);

    if ($description !== '') {
      $tags[] = $this->metaTag('property', 'og:description', $description);
      $tags[] = $this->metaTag('name', 'twitter:description', $description);
    }

    if ($image) {
      $tags[] = $this->metaTag('property', 'og:image', $image['src']);
      $tags[] = $this->metaTag('name', 'twitter:image', $image['src']);
      if ($image['alt'] !== '') {
        $tags[] = $this->metaTag('property', 'og:image:alt', $image['alt']);
      }
    }

    return implode("\n", $tags);
  }

  /**
   * @return array{src: string, alt: string}|null
   */
  private function findFirstContentImage(string $html): ?array {
    $dom = pQuery::parseStr($html);
    foreach ($dom->query('img') as $image) {
      $src = trim((string)$image->src);
      if (!$this->isUsableImage($src, $image)) {
        continue;
      }
      return [
        'src' => $src,
        'alt' => trim((string)$image->alt),
      ];
    }
    return null;
  }

  private function isUsableImage(string $src, $image): bool {
    if ($src === '' || !preg_match('/^https?:\/\//i', $src)) {
      return false;
    }

    $haystack = strtolower($src . ' ' . (string)$image->alt);
    foreach (['fake-logo', 'your-logo-placeholder', 'social-icons', 'mailpoet-logo', 'powered-by-mailpoet'] as $needle) {
      if (strpos($haystack, $needle) !== false) {
        return false;
      }
    }

    $width = isset($image->width) ? (int)$image->width : 0;
    $height = isset($image->height) ? (int)$image->height : 0;
    if (($width > 0 && $width < 64) || ($height > 0 && $height < 64)) {
      return false;
    }

    return true;
  }

  private function metaTag(string $attribute, string $name, string $content): string {
    return sprintf(
      '<meta %s="%s" content="%s" />',
      $attribute,
      $this->wp->escAttr($name),
      $this->wp->escAttr($content)
    );
  }
}
