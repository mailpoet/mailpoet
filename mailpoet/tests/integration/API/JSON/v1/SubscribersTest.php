<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Fixtures;
use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\Subscribers;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Listing\Handler;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\SubscriberSubscribeController;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\Subscription\Captcha\CaptchaSession;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions;
use MailPoetVendor\Carbon\Carbon;

class SubscribersTest extends \MailPoetTest {

  /** @var FormEntity */
  public $form;

  /** @var SubscriberEntity */
  public $subscriber2;

  /** @var SubscriberEntity */
  public $subscriber1;

  /** @var SegmentEntity */
  public $segment2;

  /** @var SegmentEntity */
  public $segment1;

  public $obfuscatedSegments;
  public $obfuscatedEmail;

  /** @var Subscribers */
  private $endpoint;

  /** @var SettingsController */
  private $settings;

  /** @var CaptchaSession */
  private $captchaSession;

  /** @var SubscribersResponseBuilder */
  private $responseBuilder;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  public function _before() {
    parent::_before();
    $container = ContainerWrapper::getInstance();
    $wp = $container->get(Functions::class);
    $this->captchaSession = new CaptchaSession($container->get(Functions::class));
    $this->responseBuilder = $container->get(SubscribersResponseBuilder::class);
    $obfuscator = new FieldNameObfuscator($wp);
    $this->endpoint = new Subscribers(
      $container->get(Handler::class),
      $container->get(ConfirmationEmailMailer::class),
      $container->get(SubscribersRepository::class),
      $this->responseBuilder,
      $container->get(SubscriberListingRepository::class),
      $container->get(SegmentsRepository::class),
      $container->get(SubscriberSaveController::class),
      $container->get(SubscriberSubscribeController::class),
      $container->get(SettingsController::class)
    );
    $this->obfuscatedEmail = $obfuscator->obfuscate('email');
    $this->obfuscatedSegments = $obfuscator->obfuscate('segments');
    $this->segment1 = (new SegmentFactory())
      ->withName('Segment 1')
      ->withType(SegmentEntity::TYPE_DEFAULT)
      ->create();
    $this->segment2 = (new SegmentFactory())
      ->withName('Segment 2')
      ->withType(SegmentEntity::TYPE_DEFAULT)
      ->create();
    $this->entityManager->persist($this->segment1);
    $this->entityManager->persist($this->segment2);

    $this->subscriber1 = (new SubscriberFactory())
      ->withEmail('john@mailpoet.com')
      ->withFirstName('John')
      ->withLastName('Doe')
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withSource(Source::API)
      ->create();

    $this->subscriber2 = (new SubscriberFactory())
      ->withEmail('jane@mailpoet.com')
      ->withFirstName('Jane')
      ->withLastName('Doe')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSource(Source::API)
      ->withSegments([$this->segment1, $this->segment2])
      ->create();

    $this->form = new FormEntity('My Form');
    $body = Fixtures::get('form_body_template');
    // Add segment selection block
    $body[] = [
      'type' => 'segment',
      'params' => [
        'values' => [['id' => $this->segment1->getId()], ['id' => $this->segment2->getId()]],
      ],
    ];
    $this->form->setBody($body);
    $this->form->setSettings([
      'segments_selected_by' => 'user',
      'segments' => [
        $this->segment1->getId(),
        $this->segment2->getId(),
      ],
    ]);
    $this->entityManager->persist($this->form);

    $this->settings = SettingsController::getInstance();
    // setup mailer
    $this->settings->set('sender', [
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender',
    ]);
    $this->entityManager->flush();

    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->sendingQueuesRepository = $this->diContainer->get(SendingQueuesRepository::class);
  }

