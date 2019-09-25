<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class WooCommerceSync extends SingleInstanceSimpleWorker {
  const TASK_TYPE = 'woocommerce_sync';

  /** @var WooCommerceSegment */
  private $woocommerce_segment;

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  function __construct(WooCommerceSegment $woocommerce_segment, WooCommerceHelper $woocommerce_helper, $timer = false) {
    $this->woocommerce_segment = $woocommerce_segment;
    $this->woocommerce_helper = $woocommerce_helper;
    parent::__construct($timer);
  }

  function checkProcessingRequirements() {
    return $this->woocommerce_helper->isWooCommerceActive();
  }

  function processTaskStrategy(ScheduledTask $task) {
    try {
      $this->woocommerce_segment->synchronizeCustomers();
    } catch (\Exception $e) {
      $this->stopProgress($task);
      throw $e;
    }
    return true;
  }

  function complete(ScheduledTask $task) {
    return parent::complete($task);
  }
}
