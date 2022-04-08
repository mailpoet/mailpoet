<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions\SendWelcomeEmail;

use MailPoet\Automation\Engine\Workflows\AbstractValidationResult;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\NewsletterEntity;

class ValidationResult extends AbstractValidationResult {
  /** @var NewsletterEntity */
  private $newsletter;
  
  /** @var SubscriberSubject */
  private $subscriberSubject;
  
  /** @var SegmentSubject */
  private $segmentSubject;

  public function setNewsletter(NewsletterEntity $newsletter): void {
    $this->newsletter = $newsletter;
  }

  public function setSubscriberSubject(SubscriberSubject $subscriberSubject): void {
    $this->subscriberSubject = $subscriberSubject;
  }

  public function setSegmentSubject(SegmentSubject $segmentSubject): void {
    $this->segmentSubject = $segmentSubject;
  }

  public function getSubscriberSubject(): SubscriberSubject {
    return $this->subscriberSubject;
  }

  public function getSegmentSubject(): SegmentSubject {
    return $this->segmentSubject;
  }

  public function getNewsletter(): NewsletterEntity {
    return $this->newsletter;
  }

  public function isValid(): bool {
    return $this->newsletter !== null
      && $this->segmentSubject !== null
      && $this->subscriberSubject !== null;
  }
}
