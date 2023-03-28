<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Test\DataFactories\NewsletterOption;

class SubscriberActionsTest extends \MailPoetTest {

  /** @var array */
  private $testData;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SubscriberActions */
  private $subscriberActions;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->testData = [
      'first_name' => 'John',
      'last_name' => 'Mailer',
      'email' => 'john@mailpoet.com',
    ];
    $this->segmentsRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);
    $this->subscriberActions = ContainerWrapper::getInstance()->get(SubscriberActions::class);
    $this->sendingQueuesRepository = ContainerWrapper::getInstance()->get(SendingQueuesRepository::class);
    $this->settings = SettingsController::getInstance();
    $this->settings->set('sender', [
      'name' => 'John Doe',
      'address' => 'john.doe@example.com',
    ]);
  }

  public function testItCanSubscribe() {
    $segment = $this->segmentsRepository->createOrUpdate('List #1');
    $segment2 = $this->segmentsRepository->createOrUpdate('List #2');

    [$subscriber] = $this->subscriberActions->subscribe(
      $this->testData,
      [$segment->getId(), $segment2->getId()]
    );

    expect($subscriber->getId() > 0)->equals(true);
    expect($subscriber->getSegments()->count())->equals(2);
    expect($subscriber->getEmail())->equals($this->testData['email']);
    expect($subscriber->getFirstName())->equals($this->testData['first_name']);
    expect($subscriber->getLastName())->equals($this->testData['last_name']);
    // signup confirmation is enabled by default
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    expect($subscriber->getDeletedAt())->equals(null);
  }

  public function testItSchedulesWelcomeNotificationUponSubscriptionWhenSubscriptionConfirmationIsDisabled() {
    // create segment
    $segment = $this->segmentsRepository->createOrUpdate('List #1');

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = $this->createNewsletter();

    $newsletterOptions = [
      NewsletterOptionFieldEntity::NAME_EVENT => 'segment',
      NewsletterOptionFieldEntity::NAME_SEGMENT => $segment->getId(),
      NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE => 'days',
      NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER => 1,
    ];
    (new NewsletterOption())->createMultipleOptions($newsletter, $newsletterOptions);

    $this->settings->set('signup_confirmation.enabled', false);
    [$subscriber] = $this->subscriberActions->subscribe($this->testData, [$segment->getId()]);
    expect($subscriber->getId() > 0)->equals(true);
    expect($subscriber->getSegments())->count(1);

    $scheduledNotification = $this->sendingQueuesRepository->findOneByNewsletterAndTaskStatus(
      $newsletter,
      ScheduledTaskEntity::STATUS_SCHEDULED
    );
    expect($scheduledNotification)->isInstanceOf(SendingQueueEntity::class);
  }

  public function testItDoesNotScheduleWelcomeNotificationUponSubscriptionWhenSubscriptionConfirmationIsEnabled() {
    // create segment
    $segment = $this->segmentsRepository->createOrUpdate('List #1');

    // create welcome notification newsletter and relevant scheduling options
    $newsletter = $this->createNewsletter();

    $newsletterOptions = [
      'event' => 'segment',
      'segment' => $segment->getId(),
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1,
    ];
    (new NewsletterOption())->createMultipleOptions($newsletter, $newsletterOptions);

    $this->settings->set('signup_confirmation.enabled', true);
    [$subscriber] = $this->subscriberActions->subscribe($this->testData, [$segment->getId()]);
    expect($subscriber->getId() > 0)->equals(true);
    expect($subscriber->getSegments())->count(1);
    $scheduledNotification = $this->sendingQueuesRepository->findOneByNewsletterAndTaskStatus(
      $newsletter,
      ScheduledTaskEntity::STATUS_SCHEDULED
    );
    expect($scheduledNotification)->null();
  }

  public function testItCannotSubscribeWithReservedColumns() {
    $segment = $this->segmentsRepository->createOrUpdate('List #1');

    [$subscriber] = $this->subscriberActions->subscribe(
      [
        'email' => 'donald@mailpoet.com',
        'first_name' => 'Donald',
        'last_name' => 'Trump',
        // the fields below should NOT be taken into account
        'id' => 1337,
        'wp_user_id' => 7331,
        'is_woocommerce_user' => 1,
        'status' => SubscriberEntity::STATUS_SUBSCRIBED,
        'created_at' => '1984-03-09 00:00:01',
        'updated_at' => '1984-03-09 00:00:02',
        'deleted_at' => '1984-03-09 00:00:03',
      ],
      [$segment->getId()]
    );

    expect($subscriber->getId() > 0)->equals(true);
    expect($subscriber->getId())->notEquals(1337);
    expect($subscriber->getSegments())->count(1);
    expect($subscriber->getEmail())->equals('donald@mailpoet.com');
    expect($subscriber->getFirstName())->equals('Donald');
    expect($subscriber->getLastName())->equals('Trump');

    $createdAt = $subscriber->getCreatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $createdAt);
    expect($subscriber->getWpUserId())->null();
    expect($subscriber->getIsWoocommerceUser())->equals(0);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    expect($createdAt->format('Y-m-d H:i:s'))->notEquals('1984-03-09 00:00:01');
    expect($subscriber->getUpdatedAt()->format('Y-m-d H:i:s'))->notEquals('1984-03-09 00:00:02');
    expect($createdAt->getTimestamp())->equals($subscriber->getUpdatedAt()->getTimestamp(), 2);
    expect($subscriber->getDeletedAt())->null();
  }

  public function testItOverwritesSubscriberDataWhenConfirmationIsDisabled() {
    $originalSettingValue = $this->settings->get('signup_confirmation.enabled');
    $this->settings->set('signup_confirmation.enabled', false);

    $segment = $this->segmentsRepository->createOrUpdate('List #1');
    $segment2 = $this->segmentsRepository->createOrUpdate('List #2');

    $data = [
      'email' => 'some@example.com',
      'first_name' => 'Some',
      'last_name' => 'Example',
    ];

    [$subscriber] = $this->subscriberActions->subscribe(
      $data,
      [$segment->getId()]
    );

    expect($subscriber->getId() > 0)->equals(true);
    expect($subscriber->getSegments())->count(1);
    expect($subscriber->getEmail())->equals($data['email']);
    expect($subscriber->getFirstName())->equals($data['first_name']);
    expect($subscriber->getLastName())->equals($data['last_name']);

    $data2 = $data;
    $data2['first_name'] = 'Aaa';
    $data2['last_name'] = 'Bbb';

    [$subscriber] = $this->subscriberActions->subscribe(
      $data2,
      [$segment->getId(), $segment2->getId()]
    );

    expect($subscriber->getId() > 0)->equals(true);
    expect($subscriber->getSegments())->count(2);
    expect($subscriber->getEmail())->equals($data2['email']);
    expect($subscriber->getFirstName())->equals($data2['first_name']);
    expect($subscriber->getLastName())->equals($data2['last_name']);

    $this->settings->set('signup_confirmation.enabled', $originalSettingValue);
  }

  public function testItStoresUnconfirmedSubscriberDataWhenConfirmationIsEnabled() {
    $originalSettingValue = $this->settings->get('signup_confirmation.enabled');
    $this->settings->set('signup_confirmation.enabled', true);

    $segment = $this->segmentsRepository->createOrUpdate('List #1');
    $segment2 = $this->segmentsRepository->createOrUpdate('List #2');

    $data = [
      'email' => 'some@example.com',
      'first_name' => 'Some',
      'last_name' => 'Example',
    ];

    [$subscriber] = $this->subscriberActions->subscribe(
      $data,
      [$segment->getId()]
    );

    expect($subscriber->getId() > 0)->equals(true);
    expect($subscriber->getSegments())->count(1);
    expect($subscriber->getEmail())->equals($data['email']);
    expect($subscriber->getFirstName())->equals($data['first_name']);
    expect($subscriber->getLastName())->equals($data['last_name']);

    expect($subscriber->getUnconfirmedData())->isEmpty();

    $data2 = $data;
    $data2['first_name'] = 'Aaa';
    $data2['last_name'] = 'Bbb';

    [$subscriber] = $this->subscriberActions->subscribe(
      $data2,
      [$segment->getId(), $segment2->getId()]
    );

    expect($subscriber->getId() > 0)->equals(true);
    expect($subscriber->getSegments())->count(2);
    // fields should be left intact
    expect($subscriber->getEmail())->equals($data['email']);
    expect($subscriber->getFirstName())->equals($data['first_name']);
    expect($subscriber->getLastName())->equals($data['last_name']);

    expect($subscriber->getUnconfirmedData())->notEmpty();
    expect($subscriber->getUnconfirmedData())->equals(json_encode($data2));

    // Unconfirmed data should be wiped after any direct update
    // during confirmation, manual admin editing
    $saveController = ContainerWrapper::getInstance()->get(SubscriberSaveController::class);
    $subscriber = $saveController->createOrUpdate($data2, $subscriber);
    expect($subscriber->getUnconfirmedData())->isEmpty();

    $this->settings->set('signup_confirmation.enabled', $originalSettingValue);
  }

  private function createNewsletter(): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_WELCOME);
    $newsletter->setStatus(NewsletterEntity::STATUS_ACTIVE);
    $newsletter->setSubject('Subject');
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
