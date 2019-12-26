<?php

namespace MailPoet\Entities;

use MailPoet\Doctrine\EntityTraits\AutoincrementedIdTrait;
use MailPoet\Doctrine\EntityTraits\CreatedAtTrait;
use MailPoet\Doctrine\EntityTraits\UpdatedAtTrait;
use MailPoetVendor\Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="stats_notifications")
 */
class StatsNotificationEntity {
  use AutoincrementedIdTrait;
  use CreatedAtTrait;
  use UpdatedAtTrait;

  /**
   * @ORM\OneToOne(targetEntity="MailPoet\Entities\NewsletterEntity")
   * @var NewsletterEntity
   */
  private $newsletter;

  /**
   * @ORM\OneToOne(targetEntity="MailPoet\Entities\ScheduledTaskEntity")
   * @var ScheduledTaskEntity
   */
  private $task;

  public function __construct(NewsletterEntity $newsletter, ScheduledTaskEntity $task) {
    $this->newsletter = $newsletter;
    $this->task = $task;
  }

  /**
   * @return NewsletterEntity
   */
  public function getNewsletter() {
    return $this->newsletter;
  }

  /**
   * @return ScheduledTaskEntity
   */
  public function getTask() {
    return $this->task;
  }

}
