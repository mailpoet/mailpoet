<?php declare(strict_types=1);

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\NewslettersRepository;

class ReEngagementScheduler {

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    NewslettersRepository $newslettersRepository
  ) {
    $this->newslettersRepository = $newslettersRepository;
  }

  /**
   * Schedules sending tasks for re-engagement emails
   * @return ScheduledTaskEntity[]
   */
  public function scheduleAll(): array {
    $scheduled = [];
    $emails = $this->newslettersRepository->findActiveByTypes([NewsletterEntity::TYPE_RE_ENGAGEMENT]);
    if (!$emails) {
      return $scheduled;
    }
    // @todo add creating scheduled task
    // @todo fill scheduled tasks subscribers
    return $scheduled;
  }
}
