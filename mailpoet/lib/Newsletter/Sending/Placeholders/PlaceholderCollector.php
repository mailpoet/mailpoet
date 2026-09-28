<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Html2Text;

/**
 * Issues `{{mp_mss_<namespace>_<n>}}` placeholders for the per-recipient parts of an email
 * and records, per email part, the value each placeholder stands for.
 *
 * A batch shares one template, so the template must come out byte-identical for every
 * recipient: placeholders are deduplicated by escaping context and source token only, never
 * by resolved value.
 */
class PlaceholderCollector {
  public const PART_SUBJECT = 'subject';
  public const PART_HTML = 'html';
  public const PART_TEXT = 'text';

  private const CONTEXT_SUBJECT_TEXT = 'subject_text';
  private const CONTEXT_SUBJECT_TEXT_FROM_HTML = 'subject_text_from_html';
  private const CONTEXT_HTML = 'html';
  private const CONTEXT_HTML_PLAIN_TEXT = 'html_plain_text';
  private const CONTEXT_HTML_URL = 'html_url';
  private const CONTEXT_HTML_URL_COMPONENT = 'html_url_component';
  private const CONTEXT_TEXT = 'text';
  private const CONTEXT_TEXT_URL = 'text_url';
  private const CONTEXT_TEXT_FROM_HTML = 'text_from_html';

  // esc_url() prepends a scheme to anything without one, which would turn a URL component such
  // as an email address into "http://john@example.com". Escaping the value behind a throwaway
  // absolute URL applies only the character-level rules. The .invalid TLD never resolves (RFC 2606).
  private const URL_COMPONENT_ESCAPING_PREFIX = 'http://mailpoet.invalid/';

  /** @var array<string, array<string, string>> */
  private array $values = [
    self::PART_SUBJECT => [],
    self::PART_HTML => [],
    self::PART_TEXT => [],
  ];

  /**
   * Maps a dedupe key (escaping context + source token) to the placeholder already issued for it,
   * so occurrences of the same token in the same context reuse one placeholder while distinct
   * tokens, or the same token in another context, always get their own.
   *
   * @var array<string, string>
   */
  private array $placeholdersByKey = [];

  /** @var array<string, array{value: string, token: string}> Unescaped URL and token by placeholder, kept so the escaping can change later */
  private array $rawUrls = [];

  /** @var array<string, true> URL placeholders that were re-escaped as the start of a whole href */
  private array $wholeUrlPlaceholders = [];

  private string $namespace;
  private int $counter = 0;

  public function __construct(
    ?string $namespace = null
  ) {
    $this->namespace = $namespace ?? self::generateNamespace();
  }

  public static function generateNamespace(): string {
    return bin2hex(random_bytes(8));
  }

  /** The placeholder without its braces, e.g. "mp_mss_<namespace>_", followed by the index. */
  public function getPlaceholderPrefix(): string {
    return 'mp_mss_' . $this->namespace . '_';
  }

  public function makePlaceholder(int $index): string {
    return '{{' . $this->getPlaceholderPrefix() . $index . '}}';
  }

  /** Plain text for the subject. */
  public function addSubjectText(string $value, string $token): string {
    return $this->addToPart(self::PART_SUBJECT, self::CONTEXT_SUBJECT_TEXT, $value, $token);
  }

  /** An HTML fragment, such as a legacy shortcode value, converted to plain text for the subject. */
  public function addSubjectTextFromHtml(string $value, string $token): string {
    return $this->findPlaceholder(self::CONTEXT_SUBJECT_TEXT_FROM_HTML, $token)
      ?? $this->addToPart(self::PART_SUBJECT, self::CONTEXT_SUBJECT_TEXT_FROM_HTML, $this->htmlToText($value), $token);
  }

  /** Markup, or text already escaped for markup, inserted into the HTML body as is. */
  public function addHtml(string $value, string $token): string {
    return $this->addToPart(self::PART_HTML, self::CONTEXT_HTML, $value, $token);
  }

  /**
   * Plain text for the <title> of the HTML body, escaped the way WP_HTML_Tag_Processor writes
   * title text: only a closing title tag is neutralized, so nothing can break out of the element.
   * The guarantee holds per value; the rendered path applies it to the whole title text.
   */
  public function addHtmlPlainText(string $value, string $token): string {
    $value = (string)preg_replace('~</(title)~i', '&lt;/$1', $value);
    return $this->addToPart(self::PART_HTML, self::CONTEXT_HTML_PLAIN_TEXT, $value, $token);
  }

