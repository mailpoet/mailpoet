<?php

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Cron\Workers\ExportFilesCleanup;
use MailPoet\Models\ScheduledTask;

class ExportFilesCleanupTest extends \MailPoetTest {

  public function testItWorks() {
    $wp_upload_dir = wp_upload_dir();
    $old_file_path = $wp_upload_dir['basedir'] . '/mailpoet/MailPoet_export_old_file.csv';
    $new_file_path = $wp_upload_dir['basedir'] . '/mailpoet/MailPoet_export_new_file.csv';
    touch($old_file_path, time() - (60 * 60 * 24 * 7));
    touch($new_file_path);

    $cleanup = new ExportFilesCleanup();
    $cleanup->processTaskStrategy(ScheduledTask::createOrUpdate([]), microtime(true));

    $this->assertFileExists($new_file_path);
    $this->assertFileNotExists($old_file_path);
  }

}
