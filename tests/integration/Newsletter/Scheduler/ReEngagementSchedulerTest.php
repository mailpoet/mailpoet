<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;

class ReEngagementSchedulerTest extends \MailPoetTest {
  /** @var NewsletterOptionFieldEntity */
  private $afterTimeNumberOptionField;

  /** @var NewsletterOptionFieldEntity */
  private $afterTimeTypeField;

  /** @var ReEngagementScheduler */
  private $scheduler;

  public function _before() {
    parent::_before();
    $this->cleanup();
    // Prepare Newsletter field options for configuring re-engagement emails
    $this->afterTimeNumberOptionField = new NewsletterOptionFieldEntity();
    $this->afterTimeNumberOptionField->setName(NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER);
    $this->afterTimeNumberOptionField->setNewsletterType(NewsletterEntity::TYPE_RE_ENGAGEMENT);
    $this->entityManager->persist($this->afterTimeNumberOptionField);
    $this->afterTimeTypeField = new NewsletterOptionFieldEntity();
    $this->afterTimeTypeField->setName(NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE);
    $this->afterTimeTypeField->setNewsletterType(NewsletterEntity::TYPE_RE_ENGAGEMENT);
    $this->entityManager->persist($this->afterTimeTypeField);
    $this->entityManager->flush();

    $this->scheduler = $this->diContainer->get(ReEngagementScheduler::class);
  }

  public function testItDoesntScheduleAnythingIfThereAreNoActiveReEngagementEmails() {
    $this->createReEngagementEmail(5, NewsletterEntity::STATUS_DRAFT); // Inactive re-engagement email
    $scheduled = $this->scheduler->scheduleAll();
    expect($scheduled)->count(0);
  }

  private function createReEngagementEmail(int $monthsAfter, string $status = NewsletterEntity::STATUS_ACTIVE) {
    $email = new NewsletterEntity();
    $email->setSubject("Re-engagement $monthsAfter months");
    $email->setType(NewsletterEntity::TYPE_RE_ENGAGEMENT);
    $email->setStatus($status);
    $afterTimeType = new NewsletterOptionEntity($email, $this->afterTimeTypeField);
    $afterTimeType->setValue('months');
    $this->entityManager->persist($afterTimeType);
    $email->getOptions()->add($afterTimeType);
    $afterTimeNumber = new NewsletterOptionEntity($email, $this->afterTimeNumberOptionField);
    $afterTimeNumber->setValue((string)$monthsAfter);
    $this->entityManager->persist($afterTimeNumber);
    $email->getOptions()->add($afterTimeNumber);
    $this->entityManager->persist($email);
    $this->entityManager->flush();
    return $email;
  }

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
  }
}
