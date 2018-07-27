<?php

namespace MailPoet\Tasks;


use MailPoet\Models\ScheduledTask;

class State
{
  /**
   * @return array
   */
  function getCountsPerStatus() {
    $stats = [
      ScheduledTask::STATUS_COMPLETED => 0,
      ScheduledTask::STATUS_PAUSED => 0,
      ScheduledTask::STATUS_SCHEDULED => 0,
      ScheduledTask::VIRTUAL_STATUS_RUNNING => 0,
    ];
    $counts = ScheduledTask::rawQuery(
      "SELECT COUNT(*) as value, status
       FROM `" . ScheduledTask::$_table . "`
       WHERE deleted_at IS NULL AND `type` = 'sending'
       GROUP BY status;"
    )->findMany();
    foreach($counts as $count) {
      if($count->status === null) {
        $stats[ScheduledTask::VIRTUAL_STATUS_RUNNING] = (int)$count->value;
        continue;
      }
      $stats[$count->status] = (int)$count->value;
    }
    return $stats;
  }
}
