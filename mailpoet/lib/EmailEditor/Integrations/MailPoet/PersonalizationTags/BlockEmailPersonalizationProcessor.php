<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\Newsletter\Sending\Placeholders\PlaceholderCollector;

class BlockEmailPersonalizationProcessor {
  private LinksToShortcodesConvertor $linksToShortcodesConvertor;
  private OrderReviewUrl $orderReviewUrl;
  private TemplatePersonalizer $templatePersonalizer;
  private Personalizer $personalizer;

  public function __construct(
    LinksToShortcodesConvertor $linksToShortcodesConvertor,
    OrderReviewUrl $orderReviewUrl,
    TemplatePersonalizer $templatePersonalizer
  ) {
    $this->linksToShortcodesConvertor = $linksToShortcodesConvertor;
    $this->orderReviewUrl = $orderReviewUrl;
    $this->templatePersonalizer = $templatePersonalizer;
    $this->personalizer = Email_Editor_Container::container()->get(Personalizer::class);
  }

  /**
   * @param array{0: string, 1: string, 2: string} $content
   * @param array<string, mixed> $context
   * @return array{0: string, 1: string, 2: string}
   */
  public function personalize(array $content, array $context): array {
    $this->personalizer->set_context($context);
    foreach ($content as $key => $part) {
      $content[$key] = $this->personalizer->personalize_content((string)$part);
    }
    return $this->restorePersonalizedUrls($content, $this->getPersonalizedUrlTokens($context));
  }

  /**
   * @param array{0: string, 1: string, 2: string} $content
   * @param array<string, mixed> $context
   * @return array{0: string, 1: string, 2: string}
   */
  public function personalizeWithPlaceholders(array $content, array $context, PlaceholderCollector $collector): array {
    $this->templatePersonalizer->setContext($context);
    foreach ($content as $key => $part) {
      if ($key === 0) {
        $contentContext = TemplatePersonalizer::CONTEXT_SUBJECT;
      } elseif ($key === 1) {
        $contentContext = TemplatePersonalizer::CONTEXT_HTML;
      } else {
        $contentContext = TemplatePersonalizer::CONTEXT_TEXT;
      }
      $content[$key] = $this->templatePersonalizer->personalizeContentWithPlaceholders((string)$part, $collector, $contentContext);
    }

    return $this->restorePersonalizedUrlsWithPlaceholders($content, $context, $collector);
  }

  /**
   * @param array{0: string, 1: string, 2: string} $content
   * @param array<string, mixed> $context
   * @return array{0: string, 1: string, 2: string}
   */
  private function restorePersonalizedUrlsWithPlaceholders(array $content, array $context, PlaceholderCollector $collector): array {
    $htmlUrlTokens = [];
    $textUrlTokens = [];
    foreach ($this->getPersonalizedUrlTokens($context) as $token => $url) {
      if (isset($content[1]) && $this->containsPersonalizedUrlToken($content[1], $token)) {
        $htmlUrlTokens[$token] = $collector->addHtmlUrl($url, $token);
      }
      if (isset($content[2]) && $this->containsPersonalizedUrlToken($content[2], $token)) {
        $textUrlTokens[$token] = $collector->addText($url, $token);
      }
    }

    if (isset($content[1])) {
      $content[1] = $this->linksToShortcodesConvertor->restorePersonalizedLinkHrefs($content[1], $htmlUrlTokens);
      foreach ($htmlUrlTokens as $placeholder) {
        $content[1] = str_replace($this->getNormalizedHrefPlaceholder($placeholder), $placeholder, $content[1]);
      }
    }
    if (isset($content[2])) {
      $content[2] = $this->linksToShortcodesConvertor->restorePersonalizedLinkUrls($content[2], $textUrlTokens);
    }
    return $content;
  }

  /**
   * @param array{0: string, 1: string, 2: string} $content
   * @param array<string, string> $personalizedUrlTokens
   * @return array{0: string, 1: string, 2: string}
   */
  private function restorePersonalizedUrls(array $content, array $personalizedUrlTokens): array {
    if (isset($content[1])) {
      $content[1] = $this->linksToShortcodesConvertor->restorePersonalizedLinkHrefs($content[1], $personalizedUrlTokens);
    }
    if (isset($content[2])) {
      $content[2] = $this->linksToShortcodesConvertor->restorePersonalizedLinkUrls($content[2], $personalizedUrlTokens);
    }
    return $content;
  }

  /**
   * @param array<string, mixed> $context
   * @return array<string, string>
   */
  private function getPersonalizedUrlTokens(array $context): array {
    return [
      '[woocommerce/order-review-url]' => $this->orderReviewUrl->getUrl($context),
    ];
  }

  private function getNormalizedHrefPlaceholder(string $placeholder): string {
    return 'http://' . trim($placeholder, '{}');
  }

  private function containsPersonalizedUrlToken(string $content, string $token): bool {
    return strpos(rawurldecode($content), $token) !== false;
  }
}
