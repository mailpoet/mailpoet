<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Actions\AutomationSendEmailSubjectResolver;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class AutomationEmailContextProvider {
  private AutomationRunStorage $automationRunStorage;
  private AutomationStorage $automationStorage;
  private AutomationSendEmailSubjectResolver $subjectResolver;
  private AutomationEmailPreviewOrderProvider $previewOrderProvider;
  private WooCommerceHelper $wooCommerceHelper;
  private WPFunctions $wp;

  public function __construct(
    AutomationRunStorage $automationRunStorage,
    AutomationStorage $automationStorage,
    AutomationSendEmailSubjectResolver $subjectResolver,
    AutomationEmailPreviewOrderProvider $previewOrderProvider,
    WooCommerceHelper $wooCommerceHelper,
    WPFunctions $wp
  ) {
    $this->automationRunStorage = $automationRunStorage;
    $this->automationStorage = $automationStorage;
    $this->subjectResolver = $subjectResolver;
    $this->previewOrderProvider = $previewOrderProvider;
    $this->wooCommerceHelper = $wooCommerceHelper;
    $this->wp = $wp;
  }

  /** @return array<string, mixed> */
  public function build(NewsletterEntity $newsletter, ?SendingQueueEntity $sendingQueue, bool $preview): array {
    if ($preview) {
      return $this->buildPreviewContext($newsletter);
    }

    if (!$sendingQueue) {
      return [];
    }

    return $this->buildRealSendContext($sendingQueue);
  }

  /** @return array<string, mixed> */
  private function buildPreviewContext(NewsletterEntity $newsletter): array {
    $automationId = $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AUTOMATION_ID);
    if (!is_numeric($automationId)) {
      return [];
    }

    $automation = $this->automationStorage->getAutomation((int)$automationId);
    if (!$automation) {
      return [];
    }

    $subjectKeys = $this->subjectResolver->getGuaranteedSubjectKeysForEmail($automation, $newsletter);
    $context = [
      'automation_subject_keys' => $subjectKeys,
    ];

    if (in_array(OrderSubject::KEY, $subjectKeys, true)) {
      $order = $this->previewOrderProvider->getOrder();
      if ($order instanceof \WC_Order) {
        $context['order'] = $order;
      }
    }

    $filteredContext = $this->wp->applyFilters('mailpoet_automation_email_preview_sample_data', $context);
    return is_array($filteredContext) ? $filteredContext : $context;
  }

  /** @return array<string, mixed> */
  private function buildRealSendContext(SendingQueueEntity $sendingQueue): array {
    $meta = $sendingQueue->getMeta();
    $runId = is_array($meta) ? ($meta['automation']['run_id'] ?? null) : null;
    if (!is_numeric($runId)) {
      return [];
    }

    $automationRun = $this->automationRunStorage->getAutomationRun((int)$runId);
    if (!$automationRun) {
      return [];
    }

    $context = [];
    $subjectKeys = [];
    foreach ($automationRun->getSubjects() as $subject) {
      $subjectKeys[] = $subject->getKey();
      if ($subject->getKey() !== OrderSubject::KEY) {
        continue;
      }

      $orderId = $subject->getArgs()['order_id'] ?? null;
      if (!is_numeric($orderId)) {
        continue;
      }

      $order = $this->wooCommerceHelper->wcGetOrder((int)$orderId);
      if ($order instanceof \WC_Order) {
        $context['order'] = $order;
      }
    }

    $subjectKeys = array_values(array_unique($subjectKeys));
    sort($subjectKeys);
    $context['automation_subject_keys'] = $subjectKeys;

    return $context;
  }
}
