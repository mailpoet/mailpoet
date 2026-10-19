<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Sending;

use Codeception\Stub;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Sending\OverdueCampaignHold;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\TimeZoneCampaignScheduler;
use MailPoet\WP\Functions as WPFunctions;

class OverdueCampaignHoldTest extends \MailPoetUnitTest {
  private const DAY = 86400;

  /** @dataProvider overdueCases */
  public function testIsOverdue(
    bool $expected,
    string $type,
    ?int $secondsLate,
    int $countProcessed,
    bool $confirmed,
    bool $isReplay,
    int $threshold = self::DAY
  ): void {
    $now = new \DateTimeImmutable('2026-10-19 12:00:00 UTC');
    $reference = $secondsLate === null ? null : $now->sub(new \DateInterval('PT0S'))->setTimestamp($now->getTimestamp() - $secondsLate);
    verify(OverdueCampaignHold::isOverdue($type, $reference, $countProcessed, $confirmed, $isReplay, $now, $threshold))->same($expected);
  }

  public function overdueCases(): array {
    $standard = NewsletterEntity::TYPE_STANDARD;
    return [
      'one second over the threshold' => [true, $standard, self::DAY + 1, 0, false, false],
      'exactly the threshold' => [false, $standard, self::DAY, 0, false, false],
      'under the threshold' => [false, $standard, 23 * 3600, 0, false, false],
      'scheduled in the future' => [false, $standard, -3600, 0, false, false],
      'already sent to someone' => [false, $standard, 3 * self::DAY, 1, false, false],
      'confirmed by the user' => [false, $standard, 3 * self::DAY, 0, true, false],
      'replay' => [false, $standard, 3 * self::DAY, 0, false, true],
      'no reference time' => [false, $standard, null, 0, false, false],
      'post notification issue' => [true, NewsletterEntity::TYPE_NOTIFICATION_HISTORY, 2 * self::DAY, 0, false, false],
      're-engagement' => [true, NewsletterEntity::TYPE_RE_ENGAGEMENT, 2 * self::DAY, 0, false, false],
      'post notification parent' => [false, NewsletterEntity::TYPE_NOTIFICATION, 2 * self::DAY, 0, false, false],
      'welcome' => [false, NewsletterEntity::TYPE_WELCOME, 2 * self::DAY, 0, false, false],
      'automatic' => [false, NewsletterEntity::TYPE_AUTOMATIC, 2 * self::DAY, 0, false, false],
      'automation' => [false, NewsletterEntity::TYPE_AUTOMATION, 2 * self::DAY, 0, false, false],
      'WooCommerce email' => [false, NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL, 2 * self::DAY, 0, false, false],
      'custom threshold exceeded' => [true, $standard, 3601, 0, false, false, 3600],
      'custom threshold not exceeded' => [false, $standard, 3600, 0, false, false, 3600],
    ];
  }

  public function testHeldTypesAreTheCampaignTypesThatCanBeHeld(): void {
    verify(OverdueCampaignHold::HELD_TYPES)->same([
      NewsletterEntity::TYPE_STANDARD,
      NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
      NewsletterEntity::TYPE_RE_ENGAGEMENT,
    ]);
  }

  /** @dataProvider enabledCases */
  public function testIsEnabled($filterValue, bool $expected): void {
    verify($this->createHold($filterValue)->isEnabled())->same($expected);
  }

  public function enabledCases(): array {
    return [
      'true' => [true, true],
      'false' => [false, false],
      'zero string' => ['0', false],
      'one string' => ['1', true],
      'zero int' => [0, false],
    ];
  }

  /** @dataProvider thresholdCases */
  public function testGetThreshold($filterValue, int $expected): void {
    verify($this->createHold($filterValue)->getThreshold())->same($expected);
  }

  public function thresholdCases(): array {
    return [
      'default' => [self::DAY, self::DAY],
      'numeric string is cast' => ['3600', 3600],
      'zero' => [0, 0],
      'negative' => ['-5', 0],
      'non numeric' => ['abc', 0],
    ];
  }

  public function testItPassesDefaultsToTheFilters(): void {
    $received = [];
    $wp = Stub::make(WPFunctions::class, [
      'applyFilters' => function ($tag, $default) use (&$received) {
        $received[$tag] = $default;
        return $default;
      },
    ]);
    $hold = $this->createHoldWithWp($wp);
    $hold->isEnabled();
    $hold->getThreshold();
    verify($received)->same([
      'mailpoet_hold_overdue_campaigns' => true,
      'mailpoet_overdue_campaign_threshold' => self::DAY,
    ]);
  }

  public function testReferenceTimeIsTheScheduledTime(): void {
    $task = new ScheduledTaskEntity();
    $scheduledAt = new \DateTimeImmutable('2026-07-23 08:00:00');
    $task->setScheduledAt($scheduledAt);
    $task->setCreatedAt(new \DateTimeImmutable('2026-07-01 08:00:00'));
    verify($this->createHold(true)->getReferenceTime($task))->same($scheduledAt);
  }

  public function testReferenceTimeFallsBackToCreatedTimeForLegacyTasks(): void {
    $task = new ScheduledTaskEntity();
    $createdAt = new \DateTimeImmutable('2026-07-01 08:00:00');
    $task->setCreatedAt($createdAt);
    verify($this->createHold(true)->getReferenceTime($task))->same($createdAt);
  }

  public function testReferenceTimeIsNullWithoutAnyDate(): void {
    verify($this->createHold(true)->getReferenceTime(new ScheduledTaskEntity()))->null();
  }

  private function createHold($filterValue): OverdueCampaignHold {
    $wp = Stub::make(WPFunctions::class, [
      'applyFilters' => function () use ($filterValue) {
        return $filterValue;
      },
    ]);
    return $this->createHoldWithWp($wp);
  }

  private function createHoldWithWp(WPFunctions $wp): OverdueCampaignHold {
    return new OverdueCampaignHold(
      $wp,
      Stub::makeEmpty(TimeZoneCampaignScheduler::class),
      Stub::makeEmpty(ScheduledTasksRepository::class),
      Stub::makeEmpty(LoggerFactory::class)
    );
  }
}