  public function testItCanGetASubscriber() {
    $response = $this->endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals(
      'This subscriber does not exist.'
    );

    $response = $this->endpoint->get(/* missing argument */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals(
      'This subscriber does not exist.'
    );

    $response = $this->endpoint->get(['id' => $this->subscriber1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['id'])->equals($this->subscriber1->getId());
    expect($response->data['first_name'])->equals($this->subscriber1->getFirstName());
    expect($response->data['email'])->equals($this->subscriber1->getEmail());
    expect($response->data['unsubscribes'])->equals([]);
    expect($response->data['subscriptions'])->equals([]);
  }

  public function testItCanSaveANewSubscriber() {
    $validData = [
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment1->getId(),
        $this->segment2->getId(),
      ],
    ];

    $response = $this->endpoint->save($validData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $this->assertInstanceOf(SuccessResponse::class, $response);
    $this->entityManager->clear();
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneBy(['email' => 'raul.doe@mailpoet.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($response->data['email'])->equals('raul.doe@mailpoet.com');
    expect($response->data['id'])->equals($subscriber->getId());
    expect($response->data['status'])->equals($subscriber->getStatus());

    $subscriberSegments = $subscriber->getSegments();
    expect($subscriberSegments->count())->equals(2);
    expect($subscriberSegments->get(0)->getName())->equals($this->segment1->getName());
    expect($subscriberSegments->get(1)->getName())->equals($this->segment2->getName());

    $this->entityManager->clear();
    $response = $this->endpoint->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    $this->assertInstanceOf(ErrorResponse::class, $response);

    expect($response->errors[0]['message'])
      ->equals('Please enter your email address');

    $invalidData = [
      'email' => 'john.doe@invalid',
    ];

    $this->entityManager->clear();
    $response = $this->endpoint->save($invalidData);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    $this->assertInstanceOf(ErrorResponse::class, $response);
    expect($response->errors[0]['message'])
      ->equals('Your email address is invalid!');
    expect($subscriber->getSource())->equals('administrator');
  }

  public function testItCanSaveANewSubscriberWithCustomField() {
    $customField = new CustomFieldEntity();
    $customField->setType(CustomFieldEntity::TYPE_TEXT);
    $customField->setName('test field');
    $this->entityManager->persist($customField);
    $this->entityManager->flush();

    $validData = [
      "cf_{$customField->getId()}" => 'testing',
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
    ];

    $response = $this->endpoint->save($validData);
    $this->assertInstanceOf(SuccessResponse::class, $response);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $this->entityManager->clear();
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneBy(['email' => 'raul.doe@mailpoet.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($response->data['email'])->equals('raul.doe@mailpoet.com');
    expect($response->data['id'])->equals($subscriber->getId());
    expect($response->data['status'])->equals($subscriber->getStatus());
    expect($response->data["cf_{$customField->getId()}"])->equals('testing');
  }

  public function testItCanSaveAnExistingSubscriber() {
    $subscriberData = [
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Super Jane',
      'last_name' => 'Doe',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'segments' => [$this->segment1->getId()],
      'source' => Source::API,
    ];

    $response = $this->endpoint->save($subscriberData);
    $this->assertInstanceOf(SuccessResponse::class, $response);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->responseBuilder->build($this->subscriber2)
    );
    expect($response->data['first_name'])->equals('Super Jane');
    expect($response->data['source'])->equals('api');
  }

  public function testItCanUpdateEmailOfAnExistingSubscriber() {
    $subscriberData = $this->responseBuilder->build($this->subscriber2);
    $subscriberData['email'] = 'newjane@mailpoet.com';
    $response = $this->endpoint->save($subscriberData);
    $this->assertInstanceOf(SuccessResponse::class, $response);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->responseBuilder->build($this->subscriber2)
    );
    expect($response->data['email'])->equals('newjane@mailpoet.com');
    expect($response->data['first_name'])->equals($subscriberData['first_name']);
  }

  public function testItCannotUpdateEmailOfAnExistingSubscriberIfEmailIsNotUnique() {
    $subscriberData = $this->responseBuilder->build($this->subscriber2);
    $subscriberData['email'] = $this->subscriber1->getEmail();
    $response = $this->endpoint->save($subscriberData);
    $this->assertInstanceOf(ErrorResponse::class, $response);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('A subscriber with E-mail "' . $this->subscriber1->getEmail() . '" already exists.');
  }

  public function testItCanRemoveListsFromAnExistingSubscriber() {
    $subscriberData = [
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Super Jane',
      'last_name' => 'Doe',
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'source' => Source::API,
    ];

    $response = $this->endpoint->save($subscriberData);
    $this->assertInstanceOf(SuccessResponse::class, $response);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->responseBuilder->build($this->subscriber2)
    );
    expect($this->subscriber2->getSubscriberSegments()->filter(function (SubscriberSegmentEntity $subscriberSegment) {
      return $subscriberSegment->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED;
    })->count())->equals(0);
  }

  public function testItCanRestoreASubscriber() {
    $this->subscriber1->setDeletedAt(new \DateTime());
    $this->entityManager->flush();

    $response = $this->endpoint->restore(['id' => $this->subscriber1->getId()]);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneById($this->subscriber1->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($response->data['id'])->equals($subscriber->getId());
    expect($response->data['email'])->equals($subscriber->getEmail());
    expect($response->data['status'])->equals($subscriber->getStatus());
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanTrashASubscriber() {
    $response = $this->endpoint->trash(['id' => $this->subscriber2->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneById($this->subscriber2->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($response->data['id'])->equals($subscriber->getId());
    expect($response->data['email'])->equals($subscriber->getEmail());
    expect($response->data['status'])->equals($subscriber->getStatus());
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteASubscriber() {
    $response = $this->endpoint->delete(['id' => $this->subscriber1->getId()]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanFilterListing() {
    // filter by non existing segment
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => '### invalid_segment_id ###',
      ],
    ]);

    // it should return all subscribers
    expect($response->meta['count'])->equals(2);

    // filter by 1st segment
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $this->segment1->getId(),
      ],
    ]);

    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($this->subscriber2->getEmail());

    // filter by 2nd segment
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $this->segment2->getId(),
      ],
    ]);

    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($this->subscriber2->getEmail());
  }

  public function testItCanLoadDynamicSegments() {
    $dynamicSegmentFactory = new DynamicSegment();
    $dynamicSegment = $dynamicSegmentFactory
      ->withName('Dynamic')
      ->withUserRoleFilter('editor')
      ->create();
    $wpUserEmail = 'wpuserEditor@example.com';
    $this->tester->deleteWordPressUser($wpUserEmail);
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $dynamicSegment->getId(),
      ],
    ]);
    expect($response->meta['filters']['segment'])->contains(['value' => $dynamicSegment->getId(), 'label' => 'Dynamic (1)']);
    $this->tester->deleteWordPressUser($wpUserEmail);
  }

  public function testItCanSearchListing() {
    $newSubscriber = new SubscriberEntity();
    $newSubscriber->setEmail('search.me@find.me');
    $newSubscriber->setFirstName('Billy Bob');
    $newSubscriber->setLastName('Thornton');
    $newSubscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $newSubscriber->setSource(Source::API);
    $this->entityManager->persist($newSubscriber);
    $this->entityManager->flush();

    // empty search returns everything
    $response = $this->endpoint->listing([
      'search' => '',
    ]);
    expect($response->meta['count'])->equals(3);

    // search by email
    $response = $this->endpoint->listing([
      'search' => '.me',
    ]);
    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($newSubscriber->getEmail());

    // search by last name
    $response = $this->endpoint->listing([
      'search' => 'doe',
    ]);
    expect($response->meta['count'])->equals(2);
    expect($response->data[0]['email'])->equals($this->subscriber1->getEmail());
    expect($response->data[1]['email'])->equals($this->subscriber2->getEmail());

    // search by first name
    $response = $this->endpoint->listing([
      'search' => 'billy',
    ]);
    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($newSubscriber->getEmail());
  }

  public function testItCanGroupListing() {
    $subscribedGroup = $this->endpoint->listing([
      'group' => SubscriberEntity::STATUS_SUBSCRIBED,
    ]);
    expect($subscribedGroup->meta['count'])->equals(1);
    expect($subscribedGroup->data[0]['email'])->equals(
      $this->subscriber2->getEmail()
    );

    $unsubscribedGroup = $this->endpoint->listing([
      'group' => SubscriberEntity::STATUS_UNSUBSCRIBED,
    ]);
    expect($unsubscribedGroup->meta['count'])->equals(0);

    $unconfirmedGroup = $this->endpoint->listing([
      'group' => SubscriberEntity::STATUS_UNCONFIRMED,
    ]);
    expect($unconfirmedGroup->meta['count'])->equals(1);
    expect($unconfirmedGroup->data[0]['email'])->equals(
      $this->subscriber1->getEmail()
    );

    $trashedGroup = $this->endpoint->listing([
      'group' => 'trash',
    ]);
    expect($trashedGroup->meta['count'])->equals(0);

    // trash 1st subscriber
    $this->subscriber1->setDeletedAt(new \DateTime());
    $this->entityManager->flush();

    $trashedGroup = $this->endpoint->listing([
      'group' => 'trash',
    ]);
    expect($trashedGroup->meta['count'])->equals(1);
    expect($trashedGroup->data[0]['email'])->equals(
      $this->subscriber1->getEmail()
    );
  }

  public function testItCorrectSubscriptionStatus() {
    $segment = (new SegmentFactory())->create();
    $subscriber = (new SubscriberFactory())
      ->withEmail('third@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->withSource(Source::API)
      ->create();
    $subscriberSegment = $subscriber->getSubscriberSegments()->first();
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $subscriberSegment);
    $subscriberSegment->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();

    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => (string)$segment->getId(),
      ],
    ]);

    expect($response->data[0]['status'])->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItCanSortAndLimitListing() {
    // get 1st page (limit items per page to 1)
    $response = $this->endpoint->listing([
      'limit' => 1,
      'sort_by' => 'first_name',
      'sort_order' => 'asc',
    ]);

    expect($response->meta['count'])->equals(2);
    expect($response->data)->count(1);
    expect($response->data[0]['email'])->equals(
      $this->subscriber2->getEmail()
    );

    // get 1st page (limit items per page to 1)
    $response = $this->endpoint->listing([
      'limit' => 1,
      'offset' => 1,
      'sort_by' => 'first_name',
      'sort_order' => 'asc',
    ]);

    expect($response->meta['count'])->equals(2);
    expect($response->data)->count(1);
    expect($response->data[0]['email'])->equals(
      $this->subscriber1->getEmail()
    );
  }

  public function testItCanFilterSubscribersWithoutSegment() {
    $subscriber = (new SubscriberFactory())
      ->withEmail('no-segment@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSource(Source::API)
      ->create();

    $trashedSubscriber = (new SubscriberFactory())
      ->withEmail('no-segment-in-trash@example.com')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSource(Source::API)
      ->withDeletedAt(new Carbon())
      ->create();

    $result = $this->endpoint->listing(
      [
        'filter' => [
          'segment' => SubscriberListingRepository::FILTER_WITHOUT_LIST,
        ],
        'group' => 'all',
      ]
    );
    $data = $result->getData();
    $meta = $result->meta;

    self::assertEquals(2, $meta['count'], "Did not find exactly two subscribers without list");
    self::assertCount(2, $data['data'], "Did not return exactly two subscribers without list");
    $foundSubscriberIds = array_map(
      function (array $data): int {
        return (int)$data['id'];
      }, $data['data']
    );
    self::assertTrue(in_array((int)$this->subscriber1->getId(), $foundSubscriberIds, true), 'Subscriber 1 was not found.');
    self::assertTrue(in_array((int)$subscriber->getId(), $foundSubscriberIds, true), 'New subscriber without list was not found.');

    $result = $this->endpoint->listing(
      [
        'filter' => [
          'segment' => SubscriberListingRepository::FILTER_WITHOUT_LIST,
        ],
        'group' => 'trash',
      ]
    );
    $data = $result->getData();
    $meta = $result->meta;

    self::assertEquals(1, $meta['count'], "Did not find exactly one trashed subscriber without list.");
    self::assertCount(1, $data['data'], "Did not return exactly one trashed subscriber without list.");
    self::assertEquals($trashedSubscriber->getId(), $data['data'][0]['id'], "Did not return the trashed subscriber without list.");
  }

  public function testItCanBulkDeleteSelectionOfSubscribers() {
    $deletableSubscriber = (new SubscriberFactory())
      ->withEmail('to.be.removed@mailpoet.com')
      ->create();

    $selectionIds = [
      $this->subscriber1->getId(),
      $deletableSubscriber->getId(),
    ];

    $response = $this->endpoint->bulkAction([
      'listing' => [
        'selection' => $selectionIds,
      ],
      'action' => 'delete',
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->isEmpty();
    expect($response->meta['count'])->equals(count($selectionIds));

    $this->entityManager->clear();

    $isSubscriber1Deleted = (
      $this->subscribersRepository->findOneById($this->subscriber1->getId()) === null
    );
    $isDeletableSubscriberDeleted = (
      $this->subscribersRepository->findOneById($deletableSubscriber->getId()) === null
    );

    expect($isSubscriber1Deleted)->true();
    expect($isDeletableSubscriberDeleted)->true();
  }

  public function testItCanBulkDeleteSubscribers() {
    $response = $this->endpoint->bulkAction([
      'action' => 'trash',
      'listing' => ['group' => 'all'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);
  }

  public function testItCannotRunAnInvalidBulkAction() {
    try {
      $this->endpoint->bulkAction([
        'action' => 'invalidAction',
        'listing' => [],
      ]);
    } catch (UnexpectedValueException $exception) {
      expect($exception->getHttpStatusCode())->equals(APIResponse::STATUS_BAD_REQUEST);
      expect($exception->getErrors()[Error::BAD_REQUEST])->stringContainsString('Invalid bulk action');
    }
  }

  public function testItFailsWithEmailFilled() {
    $response = $this->endpoint->subscribe([
      'form_id' => $this->form->getId(),
      'email' => 'toto@mailpoet.com',
      // no form ID specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please leave the first field empty.');
  }

  public function testItCannotSubscribeWithoutFormID() {
    $response = $this->endpoint->subscribe([
      'form_field_ZW1haWw' => 'toto@mailpoet.com',
      // no form ID specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a valid form ID.');
  }

  public function testItCannotSubscribeWithoutSegmentsIfTheyAreSelectedByUser() {
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      // no segments specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please select a list.');
  }

  public function testItCanSubscribe() {
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  public function testItCanSubscribeToSelectedSegment() {
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment2->getId()],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneBy(['email' => 'toto@mailpoet.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $segments = $subscriber->getSegments();
    expect($segments->count())->equals(1);
    expect($segments->get(0)->getId())->equals($this->segment2->getId());
  }

  public function testItCannotSubscribeWithoutReCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_RECAPTCHA]);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please check the CAPTCHA.');
    $this->settings->set('captcha', []);
  }

  public function testItCannotSubscribeWithoutInvisibleReCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_RECAPTCHA_INVISIBLE]);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please check the CAPTCHA.');
    $this->settings->set('captcha', []);
  }

  public function testItCannotSubscribeWithoutBuiltInCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_BUILTIN]);
    $email = 'toto@mailpoet.com';
    (new SubscriberFactory())
      ->withEmail($email)
      ->withCountConfirmations(1)
      ->create();
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $email,
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please fill in the CAPTCHA.');
    $this->settings->set('captcha', []);
  }

  public function testItCanSubscribeWithBuiltInCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => CaptchaConstants::TYPE_BUILTIN]);
    $email = 'toto@mailpoet.com';
    (new SubscriberFactory())
      ->withEmail($email)
      ->withCountConfirmations(1)
      ->create();
    $captchaValue = ['phrase' => 'ihG5W'];
    $captchaSessionId = 'abcdfgh';
    $this->captchaSession->init($captchaSessionId);
    $this->captchaSession->setCaptchaHash($captchaValue);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $email,
      'form_id' => $this->form->getId(),
      'captcha_session_id' => $captchaSessionId,
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
      'captcha' => $captchaValue['phrase'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $this->settings->set('captcha', []);
  }

  public function testItCannotSubscribeWithoutMandatoryCustomField() {
    $customField = (new CustomFieldFactory())->create();

    $form = new FormEntity('form');
    $form->setBody([[
      'type' => 'text',
      'name' => 'mandatory',
      'id' => $customField->getId(),
      'unique' => '1',
      'static' => '0',
      'params' => ['required' => '1'],
      'position' => '0',
    ]]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  public function testItCanSubscribeWithoutSegmentsIfTheyAreSelectedByAdmin() {
    $settings = $this->form->getSettings();
    $settings['segments_selected_by'] = 'admin';
    $this->form->setSettings($settings);
    $this->form->setBody(Fixtures::get('form_body_template')); // Body without select segments block
    $this->entityManager->flush();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      // no segments specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneBy(['email' => 'toto@mailpoet.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $segments = $subscriber->getSegments();
    expect($segments->count())->equals(2);
    expect($segments->get(0)->getId())->equals($settings['segments'][0]);
    expect($segments->get(1)->getId())->equals($settings['segments'][1]);
  }

  public function testItCannotSubscribeIfFormHasNoSegmentsDefined() {
    $settings = $this->form->getSettings();
    $settings['segments_selected_by'] = 'admin';
    $settings['segments'] = [];
    $this->form->setSettings($settings);
    $this->form->setBody(Fixtures::get('form_body_template')); // Body without select segments block
    $this->entityManager->flush();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please select a list.');
  }

  public function testItCannotMassSubscribe() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'tata@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('You need to wait 1 minutes before subscribing again.');
  }

  public function testItCannotMassResubscribe() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);

    // Try to resubscribe an existing subscriber that was updated just now
    $subscriber = $this->subscribersRepository->findOneBy(['email' => 'toto@mailpoet.com']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriber->setCreatedAt(Carbon::yesterday());
    $subscriber->setUpdatedAt(Carbon::now());
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $subscriber->getEmail(),
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('You need to wait 1 minutes before subscribing again.');
  }

  public function testThirdPartiesCanInterruptSubscriptionProcess() {

    $expectedErrorMessage = 'ErrorMessage';

    \MailPoet\WP\add_action(
      'mailpoet_subscription_before_subscribe',
      function($data) use ($expectedErrorMessage) {
            throw new UnexpectedValueException($expectedErrorMessage);
      }
    );

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);

    $didSubscribe = $this->subscribersRepository->findOneBy(['email' => 'toto@mailpoet.com']);
    expect($didSubscribe)->null();
    expect($response)->isInstanceOf(ErrorResponse::class);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals($expectedErrorMessage);
  }

  public function testItSchedulesWelcomeEmailNotificationWhenSubscriberIsAdded() {
    $this->_createWelcomeNewsletter();
    $subscriberData = [
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment1->getId(),
      ],
    ];

    $this->endpoint->save($subscriberData);
    expect($this->sendingQueuesRepository->findAll())->count(1);
  }

  public function testItSchedulesWelcomeEmailNotificationWhenExistedSubscriberIsUpdated() {
    $this->_createWelcomeNewsletter();
    $subscriberData = [
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment2->getId(),
      ],
    ];

    // welcome notification is created only for segment #1
    $this->endpoint->save($subscriberData);
    expect($this->sendingQueuesRepository->findAll())->isEmpty();

    $subscriberData['segments'] = [$this->segment1->getId()];
    $this->endpoint->save($subscriberData);
    expect($this->sendingQueuesRepository->findAll())->count(1);
  }

  public function testItDoesNotSchedulesWelcomeEmailNotificationWhenNoNewSegmentIsAdded() {
    $this->_createWelcomeNewsletter();
    $subscriber = (new SubscriberFactory())
      ->withEmail('raul.doe@mailpoet.com')
      ->withFirstName('Jane')
      ->withLastName('Doe')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$this->segment1])
      ->withSource(Source::IMPORTED)
      ->create();

    $subscriberData = [
      'id' => $subscriber->getId(),
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment1->getId(),
      ],
    ];

    $this->endpoint->save($subscriberData);
    expect($this->sendingQueuesRepository->findAll())->count(0);
  }

  public function testItSendsConfirmationEmail() {
    $response = $this->endpoint->sendConfirmationEmail(['id' => 'non_existent']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);

    $response = $this->endpoint->sendConfirmationEmail(['id' => $this->subscriber1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    wp_set_current_user(0);
    $this->subscriber1->setConfirmationsCount(ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS);
    $this->entityManager->flush();
    $response = $this->endpoint->sendConfirmationEmail(['id' => $this->subscriber1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  public function testItDisplaysProperErrorMessageWhenConfirmationEmailsAreDisabled() {
    $this->settings->set('signup_confirmation.enabled', false);
    $response = $this->endpoint->sendConfirmationEmail(['id' => $this->subscriber1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Sign-up confirmation is disabled in your <a href="admin.php?page=mailpoet-settings#/signup">MailPoet settings</a>. Please enable it to resend confirmation emails or update your subscriber’s status manually.');
  }

  public function testItKeepsSpecialSegmentsUnchangedAfterSaving() {
    $wcSegment = (new SegmentFactory())
      ->withName('WooCommerce Users')
      ->withType(SegmentEntity::TYPE_WC_USERS)
      ->create();
    $subscriber = (new SubscriberFactory())
      ->withEmail('woo@commerce.com')
      ->withFirstName('Woo')
      ->withLastName('Commerce')
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$this->segment1, $wcSegment])
      ->create();
    $subscriberData = [
      'id' => $subscriber->getId(),
      'email' => 'woo@commerce.com',
      'first_name' => 'Woo',
      'last_name' => 'Commerce',
      'segments' => [
        $this->segment1->getId(),
      ],
    ];
    $this->endpoint->save($subscriberData);

    $segments = $subscriber->getSegments();
    expect($segments->get(0)->getId())->equals($this->segment1->getId());
    expect($segments->get(1)->getId())->equals($wcSegment->getId());
  }

  private function _createWelcomeNewsletter(): void {
    $newsletterFactory = new NewsletterFactory();
    $newsletterFactory
      ->withActiveStatus()
      ->withWelcomeTypeForSegment($this->segment1->getId())
      ->create();
  }
}
