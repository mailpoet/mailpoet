<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Scheduler {
  const MYSQL_TIMESTAMP_MAX = '2038-01-19 03:14:07';

  /** @var WPFunctions  */
  private $wp;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    WPFunctions $wp,
    NewslettersRepository $newslettersRepository
  ) {
    $this->wp = $wp;
    $this->newslettersRepository = $newslettersRepository;
  }

  /**
   * @return string|false
   */
  public function getNextRunDate($schedule, $fromTimestamp = false) {
    $nextRunDateTime = $this->getNextRunDateTime($schedule, $fromTimestamp);
    return $nextRunDateTime ? $nextRunDateTime->format('Y-m-d H:i:s') : $nextRunDateTime;
  }

  public function getPreviousRunDate($schedule, $fromTimestamp = false) {
    // User enters time in WordPress site timezone, but we need to calculate it in UTC before we save it to DB
    // 1) As the initial tile we use time in site timezone (gmt is false)
    // 2) We use CronExpression to calculate previous run (still in site's timezone)
    // 3) We convert the calculated time to UTC
    $fromTimestamp = ($fromTimestamp) ?: $this->wp->currentTime('timestamp', false);
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $previousRunDate = $schedule->getPreviousRunDate(Carbon::createFromTimestamp($fromTimestamp, $this->wp->wpTimezone()));
      $previousRunDate->setTimezone(new \DateTimeZone('UTC'));
      $previousRunDate = $previousRunDate->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
      $previousRunDate = false;
    }
    return $previousRunDate;
  }

  public function getScheduledTimeWithDelay($afterTimeType, $afterTimeNumber): Carbon {
    $currentTime = Carbon::now()->millisecond(0);
    switch ($afterTimeType) {
      case 'minutes':
        $currentTime->addMinutes($afterTimeNumber);
        break;
      case 'hours':
        $currentTime->addHours($afterTimeNumber);
        break;
      case 'days':
        $currentTime->addDays($afterTimeNumber);
        break;
      case 'weeks':
        $currentTime->addWeeks($afterTimeNumber);
        break;
    }
    $maxScheduledTime = Carbon::createFromFormat('Y-m-d H:i:s', self::MYSQL_TIMESTAMP_MAX);
    if ($maxScheduledTime && $currentTime > $maxScheduledTime) {
      return $maxScheduledTime;
    }
    return $currentTime;
  }

  /**
   * @return NewsletterEntity[]
   */
  public function getNewsletters(string $type, ?string $group = null): array {
    return $this->newslettersRepository->findActiveByTypeAndGroup($type, $group);
  }

  public function formatDatetimeString($datetimeString) {
    return Carbon::parse($datetimeString)->format('Y-m-d H:i:s');
  }

  /**
   * @return \DateTime|false
   */
  public function getNextRunDateTime($schedule, $fromTimestamp = false) {
    // User enters time in WordPress site timezone, but we need to calculate it in UTC before we save it to DB
    // 1) As the initial tile we use time in site timezone (gmt is false)
    // 2) We use CronExpression to calculate next run (still in site's timezone)
    // 3) We convert the calculated time to UTC
    $fromTimestamp = $fromTimestamp ?: $this->wp->currentTime('timestamp', false);
    try {
      $schedule = \Cron\CronExpression::factory($schedule);
      $nextRunDate = $schedule->getNextRunDate(Carbon::createFromTimestamp($fromTimestamp, $this->wp->wpTimezone()));
      $nextRunDate->setTimezone(new \DateTimeZone('UTC'));
    } catch (\Exception $e) {
      $nextRunDate = false;
    }
    return $nextRunDate;
  }
}
