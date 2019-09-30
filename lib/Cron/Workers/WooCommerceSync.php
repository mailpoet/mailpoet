<?php

namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class WooCommerceSync extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_sync';
  const SUPPORT_MULTIPLE_INSTANCES = false;

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
    $this->woocommerce_segment->synchronizeCustomers();
    return true;
  }

  function complete(ScheduledTask $task) {
    return parent::complete($task);
  }
}
