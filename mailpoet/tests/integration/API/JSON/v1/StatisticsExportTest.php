<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\StatisticsExport;
use MailPoet\Config\Env;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

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
}
