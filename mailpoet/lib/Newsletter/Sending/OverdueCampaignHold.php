<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Sending;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class OverdueCampaignHold {
  public const HELD_TYPES = [
    NewsletterEntity::TYPE_STANDARD,
    NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
    NewsletterEntity::TYPE_RE_ENGAGEMENT,
  ];

  private const DEFAULT_THRESHOLD = 86400; // 24 hours in seconds

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function isEnabled(): bool {
    return (bool)$this->wp->applyFilters('mailpoet_hold_overdue_campaigns', true);
  }

  /**
   * Seconds a campaign may be late before it is held. Zero or less turns the hold off.
   */
  public function getThreshold(): int {
    $threshold = $this->wp->applyFilters('mailpoet_overdue_campaign_threshold', self::DEFAULT_THRESHOLD);
    return is_numeric($threshold) ? max(0, (int)$threshold) : 0;
  }

  public static function isOverdue(
    string $newsletterType,
    ?\DateTimeInterface $referenceTime,
    int $countProcessed,
    bool $confirmed,
    bool $isReplay,
    \DateTimeInterface $now,
    int $threshold
  ): bool {
    if (
      $threshold <= 0
      || $referenceTime === null
      || $countProcessed > 0
      || $confirmed
      || $isReplay
      || !in_array($newsletterType, self::HELD_TYPES, true)
    ) {
      return false;
    }
    return $now->getTimestamp() - $referenceTime->getTimestamp() > $threshold;
  }

  public function getReferenceTime(ScheduledTaskEntity $task): ?\DateTimeInterface {
    return $task->getScheduledAt() ?? $task->getCreatedAt();
  }

  /**
   * Same clock as the Scheduler uses to find due tasks.
   */
  public function now(): \DateTimeInterface {
    return Carbon::now()->millisecond(0);
  }
}
