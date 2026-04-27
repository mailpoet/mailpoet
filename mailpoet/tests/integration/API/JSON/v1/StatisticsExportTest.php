<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\StatisticsExport;
use MailPoet\Config\Env;
use MailPoet\Cron\Workers\StatisticsExport as StatisticsExportWorker;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\Util\License\Features\Data\Capability;

class StatisticsExportTest extends \MailPoetTest {
  /** @var StatisticsExport */
  private $endpoint;

  /** @var string */
  private $previousTempPath;

  /** @var string */
  private $previousTempUrl;

  /** @var string */
  private $tempDir;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(StatisticsExport::class);

    $this->previousTempPath = (string)Env::$tempPath;
    $this->previousTempUrl = (string)Env::$tempUrl;
    $this->tempDir = sys_get_temp_dir() . '/mailpoet-stats-export-' . uniqid('', true);
    mkdir($this->tempDir, 0777, true);
    Env::$tempPath = $this->tempDir;
    Env::$tempUrl = 'https://example.test/uploads/mailpoet';
  }

  public function _after() {
    foreach (glob($this->tempDir . '/*') ?: [] as $file) {
      unlink($file);
    }
    if (is_dir($this->tempDir)) {
      rmdir($this->tempDir);
    }
    Env::$tempPath = $this->previousTempPath;
    Env::$tempUrl = $this->previousTempUrl;

    parent::_after();
  }

  public function testItRejectsMissingNewsletterId() {
    $response = $this->endpoint->exportCampaign([]);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  public function testItRejectsUnsupportedFormat() {
    $newsletter = (new NewsletterFactory())->withSubject('Hello')->create();
    $response = $this->endpoint->exportCampaign([
      'id' => $newsletter->getId(),
      'format' => 'pdf',
    ]);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  public function testItReturns404ForUnknownNewsletter() {
    $response = $this->endpoint->exportCampaign([
      'id' => 999999,
      'format' => StatisticsExporter::FORMAT_CSV,
    ]);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItExportsCampaignAggregateAsCsv() {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Spring sale!')
      ->create();

    $response = $this->endpoint->exportCampaign([
      'id' => $newsletter->getId(),
      'format' => StatisticsExporter::FORMAT_CSV,
    ]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['totalExported'])->equals(1);
    verify($response->data['exportFileURL'])->stringStartsWith('https://example.test/uploads/mailpoet/MailPoet_stats_export_');
    verify($response->data['exportFileURL'])->stringEndsWith('.csv');

    $files = glob($this->tempDir . '/*.csv') ?: [];
    verify($files)->arrayCount(1);

    $content = (string)file_get_contents($files[0]);
    verify(substr($content, 0, 3))->equals(chr(0xEF) . chr(0xBB) . chr(0xBF));
    verify($content)->stringContainsString('Spring sale!');
  }

  public function testItExportsCampaignAggregateAsXlsx() {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Black Friday')
      ->create();

    $response = $this->endpoint->exportCampaign([
      'id' => $newsletter->getId(),
      'format' => StatisticsExporter::FORMAT_XLSX,
    ]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['exportFileURL'])->stringEndsWith('.xlsx');

    $files = glob($this->tempDir . '/*.xlsx') ?: [];
    verify($files)->arrayCount(1);
    verify(filesize($files[0]))->greaterThan(0);
  }

  public function testItDefaultsToCsvWhenFormatMissing() {
    $newsletter = (new NewsletterFactory())->create();

    $response = $this->endpoint->exportCampaign([
      'id' => $newsletter->getId(),
    ]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['exportFileURL'])->stringEndsWith('.csv');
  }

  public function testItRejectsRecipientsExportWhenDetailedAnalyticsRestricted() {
    $newsletter = (new NewsletterFactory())->create();
    $response = $this->endpoint->exportRecipients([
      'id' => $newsletter->getId(),
      'format' => StatisticsExporter::FORMAT_CSV,
    ]);
    verify($response->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItReturns404ForUnknownStatusTask() {
    $endpoint = $this->endpointWithDetailedAnalytics(false);
    $response = $endpoint->getStatus(['task_id' => 99999999]);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItRejectsStatusWhenDetailedAnalyticsRestricted() {
    $task = $this->createTask(['job_type' => StatisticsExportWorker::JOB_TYPE_RECIPIENTS]);
    $response = $this->endpoint->getStatus(['task_id' => $task->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_FORBIDDEN);
  }

  public function testItReturns404ForUnrelatedTaskJobType() {
    $endpoint = $this->endpointWithDetailedAnalytics(false);
    $task = $this->createTask(['job_type' => 'something_else']);
    $response = $endpoint->getStatus(['task_id' => $task->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItReturnsStatusForScheduledExportTask() {
    $endpoint = $this->endpointWithDetailedAnalytics(false);
    $userId = (int)wp_get_current_user()->ID;
    $task = $this->createTask([
      'job_type' => StatisticsExportWorker::JOB_TYPE_RECIPIENTS,
      'requested_by' => $userId,
    ]);

    $response = $endpoint->getStatus(['task_id' => $task->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['taskId'])->equals($task->getId());
    verify($response->data['status'])->equals(ScheduledTaskEntity::STATUS_SCHEDULED);
  }

  public function testItReturns404WhenStatusRequestedByDifferentUser() {
    $endpoint = $this->endpointWithDetailedAnalytics(false);
    $task = $this->createTask([
      'job_type' => StatisticsExportWorker::JOB_TYPE_RECIPIENTS,
      'requested_by' => 999999,
    ]);

    $response = $endpoint->getStatus(['task_id' => $task->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  private function createTask(array $meta): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType(StatisticsExportWorker::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setMeta($meta);
    $repo = ContainerWrapper::getInstance()->get(ScheduledTasksRepository::class);
    $repo->persist($task);
    $repo->flush();
    return $task;
  }

  private function endpointWithDetailedAnalytics(bool $isRestricted): StatisticsExport {
    $capability = new Capability('detailedAnalytics', Capability::TYPE_BOOLEAN, $isRestricted);
    return $this->getServiceWithOverrides(StatisticsExport::class, [
      'capabilitiesManager' => Stub::makeEmpty(CapabilitiesManager::class, [
        'getCapability' => $capability,
      ]),
    ]);
  }
}
