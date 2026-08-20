<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\HTML_Tag_Processor;

/**
 * Prepares links whose URL is a personalization tag token for link tracking.
 *
 * Link tracking hashes hrefs before the email is personalized, so a token left in an href
 * would be stored and tracked as a dead URL. Before hashing, this normalizer:
 *  - replaces context-free URL tokens (homepage, store, ...) with their resolved URL, and
 *  - rewrites every other token — whether it sits in `href`, in the editor's `data-link-href`,
 *    or as a plain-text markdown link target — to its canonical `[token]` form, so that link
 *    tracking stores the token symbolically and PersonalizationTagLinkResolver resolves it per
 *    recipient (at click time, or at send time for recipients who are not tracked).
 *
 * Token detection is syntactic (PersonalizationTagLinkResolver::extractToken()): some tags are
 * registered only once the recipient's automation subjects are known, which is after tracking.
 */
class PersonalizationTagLinkNormalizer {
  /**
   * @param array<string, string> $emailContent rendered email parts keyed by "html" and "text"
   * @param array<string, string> $resolvedUrlTokens token => URL for tokens resolved before tracking
   * @return array<string, string>
   */
  public function normalize(array $emailContent, array $resolvedUrlTokens): array {
    if (isset($emailContent['html'])) {
      $emailContent['html'] = $this->normalizeHtml($emailContent['html'], $resolvedUrlTokens);
    }
    if (isset($emailContent['text'])) {
      $emailContent['text'] = $this->normalizeText($emailContent['text'], $resolvedUrlTokens);
    }
    return $emailContent;
  }

  /**
   * @param array<string, string> $resolvedUrlTokens
   */
  public function normalizeHtml(string $html, array $resolvedUrlTokens): string {
    $placeholders = [];
    $contentProcessor = new HTML_Tag_Processor($html);
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

      $newHref = $this->getNormalizedUrl($href, $resolvedUrlTokens);
      if ($newHref === null) {
        continue;
      }
      if ($newHref[0] === '[') {
        // HTML_Tag_Processor runs href values through esc_url(), which prepends a scheme to a bare
        // token and percent-encodes its brackets, so a URL-shaped placeholder is written now and
        // swapped for the token once the HTML is updated. The .invalid TLD is reserved and never
        // resolves (RFC 2606), so the placeholder cannot collide with a real URL in the content.
        $placeholder = 'http://mailpoet-tag-' . count($placeholders) . '.invalid';
        $placeholders[$placeholder] = $newHref;
        $newHref = $placeholder;
      }
      $contentProcessor->set_attribute('href', $newHref);
      $contentProcessor->remove_attribute('data-link-href');
      $contentProcessor->remove_attribute('contenteditable');
    }
    $contentProcessor->flush_updates();
    return strtr($contentProcessor->get_updated_html(), $placeholders);
  }

  /**
   * Normalizes markdown link targets, e.g. "[Shop](http://%5Bwoocommerce/store-url%5D)".
   *
   * @param array<string, string> $resolvedUrlTokens
   */
  public function normalizeText(string $text, array $resolvedUrlTokens): string {
    return (string)preg_replace_callback(
      '/\]\(([^)\s]+)\)/',
      function (array $matches) use ($resolvedUrlTokens): string {
        $newUrl = $this->getNormalizedUrl($matches[1], $resolvedUrlTokens);
        return $newUrl === null ? $matches[0] : '](' . $newUrl . ')';
      },
      $text
    );
  }

  /**
   * @param array<string, string> $resolvedUrlTokens
   * @return string|null Resolved URL, canonical token, or null when the URL is not a token
   */
  private function getNormalizedUrl(string $url, array $resolvedUrlTokens): ?string {
    $token = PersonalizationTagLinkResolver::extractToken($url);
    if ($token === null) {
      return null;
    }
    return $resolvedUrlTokens[$token] ?? $token;
  }
}
