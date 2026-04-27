<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;

class StatisticsExport extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var StatisticsExporter */
  private $exporter;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    StatisticsExporter $exporter,
    LoggerFactory $loggerFactory
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->exporter = $exporter;
    $this->loggerFactory = $loggerFactory;
  }

  public function exportCampaign($data = []) {
    $newsletterId = isset($data['id']) ? (int)$data['id'] : 0;
    if ($newsletterId <= 0) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Missing newsletter id.', 'mailpoet'),
      ]);
    }

    $format = isset($data['format']) && is_string($data['format'])
      ? strtolower($data['format'])
      : StatisticsExporter::FORMAT_CSV;

    if ($format !== StatisticsExporter::FORMAT_CSV && $format !== StatisticsExporter::FORMAT_XLSX) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Unsupported export format. Use csv or xlsx.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ], [], Response::STATUS_NOT_FOUND);
    }

    try {
      $result = $this->exporter->exportSingleAggregate($newsletter, $format);
    } catch (\Throwable $e) {
      if (function_exists('error_log')) {
        // phpcs:disable QITStandard.PHP.DebugCode.DebugFunctionFound
        error_log((string)$e); // phpcs:ignore Squiz.PHP.DiscouragedFunctions
        // phpcs:enable QITStandard.PHP.DebugCode.DebugFunctionFound
      }
      $this->loggerFactory->getLogger(LoggerFactory::TOPIC_API)->warning('Campaign statistics export failed.', [
        'exceptionMessage' => $e->getMessage(),
        'exceptionTrace' => $e->getTraceAsString(),
      ]);
      return $this->errorResponse([
        APIError::UNKNOWN => __('Could not generate the export. Please try again.', 'mailpoet'),
      ], [], Response::STATUS_BAD_REQUEST);
    }

    return $this->successResponse($result);
  }
}
