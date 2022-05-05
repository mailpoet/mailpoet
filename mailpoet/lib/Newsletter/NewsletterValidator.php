<?php declare(strict_types=1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Services\Bridge;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Validator\ValidationException;

class NewsletterValidator {
  
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
      $this->validateReEngagementRequirements($newsletterEntity);
      $this->validateAutomaticLatestContentRequirements($newsletterEntity);
    } catch (ValidationException $exception) {
      return $exception->getMessage();
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
      throw new ValidationException(__('All emails must include an "Unsubscribe" link. Add a footer widget to your email to continue.', 'mailpoet'));
    }
  }

  private function validateBody(NewsletterEntity $newsletterEntity): void {
    $emptyBodyErrorMessage = __('Poet, please add prose to your masterpiece before you send it to your followers.', 'mailpoet');
    $content = $newsletterEntity->getContent();

    if ($content === '') {
      throw new ValidationException($emptyBodyErrorMessage);
    }

    $contentBlocks = $newsletterEntity->getBody()['content']['blocks'] ?? [];
    if (count($contentBlocks) < 1) {
      throw new ValidationException($emptyBodyErrorMessage);
    }
  }

  private function validateReEngagementRequirements(NewsletterEntity $newsletterEntity): void {
    if ($newsletterEntity->getType() !== NewsletterEntity::TYPE_RE_ENGAGEMENT) {
      return;
    }

    if (strpos($newsletterEntity->getContent(), '[link:subscription_re_engage_url]') === false) {
      throw new ValidationException(__('A re-engagement email must include a link with [link:subscription_re_engage_url] shortcode.', 'mailpoet'));
    }

    if (!$this->trackingConfig->isEmailTrackingEnabled()) {
      throw new ValidationException(__('Re-engagement emails are disabled because open and click tracking is disabled in MailPoet → Settings → Advanced.', 'mailpoet'));
    }
  }

  private function validateAutomaticLatestContentRequirements(NewsletterEntity $newsletterEntity) {
    if ($newsletterEntity->getType() !== NewsletterEntity::TYPE_NOTIFICATION) {
      return;
    }
    $content = $newsletterEntity->getContent();
    if (
      strpos($content, '"type":"automatedLatestContent"') === false && 
      strpos($content, '"type":"automatedLatestContentLayout"') === false
    ) {
      throw new ValidationException(__('Please add an “Automatic Latest Content” widget to the email from the right sidebar.', 'mailpoet'));
    }
  }
}
