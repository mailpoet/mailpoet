<?php declare(strict_types = 1);

namespace MailPoet\Test\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use Codeception\Stub;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WP;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Registration;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WooCommerce\Helper;
use MailPoet\WooCommerce\Subscription;
use MailPoet\WP\Functions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WPTest extends \MailPoetTest {
  /** @var array<int> */
  private $userIds = [];

  /** @var SettingsController */
  private $settings;

  /** @var WP */
  private $wpSegment;

  /** @var SubscriberFactory */
  private $subscriberFactory;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SegmentFactory */
  private $segmentFactory;

  public function _before(): void {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->wpSegment = $this->diContainer->get(WP::class);
    $currentTime = Carbon::now();
    Carbon::setTestNow($currentTime);
    $this->cleanData();

    $this->segmentFactory = new SegmentFactory();
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscriberFactory = new SubscriberFactory();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testSynchronizeUserKeepsStatusOfOldUser(): void {
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $subscriber = $this->subscriberFactory
      ->withEmail('user-sync-test' . $randomNumber . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withWpUserId($id)
      ->create();
    $this->wpSegment->synchronizeUser($id);
    $dbSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $dbSubscriber);
    verify($dbSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testSynchronizeUserKeepsStatusOfOldSubscriber(): void {
    $randomNumber = rand();
    $subscriber = $this->subscriberFactory
      ->withEmail('user-sync-test' . $randomNumber . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $dbSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $dbSubscriber);
    verify($dbSubscriber->getStatus())->equals($subscriber->getStatus());
  }

  public function testSynchronizeUserStatusIsSubscribedForNewUserWithSignUpConfirmationDisabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => '0']);
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    verify($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testSynchronizeUserStatusIsUnconfirmedForNewUserWithSignUpConfirmationEnabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => '1']);
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    verify($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testSynchronizeUsersStatusIsSubscribedForNewUsersWithSignUpConfirmationDisabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => '0']);
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = $this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->createQueryBuilder('s')
      ->where('s.email LIKE :email')
      ->setParameter('email', 'user-sync-test%')
      ->getQuery()
      ->getResult();
    verify(count($subscribers))->equals(2);
    verify($subscribers[0]->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($subscribers[1]->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testSynchronizeUsersStatusIsUnconfirmedForNewUsersWithSignUpConfirmationEnabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => '1']);
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = $this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->createQueryBuilder('s')
      ->where('s.email LIKE :email')
      ->setParameter('email', 'user-sync-test%')
      ->getQuery()
      ->getResult();

    verify(count($subscribers))->equals(2);
    verify($subscribers[0]->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    verify($subscribers[1]->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItSendsConfirmationEmailWhenSignupConfirmationAndSubscribeOnRegisterEnabled(): void {
    $registration = $this->diContainer->get(Registration::class);
    $confirmationEmailMailer = $this->diContainer->get(ConfirmationEmailMailer::class);
    // Prevent confirmation emails from previous tests from interfering with this test
    $confirmationEmailMailer->clearSentEmailsCache();
    $this->settings->set('sender', [
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender',
    ]);

    // signup confirmation enabled, subscribe on-register enabled, checkbox in form is checked
    $_POST = ['mailpoet' => ['subscribe_on_register_active' => '1', 'subscribe_on_register' => '1']];
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $user = $this->getUser((int)$id);
    $registration->onRegister([], $user['user_login'], $user['user_email']);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    verify($wpSubscriber->getConfirmationsCount())->equals(1);
    verify($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    unset($_POST['mailpoet']);

    // signup confirmation enabled, subscribe on-register enabled, checkbox in form is unchecked
    $_POST = ['mailpoet' => ['subscribe_on_register_active' => '1']];
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $user = $this->getUser((int)$id);
    $registration->onRegister([], $user['user_login'], $user['user_email']);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    verify($wpSubscriber->getConfirmationsCount())->equals(0);
    verify($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    unset($_POST['mailpoet']);

    // signup confirmation disabled, subscribe on-register enabled
    $this->settings->set('signup_confirmation.enabled', '0');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $user = $this->getUser((int)$id);
    $registration->onRegister([], $user['user_login'], $user['user_email']);
    $wpSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    verify($wpSubscriber->getConfirmationsCount())->equals(0);
    verify($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);

    // signup confirmation enabled, subscribe on-register disabled
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '0');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    verify($wpSubscriber->getConfirmationsCount())->equals(0);
    verify($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItSynchronizeNewUsers(): void {
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    verify($subscribersCount)->equals(3);
  }

  public function testItSynchronizesPresubscribedUsers(): void {
    $randomNumber = 12345;
    $subscriber = $this->subscriberFactory
      ->withEmail('user-sync-test' . $randomNumber . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUsers();
    $wpSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    verify($wpSubscriber)->notEmpty();
    verify($wpSubscriber->getId())->equals($subscriber->getId());
    verify($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItSynchronizeEmails(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, 'user-sync-test-xx@email.com');
    $this->wpSegment->synchronizeUsers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getEmail())->equals('user-sync-test-xx@email.com');
  }

  public function testRemovesUsersWithEmptyEmailsFromSunscribersDuringSynchronization(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, '');
    $this->wpSegment->synchronizeUsers();
    verify($this->subscribersRepository->findOneBy(['wpUserId' => $id]))->null();
    $this->tester->deleteWPUserFromDatabase($id);
  }

  public function testRemovesUsersWithInvalidEmailsFromSunscribersDuringSynchronization(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, 'ivalid.@email.com');
    $this->wpSegment->synchronizeUsers();
    verify($this->subscribersRepository->findOneBy(['wpUserId' => $id]))->null();
    $this->tester->deleteWPUserFromDatabase($id);
  }

  public function testItDoesNotSynchronizeEmptyEmailsForNewUsers(): void {
    $id = $this->insertUser();
    $this->updateWPUserEmail($id, '');
    $this->wpSegment->synchronizeUsers();
    verify($this->subscribersRepository->findOneBy(['wpUserId' => $id]))->null();
    $this->tester->deleteWPUserFromDatabase($id);
  }

  public function testItSynchronizeFirstNames(): void {
    $firstName = 'Very long name over 255 characters lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum';
    $trucantedFirstName = substr($firstName, 0, 255);

    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    update_user_meta((int)$id, 'first_name', $firstName);
    $this->wpSegment->synchronizeUsers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getFirstName())->equals($trucantedFirstName);
  }

  public function testItSynchronizeLastNames(): void {
    $lastName = 'Very long name over 255 characters lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum';
    $trucantedLastName = substr($lastName, 0, 255);

    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    update_user_meta((int)$id, 'last_name', $lastName);
    $this->wpSegment->synchronizeUsers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getLastName())->equals($trucantedLastName);
  }

  public function testItSynchronizeFirstNamesUsingDisplayName(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserDisplayName($id, 'First name');
    $this->wpSegment->synchronizeUsers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getFirstName())->equals('First name');
  }

  public function testItSynchronizeFirstNamesFromMetaNotDisplayName(): void {
    $id = $this->insertUser();
    update_user_meta((int)$id, 'first_name', 'First name');
    $this->updateWPUserDisplayName($id, 'display_name');
    $this->wpSegment->synchronizeUsers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getFirstName())->equals('First name');
  }

  public function testItSynchronizeSegment(): void {
    $this->insertUser();
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = $this->subscribersRepository->findBy(['wpUserId' => $this->userIds]);
    verify(count($subscribers))->equals(3);
  }

  public function testItDoesntRemoveUsersFromTrash(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriber->setDeletedAt(Carbon::now());
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    $this->wpSegment->synchronizeUsers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getDeletedAt())->notNull();
  }

  public function testItSynchronizesDeletedWPUsersUsingHooks(): void {
    $id = $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    verify($subscribersCount)->equals(2);
    wp_delete_user((int)$id);
    $subscribersCount = $this->getSubscribersCount();
    verify($subscribersCount)->equals(1);
  }

  public function testItSynchronizesNewUsersToDisabledWPSegmentAsUnconfirmedAndTrashed(): void {
    $this->disableWpSegment();
    $this->settings->set('signup_confirmation.enabled', '1');
    $id = $this->insertUser();
    $id2 = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    verify($subscribersCount)->equals(2);
    $subscriber1 = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    $deletedAt1 = $subscriber1->getDeletedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $deletedAt1);
    verify($subscriber1->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    verify($deletedAt1->getTimestamp())->equalsWithDelta(Carbon::now()->timestamp, 1);
    $subscriber2 = $this->subscribersRepository->findOneBy(['wpUserId' => $id2]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    $deletedAt2 = $subscriber2->getDeletedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $deletedAt2);
    verify($subscriber2->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    verify($deletedAt2->getTimestamp())->equalsWithDelta(Carbon::now()->timestamp, 1);
  }

  public function testItRemovesOrphanedSubscribers(): void {
    $this->insertUser();
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->tester->deleteWPUserFromDatabase($id);
    $this->wpSegment->synchronizeUsers();
    $subscribers = $this->subscribersRepository->findBy(['wpUserId' => $this->userIds]);
    verify(count($subscribers))->equals(1);
  }

  public function testItDoesntDeleteNonWPData(): void {
    $this->insertUser();
    // wp_user_id is null
    $this->subscriberFactory
      ->withFirstName('John')
      ->withLastName('John')
      ->withEmail('user-sync-test' . rand() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->create();

    // wp_user_id is zero
    $this->subscriberFactory
      ->withFirstName('Mike')
      ->withLastName('Mike')
      ->withEmail('user-sync-test2' . rand() . '@example.com')
      ->withWpUserId(0)
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    // email is empty
    $subscriber3 = $this->subscriberFactory
      ->withFirstName('Dave')
      ->withLastName('Dave')
      ->withEmail('user-sync-test3' . rand() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $this->clearEmail($subscriber3);
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    verify($subscribersCount)->equals(3);
    $dbSubscriber = $this->subscribersRepository->findOneById($subscriber3->getId());
    verify($dbSubscriber)->notEmpty();
  }

  public function testItRemovesSubscribersInWPSegmentWithoutWPId(): void {
    $wpSegment = $this->segmentsRepository->getWPUsersSegment();
    $this->assertInstanceOf(SegmentEntity::class, $wpSegment);

    $subscriber = $this->subscriberFactory
      ->withFirstName('Mike')
      ->withLastName('Mike')
      ->withEmail('user-sync-test' . rand() . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$wpSegment])
      ->create();

    $subscribersCount = $this->getSubscribersCount();
    verify($subscribersCount)->equals(1);
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    verify($subscribersCount)->equals(0);
  }

  public function testItRemovesSubscribersInWPSegmentWithoutEmail(): void {
    $id = $this->insertUser();
    $this->updateWPUserEmail($id, '');
    $wpSegment = $this->segmentsRepository->getWPUsersSegment();
    $this->assertInstanceOf(SegmentEntity::class, $wpSegment);

    $subscriber = $this->subscriberFactory
      ->withFirstName('Mike')
      ->withLastName('Mike')
      ->withEmail('user-sync-test' . rand() . '@example.com')
      ->withWpUserId($id)
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$wpSegment])
      ->create();

    $this->clearEmail($subscriber);
    $dbSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    verify($dbSubscriber)->notEmpty();
    $this->wpSegment->synchronizeUsers();
    $this->entityManager->clear();
    $dbSubscriber = $this->subscribersRepository->findOneById($subscriber->getId());
    verify($dbSubscriber)->empty();
  }

  public function testItAddsNewUserToDisabledWpSegmentAsUnconfirmedAndTrashed(): void {
    $this->disableWpSegment();
    $id = $this->insertUser();
    $wp = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, ['wp' => $wp]);
    $wpSegment->synchronizeUser($id);
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $deletedAt = $subscriber->getDeletedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $deletedAt);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    verify($deletedAt->getTimestamp())->equalsWithDelta(Carbon::now()->timestamp, 1);
  }

  public function testItAddsNewUserWhoUncheckedOptInOnCheckoutPageAsUnconfirmed(): void {
    $id = $this->insertUser();
    $wp = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, ['wp' => $wp]);
    $_POST[Subscription::CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME] = 1;
    $wpSegment->synchronizeUser($id);
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    verify($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItDoesNotSendConfirmationEmailForNewUserWhenWPSegmentIsDisabledOnRegisterEnabled(): void {
    $this->disableWpSegment();
    $this->settings->set('sender', [
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender',
    ]);

    // signup confirmation enabled, subscribe on-register enabled
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $id = $this->insertUser();
    $wp = $worker = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, ['wp' => $wp]);
    $wpSegment->synchronizeUser($id);
    $wpSubscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    verify($wpSubscriber->getConfirmationsCount())->equals(0);
  }

  public function testItDecodesHtmlEntitesInFirstAndLastName(): void {
    $args = [
      'user_login' => 'html-entities',
      'user_email' => 'user-sync-test-html-entities@example.com',
      'first_name' => 'Family & friends',
      'last_name' => 'Family & friends lastname',
      'role' => 'subscriber',
      'user_pass' => 'password',
    ];
    $userId = wp_insert_user($args);
    $this->assertIsNumeric($userId);
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'user-sync-test-html-entities@example.com']);
    /**
     * @var SubscriberEntity $subscriber
     */
    $firstName = $subscriber->getFirstName();
    $this->assertEquals($args['first_name'], $subscriber->getFirstName());
    $this->assertEquals($args['last_name'], $subscriber->getLastName());
    wp_delete_user($userId);
  }

  public function testItDecodesHtmlEntitesInDisplayName(): void {
    $args = [
      'user_login' => 'entities-display-name',
      'user_email' => 'user-sync-test-html-entities-display-name@example.com',
      'first_name' => '',
      'last_name' => '',
      'display_name' => 'Family & Frieds',
      'role' => 'subscriber',
      'user_pass' => 'password',
    ];

    $userId = wp_insert_user($args);
    $this->assertIsNumeric($userId);
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'user-sync-test-html-entities-display-name@example.com']);
    /**
     * @var SubscriberEntity $subscriber
     */
    $this->assertEquals($args['display_name'], $subscriber->getFirstName());
    wp_delete_user($userId);
  }

  public function testItDoesNotTrashNewUsersWhoHaveSomeSegmentsToDisabledWPSegment(): void {
    $this->disableWpSegment();
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $segment = $this->segmentFactory->create();
    $subscriber = $this->subscriberFactory
      ->withEmail('user-sync-test' . $randomNumber . '@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $wp = $worker = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, ['wp' => $wp]);
    $wpSegment->synchronizeUser($id);
    $subscriber1 = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    verify($subscriber1->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    verify($subscriber1->getDeletedAt())->null();
  }

  public function testItDoesNotTrashNewUsersWhoIsWooCustomerToDisabledWPSegment(): void {
    $this->disableWpSegment();
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    add_role('customer', 'customer', []);
    $wpUser = get_user_by('id', $id);
    $this->assertInstanceOf(\WP_User::class, $wpUser);
    $wpUser->add_role('customer');

    $wp = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wooHelper = Stub::make(
      $this->diContainer->get(Helper::class),
      [
        'isWooCommerceActive' => true,
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, [
      'wp' => $wp,
      'wooHelper' => $wooHelper,
    ]);
    $wpSegment->synchronizeUser($id);
    $subscriber1 = $this->subscribersRepository->findOneBy(['wpUserId' => $id]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    verify($subscriber1->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    verify($subscriber1->getDeletedAt())->null();
    remove_role('customer');
  }

  public function _after(): void {
    parent::_after();
    $this->cleanData();
  }

  private function cleanData(): void {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    global $wpdb;
    $db = ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         user_id IN (select id from %s WHERE user_email LIKE "user-sync-test%%")
    ', $wpdb->usermeta, $wpdb->users));
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         user_email LIKE "user-sync-test%%"
         OR user_login LIKE "user-sync-test%%"
    ', $wpdb->users));
  }

  private function getSubscribersCount(): int {
    return count($this->entityManager
      ->getRepository(SubscriberEntity::class)
      ->createQueryBuilder('s')
      ->where('s.email LIKE :email')
      ->setParameter('email', 'user-sync-test%')
      ->getQuery()
      ->getResult());
  }

  /**
   * Insert a user without invoking wp hooks.
   * Those tests are testing user synchronisation, so we need data in wp_users table which has not been synchronised to
   * mailpoet database yet. We cannot use wp_insert_user functions because they would do the sync on insert.
   *
   * @param int|null $number
   * @return int
   */
  private function insertUser(?int $number = null): int {
    global $wpdb;
    $db = ORM::getDb();
    $numberSql = !is_null($number) ? (int)$number : 'rand()';
    $db->exec(sprintf('
         INSERT INTO
           %s (user_login, user_nicename, user_email, user_registered)
           VALUES
           (
             CONCAT("user-sync-test", ' . $numberSql . '),
             CONCAT("user-sync-test", ' . $numberSql . '),
             CONCAT("user-sync-test", ' . $numberSql . ', "@example.com"),
             "2017-01-02 12:31:12"
           )', $wpdb->users));
    $id = $db->lastInsertId();
    if (!is_string($id)) {
      throw new \RuntimeException('Unexpected error when creating WP user.');
    }
    $this->userIds[] = (int)$id;
    return (int)$id;
  }

  private function getUser(int $id): array {
    global $wpdb;
    $user = $this->entityManager->getConnection()->executeQuery('
      SELECT user_login, user_email, user_registered
      FROM ' . $wpdb->users . '
      WHERE id = :id
    ', ['id' => $id])->fetchAssociative();
    $this->assertIsArray($user);
    return $user;
  }

  private function updateWPUserEmail(int $id, string $email): void {
    global $wpdb;
    $db = ORM::getDb();
    $db->exec(sprintf('
       UPDATE
         %s
       SET user_email = "%s"
       WHERE
         id = %s
    ', $wpdb->users, $email, $id));
  }

  private function updateWPUserDisplayName(int $id, string $name): void {
    global $wpdb;
    $db = ORM::getDb();
    $db->exec(sprintf('
       UPDATE
         %s
       SET display_name = "%s"
       WHERE
         id = %s
    ', $wpdb->users, $name, $id));
  }

  private function clearEmail(SubscriberEntity $subscriber): void {
    $this->connection->executeStatement(
      'UPDATE ' . MP_SUBSCRIBERS_TABLE . '
      SET `email` = "" WHERE `id` = ' . $subscriber->getId()
    );
  }

  private function disableWpSegment(): void {
    $segment = $this->segmentsRepository->getWPUsersSegment();
    $this->assertInstanceOf(SegmentEntity::class, $segment);
    $segment->setDeletedAt(Carbon::now());
    $this->segmentsRepository->persist($segment);
    $this->segmentsRepository->flush();
  }
}
