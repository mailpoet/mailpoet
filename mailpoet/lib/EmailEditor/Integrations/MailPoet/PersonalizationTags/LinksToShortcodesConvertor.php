<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\HTML_Tag_Processor;

/**
 * Converts link tags to shortcodes.
 *
 * This is a temporary solution so that we are able to integrate the new personalization tags
 * with the MailPoet Link tracking system which is based on shortcodes.
 *
 */
class LinksToShortcodesConvertor {
  private const TOKEN_MAP = [
    '[mailpoet/subscription-unsubscribe-url]' => '[link:subscription_unsubscribe_url]',
    '[mailpoet/subscription-manage-url]' => '[link:subscription_manage_url]',
    '[mailpoet/newsletter-view-in-browser-url]' => '[link:newsletter_view_in_browser_url]',
  ];

  private const PERSONALIZED_URL_TOKENS = [
    '[woocommerce/order-review-url]' => true,
  ];

  /**
   * @param array<string, string> $resolvedUrlTokens
   */
  public function convertLinkTagsToShortcodes(string $content, array $resolvedUrlTokens = []): string {
    $contentProcessor = new HTML_Tag_Processor($content);
    while ($contentProcessor->next_token()) {
      if ($contentProcessor->get_token_type() !== '#tag' || $contentProcessor->get_tag() !== 'A') {
        continue;
      }

      $href = $contentProcessor->get_attribute('data-link-href');
      if (!is_string($href)) {
        $href = $contentProcessor->get_attribute('href');
      }
      if (!is_string($href)) {
        continue;
      }

      $this->convertLinkToken($contentProcessor, $href, $resolvedUrlTokens);
    }
    $contentProcessor->flush_updates();
    $updated = $contentProcessor->get_updated_html();
    // Remove the temporary prefix needed for HTML_Tag_Processor href updates.
    foreach (self::TOKEN_MAP as $shortcode) {
      $updated = str_replace('http://' . $shortcode, $shortcode, $updated);
    }
    return $updated;
  }

  /**
   * @param array<string, string> $personalizedUrlTokens
   */
  public function restorePersonalizedLinkHrefs(string $content, array $personalizedUrlTokens = []): string {
    $contentProcessor = new HTML_Tag_Processor($content);
    while ($contentProcessor->next_token()) {
      if ($contentProcessor->get_token_type() !== '#tag' || $contentProcessor->get_tag() !== 'A') {
        continue;
      }

      $href = $contentProcessor->get_attribute('data-link-href');
      if (!is_string($href)) {
        continue;
      }

      if ($href === '') {
        $contentProcessor->remove_attribute('href');
        $contentProcessor->remove_attribute('data-link-href');
        $contentProcessor->remove_attribute('contenteditable');
        continue;
      }

      $personalizedUrlToken = $this->normalizePersonalizedUrlToken($href);
      if ($personalizedUrlToken !== null) {
        $resolvedHref = $personalizedUrlTokens[$personalizedUrlToken] ?? '';
        if ($resolvedHref === '') {
          $contentProcessor->remove_attribute('href');
          $contentProcessor->remove_attribute('data-link-href');
          $contentProcessor->remove_attribute('contenteditable');
          continue;
        }
        $contentProcessor->set_attribute('href', $resolvedHref);
        $contentProcessor->remove_attribute('data-link-href');
        $contentProcessor->remove_attribute('contenteditable');
        continue;
      }

      $contentProcessor->set_attribute('href', $href);
      $contentProcessor->remove_attribute('data-link-href');
      $contentProcessor->remove_attribute('contenteditable');
    }
    $contentProcessor->flush_updates();
    return $contentProcessor->get_updated_html();
  }

  /**
   * @param array<string, string> $personalizedUrlTokens
   */
  public function restorePersonalizedLinkUrls(string $content, array $personalizedUrlTokens = []): string {
    foreach ($personalizedUrlTokens as $token => $resolvedUrl) {
      $variants = $this->getPersonalizedUrlTokenVariants($token);
      usort($variants, function(string $a, string $b): int {
        return strlen($b) <=> strlen($a);
      });
      $content = str_replace($variants, $resolvedUrl, $content);
    }
    return $content;
  }

  /**
   * @param array<string, string> $resolvedUrlTokens
   */
  private function convertLinkToken(HTML_Tag_Processor $contentProcessor, string $href, array $resolvedUrlTokens): void {
    $shortcode = $this->getShortcodeForUrlToken($href);
    if ($shortcode !== null) {
      $contentProcessor->set_attribute('href', 'http://' . $shortcode);
      $contentProcessor->remove_attribute('data-link-href');
      $contentProcessor->remove_attribute('contenteditable');
      return;
    }

    $personalizedUrlToken = $this->normalizePersonalizedUrlToken($href);
    if ($personalizedUrlToken !== null) {
      $contentProcessor->set_attribute('data-link-href', $personalizedUrlToken);
      $contentProcessor->remove_attribute('href');
      return;
    }

    $resolvedUrl = $this->getResolvedUrlToken($href, $resolvedUrlTokens);
    if ($resolvedUrl !== null) {
      $contentProcessor->set_attribute('href', $resolvedUrl);
      $contentProcessor->remove_attribute('data-link-href');
      $contentProcessor->remove_attribute('contenteditable');
    }
  }

  private function normalizePersonalizedUrlToken(string $url): ?string {
    return $this->normalizeUrlToken($url, array_keys(self::PERSONALIZED_URL_TOKENS));
  }

  private function getShortcodeForUrlToken(string $url): ?string {
    $token = $this->normalizeUrlToken($url, array_keys(self::TOKEN_MAP));
    if ($token === null) {
      return null;
    }
    return self::TOKEN_MAP[$token];
  }

  /**
   * @param array<string, string> $resolvedUrlTokens
   */
  private function getResolvedUrlToken(string $url, array $resolvedUrlTokens): ?string {
    $token = $this->normalizeUrlToken($url, array_keys($resolvedUrlTokens));
    if ($token === null) {
      return null;
    }

    $resolvedUrl = $resolvedUrlTokens[$token];
    return $resolvedUrl === '' ? null : $resolvedUrl;
  }

  /**
   * @param string[] $tokens
   */
  private function normalizeUrlToken(string $url, array $tokens): ?string {
    $decodedUrl = trim($this->decodeUrl($url));
    foreach ($tokens as $token) {
      if ($decodedUrl === $token || $decodedUrl === 'http://' . $token || $decodedUrl === 'https://' . $token) {
        return $token;
      }
    }

    return null;
  }

  private function decodeUrl(string $url): string {
    return html_entity_decode(rawurldecode($url), ENT_QUOTES, 'UTF-8');
  }

  /**
   * @return string[]
   */
  private function getPersonalizedUrlTokenVariants(string $token): array {
    $encodedBracketsToken = str_replace(['[', ']'], ['%5B', '%5D'], $token);
    $closingBracketEncodedToken = str_replace(']', '%5D', $token);

    $tokens = [
      $token,
      $encodedBracketsToken,
      $closingBracketEncodedToken,
    ];

    $variants = [];
    foreach ($tokens as $tokenVariant) {
      $variants[] = $tokenVariant;
      $variants[] = 'http://' . $tokenVariant;
      $variants[] = 'https://' . $tokenVariant;
    }

    return array_values(array_unique($variants));
  }
}
