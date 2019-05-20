<?php

namespace MailPoet\API\JSON\v1;

use Carbon\Carbon;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Subscribers\ImportExport\Import\MailChimp;

if (!defined('ABSPATH')) exit;

class ImportExport extends APIEndpoint {
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SUBSCRIBERS,
  ];

  function getMailChimpLists($data) {
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

  function getMailChimpSubscribers($data) {
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

  function addSegment($data) {
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

  function processImport($data) {
    try {
      $import = new \MailPoet\Subscribers\ImportExport\Import\Import(
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

  function processExport($data) {
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

  function setupWooCommerceInitialImport() {
    try {
      $task = ScheduledTask::where('type', WooCommerceSync::TASK_TYPE)
        ->whereRaw('status = ? OR status IS NULL', [ScheduledTask::STATUS_SCHEDULED])
        ->findOne();
      if ($task && $task->status === null) {
        return $this->successResponse();
      }
      if (!$task) {
        $task = ScheduledTask::create();
        $task->type = WooCommerceSync::TASK_TYPE;
        $task->status = ScheduledTask::STATUS_SCHEDULED;
      }
      $task->scheduled_at = Carbon::createFromTimestamp(current_time('timestamp'));
      $task->save();
      return $this->successResponse();
    } catch (\Exception $e) {
      return $this->errorResponse([
        $e->getCode() => $e->getMessage(),
      ]);
    }
  }
}
