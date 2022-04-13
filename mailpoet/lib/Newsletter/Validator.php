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
      $content = $newsletterEntity->getBody()['content'];
      $encodedBody = json_encode($content);
      if ($encodedBody === false) {
        return $this->emptyContentErrorMessage();
      } else {
        $blocks = $content['blocks'] ?? [];
        if (empty($blocks)) {
          return $this->emptyContentErrorMessage();
        }
      }

      if (
        $this->bridge->isMailpoetSendingServiceEnabled()
        && (strpos($encodedBody, '[link:subscription_unsubscribe_url]') === false)
        && (strpos($encodedBody, '[link:subscription_unsubscribe]') === false)
      ) {
        return __('All emails must include an "Unsubscribe" link. Add a footer widget to your email to continue.');
      }
    } else {
      return $this->emptyContentErrorMessage();
    }
    return null;
  }
  
  private function emptyContentErrorMessage(): string {
    return __('Poet, please add prose to your masterpiece before you send it to your followers.');
  }
}
