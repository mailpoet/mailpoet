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

  /** The same token syntax as TOKEN_URL_PATTERN, found anywhere inside a longer URL. */
  private const EMBEDDED_TOKEN_PATTERN = '/\[[A-Za-z0-9-]+(?:\/[A-Za-z0-9-]+)+\]/';

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
    return $tag ? $this->executeTag($tag, $context) : null;
  }

  /**
   * Resolves tag tokens in the markdown link targets of the plain-text body: targets that are a whole
   * token, canonical ("[Review]([woocommerce/order-review-url])") or as the renderer emits them
   * ("http://%5B...%5D"), and tokens embedded in a longer target
   * ("[Profile](https://example.com/?email=[mailpoet/subscriber-email])").
   *
   * The Personalizer resolves tokens only in HTML constructs (comments, anchor hrefs), never in the
   * markdown links of the text body, so every target that can still carry a token when this runs is
   * covered here: links that were not hashed (tracking disabled), links restored for recipients
   * without tracking consent, and send previews. A whole token that does not resolve becomes an
   * empty target rather than shipping as literal text; an embedded one stays literal.
   *
   * @param array<string, mixed> $context
   * @param (callable(string, string): string)|null $urlInterceptor Receives the resolved URL or token value
   *   (empty when nothing resolved) and its token, and returns what to write instead, e.g. a placeholder
   *   standing for the value. For a whole-token target it runs for every token, for an embedded one for
   *   every registered token, so that the output does not depend on the recipient's data.
   */
  public function resolveMarkdownLinks(string $text, array $context, ?callable $urlInterceptor = null): string {
    return (string)preg_replace_callback(
      '/\]\(([^)\s]+)\)/',
      function (array $matches) use ($context, $urlInterceptor): string {
        $token = self::extractToken($matches[1]);
        if ($token === null) {
          return '](' . $this->resolveEmbeddedTokens($matches[1], $context, $urlInterceptor) . ')';
        }
        $url = $this->resolveWithContext($token, $context) ?? '';
        if ($urlInterceptor !== null) {
          $url = $urlInterceptor($url, $token);
        }
        return '](' . $url . ')';
      },
      $text
    );
  }

  /**
   * Resolves tag tokens embedded in a longer URL ("https://example.com/?email=[mailpoet/subscriber-email]")
   * the way the Personalizer does for the hrefs it processes itself. Tokens of unregistered tags stay as
   * they are, and so do tokens that resolve to nothing unless an interceptor is given. The URL comes back
   * unchanged when it has no token.
   *
   * With an interceptor every registered token is replaced, so the base choice and the forced-scheme
   * drop below depend on the URL and the registry only, never on a recipient's value: a recipient whose
   * tag resolves to nothing gets the scheme dropped or an encoded base decoded where the call without
   * an interceptor keeps the URL literal.
   *
   * @param array<string, mixed> $context A context produced by PersonalizationContextBuilder
   * @param (callable(string, string): string)|null $valueInterceptor Runs once per registered token, as the
   *   Personalizer's interceptor does for hrefs: receives the resolved value (empty when the tag produced
   *   nothing) and the canonical token, and returns what replaces the token. It must return a non-empty
   *   string, e.g. a placeholder; an empty return leaves the token literal.
   */
  public function resolveEmbeddedTokens(string $url, array $context, ?callable $valueInterceptor = null): string {
    // Tokens may arrive URL-encoded ("%5B...%5D") or as HTML entities, so they are matched in the decoded form.
    $decodedUrl = html_entity_decode(urldecode($url), ENT_QUOTES, 'UTF-8');
    if (!preg_match_all(self::EMBEDDED_TOKEN_PATTERN, $decodedUrl, $matches)) {
      return $url;
    }
    $replacements = [];
    foreach (array_unique($matches[0]) as $token) {
      $tag = $this->getTag($token);
      if (!$tag) {
        continue;
      }
      $value = $this->executeTag($tag, $context) ?? '';
      if ($valueInterceptor !== null) {
        $value = $valueInterceptor($value, $token);
      }
      if ($value !== '') {
        $replacements[$token] = $value;
      }
    }
    if (!$replacements) {
      return $url;
    }
    // The original URL keeps its percent-encoding, so it is preferred as the base; the decoded form is
    // used only when a replaced token exists just there (an URL-encoded token).
    $base = $url;
    foreach (array_keys($replacements) as $token) {
      if (substr_count($url, $token) !== substr_count($decodedUrl, $token)) {
        $base = $decodedUrl;
        break;
      }
    }
    // The editor forces a scheme in front of a tag that starts the URL ("http://[tag]?x=1"); the tag
    // value brings its own, so the forced one is dropped when that token is replaced.
    if (preg_match('#^https?://(\[[A-Za-z0-9-]+(?:/[A-Za-z0-9-]+)+\])#i', $base, $prefixMatch) && isset($replacements[$prefixMatch[1]])) {
      $base = (string)preg_replace('#^https?://#i', '', $base);
    }
    // Single pass: a tag value is never scanned for other tokens, and strtr() treats it literally.
    return strtr($base, $replacements);
  }

  /**
   * The tag's value for this recipient; null when the tag produces nothing or fails.
   *
   * @param array<string, mixed> $context
   */
  private function executeTag(Personalization_Tag $tag, array $context): ?string {
    // Set the href rendering context, as the Personalizer does when it replaces a link itself.
    $context[Personalizer::RENDERING_CONTEXT_KEY] = Personalizer::RENDERING_CONTEXT_HREF;
    try {
      $resolved = $tag->execute_callback($context);
    } catch (\Throwable $e) {
      return null;
    }
    return $resolved === '' ? null : $resolved;
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
