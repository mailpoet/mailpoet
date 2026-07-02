<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Html2Text;

class PlaceholderCollector {
  public const PART_SUBJECT = 'subject';
  public const PART_HTML = 'html';
  public const PART_TEXT = 'text';

  /** @var array<string, array<string, string>> */
  private array $values = [
    self::PART_SUBJECT => [],
    self::PART_HTML => [],
    self::PART_TEXT => [],
  ];

  /** @var array<string, array<string, string>> */
  private array $placeholdersByValue = [
    self::PART_SUBJECT => [],
    self::PART_HTML => [],
    self::PART_TEXT => [],
  ];

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

  public function add(string $value): string {
    return $this->addText($value);
  }

  public function addSubjectText(string $value): string {
    return $this->addToPart(self::PART_SUBJECT, $this->htmlToText($value));
  }

  public function addHtmlText(string $value): string {
    return $this->addToPart(self::PART_HTML, $value);
  }

  public function addHtmlUrl(string $value): string {
    return $this->addToPart(self::PART_HTML, esc_url($value));
  }

  public function addText(string $value): string {
    return $this->addToPart(self::PART_TEXT, $value);
  }

  public function addTextFromHtml(string $value): string {
    return $this->addToPart(self::PART_TEXT, $this->htmlToText($value));
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

  private function addToPart(string $part, string $value): string {
    $existing = $this->placeholdersByValue[$part][$value] ?? null;
    if ($existing !== null) {
      return $existing;
    }
    $placeholder = '{{mailpoet_mss_' . $this->namespace . '_' . (++$this->counter) . '}}';
    $this->values[$part][$placeholder] = $value;
    $this->placeholdersByValue[$part][$value] = $placeholder;
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
