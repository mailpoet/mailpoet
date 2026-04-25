<?php declare(strict_types = 1);

namespace MailPoet\Test\DataGenerator\Generators;

class SampleDataConfig {
  public const CLI_OPTIONS_DEFAULTS = [
    'preset' => null,
    'lists' => null,
    'dynamic-segments' => null,
    'subscribers' => null,
    'products' => null,
    'draft-newsletters' => null,
    'sent-newsletters' => null,
    'post-notifications' => null,
    'automatic-emails' => null,
    'automations' => null,
    'automation-runs' => null,
    'days-min' => null,
    'days-max' => null,
    'open-rate' => null,
    'click-rate' => null,
    'purchase-rate' => null,
    'orders-min' => null,
    'orders-max' => null,
    'email-domain' => null,
    'prefix' => null,
    'woocommerce' => null,
    'welcome-emails' => null,
  ];

  private const PRESET_DEFAULT = [
    'lists' => 5,
    'dynamic-segments' => 3,
    'subscribers' => 500,
    'products' => 10,
    'draft-newsletters' => 5,
    'sent-newsletters' => 30,
    'post-notifications' => 6,
    'automatic-emails' => 5,
    'automations' => 3,
    'automation-runs' => 75,
    'days-min' => 1,
    'days-max' => 180,
    'open-rate' => 0.35,
    'click-rate' => 0.2,
    'purchase-rate' => 0.3,
    'orders-min' => 1,
    'orders-max' => 3,
    'email-domain' => 'example.com',
    'prefix' => 'Sample data',
    'woocommerce' => true,
    'welcome-emails' => true,
  ];

  private const PRESET_SMALL = [
    'lists' => 3,
    'dynamic-segments' => 1,
    'subscribers' => 50,
    'products' => 5,
    'draft-newsletters' => 2,
    'sent-newsletters' => 5,
    'post-notifications' => 2,
    'automatic-emails' => 2,
    'automations' => 1,
    'automation-runs' => 10,
  ];

  private const PRESET_LARGE = [
    'lists' => 8,
    'dynamic-segments' => 4,
    'subscribers' => 2000,
    'products' => 30,
    'draft-newsletters' => 10,
    'sent-newsletters' => 80,
    'post-notifications' => 20,
    'automatic-emails' => 8,
    'automations' => 5,
    'automation-runs' => 300,
  ];

  /** @var array<string, mixed> */
  private $options;

  /**
   * @param array<string, mixed> $options
   */
  private function __construct(
    array $options
  ) {
    $this->options = $options;
    $this->validate();
  }

  /**
   * @param array<string, mixed> $options
   */
  public static function fromArray(array $options = []): self {
    $presetOption = $options['preset'] ?? 'default';
    $preset = strtolower((string)($presetOption === '' ? 'default' : $presetOption));
    $values = array_merge(self::PRESET_DEFAULT, self::getPresetOverrides($preset));

    foreach ($values as $key => $default) {
      if (!array_key_exists($key, $options) || $options[$key] === null || $options[$key] === '') {
        continue;
      }
      if (is_bool($default)) {
        $values[$key] = self::normalizeBoolean($options[$key], $key);
      } elseif (is_int($default)) {
        $values[$key] = self::normalizeInt($options[$key], $key);
      } elseif (is_float($default)) {
        $values[$key] = self::normalizeFloat($options[$key], $key);
      } else {
        $values[$key] = trim((string)$options[$key]);
      }
    }

    return new self($values);
  }

  public function getListsCount(): int {
    return $this->getInt('lists');
  }

  public function getDynamicSegmentsCount(): int {
    return $this->getInt('dynamic-segments');
  }

  public function getSubscribersCount(): int {
    return $this->getInt('subscribers');
  }

  public function getProductsCount(): int {
    return $this->getInt('products');
  }

  public function getDraftNewslettersCount(): int {
    return $this->getInt('draft-newsletters');
  }

  public function getSentNewslettersCount(): int {
    return $this->getInt('sent-newsletters');
  }

  public function getPostNotificationsCount(): int {
    return $this->getInt('post-notifications');
  }

  public function getAutomaticEmailsCount(): int {
    return $this->getInt('automatic-emails');
  }

  public function getAutomationsCount(): int {
    return $this->getInt('automations');
  }

  public function getAutomationRunsCount(): int {
    return $this->getInt('automation-runs');
  }

