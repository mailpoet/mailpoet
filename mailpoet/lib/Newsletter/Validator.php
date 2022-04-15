<?php declare(strict_types=1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Services\Bridge;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Validator\ValidationException;

class Validator {
  
  /** @var Bridge */
  private $bridge;

  /** @var TrackingConfig */
  private $trackingConfig;

  public function __construct(
    Bridge $bridge,
    TrackingConfig $trackingConfig
  ) {
    $this->bridge = $bridge;
    $this->trackingConfig = $trackingConfig;
  }
  
  public function validate(NewsletterEntity $newsletterEntity): ?string {
    try {
      $this->validateBody($newsletterEntity);
      $this->validateUnsubscribeRequirements($newsletterEntity);
      $this->validateReengagementRequirements($newsletterEntity);
    } catch (ValidationException $exception) {
      return __($exception->getMessage(), 'mailpoet');
    }
    return null;
  }

  private function validateUnsubscribeRequirements(NewsletterEntity $newsletterEntity): void {
    if (!$this->bridge->isMailpoetSendingServiceEnabled()) {
      return;
    }
    $content = $newsletterEntity->getContent();
    $hasUnsubscribeUrl = strpos($content, '[link:subscription_unsubscribe_url]') !== false;
    $hasUnsubscribeLink = strpos($content, '[link:subscription_unsubscribe]') !== false;

    if (!$hasUnsubscribeLink && !$hasUnsubscribeUrl) {
      throw new ValidationException('All emails must include an "Unsubscribe" link. Add a footer widget to your email to continue.');
    }
  }

  private function validateBody(NewsletterEntity $newsletterEntity): void {
    $emptyBodyErrorMessage = 'Poet, please add prose to your masterpiece before you send it to your followers.';
    $content = $newsletterEntity->getContent();

    if ($content === '') {
      throw new ValidationException($emptyBodyErrorMessage);
    }

    $contentBlocks = $newsletterEntity->getBody()['content']['blocks'] ?? [];
    if (count($contentBlocks) < 1) {
      throw new ValidationException($emptyBodyErrorMessage);
    }
  }

  private function validateReengagementRequirements(NewsletterEntity $newsletterEntity): void {
    if ($newsletterEntity->getType() !== NewsletterEntity::TYPE_RE_ENGAGEMENT) {
      return;
    }

    if (strpos($newsletterEntity->getContent(), '[link:subscription_re_engage_url]') === false) {
      throw new ValidationException('A re-engagement email must include a link with [link:subscription_re_engage_url] shortcode.');
    }

    if (!$this->trackingConfig->isEmailTrackingEnabled()) {
      throw new ValidationException('Re-engagement emails are disabled because open and click tracking is disabled in MailPoet → Settings → Advanced.');
    }
  }
}
