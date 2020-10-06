<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Fixtures;
use MailPoet\API\JSON\Error;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\API\JSON\v1\Subscribers;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Listing\Handler;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberIP;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberActions;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\Test\DataFactories\DynamicSegment;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

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

  public function _before() {
    parent::_before();
    $this->cleanup();
    $container = ContainerWrapper::getInstance();
    $settings = $container->get(SettingsController::class);
    $wp = $container->get(Functions::class);
    $this->captchaSession = new CaptchaSession($container->get(Functions::class));
    $obfuscator = new FieldNameObfuscator($wp);
    $this->endpoint = new Subscribers(
      $container->get(SubscriberActions::class),
      $container->get(RequiredCustomFieldValidator::class),
      $container->get(Handler::class),
      $container->get(Captcha::class),
      $wp,
      $settings,
      $this->captchaSession,
      $container->get(ConfirmationEmailMailer::class),
      new SubscriptionUrlFactory($wp, $settings, new LinkTokens),
      $container->get(Unsubscribes::class),
      $container->get(SubscribersRepository::class),
      $container->get(SubscribersResponseBuilder::class),
      $container->get(SubscriberListingRepository::class),
      $container->get(SegmentsRepository::class),
      $obfuscator
    );
    $this->obfuscatedEmail = $obfuscator->obfuscate('email');
    $this->obfuscatedSegments = $obfuscator->obfuscate('segments');
    $this->segment1 = new SegmentEntity('Segment 1', SegmentEntity::TYPE_DEFAULT, 'Segment 1');
    $this->segment2 = new SegmentEntity('Segment 2', SegmentEntity::TYPE_DEFAULT, 'Segment 2');
    $this->entityManager->persist($this->segment1);
    $this->entityManager->persist($this->segment2);


    $this->subscriber1 = new SubscriberEntity();
    $this->subscriber1->setEmail('john@mailpoet.com');
    $this->subscriber1->setFirstName('John');
    $this->subscriber1->setLastName('Doe');
    $this->subscriber1->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscriber1->setSource(Source::API);
    $this->entityManager->persist($this->subscriber1);

    $this->subscriber2 = new SubscriberEntity();
    $this->subscriber2->setEmail('jane@mailpoet.com');
    $this->subscriber2->setFirstName('Jane');
    $this->subscriber2->setLastName('Doe');
    $this->subscriber2->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $this->subscriber2->setSource(Source::API);
    $this->entityManager->persist($this->subscriber2);
    $this->entityManager->flush();

    $this->entityManager->persist(
      new SubscriberSegmentEntity($this->segment1, $this->subscriber2, SubscriberEntity::STATUS_SUBSCRIBED)
    );
    $this->entityManager->persist(
      new SubscriberSegmentEntity($this->segment2, $this->subscriber2, SubscriberEntity::STATUS_SUBSCRIBED)
    );

    $this->form = new FormEntity('My Form');
    $this->form->setBody(Fixtures::get('form_body_template'));
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
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneBy(['email' => 'raul.doe@mailpoet.com']);
    expect($response->data['email'])->equals('raul.doe@mailpoet.com');
    expect($response->data['id'])->equals($subscriber->getId());
    expect($response->data['status'])->equals($subscriber->getStatus());

    $subscriberSegments = $subscriber->getSegments();
    expect($subscriberSegments->count())->equals(2);
    expect($subscriberSegments->get(0)->getName())->equals($this->segment1->getName());
    expect($subscriberSegments->get(1)->getName())->equals($this->segment2->getName());

    $response = $this->endpoint->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('Please enter your email address');

    $invalidData = [
      'email' => 'john.doe@invalid',
    ];

    $response = $this->endpoint->save($invalidData);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('Your email address is invalid!');
    expect($subscriber->getSource())->equals('administrator');
  }

  public function testItCanSaveAnExistingSubscriber() {
    $subscriberData = [
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Super Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [$this->segment1->getId()],
      'source' => Source::API,
    ];

    $response = $this->endpoint->save($subscriberData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber2->getId())->asArray()
    );
    expect($response->data['first_name'])->equals('Super Jane');
    expect($response->data['source'])->equals('api');
  }

  public function testItCanRemoveListsFromAnExistingSubscriber() {
    $subscriberData = [
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Super Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'source' => Source::API,
    ];

    $response = $this->endpoint->save($subscriberData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber2->getId())->asArray()
    );
    expect($this->subscriber2->getSegments()->count())->equals(0);
  }

  public function testItCanRestoreASubscriber() {
    $this->subscriber1->setDeletedAt(new \DateTime());
    $this->entityManager->flush();

    $response = $this->endpoint->restore(['id' => $this->subscriber1->getId()]);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneById($this->subscriber1->getId());
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

  public function testItCanLoadDymanicSegments() {
    $dynamicSegmentFactory = new DynamicSegment();
    $dynamicSegment = $dynamicSegmentFactory
      ->withName('Dynamic')
      ->withUserRoleFilter('editor')
      ->create();
    $dynamicSegment->save();
    $wpUserEmail = 'wpuserEditor@example.com';
    $this->tester->createWordPressUser($wpUserEmail, 'editor');
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $dynamicSegment->id,
      ],
    ]);
    expect($response->meta['filters']['segment'])->contains(['value' => $dynamicSegment->id, 'label' => 'Dynamic (1)']);
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
      'group' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    expect($subscribedGroup->meta['count'])->equals(1);
    expect($subscribedGroup->data[0]['email'])->equals(
      $this->subscriber2->getEmail()
    );

    $unsubscribedGroup = $this->endpoint->listing([
      'group' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    expect($unsubscribedGroup->meta['count'])->equals(0);

    $unconfirmedGroup = $this->endpoint->listing([
      'group' => Subscriber::STATUS_UNCONFIRMED,
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
    $segment = Segment::createOrUpdate(['name' => 'Segment185245']);
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'third@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $segment->id,
      ],
      'source' => Source::API,
    ]);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => $segment->id,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $segment->id,
      ],
    ]);

    expect($response->data[0]['status'])->equals(Subscriber::STATUS_UNSUBSCRIBED);
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

  public function testItCanBulkDeleteSelectionOfSubscribers() {
    $deletableSubscriber = Subscriber::createOrUpdate([
      'email' => 'to.be.removed@mailpoet.com',
    ]);

    $selectionIds = [
      $this->subscriber1->getId(),
      $deletableSubscriber->id,
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

    $isSubscriber1Deleted = (
      Subscriber::findOne($this->subscriber1->getId()) === false
    );
    $isDeletableSubscriberDeleted = (
      Subscriber::findOne($deletableSubscriber->id) === false
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
      expect($exception->getErrors()[Error::BAD_REQUEST])->contains('Invalid bulk action');
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

  public function testItCannotSubscribeWithoutReCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => Captcha::TYPE_RECAPTCHA]);
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
    $this->settings->set('captcha', ['type' => Captcha::TYPE_BUILTIN]);
    $email = 'toto@mailpoet.com';
    $subscriber = Subscriber::create();
    $subscriber->email = $email;
    $subscriber->countConfirmations = 1;
    $subscriber->save();
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
    $this->settings->set('captcha', ['type' => Captcha::TYPE_BUILTIN]);
    $email = 'toto@mailpoet.com';
    $subscriber = Subscriber::create();
    $subscriber->email = $email;
    $subscriber->countConfirmations = 1;
    $subscriber->save();
    $captchaValue = 'ihG5W';
    $captchaSessionId = 'abcdfgh';
    $this->captchaSession->init($captchaSessionId);
    $this->captchaSession->setCaptchaHash($captchaValue);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $email,
      'form_id' => $this->form->getId(),
      'captcha_session_id' => $captchaSessionId,
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
      'captcha' => $captchaValue,
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $this->settings->set('captcha', []);
  }

  public function testItCannotSubscribeWithoutMandatoryCustomField() {
    $customField = CustomField::createOrUpdate([
      'name' => 'custom field',
      'type' => 'text',
      'params' => ['required' => '1'],
    ]);
    $form = Form::createOrUpdate([
      'name' => 'form',
      'body' => [[
        'type' => 'text',
        'name' => 'mandatory',
        'id' => $customField->id(),
        'unique' => '1',
        'static' => '0',
        'params' => ['required' => '1'],
        'position' => '0',
      ]],
    ]);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $form->id,
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  public function testItCanSubscribeWithoutSegmentsIfTheyAreSelectedByAdmin() {
    $settings = $this->form->getSettings();
    $settings['segments_selected_by'] = 'admin';
    $this->form->setSettings($settings);
    $this->entityManager->flush();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->getId(),
      // no segments specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneBy(['email' => 'toto@mailpoet.com']);
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
    $subscriber = Subscriber::where('email', 'toto@mailpoet.com')->findOne();
    $subscriber->createdAt = Carbon::yesterday();
    $subscriber->updatedAt = Carbon::now();
    $subscriber->save();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $subscriber->email,
      'form_id' => $this->form->getId(),
      $this->obfuscatedSegments => [$this->segment1->getId(), $this->segment2->getId()],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('You need to wait 1 minutes before subscribing again.');
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
    expect(SendingQueue::findMany())->count(1);
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
    expect(SendingQueue::findMany())->isEmpty();

    $subscriberData['segments'] = [$this->segment1->getId()];
    $this->endpoint->save($subscriberData);
    expect(SendingQueue::findMany())->count(1);
  }

  public function testItDoesNotSchedulesWelcomeEmailNotificationWhenNoNewSegmentIsAdded() {
    $this->_createWelcomeNewsletter();
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment1->getId(),
      ],
      'source' => Source::IMPORTED,
    ]);
    $subscriberData = [
      'id' => $subscriber->id(),
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment1->getId(),
      ],
    ];

    $this->endpoint->save($subscriberData);
    expect(SendingQueue::findMany())->count(0);
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

  public function testItKeepsSpecialSegmentsUnchangedAfterSaving() {
    $wcSegment = Segment::createOrUpdate([
      'name' => 'WooCommerce Users',
      'type' => Segment::TYPE_WC_USERS,
    ]);
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'woo@commerce.com',
      'first_name' => 'Woo',
      'last_name' => 'Commerce',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment1->getId(),
        $wcSegment->id,
      ],
    ]);
    $subscriberData = [
      'id' => $subscriber->id(),
      'email' => 'woo@commerce.com',
      'first_name' => 'Woo',
      'last_name' => 'Commerce',
      'segments' => [
        $this->segment1->getId(),
      ],
    ];
    $this->endpoint->save($subscriberData);

    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneById($subscriber->id);
    $segments = $subscriber->getSegments();
    expect($segments->get(0)->getId())->equals($this->segment1->getId());
    expect($segments->get(1)->getId())->equals($wcSegment->id);
  }

  private function _createWelcomeNewsletter() {
    $welcomeNewsletter = Newsletter::create();
    $welcomeNewsletter->type = Newsletter::TYPE_WELCOME;
    $welcomeNewsletter->status = Newsletter::STATUS_ACTIVE;
    $welcomeNewsletter->save();
    expect($welcomeNewsletter->getErrors())->false();

    $welcomeNewsletterOptions = [
      'event' => 'segment',
      'segment' => $this->segment1->getId(),
      'schedule' => '* * * * *',
    ];

    foreach ($welcomeNewsletterOptions as $option => $value) {
      $newsletterOptionField = NewsletterOptionField::create();
      $newsletterOptionField->name = $option;
      $newsletterOptionField->newsletterType = Newsletter::TYPE_WELCOME;
      $newsletterOptionField->save();
      expect($newsletterOptionField->getErrors())->false();

      $newsletterOption = NewsletterOption::create();
      $newsletterOption->optionFieldId = (int)$newsletterOptionField->id;
      $newsletterOption->newsletterId = $welcomeNewsletter->id;
      $newsletterOption->value = (string)$value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }
  }

  public function _after() {
    $this->cleanup();
  }

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(NewsletterOptionEntity::class);
    $this->truncateEntity(NewsletterOptionFieldEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(CustomFieldEntity::class);
    $this->diContainer->get(SettingsRepository::class)->truncate();
    ORM::raw_execute('TRUNCATE ' . SubscriberIP::$_table);
  }
}
