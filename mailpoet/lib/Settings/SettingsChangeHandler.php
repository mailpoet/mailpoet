<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Settings;

use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class SettingsChangeHandler {

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SettingsController */
  private $settingsController;

  public function __construct(
    ScheduledTasksRepository $scheduledTasksRepository,
    SettingsController $settingsController
  ) {
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->settingsController = $settingsController;
  }

  public function onSubscribeOldWoocommerceCustomersChange(): void {
    $task = $this->scheduledTasksRepository->findOneBy([
      'type' => WooCommerceSync::TASK_TYPE,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
    if (!($task instanceof ScheduledTaskEntity)) {
      $task = $this->createScheduledTask(WooCommerceSync::TASK_TYPE);
    }
    $datetime = Carbon::createFromTimestamp((int)WPFunctions::get()->currentTime('timestamp'));
    $task->setScheduledAt($datetime->subMinute());
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  public function onInactiveSubscribersIntervalChange(): void {
    $task = $this->scheduledTasksRepository->findOneBy([
      'type' => InactiveSubscribers::TASK_TYPE,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
    if (!($task instanceof ScheduledTaskEntity)) {
      $task = $this->createScheduledTask(InactiveSubscribers::TASK_TYPE);
    }
    $datetime = Carbon::createFromTimestamp((int)WPFunctions::get()->currentTime('timestamp'));
    $task->setScheduledAt($datetime->subMinute());
    $this->scheduledTasksRepository->persist($task);
    $this->scheduledTasksRepository->flush();
  }

  public function onMSSActivate($newSettings) {
    // see mailpoet/assets/js/src/wizard/create_sender_settings.jsx:freeAddress
    $httpHost = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    $domain = str_replace('www.', '', $httpHost);
    if (
      isset($newSettings['sender']['address'])
      && !empty($newSettings['reply_to']['address'])
      && ($newSettings['sender']['address'] === ('wordpress@' . $domain))
    ) {
      $sender = [
        'name' => $newSettings['reply_to']['name'] ?? '',
        'address' => $newSettings['reply_to']['address'],
      ];
      $this->settingsController->set('sender', $sender);
      $this->settingsController->set('reply_to', null);
    }
  }

  private function createScheduledTask(string $type): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    return $task;
  }
}
