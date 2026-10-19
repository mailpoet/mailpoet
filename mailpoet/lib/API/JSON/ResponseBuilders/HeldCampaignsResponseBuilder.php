<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Config\Menu;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\OverdueCampaignHold;
use MailPoet\WP\Functions as WPFunctions;

class HeldCampaignsResponseBuilder {
  private OverdueCampaignHold $overdueCampaignHold;

  private WPFunctions $wp;

  public function __construct(
    OverdueCampaignHold $overdueCampaignHold,
    WPFunctions $wp
  ) {
    $this->overdueCampaignHold = $overdueCampaignHold;
    $this->wp = $wp;
  }

  /**
   * @param ScheduledTaskEntity[] $tasks One held task per newsletter
   * @return array<int, array<string, mixed>>
   */
  public function buildAll(array $tasks): array {
    $rows = [];
    foreach ($tasks as $task) {
      $queue = $task->getSendingQueue();
      $newsletter = $queue ? $queue->getNewsletter() : null;
      if ($newsletter instanceof NewsletterEntity) {
        $rows[] = $this->build($task, $newsletter);
      }
    }
    return $rows;
  }

  /**
   * @return array<string, mixed>
   */
  public function build(ScheduledTaskEntity $task, NewsletterEntity $newsletter): array {
    $scheduledAt = $task->getScheduledAt();
    $referenceTime = $this->overdueCampaignHold->getReferenceTime($task);
    $parent = $newsletter->getType() === NewsletterEntity::TYPE_NOTIFICATION_HISTORY ? $newsletter->getParent() : null;

    return [
      'id' => $newsletter->getId(),
      'subject' => $newsletter->getCampaignNameOrSubject(),
      'type' => $newsletter->getType(),
      'scheduled_at' => $scheduledAt ? $scheduledAt->format(NewslettersResponseBuilder::DATE_FORMAT) : null,
      'late_seconds' => $referenceTime ? max(0, $this->overdueCampaignHold->now()->getTimestamp() - $referenceTime->getTimestamp()) : 0,
      'parent_id' => $parent instanceof NewsletterEntity ? $parent->getId() : null,
      'can_reschedule' => $newsletter->getType() === NewsletterEntity::TYPE_STANDARD,
      'can_edit_link' => $this->getLink($newsletter, $parent),
    ];
  }

  private function getLink(NewsletterEntity $newsletter, ?NewsletterEntity $parent): ?string {
    switch ($newsletter->getType()) {
      case NewsletterEntity::TYPE_STANDARD:
        $route = '#/send/' . $newsletter->getId();
        break;
      case NewsletterEntity::TYPE_NOTIFICATION_HISTORY:
        if (!$parent instanceof NewsletterEntity) {
          return null;
        }
        $route = '#/notification/history/' . $parent->getId();
        break;
      case NewsletterEntity::TYPE_RE_ENGAGEMENT:
        $route = '#/re_engagement/' . $newsletter->getId();
        break;
      default:
        return null;
    }
    return $this->wp->adminUrl('admin.php?page=' . Menu::EMAILS_PAGE_SLUG . $route);
  }
}
