<?php

namespace MailPoet\Test\API\JSON\v1;

use Codeception\Util\Fixtures;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Subscribers;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Listing\BulkActionController;
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
use MailPoet\Segments\SubscribersListings;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberActions;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class SubscribersTest extends \MailPoetTest {
  public $form;
  public $subscriber2;
  public $subscriber1;
  public $segment2;
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
      $container->get(BulkActionController::class),
      $container->get(SubscribersListings::class),
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
      $container->get(StatisticsUnsubscribesRepository::class),
      $obfuscator
    );
    $this->obfuscatedEmail = $obfuscator->obfuscate('email');
    $this->obfuscatedSegments = $obfuscator->obfuscate('segments');
    $this->segment1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $this->segment2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    $this->subscriber1 = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_UNCONFIRMED,
      'source' => Source::API,
    ]);
    $this->subscriber2 = Subscriber::createOrUpdate([
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment1->id,
        $this->segment2->id,
      ],
      'source' => Source::API,
    ]);

    $this->form = Form::createOrUpdate([
      'name' => 'My Form',
      'body' => Fixtures::get('form_body_template'),
      'settings' => [
        'segments_selected_by' => 'user',
        'segments' => [
          $this->segment1->id,
          $this->segment2->id,
        ],
      ],
    ]);

    $this->settings = SettingsController::getInstance();
    // setup mailer
    $this->settings->set('sender', [
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender',
    ]);
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

    $response = $this->endpoint->get(['id' => $this->subscriber1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['id'])->equals($this->subscriber1->id);
    expect($response->data['first_name'])->equals($this->subscriber1->first_name);
    expect($response->data['email'])->equals($this->subscriber1->email);
    expect($response->data['unsubscribes'])->equals([]);
    expect($response->data['subscriptions'])->equals([]);
  }

  public function testItCanSaveANewSubscriber() {
    $validData = [
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment1->id,
        $this->segment2->id,
      ],
    ];

    $response = $this->endpoint->save($validData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::where('email', 'raul.doe@mailpoet.com')
        ->findOne()
        ->asArray()
    );

    $subscriber = Subscriber::where('email', 'raul.doe@mailpoet.com')->findOne();
    $subscriberSegments = $subscriber->segments()->findMany();
    expect($subscriberSegments)->count(2);
    expect($subscriberSegments[0]->name)->equals($this->segment1->name);
    expect($subscriberSegments[1]->name)->equals($this->segment2->name);

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
    expect($subscriber->source)->equals('administrator');
  }

  public function testItCanSaveAnExistingSubscriber() {
    $subscriberData = $this->subscriber2->asArray();
    unset($subscriberData['created_at']);
    $subscriberData['segments'] = [$this->segment1->id];
    $subscriberData['first_name'] = 'Super Jane';

    $response = $this->endpoint->save($subscriberData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber2->id)->asArray()
    );
    expect($response->data['first_name'])->equals('Super Jane');
    expect($response->data['source'])->equals('api');
  }

  public function testItCanRemoveListsFromAnExistingSubscriber() {
    $subscriberData = $this->subscriber2->asArray();
    unset($subscriberData['created_at']);
    unset($subscriberData['segments']);

    $response = $this->endpoint->save($subscriberData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber2->id)->asArray()
    );
    expect($this->subscriber2->segments()->findArray())->count(0);
  }

  public function testItCanRestoreASubscriber() {
    $this->subscriber1->trash();

    $trashedSubscriber = Subscriber::findOne($this->subscriber1->id);
    expect($trashedSubscriber->deletedAt)->notNull();

    $response = $this->endpoint->restore(['id' => $this->subscriber1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanTrashASubscriber() {
    $response = $this->endpoint->trash(['id' => $this->subscriber2->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteASubscriber() {
    $response = $this->endpoint->delete(['id' => $this->subscriber1->id]);
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
        'segment' => $this->segment1->id,
      ],
    ]);

    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($this->subscriber2->email);

    // filter by 2nd segment
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $this->segment2->id,
      ],
    ]);

    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($this->subscriber2->email);
  }

  public function testItCanAddSegmentsUsingHooks() {
    $addSegment = function() {
      return 'segment';
    };
    add_filter('mailpoet_subscribers_listings_filters_segments', $addSegment);
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $this->segment2->id,
      ],
    ]);
    expect($response->meta['filters']['segment'])->equals('segment');
  }

  public function testItCanSearchListing() {
    $newSubscriber = Subscriber::createOrUpdate([
      'email' => 'search.me@find.me',
      'first_name' => 'Billy Bob',
      'last_name' => 'Thornton',
    ]);

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
    expect($response->data[0]['email'])->equals($newSubscriber->email);

    // search by last name
    $response = $this->endpoint->listing([
      'search' => 'doe',
    ]);
    expect($response->meta['count'])->equals(2);
    expect($response->data[0]['email'])->equals($this->subscriber1->email);
    expect($response->data[1]['email'])->equals($this->subscriber2->email);

    // search by first name
    $response = $this->endpoint->listing([
      'search' => 'billy',
    ]);
    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($newSubscriber->email);
  }

  public function testItCanGroupListing() {
    $subscribedGroup = $this->endpoint->listing([
      'group' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    expect($subscribedGroup->meta['count'])->equals(1);
    expect($subscribedGroup->data[0]['email'])->equals(
      $this->subscriber2->email
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
      $this->subscriber1->email
    );

    $trashedGroup = $this->endpoint->listing([
      'group' => 'trash',
    ]);
    expect($trashedGroup->meta['count'])->equals(0);

    // trash 1st subscriber
    $this->subscriber1->trash();

    $trashedGroup = $this->endpoint->listing([
      'group' => 'trash',
    ]);
    expect($trashedGroup->meta['count'])->equals(1);
    expect($trashedGroup->data[0]['email'])->equals(
      $this->subscriber1->email
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
      $this->subscriber2->email
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
      $this->subscriber1->email
    );
  }

  public function testItCanBulkDeleteSelectionOfSubscribers() {
    $deletableSubscriber = Subscriber::createOrUpdate([
      'email' => 'to.be.removed@mailpoet.com',
    ]);

    $selectionIds = [
      $this->subscriber1->id,
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
      Subscriber::findOne($this->subscriber1->id) === false
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
    $response = $this->endpoint->bulkAction([
      'action' => 'invalidAction',
      'listing' => [],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('has no method');
  }

  public function testItFailsWithEmailFilled() {
    $response = $this->endpoint->subscribe([
      'form_id' => $this->form->id,
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
      'form_id' => $this->form->id,
      // no segments specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please select a list.');
  }

  public function testItCanSubscribe() {
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  public function testItCannotSubscribeWithoutReCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => Captcha::TYPE_RECAPTCHA]);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
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
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
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
      'form_id' => $this->form->id,
      'captcha_session_id' => $captchaSessionId,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
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
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  public function testItCanSubscribeWithoutSegmentsIfTheyAreSelectedByAdmin() {
    $form = $this->form->asArray();
    $form['settings']['segments_selected_by'] = 'admin';
    $this->form->settings = $form['settings'];
    $this->form->save();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      // no segments specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    $subscriber = Subscriber::where('email', 'toto@mailpoet.com')->findOne();
    $subscriberSegments = $subscriber->segments()->findArray();
    expect($subscriberSegments)->count(2);
    expect($subscriberSegments[0]['id'])->equals($form['settings']['segments'][0]);
    expect($subscriberSegments[1]['id'])->equals($form['settings']['segments'][1]);
  }

  public function testItCannotSubscribeIfFormHasNoSegmentsDefined() {
    $form = $this->form->asArray();
    $form['settings']['segments_selected_by'] = 'admin';
    unset($form['settings']['segments']);
    $this->form->settings = $form['settings'];
    $this->form->save();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please select a list.');
  }

  public function testItCannotMassSubscribe() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
    ]);

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'tata@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('You need to wait 1 minutes before subscribing again.');
  }

  public function testItCannotMassResubscribe() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
    ]);

    // Try to resubscribe an existing subscriber that was updated just now
    $subscriber = Subscriber::where('email', 'toto@mailpoet.com')->findOne();
    $subscriber->createdAt = Carbon::yesterday();
    $subscriber->updatedAt = Carbon::now();
    $subscriber->save();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $subscriber->email,
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment1->id, $this->segment2->id],
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
        $this->segment1->id,
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
        $this->segment2->id,
      ],
    ];

    // welcome notification is created only for segment #1
    $this->endpoint->save($subscriberData);
    expect(SendingQueue::findMany())->isEmpty();

    $subscriberData['segments'] = [$this->segment1->id];
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
        $this->segment1->id,
      ],
      'source' => Source::IMPORTED,
    ]);
    $subscriberData = [
      'id' => $subscriber->id(),
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment1->id,
      ],
    ];

    $this->endpoint->save($subscriberData);
    expect(SendingQueue::findMany())->count(0);
  }

  public function testItSendsConfirmationEmail() {
    $response = $this->endpoint->sendConfirmationEmail(['id' => 'non_existent']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);

    $response = $this->endpoint->sendConfirmationEmail(['id' => $this->subscriber1->id()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    wp_set_current_user(0);
    $this->subscriber1->count_confirmations = ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS;
    $this->subscriber1->save();
    $response = $this->endpoint->sendConfirmationEmail(['id' => $this->subscriber1->id()]);
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
        $this->segment1->id,
        $wcSegment->id,
      ],
    ]);
    $subscriberData = [
      'id' => $subscriber->id(),
      'email' => 'woo@commerce.com',
      'first_name' => 'Woo',
      'last_name' => 'Commerce',
      'segments' => [
        $this->segment1->id,
      ],
    ];
    $this->endpoint->save($subscriberData);

    $subscriber = Subscriber::findOne($subscriber->id);
    $subscriberSegments = $subscriber->segments()->findArray();
    expect($subscriberSegments[0]['id'])->equals($this->segment1->id);
    expect($subscriberSegments[1]['id'])->equals($wcSegment->id);
  }

  private function _createWelcomeNewsletter() {
    $welcomeNewsletter = Newsletter::create();
    $welcomeNewsletter->type = Newsletter::TYPE_WELCOME;
    $welcomeNewsletter->status = Newsletter::STATUS_ACTIVE;
    $welcomeNewsletter->save();
    expect($welcomeNewsletter->getErrors())->false();

    $welcomeNewsletterOptions = [
      'event' => 'segment',
      'segment' => $this->segment1->id,
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
      $newsletterOption->value = $value;
      $newsletterOption->save();
      expect($newsletterOption->getErrors())->false();
    }
  }

  public function _after() {
    $this->cleanup();
  }

  private function cleanup() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberIP::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    $this->diContainer->get(SettingsRepository::class)->truncate();
  }
}
