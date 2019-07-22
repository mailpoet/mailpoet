<?php

namespace MailPoet\Subscribers;

use Carbon\Carbon;
use MailPoet\Config\MP2Migrator;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Setting;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;

if (!defined('ABSPATH')) exit;

class InactiveSubscribersController {

  /**
   * @param int $days_to_inactive
   * @param int $batch_size
   * @return int
   */
  function markInactiveSubscribers($days_to_inactive, $batch_size) {
    $threshold_date = $this->getThresholdDate($days_to_inactive);
    return $this->deactivateSubscribers($threshold_date, $batch_size);
  }

  /**
   * @param int $days_to_inactive
   * @param int $batch_size
   * @return int
   */
  function markActiveSubscribers($days_to_inactive, $batch_size) {
    $threshold_date = $this->getThresholdDate($days_to_inactive);
    return $this->activateSubscribers($threshold_date, $batch_size);
  }

  /**
   * @return void
   */
  function reactivateInactiveSubscribers() {
    $reactivate_all_inactive_query = sprintf(
      "UPDATE %s SET status = '%s' WHERE status = '%s';",
      Subscriber::$_table, Subscriber::STATUS_SUBSCRIBED, Subscriber::STATUS_INACTIVE
    );
    \ORM::rawExecute($reactivate_all_inactive_query);
  }

  /**
   * @param int $days_to_inactive
   * @return Carbon
   */
  private function getThresholdDate($days_to_inactive) {
    $now = new Carbon();
    return $now->subDays($days_to_inactive);
  }

  /**
   * @param Carbon $threshold_date
   * @param int $batch_size
   * @return int
   */
  private function deactivateSubscribers(Carbon $threshold_date, $batch_size) {
    $subscribers_table = Subscriber::$_table;
    $scheduled_tasks_table = ScheduledTask::$_table;
    $scheduled_task_subcribres_table = ScheduledTaskSubscriber::$_table;
    $statistics_opens_table = StatisticsOpens::$_table;
    $sending_queues_table = SendingQueue::$_table;

    $threshold_date_iso = $threshold_date->toIso8601String();
    $day_ago = new Carbon();
    $day_ago_iso = $day_ago->subDay()->toIso8601String();

    // We take into account only emails which have at least one opening tracked
    // to ensure that tracking was enabled for the particular email
    $scheduled_task_ids_query = sprintf("
      SELECT task_id as id FROM $sending_queues_table as sq
        JOIN (SELECT newsletter_id as id FROM $statistics_opens_table as so WHERE so.created_at > '%s' GROUP BY newsletter_id) newsletters_ids ON newsletters_ids.id = sq.newsletter_id
        JOIN $scheduled_tasks_table as st ON sq.task_id = st.id AND st.processed_at > '%s' AND st.processed_at < '%s'
      GROUP BY task_id",
      $threshold_date_iso, $threshold_date_iso, $day_ago_iso
    );

    // If MP2 migration occurred during detection interval we can't deactivate subscribers
    // because they are imported with original subscription date but they were not present in a list for whole period
    $mp2_migration_date = $this->getMP2MigrationDate();
    if ($mp2_migration_date && $mp2_migration_date > $threshold_date) {
      return 0;
    }

    // Select subscribers who received a recent tracked email but didn't open it
    $ids_to_deactivate = \ORM::forTable($subscribers_table)->rawQuery("
      SELECT s.id FROM $subscribers_table as s
        JOIN $scheduled_task_subcribres_table as sts ON s.id = sts.subscriber_id
        JOIN ($scheduled_task_ids_query) task_ids ON task_ids.id = sts.task_id
        LEFT OUTER JOIN $statistics_opens_table as so ON s.id = so.subscriber_id AND so.created_at > ?
      WHERE s.last_subscribed_at < ? AND s.status = ? AND so.id IS NULL
      GROUP BY s.id LIMIT ?",
      [$threshold_date_iso, $threshold_date_iso, Subscriber::STATUS_SUBSCRIBED, $batch_size]
    )->findArray();

    $ids_to_deactivate = array_map(
      function ($id) {
        return (int)$id['id'];
      },
      $ids_to_deactivate
    );
    if (!count($ids_to_deactivate)) {
      return 0;
    }
    \ORM::rawExecute(sprintf(
      "UPDATE %s SET status='" . Subscriber::STATUS_INACTIVE . "' WHERE id IN (%s);",
      $subscribers_table,
      implode(',', $ids_to_deactivate)
    ));
    return count($ids_to_deactivate);
  }

  /**
   * @param Carbon $threshold_date
   * @param int $batch_size
   * @return int
   */
  private function activateSubscribers(Carbon $threshold_date, $batch_size) {
    $subscribers_table = Subscriber::$_table;
    $stats_opens_table = StatisticsOpens::$_table;

    $mp2_migration_date = $this->getMP2MigrationDate();
    if ($mp2_migration_date && $mp2_migration_date > $threshold_date) {
      // If MP2 migration occurred during detection interval re-activate all subscribers created before migration
      $ids_to_activate = \ORM::forTable($subscribers_table)->select("$subscribers_table.id")
        ->whereLt("$subscribers_table.created_at", $mp2_migration_date)
        ->where("$subscribers_table.status", Subscriber::STATUS_INACTIVE)
        ->limit($batch_size)
        ->findArray();
    } else {
      $ids_to_activate = \ORM::forTable($subscribers_table)->select("$subscribers_table.id")
        ->leftOuterJoin($stats_opens_table, "$subscribers_table.id = $stats_opens_table.subscriber_id AND $stats_opens_table.created_at > '$threshold_date'")
        ->whereLt("$subscribers_table.last_subscribed_at", $threshold_date)
        ->where("$subscribers_table.status", Subscriber::STATUS_INACTIVE)
        ->whereRaw("$stats_opens_table.id IS NOT NULL")
        ->limit($batch_size)
        ->groupByExpr("$subscribers_table.id")
        ->findArray();
    }

    $ids_to_activate = array_map(
      function($id) {
        return (int)$id['id'];
      }, $ids_to_activate
    );
    if (!count($ids_to_activate)) {
      return 0;
    }
    \ORM::rawExecute(sprintf(
      "UPDATE %s SET status='" . Subscriber::STATUS_SUBSCRIBED . "' WHERE id IN (%s);",
      $subscribers_table,
      implode(',', $ids_to_activate)
    ));
    return count($ids_to_activate);
  }

  private function getMP2MigrationDate() {
    $migration_complete = Setting::where('name', MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY)->findOne();
    if ($migration_complete === false) {
      return null;
    }
    return new Carbon($migration_complete->created_at);
  }
}