  public function getMinDaysAgo(): int {
    return $this->getInt('days-min');
  }

  public function getMaxDaysAgo(): int {
    return $this->getInt('days-max');
  }

  public function getOpenRate(): float {
    return $this->getFloat('open-rate');
  }

  public function getClickRate(): float {
    return $this->getFloat('click-rate');
  }

  public function getPurchaseRate(): float {
    return $this->getFloat('purchase-rate');
  }

  public function getMinOrdersPerBuyer(): int {
    return $this->getInt('orders-min');
  }

  public function getMaxOrdersPerBuyer(): int {
    return $this->getInt('orders-max');
  }

  public function getEmailDomain(): string {
    return ltrim($this->getString('email-domain'), '@') ?: self::PRESET_DEFAULT['email-domain'];
  }

  public function getPrefix(): string {
    return $this->getString('prefix') ?: self::PRESET_DEFAULT['prefix'];
  }

  public function shouldGenerateWooCommerceData(): bool {
    return $this->getBool('woocommerce');
  }

  public function shouldGenerateWelcomeEmails(): bool {
    return $this->getBool('welcome-emails');
  }

  private function validate(): void {
    $nonNegativeIntegerOptions = [
      'lists',
      'dynamic-segments',
      'subscribers',
      'products',
      'draft-newsletters',
      'sent-newsletters',
      'post-notifications',
      'automatic-emails',
      'automations',
      'automation-runs',
      'days-min',
      'days-max',
      'orders-min',
      'orders-max',
    ];
    foreach ($nonNegativeIntegerOptions as $key) {
      if ($this->getInt($key) < 0) {
        throw new \InvalidArgumentException("Option '$key' must be zero or greater.");
      }
    }

    foreach (['open-rate', 'click-rate', 'purchase-rate'] as $key) {
      $rate = $this->getFloat($key);
      if ($rate < 0 || $rate > 1) {
        throw new \InvalidArgumentException("Option '$key' must be between 0 and 1.");
      }
    }

    if ($this->getMinDaysAgo() > $this->getMaxDaysAgo()) {
      throw new \InvalidArgumentException("Option 'days-min' cannot be greater than 'days-max'.");
    }

    if ($this->getMinOrdersPerBuyer() > $this->getMaxOrdersPerBuyer()) {
      throw new \InvalidArgumentException("Option 'orders-min' cannot be greater than 'orders-max'.");
    }

    if ($this->getListsCount() === 0 && $this->getSubscribersCount() > 0) {
      throw new \InvalidArgumentException("Option 'lists' must be greater than zero when subscribers are generated.");
    }
  }

  /**
   * @return array<string, mixed>
   */
  private static function getPresetOverrides(string $preset): array {
    switch ($preset) {
      case 'default':
        return [];
      case 'small':
        return self::PRESET_SMALL;
      case 'large':
        return self::PRESET_LARGE;
      default:
        throw new \InvalidArgumentException("Unknown sample data preset '$preset'. Use default, small, or large.");
    }
  }

  /**
   * @param mixed $value
   */
  private static function normalizeBoolean($value, string $key): bool {
    if (is_bool($value)) {
      return $value;
    }
    $normalized = strtolower(trim((string)$value));
    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
      return true;
    }
    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
      return false;
    }
    throw new \InvalidArgumentException("Option '$key' must be boolean.");
  }

  /**
   * @param mixed $value
   */
  private static function normalizeInt($value, string $key): int {
    if (is_int($value)) {
      return $value;
    }
    if (is_string($value) && ctype_digit($value)) {
      return (int)$value;
    }
    throw new \InvalidArgumentException("Option '$key' must be an integer.");
  }

  /**
   * @param mixed $value
   */
  private static function normalizeFloat($value, string $key): float {
    if (is_int($value) || is_float($value)) {
      return (float)$value;
    }
    if (is_string($value) && is_numeric($value)) {
      return (float)$value;
    }
    throw new \InvalidArgumentException("Option '$key' must be a number.");
  }

  private function getInt(string $key): int {
    return (int)$this->options[$key];
  }

  private function getFloat(string $key): float {
    return (float)$this->options[$key];
  }

  private function getString(string $key): string {
    return (string)$this->options[$key];
  }

  private function getBool(string $key): bool {
    return (bool)$this->options[$key];
  }
}
