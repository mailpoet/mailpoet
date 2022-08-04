<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\Config\AccessControl;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\SubscribersEngagementScore;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Form\FormMessageController;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsChangeHandler;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\Subscribers\SubscribersCountsController;
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

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscribersCountsController */
  private $subscribersCountsController;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SETTINGS,
  ];
  /**  @var NewslettersRepository */
  private $newsletterRepository;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var SettingsChangeHandler */
  private $settingsChangeHandler;

  public function __construct(
    SettingsController $settings,
    Bridge $bridge,
    AuthorizedEmailsController $authorizedEmailsController,
    TransactionalEmails $wcTransactionalEmails,
    WPFunctions $wp,
    EntityManager $entityManager,
    NewslettersRepository $newslettersRepository,
    StatisticsOpensRepository $statisticsOpensRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    FormMessageController $messageController,
    ServicesChecker $servicesChecker,
    SegmentsRepository $segmentsRepository,
    SettingsChangeHandler $settingsChangeHandler,
    SubscribersCountsController $subscribersCountsController,
    TrackingConfig $trackingConfig
  ) {
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->authorizedEmailsController = $authorizedEmailsController;
    $this->wcTransactionalEmails = $wcTransactionalEmails;
    $this->servicesChecker = $servicesChecker;
    $this->wp = $wp;
    $this->entityManager = $entityManager;
    $this->newsletterRepository = $newslettersRepository;
    $this->statisticsOpensRepository = $statisticsOpensRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->messageController = $messageController;
    $this->segmentsRepository = $segmentsRepository;
    $this->settingsChangeHandler = $settingsChangeHandler;
    $this->subscribersCountsController = $subscribersCountsController;
    $this->trackingConfig = $trackingConfig;
  }

  public function get() {
    return $this->successResponse($this->settings->getAll());
  }

  public function set($settings = []) {
    if (empty($settings)) {
      return $this->badRequest(
        [
          APIError::BAD_REQUEST =>
            __('You have not specified any settings to be saved.', 'mailpoet'),
        ]);
    } else {
      $oldSettings = $this->settings->getAll();
      $meta = [];
      $signupConfirmation = $this->settings->get('signup_confirmation.enabled');
      foreach ($settings as $name => $value) {
        $this->settings->set($name, $value);
      }

      $this->onSettingsChange($oldSettings, $this->settings->getAll());

      // when pending approval, leave this to cron / Key Activation tab logic
      if (!$this->servicesChecker->isMailPoetAPIKeyPendingApproval()) {
        $this->bridge->onSettingsSave($settings);
      }

      $meta = $this->authorizedEmailsController->onSettingsSave($settings);
      if ($signupConfirmation !== $this->settings->get('signup_confirmation.enabled')) {
        $this->messageController->updateSuccessMessages();
      }

      // Tracking and re-engagement Emails
      $meta['showNotice'] = false;
      if ($oldSettings['tracking'] !== $this->settings->get('tracking')) {
        try {
          $meta = $this->updateReEngagementEmailStatus($this->settings->get('tracking'));
        } catch (\Exception $e) {
          return $this->badRequest([
            APIError::UNKNOWN => $e->getMessage()]);
        }
      }

      return $this->successResponse($this->settings->getAll(), $meta);
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
        APIError::BAD_REQUEST => __('No email address specified.', 'mailpoet'),
      ]);
    }
    $address = trim($address);

    try {
      $this->authorizedEmailsController->setFromEmailAddress($address);
    } catch (\InvalidArgumentException $e) {
      return $this->badRequest([
        APIError::UNAUTHORIZED => __('Can’t use this email yet! Please authorize it first.', 'mailpoet'),
      ]);
    }

    if (!$this->servicesChecker->isMailPoetAPIKeyPendingApproval()) {
      MailerLog::resumeSending();
    }
    return $this->successResponse();
  }

  /**
   * Create POST request to Bridge endpoint to add email to user email authorization list
   */
  public function authorizeSenderEmailAddress($data = []) {
    $emailAddress = $data['email'] ?? null;

    if (!$emailAddress) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('No email address specified.', 'mailpoet'),
      ]);
    }

    $emailAddress = trim($emailAddress);

    try {
      $response = $this->authorizedEmailsController->createAuthorizedEmailAddress($emailAddress);
    } catch (\InvalidArgumentException $e) {
      if (
        $e->getMessage() === AuthorizedEmailsController::AUTHORIZED_EMAIL_ERROR_ALREADY_AUTHORIZED ||
        $e->getMessage() === AuthorizedEmailsController::AUTHORIZED_EMAIL_ERROR_PENDING_CONFIRMATION
      ) {
        // return true if the email is already authorized or pending confirmation
        $response = ['status' => true];
      } else {
        return $this->badRequest([
          APIError::BAD_REQUEST => __($e->getMessage(), 'mailpoet'),
        ]);
      }
    }

    return $this->successResponse($response);
  }

  public function confirmSenderEmailAddressIsAuthorized($data = []) {
    $emailAddress = $data['email'] ?? null;

    if (!$emailAddress) {
      return $this->badRequest([
        APIError::BAD_REQUEST => __('No email address specified.', 'mailpoet'),
      ]);
    }

    $emailAddress = trim($emailAddress);

    $response = ['isAuthorized' => $this->authorizedEmailsController->isEmailAddressAuthorized($emailAddress)];

    return $this->successResponse($response);
  }

  private function onSettingsChange($oldSettings, $newSettings) {
    // Recalculate inactive subscribers
    $oldInactivationInterval = $oldSettings['deactivate_subscriber_after_inactive_days'];
    $newInactivationInterval = $newSettings['deactivate_subscriber_after_inactive_days'];
    if ($oldInactivationInterval !== $newInactivationInterval) {
      $this->settingsChangeHandler->onInactiveSubscribersIntervalChange();
    }

    $oldSendingMethod = $oldSettings['mta_group'];
    $newSendingMethod = $newSettings['mta_group'];
    if (($oldSendingMethod !== $newSendingMethod) && ($newSendingMethod === 'mailpoet')) {
      $this->settingsChangeHandler->onMSSActivate($newSettings);
    }

    // Sync WooCommerce Customers list
    $oldSubscribeOldWoocommerceCustomers = isset($oldSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $oldSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    $newSubscribeOldWoocommerceCustomers = isset($newSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled'])
      ? $newSettings['mailpoet_subscribe_old_woocommerce_customers']['enabled']
      : '0';
    if ($oldSubscribeOldWoocommerceCustomers !== $newSubscribeOldWoocommerceCustomers) {
      $this->settingsChangeHandler->onSubscribeOldWoocommerceCustomersChange();
    }

    if (!empty($newSettings['woocommerce']['use_mailpoet_editor'])) {
      $this->wcTransactionalEmails->init();
    }
  }

  public function recalculateSubscribersCountsCache() {
    $segments = $this->segmentsRepository->findAll();
    foreach ($segments as $segment) {
      $this->subscribersCountsController->recalculateSegmentStatisticsCache($segment);
      if ($segment->isStatic()) {
        $this->subscribersCountsController->recalculateSegmentGlobalStatusStatisticsCache($segment);
      }
    }
    $this->subscribersCountsController->recalculateSubscribersWithoutSegmentStatisticsCache();
    // remove redundancies from cache
      $this->subscribersCountsController->removeRedundancyFromStatisticsCache();
    return $this->successResponse();
  }

  /**
   * @throws \Exception
   */
  public function updateReEngagementEmailStatus($newTracking): array {
    if (!empty($newTracking['level']) && $this->trackingConfig->isEmailTrackingEnabled($newTracking['level'])) {
      return $this->reactivateReEngagementEmails();
    }
    try {
      return $this->deactivateReEngagementEmails();
    } catch (\Exception $e) {
      throw new \Exception(
        __('Unable to deactivate re-engagement emails: ' . $e->getMessage(), 'mailpoet'));
    }
  }

  /**
   * @throws \Exception
   */
  public function deactivateReEngagementEmails(): array {
    $reEngagementEmails = $this->newsletterRepository->findActiveByTypes(([NewsletterEntity::TYPE_RE_ENGAGEMENT]));
    if (!$reEngagementEmails) {
      return [
        'showNotice' => false,
        'action' => 'deactivate',
      ];
    }

    foreach ($reEngagementEmails as $reEngagementEmail) {
      $reEngagementEmail->setStatus(NewsletterEntity::STATUS_DRAFT);
      $this->entityManager->persist($reEngagementEmail);
      $this->entityManager->flush();
    }
    return [
      'showNotice' => true,
      'action' => 'deactivate',
    ];
  }

  public function reactivateReEngagementEmails(): array {
    $draftReEngagementEmails = $this->newsletterRepository->findDraftByTypes(([NewsletterEntity::TYPE_RE_ENGAGEMENT]));
    return [
      'showNotice' => !!$draftReEngagementEmails,
      'action' => 'reactivate',
    ];
  }
}
