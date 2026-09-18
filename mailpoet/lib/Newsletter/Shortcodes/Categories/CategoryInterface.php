<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;

interface CategoryInterface {
  /**
   * Shortcodes::replace() splices the return value verbatim into the subject, the
   * HTML body and the plain-text body, and applies no escaping of its own. A
   * category is therefore responsible for returning a value already suited to all
   * three -- categories that build markup on purpose, such as site:homepage_link,
   * do so knowingly, while categories returning user-controlled text must not let
   * that text become markup.
   */
  public function process(
    array $shortcodeDetails,
    ?NewsletterEntity $newsletter = null,
    ?SubscriberEntity $subscriber = null,
    ?SendingQueueEntity $queue = null,
    string $content = '',
    bool $wpUserPreview = false
  ): ?string;
}
