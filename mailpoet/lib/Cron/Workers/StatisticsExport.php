<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;

/**
 * Async statistics export worker. Tasks are scheduled on demand from the
 * StatisticsExport API endpoint and store the export parameters in the
 * task's meta JSON column. After processing, the file URL is written back
 * to meta so the UI can poll for completion and offer the download.
 *
 * Job meta shape:
 *   - job_type: 'recipients'
 *   - newsletter_id: int (recipients job only)
 *   - format: StatisticsExporter::FORMAT_CSV | StatisticsExporter::FORMAT_XLSX
 *   - requested_by: int (WP user id)
 *   - export_file_url: string (set after processing)
 *   - total_exported: int (set after processing)
 *   - error: string (set on failure)
 */
class StatisticsExport extends SimpleWorker {
  const TASK_TYPE = 'statistics_export';
  const AUTOMATIC_SCHEDULING = false;
  const SUPPORT_MULTIPLE_INSTANCES = false;

  const JOB_TYPE_RECIPIENTS = 'recipients';

  /** @var StatisticsExporter */
  private $exporter;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(
    StatisticsExporter $exporter,
    NewslettersRepository $newslettersRepository
  ) {
    $this->exporter = $exporter;
    $this->newslettersRepository = $newslettersRepository;
    parent::__construct();
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    $meta = $task->getMeta() ?? [];
    unset($meta['export_file_url'], $meta['total_exported'], $meta['error']);
    $jobType = isset($meta['job_type']) ? (string)$meta['job_type'] : '';
    $format = isset($meta['format']) ? (string)$meta['format'] : StatisticsExporter::FORMAT_CSV;

    try {
      if ($jobType === self::JOB_TYPE_RECIPIENTS) {
        $newsletterId = isset($meta['newsletter_id']) ? (int)$meta['newsletter_id'] : 0;
        $newsletter = $this->newslettersRepository->findOneById($newsletterId);
        if (!$newsletter) {
          throw new \RuntimeException(sprintf('Newsletter %d not found.', $newsletterId));
        }
        $result = $this->exporter->exportRecipients($newsletter, $format);
      } else {
        throw new \RuntimeException(sprintf('Unsupported export job type "%s".', $jobType));
      }
      $meta['export_file_url'] = $result['exportFileURL'];
      $meta['total_exported'] = $result['totalExported'];
    } catch (\Throwable $e) {
      $meta['error'] = $e->getMessage();
    }

    $task->setMeta($meta);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
    return true;
  }
}
