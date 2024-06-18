<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;

class Help extends APIEndpoint {

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_HELP,
  ];

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
  }

  public function cancelTask($data) {
    $task = $this->validateAndFindTask($data);
    if (!($task instanceof ScheduledTaskEntity)) {
      return $task;
    }
    try {
      $this->scheduledTasksRepository->cancelTask($task);
      return $this->successResponse();
    } catch (\Exception $e) {
      return $this->handleException($e->getMessage());
    }
  }

  public function rescheduleTask($data) {
    $task = $this->validateAndFindTask($data);
    if (!($task instanceof ScheduledTaskEntity)) {
      return $task;
    }
    try {
      $this->scheduledTasksRepository->rescheduleTask($task);
      return $this->successResponse();
    } catch (\Exception $e) {
      return $this->handleException($e->getMessage());
    }
  }

  private function validateAndFindTask($data) {
    if (!isset($data['id'])) {
      return $this->handleException(__('Missing mandatory argument `id`.', 'mailpoet'));
    }
    $id = $data['id'];
    $task = $this->scheduledTasksRepository->findOneById($id);
    if (!$task) {
      return $this->handleException(__('Task not found.', 'mailpoet'));
    }
    return $task;
  }

  private function handleException($message): \MailPoet\API\JSON\ErrorResponse {
    return $this->badRequest([
      ApiError::BAD_REQUEST => $message,
    ]);
  }
}
