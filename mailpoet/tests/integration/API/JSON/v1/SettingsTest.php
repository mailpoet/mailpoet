<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\Settings;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Form\FormMessageController;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsChangeHandler;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\Subscribers\ConfirmationEmailCustomizer;
use MailPoet\Subscribers\SubscribersCountsController;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class SettingsTest extends \MailPoetTest {

  /** @var Settings */
  private $endpoint;

  /** @var SettingsController */
  private $settings;

  /* @var NewslettersRepository */
  private $newsletterRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('some.setting.key', true);
    $this->endpoint = new Settings(
      $this->settings,
      new Bridge,
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => null]),
      $this->diContainer->get(AuthorizedSenderDomainController::class),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false]),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SettingsChangeHandler::class),
      $this->diContainer->get(SubscribersCountsController::class),
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );
  }

  public function testItCanGetSettings() {
    $response = $this->endpoint->get();
    verify($response->status)->equals(APIResponse::STATUS_OK);

    verify($response->data)->notEmpty();
    verify($response->data['some']['setting']['key'])->true();

    $this->diContainer->get(SettingsRepository::class)->truncate();
    $this->settings->resetCache();
    $response = $this->endpoint->get();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals($this->settings->getAllDefaults());
  }

  public function testItCanSetSettings() {
    $newSettings = [
      'some' => [
        'setting' => [
          'new_key' => true,
        ],
        'new_setting' => true,
      ],
    ];

    $this->endpoint = new Settings(
      $this->settings,
      $this->diContainer->get(Bridge::class),
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => Expected::once()]),
      $this->diContainer->get(AuthorizedSenderDomainController::class),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false]),
      $this->diContainer->get(SegmentsRepository::class),
      $this->make(SettingsChangeHandler::class, ['updateBridge' => Expected::once()]),
      $this->diContainer->get(SubscribersCountsController::class),
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    $response = $this->endpoint->set(/* missing data */);
    verify($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $response = $this->endpoint->set($newSettings);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->get();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['some']['setting'])->arrayHasNotKey('key');
    verify($response->data['some']['setting']['new_key'])->true();
    verify($response->data['some']['new_setting'])->true();
  }

  public function testItSetsAuthorizedFromAddressAndResumesSending() {
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(['authorized@email.com'])]);
    $senderDomainController = $this->diContainer->get(AuthorizedSenderDomainController::class);
    $this->endpoint = new Settings(
      $this->settings,
      $bridgeMock,
      new AuthorizedEmailsController($this->settings, $bridgeMock, $this->diContainer->get(NewslettersRepository::class), $senderDomainController),
      $this->diContainer->get(AuthorizedSenderDomainController::class),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false]),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SettingsChangeHandler::class),
      $this->diContainer->get(SubscribersCountsController::class),
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    MailerLog::pauseSending(MailerLog::getMailerLog());
    $this->settings->set('sender.address', '');
    $response = $this->endpoint->setAuthorizedFromAddress(['address' => 'authorized@email.com']);
    verify($response->status)->same(200);
    verify($this->settings->get('sender.address'))->same('authorized@email.com');
    verify(MailerLog::isSendingPaused())->false();
  }

  public function testItSaveUnauthorizedAddressAndReturnsMeta() {
    $this->settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, ['method' => Mailer::METHOD_MAILPOET]);
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(['authorized@email.com'])]);
    $senderDomainController = $this->diContainer->get(AuthorizedSenderDomainController::class);
    $this->endpoint = new Settings(
      $this->settings,
      $bridgeMock,
      new AuthorizedEmailsController($this->settings, $bridgeMock, $this->diContainer->get(NewslettersRepository::class), $senderDomainController),
      $this->diContainer->get(AuthorizedSenderDomainController::class),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false]),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SettingsChangeHandler::class),
      $this->diContainer->get(SubscribersCountsController::class),
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    $response = $this->endpoint->set([
      'sender' => ['address' => 'invalid@email.com'],
    ]);
    verify($response->status)->same(200);
    verify($this->settings->get('sender.address'))->same('invalid@email.com');
    verify($response->meta)->equals([
      'invalid_sender_address' => 'invalid@email.com',
      'showNotice' => false,
    ]);
  }

  public function testItRejectsUnauthorizedFromAddress() {
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(['authorized@email.com'])]);
    $senderDomainController = $this->diContainer->get(AuthorizedSenderDomainController::class);
    $this->endpoint = new Settings(
      $this->settings,
      $bridgeMock,
      new AuthorizedEmailsController($this->settings, $bridgeMock, $this->diContainer->get(NewslettersRepository::class), $senderDomainController),
      $this->diContainer->get(AuthorizedSenderDomainController::class),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SettingsChangeHandler::class),
      $this->diContainer->get(SubscribersCountsController::class),
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    $this->settings->set('sender.address', '');
    $response = $this->endpoint->setAuthorizedFromAddress(['address' => 'invalid@email.com']);
    verify($response->status)->same(400);
    verify($response->getData()['errors'][0])->same([
      'error' => 'unauthorized',
      'message' => 'Can’t use this email yet! Please authorize it first.',
    ]);
    verify($this->settings->get('sender.address'))->same('');
  }

  public function testItSchedulesInactiveSubscribersCheckIfIntervalSettingChanges() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 30);
    $settings = ['deactivate_subscriber_after_inactive_days' => 30];
    $this->endpoint->set($settings);
    $task = $this->scheduledTasksRepository->findOneBy(
      [
        'type' => InactiveSubscribers::TASK_TYPE,
        'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
      ]
    );
    verify($task)->null();

    $settings = ['deactivate_subscriber_after_inactive_days' => 0];
    $this->endpoint->set($settings);
    $task = $this->scheduledTasksRepository->findOneBy(
      [
        'type' => InactiveSubscribers::TASK_TYPE,
        'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
      ]
    );
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    verify($task->getScheduledAt())->lessThan(Carbon::now());
  }

  public function testItRemovesFreeAddressOverrideOnMSSActivation() {
    $_SERVER['HTTP_HOST'] = 'www.mailpoet.com';

    $this->settings->set('sender', ['address' => 'wordpress@mailpoet.com']);
    $this->settings->set('reply_to', ['address' => 'johndoeexampletestnonexistinghopefullyfreemail@gmail.com']);
    $this->settings->set('mta_group', 'non-mss-sending-method');


    $newSettings = ['mta_group' => 'mailpoet'];
    $this->endpoint->set($newSettings);

    $this->settings->resetCache();
    verify($this->settings->get('sender')['address'])->equals('johndoeexampletestnonexistinghopefullyfreemail@gmail.com');
    verify($this->settings->get('reply_to'))->empty();
  }

  public function testItDeactivatesReEngagementEmailsIfTrackingDisabled(): void {
    $this->createNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT, NewsletterEntity::STATUS_ACTIVE);
    $this->settings->set('tracking', ['level' => TrackingConfig::LEVEL_PARTIAL]);
    $response = $this->endpoint->set(['tracking' => ['level' => TrackingConfig::LEVEL_BASIC]]);
    verify($response->meta['showNotice'])->equals(true);
    verify($response->meta['action'])->equals('deactivate');
    verify($this->newsletterRepository->findActiveByTypes([NewsletterEntity::TYPE_RE_ENGAGEMENT]))->equals([]);
  }

  public function testItFlagsNoticeToReactivateReEngagementEmailsIfTrackingEnabled(): void {
    $this->createNewsletter(NewsletterEntity::TYPE_RE_ENGAGEMENT);
    $this->settings->set('tracking', ['level' => TrackingConfig::LEVEL_BASIC]);
    $response = $this->endpoint->set(['tracking' => ['level' => TrackingConfig::LEVEL_PARTIAL]]);
    verify($response->meta['showNotice'])->equals(true);
    verify($response->meta['action'])->equals('reactivate');
  }

  public function testNoNoticeWhenTrackingChangesIfNoReEngagementEmails(): void {
    $this->createNewsletter(NewsletterEntity::TYPE_STANDARD, NewsletterEntity::STATUS_ACTIVE);
    $this->settings->set('tracking', ['level' => TrackingConfig::LEVEL_BASIC]);
    $response = $this->endpoint->set(['tracking' => ['level' => TrackingConfig::LEVEL_PARTIAL]]);
    verify($response->meta['showNotice'])->equals(false);
    $response = $this->endpoint->set(['tracking' => ['level' => TrackingConfig::LEVEL_BASIC]]);
    verify($response->meta['showNotice'])->equals(false);
  }

  public function testItCanDeleteSetting() {
    $this->settings->set('setting_to_be_deleted', true);
    $response = $this->endpoint->delete('setting_to_be_deleted');
    verify($response)->instanceOf(SuccessResponse::class);
    verify($this->settings->get('setting_to_be_deleted'))->null();
  }

  public function testDeleteReturnErrorForEmptySettingName() {
    verify($this->endpoint->delete(''))->instanceOf(ErrorResponse::class);
  }

  public function testDeleteReturnErrorIfSettingDoesntExist() {
    verify($this->endpoint->delete('unexistent_setting'))->instanceOf(ErrorResponse::class);
  }

  public function testItSetsUpMSSWithProvidedKey() {
    $newKey = 'some-new-key';
    $this->endpoint = new Settings(
      $this->settings,
      $this->make(Bridge::class, ['onSettingsSave' => Expected::once()]),
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => Expected::once()]),
      $this->diContainer->get(AuthorizedSenderDomainController::class),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(NewslettersRepository::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false]),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SettingsChangeHandler::class),
      $this->diContainer->get(SubscribersCountsController::class),
      $this->diContainer->get(TrackingConfig::class),
      $this->diContainer->get(ConfirmationEmailCustomizer::class)
    );

    verify($this->endpoint->setKeyAndSetupMss($newKey))->instanceOf(SuccessResponse::class);
    verify($this->settings->get('mta.mailpoet_api_key'))->equals($newKey);
    verify($this->settings->get('mta_group'))->equals('mailpoet');
    verify($this->settings->get('mta.method'))->equals('MailPoet');
    verify($this->settings->get('signup_confirmation.enabled'))->equals(1);
    verify($this->settings->get('premium.premium_key'))->equals($newKey);
  }

  private function createNewsletter(string $type, string $status = NewsletterEntity::STATUS_DRAFT, $parent = null): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType($type);
    $newsletter->setSubject('My Standard Newsletter');
    $newsletter->setBody(Fixtures::get('newsletter_body_template'));
    $newsletter->setStatus($status);
    $newsletter->setParent($parent);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }
}
