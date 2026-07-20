<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Html2Text;

class PlaceholderCollector {
  public const PART_SUBJECT = 'subject';
  public const PART_HTML = 'html';
  public const PART_TEXT = 'text';

  private const CONTEXT_SUBJECT_TEXT = 'subject_text';
  private const CONTEXT_HTML_TEXT = 'html_text';
  private const CONTEXT_HTML_URL = 'html_url';
  private const CONTEXT_TEXT = 'text';
  private const CONTEXT_TEXT_FROM_HTML = 'text_from_html';

  /** @var array<string, array<string, string>> */
  private array $values = [
    self::PART_SUBJECT => [],
    self::PART_HTML => [],
    self::PART_TEXT => [],
  ];

  /**
   * Maps a dedupe key to the placeholder already issued for it. The key is the
   * escaping context plus the source token, so occurrences of the same token in
   * the same context reuse one placeholder, while distinct tokens (or the same
   * token in a different escaping context) always get their own. Keying on the
   * token instead of the resolved value keeps the generated template identical
   * across subscribers even when two tags resolve to the same value.
   *
   * @var array<string, string>
   */
  private array $placeholdersByKey = [];

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

  public function add(string $value, ?string $token = null): string {
    return $this->addText($value, $token);
  }

  public function addSubjectText(string $value, ?string $token = null): string {
    return $this->addToPart(self::PART_SUBJECT, self::CONTEXT_SUBJECT_TEXT, $this->htmlToText($value), $token);
  }

  public function addHtmlText(string $value, ?string $token = null): string {
    return $this->addToPart(self::PART_HTML, self::CONTEXT_HTML_TEXT, $value, $token);
  }

  public function addHtmlUrl(string $value, ?string $token = null): string {
    return $this->addToPart(self::PART_HTML, self::CONTEXT_HTML_URL, esc_url($value), $token);
  }

  public function addText(string $value, ?string $token = null): string {
    return $this->addToPart(self::PART_TEXT, self::CONTEXT_TEXT, $value, $token);
  }

  public function addTextFromHtml(string $value, ?string $token = null): string {
    return $this->addToPart(self::PART_TEXT, self::CONTEXT_TEXT_FROM_HTML, $this->htmlToText($value), $token);
  }

  /**
   * @return array{subject: array<string, string>, html: array<string, string>, text: array<string, string>}
   */
  public function getValues(): array {
    return [
      self::PART_SUBJECT => $this->values[self::PART_SUBJECT] ?? [],
      self::PART_HTML => $this->values[self::PART_HTML] ?? [],
      self::PART_TEXT => $this->values[self::PART_TEXT] ?? [],
    ];
  }

  private function addToPart(string $part, string $context, string $value, ?string $token): string {
    $dedupeKey = $context . "\0" . ($token ?? $value);
    $existing = $this->placeholdersByKey[$dedupeKey] ?? null;
    if ($existing !== null) {
      return $existing;
    }
    $placeholder = '{{mailpoet_mss_' . $this->namespace . '_' . (++$this->counter) . '}}';
    $this->values[$part][$placeholder] = $value;
    $this->placeholdersByKey[$dedupeKey] = $placeholder;
    return $placeholder;
  }

  private function htmlToText(string $value): string {
    if (!mb_detect_encoding($value, 'UTF-8', true)) {
      $converted = mb_convert_encoding($value, 'UTF-8', mb_list_encodings());
      $value = $converted !== false ? $converted : $value;
    }
    return @Html2Text::convert($value);
  }
}
