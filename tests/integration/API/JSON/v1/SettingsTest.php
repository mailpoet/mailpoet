<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Stub\Expected;
use MailPoet\API\JSON\Error as APIError;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Settings;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\Workers\InactiveSubscribers;
use MailPoet\Cron\Workers\WooCommerceSync;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Form\FormMessageController;
use MailPoet\Mailer\MailerLog;
use MailPoet\Models\ScheduledTask;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Statistics\StatisticsOpensRepository;
use MailPoet\Subscribers\SubscribersCountsController;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;
use MailPoetVendor\Idiorm\ORM;

class SettingsTest extends \MailPoetTest {

  /** @var Settings */
  private $endpoint;

  /** @var SettingsController */
  private $settings;

  /** @var ScheduledTasksRepository */
  private $tasksRepository;

  public function _before() {
    parent::_before();
    ORM::raw_execute('TRUNCATE ' . ScheduledTask::$_table);
    $this->tasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('some.setting.key', true);
    $this->endpoint = new Settings(
      $this->settings,
      new Bridge,
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => true ]),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false]),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SubscribersCountsController::class)
    );
  }

  public function testItCanGetSettings() {
    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->data)->notEmpty();
    expect($response->data['some']['setting']['key'])->true();

    $this->diContainer->get(SettingsRepository::class)->truncate();
    $this->settings->resetCache();
    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals($this->settings->getAllDefaults());
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
      $this->make(Bridge::class, ['onSettingsSave' => Expected::once()]),
      $this->make(AuthorizedEmailsController::class, ['onSettingsSave' => Expected::once()]),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false]),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SubscribersCountsController::class)
    );

    $response = $this->endpoint->set(/* missing data */);
    expect($response->errors[0]['error'])->equals(APIError::BAD_REQUEST);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);

    $response = $this->endpoint->set($newSettings);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $response = $this->endpoint->get();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['some']['setting'])->hasNotKey('key');
    expect($response->data['some']['setting']['new_key'])->true();
    expect($response->data['some']['new_setting'])->true();
  }

  public function testItSetsAuthorizedFromAddressAndResumesSending() {
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(['authorized@email.com'])]);
    $this->endpoint = new Settings(
      $this->settings,
      $bridgeMock,
      new AuthorizedEmailsController($this->settings, $bridgeMock, $this->diContainer->get(NewslettersRepository::class)),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class, ['isMailPoetAPIKeyPendingApproval' => false]),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SubscribersCountsController::class)
    );

    MailerLog::pauseSending(MailerLog::getMailerLog());
    $this->settings->set('sender.address', '');
    $response = $this->endpoint->setAuthorizedFromAddress(['address' => 'authorized@email.com']);
    expect($response->status)->same(200);
    expect($this->settings->get('sender.address'))->same('authorized@email.com');
    expect(MailerLog::isSendingPaused())->false();
  }

  public function testItRejectsUnauthorizedFromAddress() {
    $bridgeMock = $this->make(Bridge::class, ['getAuthorizedEmailAddresses' => Expected::once(['authorized@email.com'])]);
    $this->endpoint = new Settings(
      $this->settings,
      $bridgeMock,
      new AuthorizedEmailsController($this->settings, $bridgeMock, $this->diContainer->get(NewslettersRepository::class)),
      $this->make(TransactionalEmails::class),
      WPFunctions::get(),
      $this->diContainer->get(EntityManager::class),
      $this->diContainer->get(StatisticsOpensRepository::class),
      $this->diContainer->get(ScheduledTasksRepository::class),
      $this->diContainer->get(FormMessageController::class),
      $this->make(ServicesChecker::class),
      $this->diContainer->get(SegmentsRepository::class),
      $this->diContainer->get(SubscribersCountsController::class)
    );

    $this->settings->set('sender.address', '');
    $response = $this->endpoint->setAuthorizedFromAddress(['address' => 'invalid@email.com']);
    expect($response->status)->same(400);
    expect($response->getData()['errors'][0])->same([
      'error' => 'unauthorized',
      'message' => 'Can’t use this email yet! Please authorize it first.',
    ]);
    expect($this->settings->get('sender.address'))->same('');
  }

  public function testItSchedulesInactiveSubscribersCheckIfIntervalSettingChanges() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 30);
    $settings = ['deactivate_subscriber_after_inactive_days' => 30];
    $this->endpoint->set($settings);
    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    expect($task)->false();

    $settings = ['deactivate_subscriber_after_inactive_days' => 0];
    $this->endpoint->set($settings);
    $task = ScheduledTask::where('type', InactiveSubscribers::TASK_TYPE)
      ->whereRaw('status = ?', [ScheduledTask::STATUS_SCHEDULED])
      ->findOne();
    assert($task instanceof ScheduledTask);
    expect($task->scheduledAt)->lessThan(Carbon::now());
  }

  public function testItRemovesFreeAddressOverrideOnMSSActivation() {
    $_SERVER['HTTP_HOST'] = 'www.mailpoet.com';

    $this->settings->set('sender', ['address' => 'wordpress@mailpoet.com']);
    $this->settings->set('reply_to', ['address' => 'johndoeexampletestnonexistinghopefullyfreemail@gmail.com']);
    $this->settings->set('mta_group', 'non-mss-sending-method');


    $newSettings = ['mta_group' => 'mailpoet'];
    $this->endpoint->set($newSettings);

    $this->settings->resetCache();
    expect($this->settings->get('sender')['address'])->equals('johndoeexampletestnonexistinghopefullyfreemail@gmail.com');
    expect($this->settings->get('reply_to'))->isEmpty();
  }

  public function testItReschedulesScheduledTaskForWoocommerceSync(): void {
    $newTask = $this->createScheduledTask(WooCommerceSync::TASK_TYPE);
    assert($newTask instanceof ScheduledTaskEntity);

    $this->endpoint->onSubscribeOldWoocommerceCustomersChange();

    $this->entityManager->clear();
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    assert($task instanceof ScheduledTaskEntity);
    $scheduledAt = $task->getScheduledAt();
    assert($scheduledAt instanceof \DateTime);
    $expectedScheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $expectedScheduledAt->subMinute();
    expect($scheduledAt)->equals($expectedScheduledAt);
    expect($newTask->getId())->equals($task->getId());
  }

  public function testItCreatesScheduledTaskForWoocommerceSync(): void {
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    expect($task)->null();
    $this->endpoint->onSubscribeOldWoocommerceCustomersChange();
    $task = $this->getScheduledTaskByType(WooCommerceSync::TASK_TYPE);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
  }

  public function testItReschedulesScheduledTaskForInactiveSubscribers(): void {
    $newTask = $this->createScheduledTask(InactiveSubscribers::TASK_TYPE);
    assert($newTask instanceof ScheduledTaskEntity);
    $this->endpoint->onInactiveSubscribersIntervalChange();

    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    assert($task instanceof ScheduledTaskEntity);
    $scheduledAt = $task->getScheduledAt();
    assert($scheduledAt instanceof \DateTime);
    $expectedScheduledAt = Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'));
    $expectedScheduledAt->subMinute();
    expect($scheduledAt)->equals($expectedScheduledAt);
    expect($newTask->getId())->equals($task->getId());
  }

  public function testItCreatesScheduledTaskForInactiveSubscribers(): void {
    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    expect($task)->null();
    $this->endpoint->onInactiveSubscribersIntervalChange();
    $task = $this->getScheduledTaskByType(InactiveSubscribers::TASK_TYPE);
    expect($task)->isInstanceOf(ScheduledTaskEntity::class);
  }

  private function createScheduledTask(string $type): ScheduledTaskEntity {
    $task = new ScheduledTaskEntity();
    $task->setType($type);
    $task->setStatus(ScheduledTaskEntity::STATUS_SCHEDULED);
    $this->tasksRepository->persist($task);
    $this->tasksRepository->flush();
    return $task;
  }

  private function getScheduledTaskByType(string $type): ?ScheduledTaskEntity {
    return $this->tasksRepository->findOneBy([
      'type' => $type,
      'status' => ScheduledTaskEntity::STATUS_SCHEDULED,
    ]);
  }

  public function _after() {
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
