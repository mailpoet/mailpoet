<?php

namespace MailPoet\API\JSON\v1;

use InvalidArgumentException;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ResponseBuilders\SegmentsResponseBuilder;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Doctrine\Validator\ValidationException;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentSaveController;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP;
use MailPoet\Subscribers\ImportExport\Export\Export;
use MailPoet\Subscribers\ImportExport\Import\Import;
use MailPoet\Subscribers\ImportExport\Import\MailChimp;
use MailPoet\Subscribers\ImportExport\ImportExportRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

class ImportExport extends APIEndpoint {

  /** @var WP */
  private $wpSegment;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var ImportExportRepository */
  private $importExportRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscribersRepository */
  private $subscriberRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SegmentSaveController */
  private $segmentSavecontroller;

  /** @var SegmentsResponseBuilder */
  private $segmentsResponseBuilder;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
  ];

  public function __construct(
    WP $wpSegment,
    CustomFieldsRepository $customFieldsRepository,
    ImportExportRepository $importExportRepository,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    SegmentsRepository $segmentsRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    SegmentSaveController $segmentSavecontroller,
    SegmentsResponseBuilder $segmentsResponseBuilder,
    SubscribersRepository $subscribersRepository
  ) {
    $this->wpSegment = $wpSegment;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->importExportRepository = $importExportRepository;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->subscriberRepository = $subscribersRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->segmentSavecontroller = $segmentSavecontroller;
    $this->segmentsResponseBuilder = $segmentsResponseBuilder;
  }

  public function getMailChimpLists($data) {
    try {
      $mailChimp = new MailChimp($data['api_key']);
      $lists = $mailChimp->getLists();
      return $this->successResponse($lists);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  public function getMailChimpSubscribers($data) {
    try {
      $mailChimp = new MailChimp($data['api_key']);
      $subscribers = $mailChimp->getSubscribers($data['lists']);
      return $this->successResponse($subscribers);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  public function addSegment($data) {
    try {
      $segment = $this->segmentSavecontroller->save($data);
      $response = $this->segmentsResponseBuilder->build($segment);
      return $this->successResponse($response);
    } catch (ValidationException $exception) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => __('Please specify a name.', 'mailpoet'),
      ]);
    } catch (InvalidArgumentException $exception) {
      return $this->badRequest([
        APIError::BAD_REQUEST  => __('Another record already exists. Please specify a different "name".', 'mailpoet'),
      ]);
    }
  }

  public function processImport($data) {
    try {
      $import = new Import(
        $this->wpSegment,
        $this->customFieldsRepository,
        $this->importExportRepository,
        $this->newsletterOptionsRepository,
        $this->subscriberRepository,
        json_decode($data, true)
      );
      $process = $import->process();
      return $this->successResponse($process);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  public function processExport($data) {
    try {
      $export = new Export(
        $this->customFieldsRepository,
        $this->importExportRepository,
        $this->segmentsRepository,
        json_decode($data, true)
      );
      $process = $export->process();
      return $this->successResponse($process);
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }

  public function setupWooCommerceInitialImport() {
    try {
      $task = $this->scheduledTasksRepository->findScheduledOrRunningTask(WooCommerceSync::TASK_TYPE);
      if (($task instanceof ScheduledTaskEntity) && $task->getStatus() === null) {
        return $this->successResponse();
      }
      if (!($task instanceof ScheduledTaskEntity)) {
        $task = new ScheduledTaskEntity();
        $task->setType(WooCommerceSync::TASK_TYPE);
        $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
        $this->scheduledTasksRepository->persist($task);
      }
      $task->setScheduledAt(Carbon::createFromTimestamp((int)current_time('timestamp')));
      $this->scheduledTasksRepository->flush();
      return $this->successResponse();
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}
