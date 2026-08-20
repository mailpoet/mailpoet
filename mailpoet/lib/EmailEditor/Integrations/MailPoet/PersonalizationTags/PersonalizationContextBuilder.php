<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Builds the context passed to personalization tag callbacks for a recipient of a sending queue.
 *
 * Shared by the sending path and the click-tracking redirect so that a tag resolves to the same
 * URL no matter whether it is personalized at send time or at click time.
 *
 * For automation emails this also fires the action that registers subject-dependent tags
 * (order, customer, ...), so callers must build the context before looking those tags up.
 */
class PersonalizationContextBuilder {
  private AutomationRunStorage $automationRunStorage;
  private WPFunctions $wp;
  private WooCommerceHelper $wooCommerceHelper;

  public function __construct(
    AutomationRunStorage $automationRunStorage,
    WPFunctions $wp,
    WooCommerceHelper $wooCommerceHelper
  ) {
    $this->automationRunStorage = $automationRunStorage;
    $this->wp = $wp;
    $this->wooCommerceHelper = $wooCommerceHelper;
  }

  /**
   * @return array<string, mixed>
   */
  public function build(
    NewsletterEntity $newsletter,
    SubscriberEntity $subscriber,
    SendingQueueEntity $queue,
    bool $isPreview = false
  ): array {
    $context = [
      'recipient_email' => $subscriber->getEmail(),
      'newsletter_id' => $newsletter->getId(),
      'queue_id' => $queue->getId(),
      'is_preview' => $isPreview,
    ];

    $queueMeta = $queue->getMeta();
    if (!isset($queueMeta['automation']['run_id'])) {
      return $context;
    }

    $automationRun = $this->automationRunStorage->getAutomationRun((int)$queueMeta['automation']['run_id']);
    if (!$automationRun) {
      return $context;
    }

    $subjectsArray = [];
    foreach ($automationRun->getSubjects() as $subject) {
      $subjectsArray[$subject->getKey()] = [
        'key' => $subject->getKey(),
        'args' => $subject->getArgs(),
      ];
    }

    if (isset($subjectsArray['woocommerce:order']['args']['order_id']) && $this->wooCommerceHelper->isWooCommerceActive()) {
      $order = $this->wooCommerceHelper->wcGetOrder((int)$subjectsArray['woocommerce:order']['args']['order_id']);
      if ($order instanceof \WC_Order) {
        $context['order'] = $order;
      }
    }

    if (isset($subjectsArray['woocommerce:customer']['args']['customer_id'])) {
      $customer = $this->wooCommerceHelper->wcGetCustomer((int)$subjectsArray['woocommerce:customer']['args']['customer_id']);
      if ($customer && $customer->get_id()) {
        $context['customer'] = $customer;
      }
    }

    // Allow extensions to add their own subject context
    /** @var array<string, mixed> $context */
    $context = $this->wp->applyFilters('mailpoet_automation_email_personalization_context', $context, $subjectsArray);

    // Allow extensions to register additional personalization tags based on available subjects
    $this->wp->doAction('mailpoet_automation_email_extend_personalization_tags_for_sending', array_keys($subjectsArray));

    return $context;
  }
}
