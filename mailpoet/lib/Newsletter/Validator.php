<?php declare(strict_types=1);

namespace MailPoet\Newsletter;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Services\Bridge;

class Validator {
  
  /** @var Bridge */
  private $bridge;
  
  public function __construct(
    Bridge $bridge
  ) {
    $this->bridge = $bridge;
  }
  
  public function validate(NewsletterEntity $newsletterEntity): ?string {
    if (
      $newsletterEntity->getBody()
      && is_array($newsletterEntity->getBody())
      && $newsletterEntity->getBody()['content']
    ) {
      $body = json_encode($newsletterEntity->getBody()['content']);
      if ($body === false) {
        return __('Poet, please add prose to your masterpiece before you send it to your followers.');
      }

      if (
        $this->bridge->isMailpoetSendingServiceEnabled()
        && (strpos($body, '[link:subscription_unsubscribe_url]') === false)
        && (strpos($body, '[link:subscription_unsubscribe]') === false)
      ) {
        return __('All emails must include an "Unsubscribe" link. Add a footer widget to your email to continue.');
      }
    } else {
      return __('Poet, please add prose to your masterpiece before you send it to your followers.');
    }
    return null;
  }
}
