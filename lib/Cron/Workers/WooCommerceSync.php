<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class WooCommerceSync extends SimpleWorker {
  const TASK_TYPE = 'woocommerce_sync';

  const TASK_RUN_TIMEOUT = 120;
  const TIMED_OUT_TASK_RESCHEDULE_TIMEOUT = 5;

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
    $meta = $task->getMeta();
    $current_time = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $updated_at = Carbon::createFromTimestamp(strtotime($task->updated_at));

    // If the task is running for too long consider it stuck and reschedule
    if (!empty($task->updated_at) && $updated_at->diffInMinutes($current_time, false) > self::TASK_RUN_TIMEOUT) {
      $task->meta = null;
      $this->reschedule($task, self::TIMED_OUT_TASK_RESCHEDULE_TIMEOUT);
      return false;
    } elseif (!empty($meta['in_progress'])) {
      // Do not run multiple instances of the task
      return false;
    }

    $task->meta = ['in_progress' => true];
    $task->save();

    try {
      $this->woocommerce_segment->synchronizeCustomers();
    } catch (\Exception $e) {
      $task->meta = null;
      $task->save();
      throw $e;
    }

    return true;
  }

  function complete(ScheduledTask $task) {
    $task->meta = null;
    return parent::complete($task);
  }
}
