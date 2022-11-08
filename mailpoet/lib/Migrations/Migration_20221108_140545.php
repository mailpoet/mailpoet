<?php declare(strict_types = 1);

namespace MailPoet\Migrations;

use MailPoet\Config\Env;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Migrator\Migration;
use MailPoet\Settings\SettingsController;

class Migration_20221108_140545 extends Migration {
  /** @var string */
  private $prefix;

  /** @var SettingsController */
  private $settings;

  public function run(): void {
    $this->prefix = Env::$dbPrefix;
    $this->settings = $this->container->get(SettingsController::class);

    $this->migrateSegmentDisplaySettingsOnManageSubscriptionPage();
  }

  private function migrateSegmentDisplaySettingsOnManageSubscriptionPage(): bool {
    global $wpdb;
    $segmentsTable = esc_sql("{$this->prefix}segments");

    // Add display_in_manage_subscription_page column in case it doesn't exist
    $displayInManageSubscriptionPageColumnExists = $wpdb->get_results($wpdb->prepare("
      SELECT COLUMN_NAME
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE table_name = %s AND column_name = 'display_in_manage_subscription_page';
     ", $segmentsTable));

    if (empty($displayInManageSubscriptionPageColumnExists)) {
      $addNewColumnQuery = "
        ALTER TABLE `$segmentsTable`
        ADD `display_in_manage_subscription_page` tinyint(1) DEFAULT 0;
      ";
      $wpdb->query($addNewColumnQuery);
    }

    $segmentIds = $this->settings->get('subscription.segments', []);

    if (!empty($segmentIds)) {
      // when a segment exist in the list (subscription.segments), show only that segment, do not display the other segments
      foreach ($segmentIds as $segmentId) {
        $wpdb->update($segmentsTable, [
          'display_in_manage_subscription_page' => 1,
        ], ['id' => $segmentId]);
      }

      $this->settings->set('subscription.segments', []);
    } else {
      $wpdb->update($segmentsTable, [
        'display_in_manage_subscription_page' => 1,
      ], ['type' => SegmentEntity::TYPE_DEFAULT]);
    }

    return true;
  }
}
