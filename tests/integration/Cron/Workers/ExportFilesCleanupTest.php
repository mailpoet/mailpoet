<?php

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\ExportFilesCleanup;
use MailPoet\Models\ScheduledTask;

class ExportFilesCleanupTest extends \MailPoetTest {
  public function testItWorks() {
    $wpUploadDir = wp_upload_dir();
    $oldFilePath = $wpUploadDir['basedir'] . '/mailpoet/MailPoet_export_old_file.csv';
    $newFilePath = $wpUploadDir['basedir'] . '/mailpoet/MailPoet_export_new_file.csv';
    touch($oldFilePath, time() - (60 * 60 * 24 * 7));
    touch($newFilePath);

    $cleanup = new ExportFilesCleanup();
    $cleanup->processTaskStrategy(ScheduledTask::createOrUpdate([]), microtime(true));

    $this->assertFileExists($newFilePath);
    $this->assertFileNotExists($oldFilePath);
  }
}
