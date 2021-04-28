<?php

namespace MailPoet\Subscribers;

use MailPoet\Config\MP2Migrator;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\ScheduledTaskSubscriber;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\StatisticsOpens;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsRepository;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class InactiveSubscribersController {

  private $inactivesTaskIdsTableCreated = false;

  /** @var SettingsRepository */
  private $settingsRepository;

  public function __construct(SettingsRepository $settingsRepository) {
    $this->settingsRepository = $settingsRepository;
  }

  /**
   * @param int $daysToInactive
   * @param int $batchSize
   * @return int|bool
   */
  public function markInactiveSubscribers($daysToInactive, $batchSize, $startId = null) {
    $thresholdDate = $this->getThresholdDate($daysToInactive);
    return $this->deactivateSubscribers($thresholdDate, $batchSize, $startId);
  }

  /**
   * @param int $daysToInactive
   * @param int $batchSize
   * @return int
   */
  public function markActiveSubscribers($daysToInactive, $batchSize) {
    $thresholdDate = $this->getThresholdDate($daysToInactive);
    return $this->activateSubscribers($thresholdDate, $batchSize);
  }

  /**
   * @return void
   */
  public function reactivateInactiveSubscribers() {
    $reactivateAllInactiveQuery = sprintf(
      "UPDATE %s SET status = '%s' WHERE status = '%s';",
      Subscriber::$_table, Subscriber::STATUS_SUBSCRIBED, Subscriber::STATUS_INACTIVE
    );
    ORM::rawExecute($reactivateAllInactiveQuery);
  }

  /**
   * @param int $daysToInactive
   * @return Carbon
   */
  private function getThresholdDate($daysToInactive) {
    $now = new Carbon();
    return $now->subDays($daysToInactive);
  }

  /**
   * @param Carbon $thresholdDate
   * @param int $batchSize
   * @return int|bool
   */
  private function deactivateSubscribers(Carbon $thresholdDate, $batchSize, $startId = null) {
    $subscribersTable = Subscriber::$_table;
    $scheduledTasksTable = ScheduledTask::$_table;
    $scheduledTaskSubcribresTable = ScheduledTaskSubscriber::$_table;
    $statisticsOpensTable = StatisticsOpens::$_table;
    $sendingQueuesTable = SendingQueue::$_table;

    $thresholdDateIso = $thresholdDate->toDateTimeString();
    $dayAgo = new Carbon();
    $dayAgoIso = $dayAgo->subDay()->toDateTimeString();

    // If MP2 migration occurred during detection interval we can't deactivate subscribers
    // because they are imported with original subscription date but they were not present in a list for whole period
    $mp2MigrationDate = $this->getMP2MigrationDate();
    if ($mp2MigrationDate && $mp2MigrationDate > $thresholdDate) {
      return false;
    }

    // We take into account only emails which have at least one opening tracked
    // to ensure that tracking was enabled for the particular email
    if (!$this->inactivesTaskIdsTableCreated) {
      $inactivesTaskIdsTable = sprintf("
      CREATE TEMPORARY TABLE IF NOT EXISTS inactives_task_ids
      (INDEX task_id_ids (id))
      SELECT DISTINCT task_id as id FROM $sendingQueuesTable as sq
        JOIN $scheduledTasksTable as st ON sq.task_id = st.id
        JOIN (
          SELECT so.newsletter_id
          FROM $statisticsOpensTable as so
          GROUP BY so.newsletter_id
        ) sno ON sno.newsletter_id = sq.newsletter_id
        WHERE st.processed_at > '%s'
        AND st.processed_at < '%s'",
        $thresholdDateIso, $dayAgoIso
      );
      ORM::rawExecute($inactivesTaskIdsTable);
      $this->inactivesTaskIdsTableCreated = true;
    }

    // Select subscribers who received a recent tracked email but didn't open it
    $startId = (int)$startId;
    $endId = $startId + $batchSize;
    $inactiveSubscriberIdsTmpTable = 'inactive_subscriber_ids';
    ORM::rawExecute("
      CREATE TEMPORARY TABLE IF NOT EXISTS $inactiveSubscriberIdsTmpTable
      (UNIQUE subscriber_id (id))
      SELECT DISTINCT s.id FROM $subscribersTable as s
        JOIN $scheduledTaskSubcribresTable as sts USE INDEX (subscriber_id) ON s.id = sts.subscriber_id
        JOIN inactives_task_ids task_ids ON task_ids.id = sts.task_id
      WHERE s.last_subscribed_at < ? AND s.status = ? AND s.id >= ? AND s.id < ?",
      [$thresholdDateIso, Subscriber::STATUS_SUBSCRIBED, $startId, $endId]
    );

    $idsToDeactivate = ORM::forTable($inactiveSubscriberIdsTmpTable)->rawQuery("
      SELECT s.id FROM $inactiveSubscriberIdsTmpTable s
        LEFT OUTER JOIN $statisticsOpensTable as so ON s.id = so.subscriber_id AND so.created_at > ?
        WHERE so.id IS NULL",
      [$thresholdDateIso]
    )->findArray();

    ORM::rawExecute("DROP TABLE $inactiveSubscriberIdsTmpTable");

    $idsToDeactivate = array_map(
      function ($id) {
        return (int)$id['id'];
      },
      $idsToDeactivate
    );
    if (!count($idsToDeactivate)) {
      return 0;
    }
    ORM::rawExecute(sprintf(
      "UPDATE %s SET status='" . Subscriber::STATUS_INACTIVE . "' WHERE id IN (%s);",
      $subscribersTable,
      implode(',', $idsToDeactivate)
    ));
    return count($idsToDeactivate);
  }

  /**
   * @param Carbon $thresholdDate
   * @param int $batchSize
   * @return int
   */
  private function activateSubscribers(Carbon $thresholdDate, $batchSize) {
    $subscribersTable = Subscriber::$_table;
    $statsOpensTable = StatisticsOpens::$_table;

    $mp2MigrationDate = $this->getMP2MigrationDate();
    if ($mp2MigrationDate && $mp2MigrationDate > $thresholdDate) {
      // If MP2 migration occurred during detection interval re-activate all subscribers created before migration
      $idsToActivate = ORM::forTable($subscribersTable)->select("$subscribersTable.id")
        ->whereLt("$subscribersTable.created_at", $mp2MigrationDate)
        ->where("$subscribersTable.status", Subscriber::STATUS_INACTIVE)
        ->limit($batchSize)
        ->findArray();
    } else {
      $idsToActivate = ORM::forTable($subscribersTable)->select("$subscribersTable.id")
        ->leftOuterJoin($statsOpensTable, "$subscribersTable.id = $statsOpensTable.subscriber_id AND $statsOpensTable.created_at > '$thresholdDate'")
        ->whereLt("$subscribersTable.last_subscribed_at", $thresholdDate)
        ->where("$subscribersTable.status", Subscriber::STATUS_INACTIVE)
        ->whereRaw("$statsOpensTable.id IS NOT NULL")
        ->limit($batchSize)
        ->groupByExpr("$subscribersTable.id")
        ->findArray();
    }

    $idsToActivate = array_map(
      function($id) {
        return (int)$id['id'];
      }, $idsToActivate
    );
    if (!count($idsToActivate)) {
      return 0;
    }
    ORM::rawExecute(sprintf(
      "UPDATE %s SET status='" . Subscriber::STATUS_SUBSCRIBED . "' WHERE id IN (%s);",
      $subscribersTable,
      implode(',', $idsToActivate)
    ));
    return count($idsToActivate);
  }

  private function getMP2MigrationDate() {
    $setting = $this->settingsRepository->findOneByName(MP2Migrator::MIGRATION_COMPLETE_SETTING_KEY);
    return $setting ? Carbon::instance($setting->getCreatedAt()) : null;
  }
}
