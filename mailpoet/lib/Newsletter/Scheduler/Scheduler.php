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
  public function getNextRunDate($schedule) {
    $nextRunDateTime = $this->getNextRunDateTime($schedule);
    return $nextRunDateTime ? $nextRunDateTime->format('Y-m-d H:i:s') : $nextRunDateTime;
  }

  public function getPreviousRunDate($schedule) {
    // User enters time in WordPress site timezone, but we need to calculate it in UTC before we save it to DB
    // 1) As the initial time we use time in site timezone via current_datetime
    // 2) We use CronExpression to calculate previous run (still in site's timezone)
    // 3) We convert the calculated time to UTC
    $from = $this->wp->currentDatetime();
    try {
      $schedule = new \Cron\CronExpression((string)$schedule);
      $previousRunDate = $schedule->getPreviousRunDate(Carbon::instance($from));
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
  public function getNextRunDateTime($schedule) {
    // User enters time in WordPress site timezone, but we need to calculate it in UTC before we save it to DB
    // 1) As the initial time we use time in site timezone via current_datetime
    // 2) We use CronExpression to calculate next run (still in site's timezone)
    // 3) We convert the calculated time to UTC
    //$fromTimestamp = $this->wp->currentTime('timestamp', false);
    $from = $this->wp->currentDatetime();
    try {
      $schedule = new \Cron\CronExpression((string)$schedule);
      $nextRunDate = $schedule->getNextRunDate(Carbon::instance($from));
      $nextRunDate->setTimezone(new \DateTimeZone('UTC'));
      // Work around CronExpression transforming Carbon into DateTime
      if (!$nextRunDate instanceof Carbon) {
        $nextRunDate = new Carbon($nextRunDate);
      }
    } catch (\Exception $e) {
      $nextRunDate = false;
    }
    return $nextRunDate;
  }
}
