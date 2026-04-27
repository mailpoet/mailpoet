<?php declare(strict_types = 1);

namespace MailPoet\Test\Cron\Workers;

use MailPoet\Config\Env;
use MailPoet\Cron\Workers\StatisticsExport as StatisticsExportWorker;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class StatisticsExportTest extends \MailPoetTest {
  /** @var StatisticsExportWorker */
  private $worker;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var string */
  private $previousTempPath;

  /** @var string */
  private $previousTempUrl;

  /** @var string */
  private $tempDir;

  public function _before() {
    parent::_before();
    $this->worker = $this->diContainer->get(StatisticsExportWorker::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);

    $this->previousTempPath = (string)Env::$tempPath;
    $this->previousTempUrl = (string)Env::$tempUrl;
    $this->tempDir = sys_get_temp_dir() . '/mailpoet-stats-export-' . uniqid('', true);
    mkdir($this->tempDir, 0777, true);
    Env::$tempPath = $this->tempDir;
    Env::$tempUrl = 'https://example.test/uploads/mailpoet';
  }

  public function _after() {
    parent::_after();
    foreach (glob($this->tempDir . '/*') ?: [] as $file) {
      unlink($file);
    }
    if (is_dir($this->tempDir)) {
      rmdir($this->tempDir);
    }
    Env::$tempPath = $this->previousTempPath;
    Env::$tempUrl = $this->previousTempUrl;
  }

  public function testItProcessesRecipientsExportTask() {
    $newsletter = (new NewsletterFactory())->withSubject('Hello world')->create();
    $task = $this->createTask([
      'job_type' => StatisticsExportWorker::JOB_TYPE_RECIPIENTS,
      'newsletter_id' => $newsletter->getId(),
      'format' => StatisticsExporter::FORMAT_CSV,
    ]);

    $result = $this->worker->processTaskStrategy($task, microtime(true));
    verify($result)->true();

    $meta = $task->getMeta() ?? [];
    verify($meta['export_file_url'])->stringContainsString('MailPoet_stats_export_');
    verify($meta['total_exported'])->equals(0);
    verify(isset($meta['error']))->false();
  }

  public function testItRecordsErrorOnUnknownJobType() {
    $task = $this->createTask([
      'job_type' => 'unknown',
      'format' => StatisticsExporter::FORMAT_CSV,
    ]);

    $result = $this->worker->processTaskStrategy($task, microtime(true));
    verify($result)->true();

    $meta = $task->getMeta() ?? [];
    verify($meta['error'])->stringContainsString('Unsupported export job type');
  }

  private function createTask(array $meta): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(StatisticsExportWorker::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setMeta($meta);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
    return $task;
  }
}
