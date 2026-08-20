<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tag;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use Automattic\WooCommerce\EmailEditor\Engine\Personalizer;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;

/**
 * Resolves a link stored symbolically as a personalization tag token (e.g. "[woocommerce/order-review-url]")
 * to the real per-recipient URL.
 *
 * Context-dependent URL tags are tracked this way because their value is only known once the
 * recipient is. Detection is purely syntactic ("[vendor/name]") because some tags are registered
 * only once the recipient's automation subjects are known; only registered tags ever resolve.
 *
 * Bare tokens only: the Personalizer does pass tag arguments to callbacks in links, but its
 * argument parser is private and no URL tag takes arguments today, so a token with arguments
 * is treated as a plain URL here.
 */
class PersonalizationTagLinkResolver {
  /**
   * Matches a whole URL that is a personalization tag token. The character set mirrors the
   * Personalizer's TAG_NAME_PATTERN: both cases accepted, resolution stays an exact registry
   * lookup. The slash is what protects shortcodes from being picked up here: WordPress reserves
   * "/" in shortcode names (add_shortcode() rejects it) and MailPoet shortcodes use the
   * "[category:action]" syntax, so no shortcode can ever look like a tag token.
   */
  public const TOKEN_URL_PATTERN = '/^\[[A-Za-z0-9-]+(?:\/[A-Za-z0-9-]+)+\]$/D';

  private PersonalizationContextBuilder $contextBuilder;

  public function __construct(
    PersonalizationContextBuilder $contextBuilder
  ) {
    $this->contextBuilder = $contextBuilder;
  }

  public function isTokenUrl(string $url): bool {
    return preg_match(self::TOKEN_URL_PATTERN, $url) === 1;
  }

  /**
   * Canonical token for a URL the editor or renderer produced from a tag, e.g. "http://%5Bacme/url%5D"
   * or "&#91;acme/url&#93;"; null when the URL is not a token.
   */
  public static function extractToken(string $url): ?string {
    $decodedUrl = trim(html_entity_decode(rawurldecode($url), ENT_QUOTES, 'UTF-8'));
    // The editor forces a scheme in front of a tag used as the whole URL ("http://[tag]").
    $withoutScheme = (string)preg_replace('#^https?://#', '', $decodedUrl);
    return preg_match(self::TOKEN_URL_PATTERN, $withoutScheme) === 1 ? $withoutScheme : null;
  }

  /**
   * Display name of the tag a token URL belongs to; null when the URL is not a registered token.
   */
  public function getDisplayName(string $url): ?string {
    $tag = $this->getTag($url);
    return $tag ? $tag->get_name() : null;
  }

  /**
   * Returns null when the URL is not a registered token or the tag cannot produce a URL.
   */
  public function resolve(
    string $url,
    NewsletterEntity $newsletter,
    SubscriberEntity $subscriber,
    SendingQueueEntity $queue,
    bool $isPreview = false
  ): ?string {
    if (!$this->isTokenUrl($url)) {
      return null;
    }
    return $this->resolveWithContext($url, $this->contextBuilder->build($newsletter, $subscriber, $queue, $isPreview));
  }

  /**
   * @param array<string, mixed> $context A context produced by PersonalizationContextBuilder, which
   *   also registers the subject-dependent tags the token may belong to.
   */
  public function resolveWithContext(string $url, array $context): ?string {
    $tag = $this->getTag($url);
    if (!$tag) {
      return null;
    }

    // Set the href rendering context, as the Personalizer does when it replaces a link itself.
    $context[Personalizer::RENDERING_CONTEXT_KEY] = Personalizer::RENDERING_CONTEXT_HREF;
    try {
      $resolved = $tag->execute_callback($context);
    } catch (\Throwable $e) {
      return null;
    }
    return $resolved === '' ? null : $resolved;
  }

  /**
   * Resolves token URLs used as markdown link targets in the plain-text body, whether canonical
   * ("[Review]([woocommerce/order-review-url])") or as the renderer emits them ("http://%5B...%5D").
   *
   * The Personalizer resolves tokens only in HTML constructs (comments, anchor hrefs), never in
   * the markdown links of the text body, so the text body needs this extra pass. It runs where
   * tokens can still be in the body: sending with tracking disabled, and send previews. For
   * tracked and opted-out recipients link tracking already replaced them, and this is a no-op.
   * Unresolvable tokens are replaced by an empty target rather than shipping as literal text.
   *
   * @param array<string, mixed> $context
   */
  public function resolveMarkdownLinks(string $text, array $context): string {
    return (string)preg_replace_callback(
      '/\]\(([^)\s]+)\)/',
      function (array $matches) use ($context): string {
        $token = self::extractToken($matches[1]);
        if ($token === null) {
          return $matches[0];
        }
        return '](' . ($this->resolveWithContext($token, $context) ?? '') . ')';
      },
      $text
    );
  }

  private function getTag(string $url): ?Personalization_Tag {
    if (!$this->isTokenUrl($url)) {
      return null;
    }
    return $this->getRegistry()->get_by_token($url);
  }

  private function getRegistry(): Personalization_Tags_Registry {
    return Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
  }
}
