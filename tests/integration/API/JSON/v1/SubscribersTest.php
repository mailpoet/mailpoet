<?php
namespace MailPoet\Test\API\JSON\v1;

use Carbon\Carbon;
use Codeception\Util\Fixtures;
use MailPoet\API\JSON\v1\Subscribers;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Config\Session;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\Handler;
use MailPoet\Models\CustomField;
use MailPoet\Models\Form;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberIP;
use MailPoet\Models\Segment;
use MailPoet\Models\Setting;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\SubscribersListings;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberActions;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\CaptchaSession;
use MailPoet\Util\Cookies;
use MailPoet\WP\Functions;

class SubscribersTest extends \MailPoetTest {

  /** @var Subscribers */
  private $endpoint;

  /** @var SettingsController */
  private $settings;

  /** @var CaptchaSession */
  private $captcha_session;

  function _before() {
    parent::_before();
    $this->cleanup();
    $cookies_mock = $this->createMock(Cookies::class);
    $cookies_mock->method('get')
      ->willReturn('abcd');
    $session = new Session($cookies_mock);
    $container = ContainerWrapper::getInstance();
    $this->captcha_session = new CaptchaSession($container->get(Functions::class), $session);
    $this->endpoint = new Subscribers(
      $container->get(BulkActionController::class),
      $container->get(SubscribersListings::class),
      $container->get(SubscriberActions::class),
      $container->get(RequiredCustomFieldValidator::class),
      $container->get(Handler::class),
      $container->get(Captcha::class),
      $container->get(Functions::class),
      $container->get(SettingsController::class),
      $this->captcha_session,
      $container->get(ConfirmationEmailMailer::class)
    );
    $obfuscator = new FieldNameObfuscator();
    $this->obfuscatedEmail = $obfuscator->obfuscate('email');
    $this->obfuscatedSegments = $obfuscator->obfuscate('segments');
    $this->segment_1 = Segment::createOrUpdate(['name' => 'Segment 1']);
    $this->segment_2 = Segment::createOrUpdate(['name' => 'Segment 2']);

    $this->subscriber_1 = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_UNCONFIRMED,
      'source' => Source::API,
    ]);
    $this->subscriber_2 = Subscriber::createOrUpdate([
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment_1->id,
        $this->segment_2->id,
      ],
      'source' => Source::API,
    ]);

    $this->form = Form::createOrUpdate([
      'name' => 'My Form',
      'body' => Fixtures::get('form_body_template'),
      'settings' => [
        'segments_selected_by' => 'user',
        'segments' => [
          $this->segment_1->id,
          $this->segment_2->id,
        ],
      ],
    ]);

    $this->settings = new SettingsController();
    // setup mailer
    $this->settings->set('sender', [
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender',
    ]);
  }

  function testItCanGetASubscriber() {
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

    $response = $this->endpoint->get(['id' => $this->subscriber_1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_1->id)
        ->withCustomFields()
        ->withSubscriptions()
        ->asArray()
    );
  }

  function testItCanSaveANewSubscriber() {
    $valid_data = [
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment_1->id,
        $this->segment_2->id,
      ],
    ];

    $response = $this->endpoint->save($valid_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::where('email', 'raul.doe@mailpoet.com')
        ->findOne()
        ->asArray()
    );

    $subscriber = Subscriber::where('email', 'raul.doe@mailpoet.com')->findOne();
    $subscriber_segments = $subscriber->segments()->findMany();
    expect($subscriber_segments)->count(2);
    expect($subscriber_segments[0]->name)->equals($this->segment_1->name);
    expect($subscriber_segments[1]->name)->equals($this->segment_2->name);

    $response = $this->endpoint->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('Please enter your email address');

    $invalid_data = [
      'email' => 'john.doe@invalid',
    ];

    $response = $this->endpoint->save($invalid_data);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('Your email address is invalid!');
    expect($subscriber->source)->equals('administrator');
  }

  function testItCanSaveAnExistingSubscriber() {
    $subscriber_data = $this->subscriber_2->asArray();
    unset($subscriber_data['created_at']);
    $subscriber_data['segments'] = [$this->segment_1->id];
    $subscriber_data['first_name'] = 'Super Jane';

    $response = $this->endpoint->save($subscriber_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_2->id)->asArray()
    );
    expect($response->data['first_name'])->equals('Super Jane');
    expect($response->data['source'])->equals('api');
  }

  function testItCanRemoveListsFromAnExistingSubscriber() {
    $subscriber_data = $this->subscriber_2->asArray();
    unset($subscriber_data['created_at']);
    unset($subscriber_data['segments']);

    $response = $this->endpoint->save($subscriber_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_2->id)->asArray()
    );
    expect($this->subscriber_2->segments()->findArray())->count(0);
  }

  function testItCanRestoreASubscriber() {
    $this->subscriber_1->trash();

    $trashed_subscriber = Subscriber::findOne($this->subscriber_1->id);
    expect($trashed_subscriber->deleted_at)->notNull();

    $response = $this->endpoint->restore(['id' => $this->subscriber_1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanTrashASubscriber() {
    $response = $this->endpoint->trash(['id' => $this->subscriber_2->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDeleteASubscriber() {
    $response = $this->endpoint->delete(['id' => $this->subscriber_1->id]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  function testItCanFilterListing() {
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
        'segment' => $this->segment_1->id,
      ],
    ]);

    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($this->subscriber_2->email);

    // filter by 2nd segment
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $this->segment_2->id,
      ],
    ]);

    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($this->subscriber_2->email);
  }

  function testItCanAddSegmentsUsingHooks() {
    $add_segment = function() {
      return 'segment';
    };
    add_filter('mailpoet_subscribers_listings_filters_segments', $add_segment);
    $response = $this->endpoint->listing([
      'filter' => [
        'segment' => $this->segment_2->id,
      ],
    ]);
    expect($response->meta['filters']['segment'])->equals('segment');
  }

  function testItCanSearchListing() {
    $new_subscriber = Subscriber::createOrUpdate([
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
    expect($response->data[0]['email'])->equals($new_subscriber->email);

    // search by last name
    $response = $this->endpoint->listing([
      'search' => 'doe',
    ]);
    expect($response->meta['count'])->equals(2);
    expect($response->data[0]['email'])->equals($this->subscriber_1->email);
    expect($response->data[1]['email'])->equals($this->subscriber_2->email);

    // search by first name
    $response = $this->endpoint->listing([
      'search' => 'billy',
    ]);
    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($new_subscriber->email);
  }

  function testItCanGroupListing() {
    $subscribed_group = $this->endpoint->listing([
      'group' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    expect($subscribed_group->meta['count'])->equals(1);
    expect($subscribed_group->data[0]['email'])->equals(
      $this->subscriber_2->email
    );

    $unsubscribed_group = $this->endpoint->listing([
      'group' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    expect($unsubscribed_group->meta['count'])->equals(0);

    $unconfirmed_group = $this->endpoint->listing([
      'group' => Subscriber::STATUS_UNCONFIRMED,
    ]);
    expect($unconfirmed_group->meta['count'])->equals(1);
    expect($unconfirmed_group->data[0]['email'])->equals(
      $this->subscriber_1->email
    );

    $trashed_group = $this->endpoint->listing([
      'group' => 'trash',
    ]);
    expect($trashed_group->meta['count'])->equals(0);

    // trash 1st subscriber
    $this->subscriber_1->trash();

    $trashed_group = $this->endpoint->listing([
      'group' => 'trash',
    ]);
    expect($trashed_group->meta['count'])->equals(1);
    expect($trashed_group->data[0]['email'])->equals(
      $this->subscriber_1->email
    );
  }

  function testItCorrectSubscriptionStatus() {
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

  function testItCanSortAndLimitListing() {
    // get 1st page (limit items per page to 1)
    $response = $this->endpoint->listing([
      'limit' => 1,
      'sort_by' => 'first_name',
      'sort_order' => 'asc',
    ]);

    expect($response->meta['count'])->equals(2);
    expect($response->data)->count(1);
    expect($response->data[0]['email'])->equals(
      $this->subscriber_2->email
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
      $this->subscriber_1->email
    );
  }

  function testItCanBulkDeleteSelectionOfSubscribers() {
    $deletable_subscriber = Subscriber::createOrUpdate([
      'email' => 'to.be.removed@mailpoet.com',
    ]);

    $selection_ids = [
      $this->subscriber_1->id,
      $deletable_subscriber->id,
    ];

    $response = $this->endpoint->bulkAction([
      'listing' => [
        'selection' => $selection_ids,
      ],
      'action' => 'delete',
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->isEmpty();
    expect($response->meta['count'])->equals(count($selection_ids));

    $is_subscriber_1_deleted = (
      Subscriber::findOne($this->subscriber_1->id) === false
    );
    $is_deletable_subscriber_deleted = (
      Subscriber::findOne($deletable_subscriber->id) === false
    );

    expect($is_subscriber_1_deleted)->true();
    expect($is_deletable_subscriber_deleted)->true();
  }

  function testItCanBulkDeleteSubscribers() {
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

  function testItCannotRunAnInvalidBulkAction() {
    $response = $this->endpoint->bulkAction([
      'action' => 'invalidAction',
      'listing' => [],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('has no method');
  }

  function testItFailsWithEmailFilled() {
    $response = $this->endpoint->subscribe([
      'form_id' => $this->form->id,
      'email' => 'toto@mailpoet.com',
      // no form ID specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please leave the first field empty.');
  }

  function testItCannotSubscribeWithoutFormID() {
    $response = $this->endpoint->subscribe([
      'form_field_ZW1haWw' => 'toto@mailpoet.com',
      // no form ID specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a valid form ID.');
  }

  function testItCannotSubscribeWithoutSegmentsIfTheyAreSelectedByUser() {
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      // no segments specified
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please select a list.');
  }

  function testItCanSubscribe() {
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItCannotSubscribeWithoutReCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => Captcha::TYPE_RECAPTCHA]);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please check the CAPTCHA.');
    $this->settings->set('captcha', []);
  }

  function testItCannotSubscribeWithoutBuiltInCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => Captcha::TYPE_BUILTIN]);
    $email = 'toto@mailpoet.com';
    $subscriber = Subscriber::create();
    $subscriber->email = $email;
    $subscriber->count_confirmations = 1;
    $subscriber->save();
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $email,
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please fill in the CAPTCHA.');
    $this->settings->set('captcha', []);
  }

  function testItCanSubscribeWithBuiltInCaptchaWhenEnabled() {
    $this->settings->set('captcha', ['type' => Captcha::TYPE_BUILTIN]);
    $email = 'toto@mailpoet.com';
    $subscriber = Subscriber::create();
    $subscriber->email = $email;
    $subscriber->count_confirmations = 1;
    $subscriber->save();
    $captcha_value = 'ihg5w';
    $this->captcha_session->setCaptchaHash($captcha_value);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $email,
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
      'captcha' => $captcha_value,
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $this->settings->set('captcha', []);
  }

  function testItCannotSubscribeWithoutMandatoryCustomField() {
    $custom_field = CustomField::createOrUpdate([
      'name' => 'custom field',
      'type' => 'text',
      'params' => ['required' => '1'],
    ]);
    $form = Form::createOrUpdate([
      'name' => 'form',
      'body' => [[
        'type' => 'text',
        'name' => 'mandatory',
        'id' => $custom_field->id(),
        'unique' => '1',
        'static' => '0',
        'params' => ['required' => '1'],
        'position' => '0',
      ]],
    ]);
    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
  }

  function testItCanSubscribeWithoutSegmentsIfTheyAreSelectedByAdmin() {
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
    $subscriber_segments = $subscriber->segments()->findArray();
    expect($subscriber_segments)->count(2);
    expect($subscriber_segments[0]['id'])->equals($form['settings']['segments'][0]);
    expect($subscriber_segments[1]['id'])->equals($form['settings']['segments'][1]);
  }

  function testItCannotSubscribeIfFormHasNoSegmentsDefined() {
    $form = $this->form->asArray();
    $form['settings']['segments_selected_by'] = 'admin';
    unset($form['settings']['segments']);
    $this->form->settings = $form['settings'];
    $this->form->save();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please select a list.');
  }

  function testItCannotMassSubscribe() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'tata@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('You need to wait 1 minutes before subscribing again.');
  }

  function testItCannotMassResubscribe() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $this->endpoint->subscribe([
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);

    // Try to resubscribe an existing subscriber that was updated just now
    $subscriber = Subscriber::where('email', 'toto@mailpoet.com')->findOne();
    $subscriber->created_at = Carbon::yesterday();
    $subscriber->updated_at = Carbon::now();
    $subscriber->save();

    $response = $this->endpoint->subscribe([
      $this->obfuscatedEmail => $subscriber->email,
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => [$this->segment_1->id, $this->segment_2->id],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('You need to wait 1 minutes before subscribing again.');
  }

  function testItSchedulesWelcomeEmailNotificationWhenSubscriberIsAdded() {
    $this->_createWelcomeNewsletter();
    $subscriber_data = [
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment_1->id,
      ],
    ];

    $this->endpoint->save($subscriber_data);
    expect(SendingQueue::findMany())->count(1);
  }

  function testItSchedulesWelcomeEmailNotificationWhenExistedSubscriberIsUpdated() {
    $this->_createWelcomeNewsletter();
    $subscriber_data = [
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment_2->id,
      ],
    ];

    // welcome notification is created only for segment #1
    $this->endpoint->save($subscriber_data);
    expect(SendingQueue::findMany())->isEmpty();

    $subscriber_data['segments'] = [$this->segment_1->id];
    $this->endpoint->save($subscriber_data);
    expect(SendingQueue::findMany())->count(1);
  }

  function testItDoesNotSchedulesWelcomeEmailNotificationWhenNoNewSegmentIsAdded() {
    $this->_createWelcomeNewsletter();
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => [
        $this->segment_1->id,
      ],
      'source' => Source::IMPORTED,
    ]);
    $subscriber_data = [
      'id' => $subscriber->id(),
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => [
        $this->segment_1->id,
      ],
    ];

    $this->endpoint->save($subscriber_data);
    expect(SendingQueue::findMany())->count(0);
  }

  function testItSendsConfirmationEmail() {
    $response = $this->endpoint->sendConfirmationEmail(['id' => 'non_existent']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);

    $response = $this->endpoint->sendConfirmationEmail(['id' => $this->subscriber_1->id()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    wp_set_current_user(0);
    $this->subscriber_1->count_confirmations = ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS;
    $this->subscriber_1->save();
    $response = $this->endpoint->sendConfirmationEmail(['id' => $this->subscriber_1->id()]);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
  }

  private function _createWelcomeNewsletter() {
    $welcome_newsletter = Newsletter::create();
    $welcome_newsletter->type = Newsletter::TYPE_WELCOME;
    $welcome_newsletter->status = Newsletter::STATUS_ACTIVE;
    $welcome_newsletter->save();
    expect($welcome_newsletter->getErrors())->false();

    $welcome_newsletter_options = [
      'event' => 'segment',
      'segment' => $this->segment_1->id,
      'schedule' => '* * * * *',
    ];

    foreach ($welcome_newsletter_options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = Newsletter::TYPE_WELCOME;
      $newsletter_option_field->save();
      expect($newsletter_option_field->getErrors())->false();

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $welcome_newsletter->id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }
  }

  function _after() {
    $this->cleanup();
  }

  private function cleanup() {
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberIP::$_table);
    \ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
