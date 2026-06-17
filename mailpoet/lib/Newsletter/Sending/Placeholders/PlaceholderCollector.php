<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending\Placeholders;

class PlaceholderCollector {
  /** @var array<string, string> */
  private array $values = [];

  public function add(string $value): string {
    $placeholder = '{{mailpoet_mss_' . (count($this->values) + 1) . '}}';
    $this->values[$placeholder] = $value;
    return $placeholder;
  }

  public function addHtmlText(string $value): string {
    return $this->add($value);
  }

  public function addHtmlUrl(string $value): string {
    return $this->add(esc_url($value));
  }

  /**
   * @return array<string, string>
   */
  public function getValues(): array {
    return $this->values;
  }
}
