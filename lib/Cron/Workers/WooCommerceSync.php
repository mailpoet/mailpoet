<?php
namespace MailPoet\Cron\Workers;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\ScheduledTask;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

if (!defined('ABSPATH')) exit;

class WooCommerceSync extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_sync';

  /** @var WooCommerceSegment */
  private $woocommerce_segment;

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  private $taskAlreadyInProgress = false;

  function __construct(WooCommerceSegment $woocommerce_segment, WooCommerceHelper $woocommerce_helper, $timer = false) {
    $this->woocommerce_segment = $woocommerce_segment;
    $this->woocommerce_helper = $woocommerce_helper;
    parent::__construct($timer);
  }

  function checkProcessingRequirements() {
    return $this->woocommerce_helper->isWooCommerceActive();
  }

  function prepareTask(ScheduledTask $task) {
    if (is_null($task->status)) {
      $this->taskAlreadyInProgress = true;
      return false;
    }

    return parent::prepareTask($task);
  }

  function processTaskStrategy(ScheduledTask $task) {
    if ($this->taskAlreadyInProgress) {
      return false;
    }

    $this->woocommerce_segment->synchronizeCustomers();

    return true;
  }
}
