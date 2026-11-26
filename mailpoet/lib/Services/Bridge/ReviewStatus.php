<?php declare(strict_types = 1);

namespace MailPoet\Services\Bridge;

/**
 * Where the review of a plan that is not approved yet stands, as the Bridge reports it.
 */
class ReviewStatus {
  // We asked the customer to fix something.
  public const STATUS_FIX = 'fix';
  // Support asked the customer a question.
  public const STATUS_QUESTION = 'question';
  // The customer replied; it is our turn.
  public const STATUS_REPLIED = 'replied';
  public const STATUS_REVIEWING = 'reviewing';

  public const FIX_REASONS = [
    'generic_content',
    'http_error',
    'unreachable_website',
    'never_emailed_list',
    'customers_invite_once',
  ];

  private const DATE_FORMATS = ['Y-m-d\TH:i:s.vP', 'Y-m-d\TH:i:sP'];

  /** @var string */
  private $status;

  /** @var string|null */
  private $reason;

  /** @var \DateTimeImmutable|null */
  private $since;

  private function __construct(
    string $status,
    ?string $reason,
    ?\DateTimeImmutable $since
  ) {
    $this->status = $status;
    $this->reason = $reason;
    $this->since = $since;
  }

  /**
   * Returns null for anything the plugin can't show, so the caller keeps the general notice.
   *
   * @param mixed $data
   */
  public static function fromArray($data): ?self {
    if (!is_array($data)) {
      return null;
    }
    $status = $data['status'] ?? null;
    $statuses = [self::STATUS_FIX, self::STATUS_QUESTION, self::STATUS_REPLIED, self::STATUS_REVIEWING];
    if (!in_array($status, $statuses, true)) {
      return null;
    }
    $reason = null;
    if ($status === self::STATUS_FIX) {
      $reason = $data['reason'] ?? null;
      if (!in_array($reason, self::FIX_REASONS, true)) {
        return null;
      }
    }
    $since = self::parseDate($data['since'] ?? null);
    // These messages name the date of our email, so they need one.
    if (!$since && in_array($status, [self::STATUS_FIX, self::STATUS_QUESTION], true)) {
      return null;
    }
    return new self($status, $reason, $since);
  }

  public function toArray(): array {
    return [
      'status' => $this->status,
      'reason' => $this->reason,
      'since' => $this->since ? $this->since->format(self::DATE_FORMATS[0]) : null,
    ];
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function getReason(): ?string {
    return $this->reason;
  }

  public function getSince(): ?\DateTimeImmutable {
    return $this->since;
  }

  /**
   * @param mixed $value
   */
  private static function parseDate($value): ?\DateTimeImmutable {
    if (!is_string($value)) {
      return null;
    }
    foreach (self::DATE_FORMATS as $format) {
      $date = \DateTimeImmutable::createFromFormat($format, $value);
      if ($date) {
        return $date->setTimezone(new \DateTimeZone('UTC'));
      }
    }
    return null;
  }
}
