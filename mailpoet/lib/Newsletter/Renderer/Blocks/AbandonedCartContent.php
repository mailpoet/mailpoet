<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\AutomaticEmails\WooCommerce\Events\AbandonedCart;
use MailPoet\AutomaticEmails\WooCommerce\WooCommerce as WooCommerceEmail;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Tasks\Sending as SendingTask;

class AbandonedCartContent {
  /** @var AutomatedLatestContentBlock  */
  private $ALCBlock;

  public function __construct(
    AutomatedLatestContentBlock $ALCBlock
  ) {
    $this->ALCBlock = $ALCBlock;
  }

  public function render(
    NewsletterEntity $newsletter,
    array $args,
    bool $preview = false,
    SendingTask $sendingTask = null
  ): array {
    if ($newsletter->getType() !== NewsletterEntity::TYPE_AUTOMATIC) {
      // Do not display the block if not an automatic email
      return [];
    }
    $groupOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'group';
    })->first();
    $eventOption = $newsletter->getOptions()->filter(function (NewsletterOptionEntity $newsletterOption) {
      $optionField = $newsletterOption->getOptionField();
      return $optionField && $optionField->getName() === 'event';
    })->first();
    if (
      ($groupOption instanceof NewsletterOptionEntity && $groupOption->getValue() !== WooCommerceEmail::SLUG)
      || ($eventOption instanceof NewsletterOptionEntity && $eventOption->getValue() !== AbandonedCart::SLUG)
    ) {
      // Do not display the block if not an AbandonedCart email
      return [];
    }
    if ($preview) {
      // Display latest products for preview (no 'posts' argument specified)
      return $this->ALCBlock->render($newsletter, $args);
    }
    if (!($sendingTask instanceof SendingTask)) {
      // Do not display the block if we're not sending an email
      return [];
    }
    $meta = $sendingTask->getMeta();
    if (empty($meta[AbandonedCart::TASK_META_NAME])) {
      // Do not display the block if a cart is empty
      return [];
    }
    $args['amount'] = 50;
    $args['posts'] = $meta[AbandonedCart::TASK_META_NAME];
    return $this->ALCBlock->render($newsletter, $args);
  }
}
