<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Cache\TransientCache;
use MailPoet\Config\AccessControl;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\SubscribersEngagementScore;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Form\FormMessageController;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentSubscribersRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Settings extends APIEndpoint {

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  /** @var AuthorizedEmailsController */
  private $authorizedEmailsController;

  /** @var TransactionalEmails */
  private $wcTransactionalEmails;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var WPFunctions */
  private $wp;

  /** @var EntityManager */
  private $entityManager;

  /** @var StatisticsOpensRepository */
  private $statisticsOpensRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var FormMessageController */
  private $messageController;

  /** @var TransientCache */
  private $transientCache;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentSubscribersRepository */
  private $segmentSubscribersRepository;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];

  public function __construct(
    SettingsController $settings,
    Bridge $bridge,
    AuthorizedEmailsController $authorizedEmailsController,
    TransactionalEmails $wcTransactionalEmails,
    WPFunctions $wp,
    EntityManager $entityManager,
    StatisticsOpensRepository $statisticsOpensRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    FormMessageController $messageController,
    ServicesChecker $servicesChecker,
    TransientCache $transientCache,
    SegmentsRepository $segmentsRepository,
    SegmentSubscribersRepository $segmentSubscribersRepository
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->wcTransactionalEmails = $wcTransactionalEmails;
    $this->servicesChecker = $servicesChecker;
    $this->wp = $wp;
    $this->entityManager = $entityManager;
    $this->statisticsOpensRepository = $statisticsOpensRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->messageController = $messageController;
    $this->transientCache = $transientCache;
    $this->segmentsRepository = $segmentsRepository;
    $this->segmentSubscribersRepository = $segmentSubscribersRepository;
  }

  public function get() {
    return $this->successResponse($this->settings->getAll());
  }

  public function set($settings = []) {
    if (empty($settings)) {
      return $this->badRequest(
        [
          APIError::BAD_REQUEST =>
            WPFunctions::get()->__('You have not specified any settings to be saved.', 'mailpoet'),
        ]);
    } else {
      $oldSettings = $this->settings->getAll();
      $signupConfirmation = $this->settings->get('signup_confirmation.enabled');
      foreach ($settings as $name => $value) {
        $this->settings->set($name, $value);
      }

      $this->onSettingsChange($oldSettings, $this->settings->getAll());

      // when pending approval, leave this to cron / Key Activation tab logic
      if (!$this->servicesChecker->isMailPoetAPIKeyPendingApproval()) {
        $this->bridge->onSettingsSave($settings);
      }

      $this->authorizedEmailsController->onSettingsSave($settings);
      if ($signupConfirmation !== $this->settings->get('signup_confirmation.enabled')) {
        $this->messageController->updateSuccessMessages();
      }
      return $this->successResponse($this->settings->getAll());
    }
  }

  public function recalculateSubscribersScore() {
    $this->statisticsOpensRepository->resetSubscribersScoreCalculation();
    $this->statisticsOpensRepository->resetSegmentsScoreCalculation();
    $task = $this->scheduledTasksRepository->findOneBy([
      'type' => SubscribersEngagementScore::TASK_TYPE,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
    if (!$task) {
      $task = new ScheduledTaskEntity();
      $task->setType(SubscribersEngagementScore::TASK_TYPE);
      $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    }
    $task->setScheduledAt(Carbon::createFromTimestamp($this->wp->currentTime('timestamp')));
    $this->entityManager->persist($task);
    $this->entityManager->flush();
    return $this->successResponse();
  }

  public function setAuthorizedFromAddress($data = []) {
    $address = $data['address'] ?? null;
    if (!$address) {
      return $this->badRequest([
        APIError::BAD_REQUEST => WPFunctions::get()->__('No email address specified.', 'mailpoet'),
      ]);
    }
    $address = trim($address);

    try {
      $this->authorizedEmailsController->setFromEmailAddress($address);
    } catch (\InvalidArgumentException $e) {
      return $this->badRequest([
        APIError::UNAUTHORIZED => WPFunctions::get()->__('Can’t use this email yet! Please authorize it first.', 'mailpoet'),
      ]);
    }

    if (!$this->servicesChecker->isMailPoetAPIKeyPendingApproval()) {
      MailerLog::resumeSending();
    }
    return $this->successResponse();
  }

  private function onSettingsChange($oldSettings, $newSettings) {
    // Recalculate inactive subscribers
    $oldInactivationInterval = $oldSettings['deactivate_subscriber_after_inactive_days'];
    $newInactivationInterval = $newSettings['deactivate_subscriber_after_inactive_days'];
    if ($oldInactivationInterval !== $newInactivationInterval) {
      $this->onInactiveSubscribersIntervalChange();
    }

    $oldSendingMethod = $oldSettings['mta_group'];
    $newSendingMethod = $newSettings['mta_group'];
    if (($oldSendingMethod !== $newSendingMethod) && ($newSendingMethod === 'mailpoet')) {
      $this->onMSSActivate($newSettings);
    }

    // Sync WooCommerce Customers list
    $oldSubscribeOldWoocommerceCustomers = isset($oldSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $oldSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    $newSubscribeOldWoocommerceCustomers = isset($newSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $newSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    if ($oldSubscribeOldWoocommerceCustomers !== $newSubscribeOldWoocommerceCustomers) {
      $this->onSubscribeOldWoocommerceCustomersChange();
    }

    if (!empty($newSettings['woocommerce']['use_mailpoet_editor'])) {
      $this->wcTransactionalEmails->init();
    }
  }

  private function onMSSActivate($newSettings) {
    // see mailpoet/assets/js/src/wizard/create_sender_settings.jsx:freeAddress
    $domain = str_replace('www.', '', $_SERVER['HTTP_HOST']);
    if (
      isset($newSettings['sender']['address'])
      && !empty($newSettings['reply_to']['address'])
      && ($newSettings['sender']['address'] === ('wordpress@' . $domain))
    ) {
      $sender = [
        'name' => $newSettings['reply_to']['name'] ?? '',
        'address' => $newSettings['reply_to']['address'],
      ];
      $this->settings->set('sender', $sender);
      $this->settings->set('reply_to', null);
    }
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

  private function createScheduledTask(string $type): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    return $task;
  }

  public function recalculateSubscribersCountsCache() {
    $this->transientCache->invalidateItems(TransientCache::SUBSCRIBERS_STATISTICS_COUNT_KEY);
    $segments = $this->segmentsRepository->findAll();
    foreach ($segments as $segment) {
      $this->segmentSubscribersRepository->getSubscribersStatisticsCount($segment);
    }
    $this->segmentSubscribersRepository->getSubscribersWithoutSegmentStatisticsCount();
    return $this->successResponse();
  }
}
