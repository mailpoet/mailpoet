<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Shortcodes\Categories;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;

interface CategoryInterface {
  /**
   * Shortcodes::replace() splices the return value verbatim into the subject, the
   * HTML body and the plain-text body. The only exception is a shortcode inside an
   * HTML tag, typically in an attribute value, where quotes and angle brackets in
   * the value are encoded. A category is otherwise responsible for returning a
   * value already suited to all three -- categories that build markup on purpose,
   * such as site:homepage_link, do so knowingly, while categories returning
   * user-controlled text must not let that text become markup.
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
