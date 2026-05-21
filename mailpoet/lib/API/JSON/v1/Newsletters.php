<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\Workers\StatisticsExport as StatisticsExportWorker;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Listing;
use MailPoet\Newsletter\BulkActionController;
use MailPoet\Newsletter\BulkActionException;
use MailPoet\Newsletter\Listing\NewsletterListingRepository;
use MailPoet\Newsletter\NewsletterDeleteController;
use MailPoet\Newsletter\NewsletterResendController;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Preview\SendPreviewController;
use MailPoet\Newsletter\Preview\SendPreviewException;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Statistics\Export\StatisticsExporter;
use MailPoet\Newsletter\StatusController;
use MailPoet\Newsletter\Url as NewsletterUrl;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailCustomizer;
use MailPoet\UnexpectedValueException;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\WP\Emoji;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Newsletters extends APIEndpoint {

  /** @var Listing\Handler */
  private $listingHandler;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterListingRepository */
  private $newsletterListingRepository;

  /** @var NewslettersResponseBuilder */
  private $newslettersResponseBuilder;

  /** @var Emoji */
  private $emoji;

  /** @var SendPreviewController */
  private $sendPreviewController;

  /** @var NewsletterSaveController */
  private $newsletterSaveController;

  private NewsletterDeleteController $newsletterDeleteController;

  /** @var NewsletterResendController */
  private $newsletterResendController;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var CapabilitiesManager */
  private $capabilitiesManager;

  /** @var ConfirmationEmailCustomizer */
  private $confirmationEmailCustomizer;

  /** @var BulkActionController */
  private $bulkActionController;

  /** @var StatusController */
  private $statusController;

  public function __construct(
    Listing\Handler $listingHandler,
    WPFunctions $wp,
    SettingsController $settings,
    CronHelper $cronHelper,
    NewslettersRepository $newslettersRepository,
    NewsletterListingRepository $newsletterListingRepository,
    NewslettersResponseBuilder $newslettersResponseBuilder,
    Emoji $emoji,
    SendPreviewController $sendPreviewController,
    NewsletterSaveController $newsletterSaveController,
    NewsletterDeleteController $newsletterDeleteController,
    NewsletterResendController $newsletterResendController,
    NewsletterUrl $newsletterUrl,
    ScheduledTasksRepository $scheduledTasksRepository,
    CapabilitiesManager $capabilitiesManager,
    ConfirmationEmailCustomizer $confirmationEmailCustomizer,
    BulkActionController $bulkActionController,
    StatusController $statusController
  ) {
    $this->listingHandler = $listingHandler;
    $this->wp = $wp;
    $this->settings = $settings;
    $this->cronHelper = $cronHelper;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterListingRepository = $newsletterListingRepository;
    $this->newslettersResponseBuilder = $newslettersResponseBuilder;
    $this->emoji = $emoji;
    $this->sendPreviewController = $sendPreviewController;
    $this->newsletterSaveController = $newsletterSaveController;
    $this->newsletterDeleteController = $newsletterDeleteController;
    $this->newsletterResendController = $newsletterResendController;
    $this->newsletterUrl = $newsletterUrl;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->capabilitiesManager = $capabilitiesManager;
    $this->confirmationEmailCustomizer = $confirmationEmailCustomizer;
    $this->bulkActionController = $bulkActionController;
    $this->statusController = $statusController;
  }

  public function get($data = []) {
    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $response = $this->newslettersResponseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_SEGMENTS,
      NewslettersResponseBuilder::RELATION_OPTIONS,
      NewslettersResponseBuilder::RELATION_QUEUE,
    ]);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $response);
    return $this->successResponse($response, ['preview_url' => $this->getViewInBrowserUrl($newsletter)]);
  }

  public function getWithStats($data = []) {
    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $response = $this->newslettersResponseBuilder->build($newsletter, [
        NewslettersResponseBuilder::RELATION_SEGMENTS,
        NewslettersResponseBuilder::RELATION_OPTIONS,
        NewslettersResponseBuilder::RELATION_QUEUE,
        NewslettersResponseBuilder::RELATION_TOTAL_SENT,
        NewslettersResponseBuilder::RELATION_STATISTICS,
    ]);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_get_after', $response);
    if (!is_array($response)) {
      $response = [];
    }
    $response['preview_url'] = $this->getViewInBrowserUrl($newsletter);
    return $this->successResponse($response);
  }

  public function save($data = []) {
    $data = $this->wp->applyFilters('mailpoet_api_newsletters_save_before', $data);
    if (!is_array($data)) {
      $data = [];
    }
    $newsletter = $this->newsletterSaveController->save($data);
    $response = $this->newslettersResponseBuilder->build($newsletter, [
      NewslettersResponseBuilder::RELATION_SEGMENTS,
    ]);
    $previewUrl = $this->getViewInBrowserUrl($newsletter);
    $response = $this->wp->applyFilters('mailpoet_api_newsletters_save_after', $response);
    return $this->successResponse($response, ['preview_url' => $previewUrl]);
  }

  public function updateShareVisibility($data = []) {
    if (!is_array($data) || !isset($data['share_visibility']) || !is_string($data['share_visibility'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('You need to specify a sharing visibility.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->newsletterSaveController->updateShareVisibility($newsletter, $data['share_visibility']);
    $response = $this->newslettersResponseBuilder->build($newsletter);
    return $this->successResponse($response);
  }

  /**
   * @deprecated Use the REST endpoint `PUT /mailpoet/v1/newsletters/{id}/status`
   *   instead. Kept callable for third-party integrations. The orchestration
   *   lives in {@see StatusController}.
   */
  public function setStatus($data = []) {
    $status = (isset($data['status']) ? $data['status'] : null);
    if (!$status) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('You need to specify a status.', 'mailpoet'),
      ]);
    }
    $newsletter = $this->getNewsletter($data);
    if ($newsletter === null) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
    try {
      $updated = $this->statusController->setStatus($newsletter, (string)$status);
    } catch (BulkActionException $exception) {
      return $this->errorResponse(
        [$exception->getErrorCode() => $exception->getMessage()],
        [],
        $exception->getStatusCode()
      );
    }
    return $this->successResponse($this->newslettersResponseBuilder->build($updated));
  }

  public function restore($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->newslettersRepository->bulkRestore([$newsletter->getId()]);
      $this->newslettersRepository->refresh($newsletter);
      return $this->successResponse(
        $this->newslettersResponseBuilder->build($newsletter),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function trash($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->newslettersRepository->bulkTrash([$newsletter->getId()]);
      $this->newslettersRepository->refresh($newsletter);
      return $this->successResponse(
        $this->newslettersResponseBuilder->build($newsletter),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function delete($data = []) {
    $newsletter = $this->getNewsletter($data);
    if ($newsletter instanceof NewsletterEntity) {
      $this->wp->doAction('mailpoet_api_newsletters_delete_before', [$newsletter->getId()]);
      $this->newsletterDeleteController->bulkDelete([(int)$newsletter->getId()]);
      $this->wp->doAction('mailpoet_api_newsletters_delete_after', [$newsletter->getId()]);
      return $this->successResponse(null, ['count' => 1]);
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  /**
   * @deprecated Use the REST endpoint `POST /mailpoet/v1/newsletters/{id}/duplicate`
   *   instead. Kept callable for third-party integrations.
   */
  public function duplicate($data = []) {
    $newsletter = $this->getNewsletter($data);

    if ($newsletter instanceof NewsletterEntity) {
      $duplicate = $this->newsletterSaveController->duplicate($newsletter);
      $this->wp->doAction('mailpoet_api_newsletters_duplicate_after', $newsletter, $duplicate);
      return $this->successResponse(
        $this->newslettersResponseBuilder->build($duplicate),
        ['count' => 1]
      );
    } else {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
  }

  public function showPreview($data = []) {
    if (empty($data['body'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Newsletter data is missing.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    $newslettersTableName = $this->newslettersRepository->getTableName();
    $newsletter->setBody(
      json_decode($this->emoji->encodeForUTF8Column($newslettersTableName, 'body', $data['body']), true)
    );
    $this->newslettersRepository->flush();

    $response = $this->newslettersResponseBuilder->build($newsletter);
    return $this->successResponse($response, ['preview_url' => $this->getViewInBrowserUrl($newsletter)]);
  }

  public function sendPreview($data = []) {
    if (empty($data['subscriber'])) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('Please specify receiver information.', 'mailpoet'),
      ]);
    }

    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }

    try {
      $this->sendPreviewController->sendPreview($newsletter, $data['subscriber']);
    } catch (SendPreviewException $e) {
      return $this->errorResponse([APIError::BAD_REQUEST => $e->getMessage()]);
    } catch (\Throwable $e) {
      return $this->errorResponse([$e->getCode() => $e->getMessage()]);
    }
    return $this->successResponse($this->newslettersResponseBuilder->build($newsletter));
  }

  /**
   * @deprecated Use the REST endpoint `GET /mailpoet/v1/newsletters` instead.
   *   Kept callable for third-party integrations posting to the legacy JSON API.
   */
  public function listing($data = []) {
    $definition = $this->listingHandler->getListingDefinition($data);
    $items = $this->newsletterListingRepository->getData($definition);
    $count = $this->newsletterListingRepository->getCount($definition);
    $filters = $this->newsletterListingRepository->getFilters($definition);
    $groups = $this->newsletterListingRepository->getGroups($definition);

    $data = [];
    foreach ($this->newslettersResponseBuilder->buildForListing($items) as $newsletterData) {
      $data[] = $this->wp->applyFilters('mailpoet_api_newsletters_listing_item', $newsletterData);
    }

    return $this->successResponse($data, [
      'count' => $count,
      'filters' => $filters,
      'groups' => $groups,
      'mta_log' => $this->settings->get('mta_log'),
      'mta_method' => $this->settings->get('mta.method'),
      'cron_accessible' => $this->cronHelper->isDaemonAccessible(),
      'current_time' => $this->wp->currentTime('mysql'),
    ]);
  }

  /**
   * @deprecated Use the REST endpoint `POST /mailpoet/v1/newsletters/bulk-action`
   *   instead. Kept callable for third-party integrations. The orchestration
   *   lives in {@see BulkActionController}; `export_stats` is still handled
   *   inline because it is premium-gated and async.
   */
  public function bulkAction($data = []) {
    $action = (string)($data['action'] ?? '');
    $definition = $this->listingHandler->getListingDefinition($data['listing']);

    if ($action === 'export_stats') {
      $ids = $this->newsletterListingRepository->getActionableIds($definition);
      return $this->scheduleStatsExport($ids, $data);
    }

    try {
      $result = $this->bulkActionController->execute($action, $definition);
    } catch (BulkActionException $exception) {
      return $this->errorResponse(
        [$exception->getErrorCode() => $exception->getMessage()],
        [],
        $exception->getStatusCode()
      );
    }
    return $this->successResponse(null, ['count' => $result['count']]);
  }

  /**
   * Schedules an asynchronous bulk stats export task and returns its id.
   * Premium-gated via the detailedAnalytics capability.
   *
   * @param int[] $ids
   */
  private function scheduleStatsExport(array $ids, array $data) {
    $capability = $this->capabilitiesManager->getCapability('detailedAnalytics');
    if ($capability === null || $capability->isRestricted) {
      return $this->errorResponse([
        APIError::FORBIDDEN => __('Bulk statistics export requires a MailPoet plan with detailed analytics.', 'mailpoet'),
      ], [], Response::STATUS_FORBIDDEN);
    }

    if (empty($ids)) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('No newsletters selected for export.', 'mailpoet'),
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

    $task = new ScheduledTaskEntity();
    $task->setType(StatisticsExportWorker::TASK_TYPE);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $task->setScheduledAt(Carbon::now()->millisecond(0));
    $task->setPriority(ScheduledTaskEntity::PRIORITY_HIGH);
    $task->setMeta([
      'job_type' => StatisticsExportWorker::JOB_TYPE_BULK,
      'newsletter_ids' => array_values(array_map('intval', $ids)),
      'format' => $format,
      'requested_by' => (int)$this->wp->getCurrentUserId(),
    ]);
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();

    return $this->successResponse([
      'taskId' => (int)$task->getId(),
      'count' => count($ids),
    ]);
  }

  public function create($data = []) {
    try {
      $newsletter = $this->newsletterSaveController->save($data);
    } catch (ValidationException $exception) {
      return $this->badRequest(['Please specify a type.']);
    }
    $response = $this->newslettersResponseBuilder->build($newsletter);
    return $this->successResponse($response);
  }

  public function resendToNonOpeners($data = []) {
    $newsletter = $this->getNewsletter($data);
    if (!$newsletter) {
      return $this->errorResponse([
        APIError::NOT_FOUND => __('This email does not exist.', 'mailpoet'),
      ]);
    }
    $subject = isset($data['subject']) ? (string)$data['subject'] : '';
    try {
      $duplicate = $this->newsletterResendController->resendToNonOpeners($newsletter, $subject);
    } catch (UnexpectedValueException $e) {
      return $this->badRequest([
        APIError::BAD_REQUEST => $e->getMessage(),
      ]);
    }
    return $this->successResponse(
      $this->newslettersResponseBuilder->build($duplicate),
      ['count' => 1]
    );
  }

  private function getNewsletter(array $data) {
    return isset($data['id'])
      ? $this->newslettersRepository->findOneById((int)$data['id'])
      : null;
  }

  private function getViewInBrowserUrl(NewsletterEntity $newsletter): string {
    $url = $this->newsletterUrl->getViewInBrowserUrl($newsletter);
    // strip protocol to avoid mix content error
    return preg_replace('/^https?:/i', '', $url);
  }

  /**
   * Get all confirmation email newsletters for use in form editor.
   * @return Response
   */
  public function getConfirmationEmails() {
    $newsletters = $this->newslettersRepository->findBy([
      'type' => NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER,
      'deletedAt' => null,
    ]);

    $result = [];
    foreach ($newsletters as $newsletter) {
      $id = $newsletter->getId();
      if ($id === null) {
        continue;
      }
      $result[] = [
        'id' => $id,
        'subject' => $newsletter->getSubject() ?: __('(no subject)', 'mailpoet'),
      ];
    }

    return $this->successResponse($result);
  }

  /**
   * Create a new confirmation email from the global default template.
   * @return Response
   */
  public function createConfirmationEmail() {
    // Get the global default confirmation email as a base
    $defaultNewsletter = $this->confirmationEmailCustomizer->getNewsletter();

    $newsletterData = [
      'type' => NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER,
      'subject' => $defaultNewsletter->getSubject(),
      'body' => json_encode($defaultNewsletter->getBody()),
    ];

    $newsletter = $this->newsletterSaveController->save($newsletterData);

    return $this->successResponse([
      'id' => $newsletter->getId(),
      'subject' => $newsletter->getSubject(),
    ]);
  }
}