  /**
   * A URL, or a component of one, for an href in the HTML body. It is escaped as a URL
   * component; call useFullUrlEscaping() once the placeholder turns out to be the whole href.
   */
  public function addHtmlUrl(string $value, string $token): string {
    $placeholder = $this->addToPart(self::PART_HTML, self::CONTEXT_HTML_URL, $this->escapeUrlComponent($value), $token);
    if (!isset($this->rawUrls[$placeholder])) {
      $this->rawUrls[$placeholder] = ['value' => $value, 'token' => $token];
    }
    return $placeholder;
  }

  /**
   * Re-escapes a URL placeholder that opens its href the way esc_url() treats the start of a whole
   * href: a disallowed scheme, obfuscated or not, empties it, and when the href has no colon at all
   * esc_url() also falls back to "http://". Any other use of the same token as a URL component
   * must then go through getUrlComponentPlaceholder().
   *
   * @param bool $schemeAdded Whether esc_url() prepended "http://" to the href in the template
   */
  public function useFullUrlEscaping(string $placeholder, bool $schemeAdded = true): void {
    if (!isset($this->rawUrls[$placeholder])) {
      return;
    }
    $raw = $this->rawUrls[$placeholder]['value'];
    if ($schemeAdded) {
      $escaped = esc_url($raw);
    } else {
      $goodProtocolUrl = wp_kses_bad_protocol($raw, wp_allowed_protocols());
      $escaped = strtolower($goodProtocolUrl) === strtolower($raw) ? $this->escapeUrlComponent($raw) : '';
    }
    $this->values[self::PART_HTML][$placeholder] = $escaped;
    $this->wholeUrlPlaceholders[$placeholder] = true;
  }

  /**
   * The placeholder to use where a URL placeholder sits inside a longer href: the placeholder
   * itself, unless it was re-escaped as a whole href, in which case a separate one carries the
   * component escaping so both uses stay correct.
   */
  public function getUrlComponentPlaceholder(string $placeholder): string {
    if (!isset($this->wholeUrlPlaceholders[$placeholder])) {
      return $placeholder;
    }
    $raw = $this->rawUrls[$placeholder];
    return $this->addToPart(self::PART_HTML, self::CONTEXT_HTML_URL_COMPONENT, $this->escapeUrlComponent($raw['value']), $raw['token']);
  }

  /** Plain text for the text body. */
  public function addText(string $value, string $token): string {
    return $this->addToPart(self::PART_TEXT, self::CONTEXT_TEXT, $value, $token);
  }

  /** A link target in the text body; kept apart from the same token's visible text. */
  public function addTextUrl(string $value, string $token): string {
    return $this->addToPart(self::PART_TEXT, self::CONTEXT_TEXT_URL, $value, $token);
  }

  /** An HTML fragment, such as a legacy shortcode value, converted to plain text for the text body. */
  public function addTextFromHtml(string $value, string $token): string {
    return $this->findPlaceholder(self::CONTEXT_TEXT_FROM_HTML, $token)
      ?? $this->addToPart(self::PART_TEXT, self::CONTEXT_TEXT_FROM_HTML, $this->htmlToText($value), $token);
  }

  /**
   * @return array{subject: array<string, string>, html: array<string, string>, text: array<string, string>}
   */
  public function getValues(): array {
    return [
      self::PART_SUBJECT => $this->values[self::PART_SUBJECT],
      self::PART_HTML => $this->values[self::PART_HTML],
      self::PART_TEXT => $this->values[self::PART_TEXT],
    ];
  }

  private function addToPart(string $part, string $escapingContext, string $value, string $token): string {
    $existing = $this->findPlaceholder($escapingContext, $token);
    if ($existing !== null) {
      return $existing;
    }
    $placeholder = $this->makePlaceholder(++$this->counter);
    $this->values[$part][$placeholder] = $value;
    $this->placeholdersByKey[$this->dedupeKey($escapingContext, $token)] = $placeholder;
    return $placeholder;
  }

  private function findPlaceholder(string $escapingContext, string $token): ?string {
    return $this->placeholdersByKey[$this->dedupeKey($escapingContext, $token)] ?? null;
  }

  private function dedupeKey(string $escapingContext, string $token): string {
    return $escapingContext . "\0" . $token;
  }

  private function escapeUrlComponent(string $value): string {
    $escaped = esc_url(self::URL_COMPONENT_ESCAPING_PREFIX . $value);
    if (strpos($escaped, self::URL_COMPONENT_ESCAPING_PREFIX) !== 0) {
      return $escaped;
    }
    return substr($escaped, strlen(self::URL_COMPONENT_ESCAPING_PREFIX));
  }

  private function htmlToText(string $value): string {
    if (!mb_detect_encoding($value, 'UTF-8', true)) {
      $converted = mb_convert_encoding($value, 'UTF-8', mb_list_encodings());
      $value = $converted !== false ? $converted : $value;
    }
    return @Html2Text::convert($value);
  }
}
