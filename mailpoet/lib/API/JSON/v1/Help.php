<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;

class Help extends APIEndpoint {

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_HELP,
  ];

  private ScheduledTasksRepository $scheduledTasksRepository;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
  }

  public function cancelTask($data): Response {
    try {
      $this->validateTaskId($data);

      $task = $this->scheduledTasksRepository->findOneById($data['id']);
      if (!$task instanceof ScheduledTaskEntity) {
        return $this->errorResponse([
          APIError::NOT_FOUND => __('Task not found.', 'mailpoet'),
        ]);
      }

      $this->scheduledTasksRepository->cancelTask($task);
      return $this->successResponse();
    } catch (\Exception $e) {
      return $this->badRequest([ApiError::BAD_REQUEST => $e->getMessage()]);
    }
  }

  public function rescheduleTask($data): Response {
    try {
      $this->validateTaskId($data);

      $task = $this->scheduledTasksRepository->findOneById($data['id']);
      if (!$task instanceof ScheduledTaskEntity) {
        return $this->errorResponse([
          APIError::NOT_FOUND => __('Task not found.', 'mailpoet'),
        ]);
      }

      $this->scheduledTasksRepository->rescheduleTask($task);
      return $this->successResponse();
    } catch (\Exception $e) {
      return $this->badRequest([ApiError::BAD_REQUEST => $e->getMessage()]);
    }
  }

  private function validateTaskId($data): void {
    $isValid = isset($data['id']) && is_numeric($data['id']);
    if (!$isValid) {
      throw new \Exception(__('Invalid or missing parameter `id`.', 'mailpoet'));
    }
  }
}
