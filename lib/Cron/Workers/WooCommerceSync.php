<?php

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Segments\WooCommerce;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

class WooCommerceSync extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_sync';
  const SUPPORT_MULTIPLE_INSTANCES = false;
  const AUTOMATIC_SCHEDULING = false;

  /** @var WooCommerceSegment */
  private $woocommerceSegment;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  public function __construct(
    WooCommerceSegment $woocommerceSegment,
    WooCommerceHelper $woocommerceHelper
  ) {
    $this->woocommerceSegment = $woocommerceSegment;
    $this->woocommerceHelper = $woocommerceHelper;
    parent::__construct();
  }

  public function checkProcessingRequirements() {
    return $this->woocommerceHelper->isWooCommerceActive();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $countOfSynchronized = $task->getMeta()['count_of_synchronized'] ?? 0;
    $count = $this->woocommerceSegment->synchronizeCustomers($countOfSynchronized);

    $countOfSynchronized += $count;
    $task->setMeta(['count_of_synchronized' => $countOfSynchronized]);
    $this->scheduledTasksRepository->flush();
    if ($count === WooCommerce::BATCH_SIZE) {
      return false;
    }
    return true;
  }
}
