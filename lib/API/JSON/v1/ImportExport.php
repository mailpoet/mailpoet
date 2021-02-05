<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Newsletter\Options\NewsletterOptionsRepository;
use MailPoet\Segments\WP;
use MailPoet\Subscribers\ImportExport\Import\Import;
use MailPoet\Subscribers\ImportExport\Import\MailChimp;
use MailPoet\Subscribers\SubscriberCustomFieldRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

class ImportExport extends APIEndpoint {

  /** @var WP */
  private $wpSegment;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var NewsletterOptionsRepository */
  private $newsletterOptionsRepository;

  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  /** @var SubscribersRepository */
  private $subscriberRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
  ];

  public function __construct(
    WP $wpSegment,
    CustomFieldsRepository $customFieldsRepository,
    NewsletterOptionsRepository $newsletterOptionsRepository,
    SubscriberCustomFieldRepository $subscriberCustomFieldRepository,
    SubscribersRepository $subscribersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository
  ) {
    $this->wpSegment = $wpSegment;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->newsletterOptionsRepository = $newsletterOptionsRepository;
    $this->subscriberCustomFieldRepository = $subscriberCustomFieldRepository;
    $this->subscriberRepository = $subscribersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
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
    $segment = Segment::createOrUpdate($data);
    $errors = $segment->getErrors();

    if (!empty($errors)) {
      return $this->errorResponse($errors);
    } else {
      $segment = Segment::findOne($segment->id);
      if(!$segment instanceof Segment) return $this->errorResponse();
      return $this->successResponse($segment->asArray());
    }
  }

  public function processImport($data) {
    try {
      $import = new Import(
        $this->wpSegment,
        $this->customFieldsRepository,
        $this->newsletterOptionsRepository,
        $this->subscriberCustomFieldRepository,
        $this->subscriberRepository,
        $this->subscriberSegmentRepository,
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
      $export = new \MailPoet\Subscribers\ImportExport\Export\Export(
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
      $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)
        ->whereRaw('status = ? OR status IS NULL', [ScheduledTask::STATUS_SCHEDULED])
        ->findOne();
      if (($task instanceof ScheduledTask) && $task->status === null) {
        return $this->successResponse();
      }
      if (!($task instanceof ScheduledTask)) {
        $task = ScheduledTask::create();
        $task->type = WooCommerceSync::TASK_TYPE;
        $task->status = ScheduledTask::STATUS_SCHEDULED;
      }
      $task->scheduledAt = Carbon::createFromTimestamp((int)current_time('timestamp'));
      $task->save();
      return $this->successResponse();
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}
