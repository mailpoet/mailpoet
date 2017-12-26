<?php
namespace MailPoet\Test\API\JSON\v1;

use Carbon\Carbon;
use Codeception\Util\Fixtures;
use MailPoet\API\JSON\v1\Subscribers;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Form\Util\FieldNameObfuscator;
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

class SubscribersTest extends \MailPoetTest {
  function _before() {
    $obfuscator = new FieldNameObfuscator();
    $this->obfuscatedEmail = $obfuscator->obfuscate('email');
    $this->obfuscatedSegments = $obfuscator->obfuscate('segments');
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    $this->subscriber_1 = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com',
      'first_name' => 'John',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_UNCONFIRMED
    ));
    $this->subscriber_2 = Subscriber::createOrUpdate(array(
      'email' => 'jane@mailpoet.com',
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'segments' => array(
        $this->segment_1->id,
        $this->segment_2->id
      )
    ));

    $this->form = Form::createOrUpdate(array(
      'name' => 'My Form',
      'body' => Fixtures::get('form_body_template'),
      'settings' => array(
        'segments_selected_by' => 'user',
        'segments' => array(
          $this->segment_1->id,
          $this->segment_2->id
        )
      )
    ));

    // setup mailer
    Setting::setValue('sender', array(
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender'
    ));
  }

  function testItCanGetASubscriber() {
    $router = new Subscribers();

    $response = $router->get(array('id' => 'not_an_id'));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals(
      'This subscriber does not exist.'
    );

    $response = $router->get(/* missing argument */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals(
      'This subscriber does not exist.'
    );

    $response = $router->get(array('id' => $this->subscriber_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_1->id)
        ->withCustomFields()
        ->withSubscriptions()
        ->asArray()
    );
  }

  function testItCanSaveANewSubscriber() {
    $valid_data = array(
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => array(
        $this->segment_1->id,
        $this->segment_2->id
      )
    );

    $router = new Subscribers();
    $response = $router->save($valid_data);
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

    $response = $router->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('Please enter your email address');

    $invalid_data = array(
      'email' => 'john.doe@invalid'
    );

    $response = $router->save($invalid_data);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('Your email address is invalid!');
  }

  function testItCanSaveAnExistingSubscriber() {
    $router = new Subscribers();
    $subscriber_data = $this->subscriber_2->asArray();
    unset($subscriber_data['created_at']);
    $subscriber_data['segments'] = array($this->segment_1->id);
    $subscriber_data['first_name'] = 'Super Jane';

    $response = $router->save($subscriber_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_2->id)->asArray()
    );
    expect($response->data['first_name'])->equals('Super Jane');
  }


  function testItCanRemoveListsFromAnExistingSubscriber() {
    $router = new Subscribers();
    $subscriber_data = $this->subscriber_2->asArray();
    unset($subscriber_data['created_at']);
    unset($subscriber_data['segments']);

    $response = $router->save($subscriber_data);
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

    $router = new Subscribers();
    $response = $router->restore(array('id' => $this->subscriber_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanTrashASubscriber() {
    $router = new Subscribers();
    $response = $router->trash(array('id' => $this->subscriber_2->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Subscriber::findOne($this->subscriber_2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDeleteASubscriber() {
    $router = new Subscribers();
    $response = $router->delete(array('id' => $this->subscriber_1->id));
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  function testItCanFilterListing() {
    $router = new Subscribers();

    // filter by non existing segment
    $response = $router->listing(array(
      'filter' => array(
        'segment' => '### invalid_segment_id ###'
      )
    ));

    // it should return all subscribers
    expect($response->meta['count'])->equals(2);

    // filter by 1st segment
    $response = $router->listing(array(
      'filter' => array(
        'segment' => $this->segment_1->id
      )
    ));

    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($this->subscriber_2->email);

    // filter by 2nd segment
    $response = $router->listing(array(
      'filter' => array(
        'segment' => $this->segment_2->id
      )
    ));

    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($this->subscriber_2->email);
  }

  function testItCanAddSegmentsUsingHooks() {
    $add_segment = function() {
      return 'segment';
    };
    add_filter('mailpoet_subscribers_listings_filters_segments', $add_segment);
    $router = new Subscribers();
    $response = $router->listing(array(
      'filter' => array(
        'segment' => $this->segment_2->id
      )
    ));
    expect($response->meta['filters']['segment'])->equals('segment');
  }

  function testItCanSearchListing() {
    $new_subscriber =  Subscriber::createOrUpdate(array(
      'email' => 'search.me@find.me',
      'first_name' => 'Billy Bob',
      'last_name' => 'Thornton'
    ));

    $router = new Subscribers();

    // empty search returns everything
    $response = $router->listing(array(
      'search' => ''
    ));
    expect($response->meta['count'])->equals(3);

    // search by email
    $response = $router->listing(array(
      'search' => '.me'
    ));
    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($new_subscriber->email);

    // search by last name
    $response = $router->listing(array(
      'search' => 'doe'
    ));
    expect($response->meta['count'])->equals(2);
    expect($response->data[0]['email'])->equals($this->subscriber_1->email);
    expect($response->data[1]['email'])->equals($this->subscriber_2->email);

    // search by first name
    $response = $router->listing(array(
      'search' => 'billy'
    ));
    expect($response->meta['count'])->equals(1);
    expect($response->data[0]['email'])->equals($new_subscriber->email);
  }

  function testItCanGroupListing() {
    $router = new Subscribers();

    $subscribed_group = $router->listing(array(
      'group' => Subscriber::STATUS_SUBSCRIBED
    ));
    expect($subscribed_group->meta['count'])->equals(1);
    expect($subscribed_group->data[0]['email'])->equals(
      $this->subscriber_2->email
    );

    $unsubscribed_group = $router->listing(array(
      'group' => Subscriber::STATUS_UNSUBSCRIBED
    ));
    expect($unsubscribed_group->meta['count'])->equals(0);

    $unconfirmed_group = $router->listing(array(
      'group' => Subscriber::STATUS_UNCONFIRMED
    ));
    expect($unconfirmed_group->meta['count'])->equals(1);
    expect($unconfirmed_group->data[0]['email'])->equals(
      $this->subscriber_1->email
    );

    $trashed_group = $router->listing(array(
      'group' => 'trash'
    ));
    expect($trashed_group->meta['count'])->equals(0);

    // trash 1st subscriber
    $this->subscriber_1->trash();

    $trashed_group = $router->listing(array(
      'group' => 'trash'
    ));
    expect($trashed_group->meta['count'])->equals(1);
    expect($trashed_group->data[0]['email'])->equals(
      $this->subscriber_1->email
    );
  }

  function testItCanSortAndLimitListing() {
    $router = new Subscribers();
    // get 1st page (limit items per page to 1)
    $response = $router->listing(array(
      'limit' => 1,
      'sort_by' => 'first_name',
      'sort_order' => 'asc'
    ));

    expect($response->meta['count'])->equals(2);
    expect($response->data)->count(1);
    expect($response->data[0]['email'])->equals(
      $this->subscriber_2->email
    );

    // get 1st page (limit items per page to 1)
    $response = $router->listing(array(
      'limit' => 1,
      'offset' => 1,
      'sort_by' => 'first_name',
      'sort_order' => 'asc'
    ));

    expect($response->meta['count'])->equals(2);
    expect($response->data)->count(1);
    expect($response->data[0]['email'])->equals(
      $this->subscriber_1->email
    );
  }

  function testItCanBulkDeleteSelectionOfSubscribers() {
    $deletable_subscriber =  Subscriber::createOrUpdate(array(
      'email' => 'to.be.removed@mailpoet.com'
    ));

    $selection_ids = array(
      $this->subscriber_1->id,
      $deletable_subscriber->id
    );

    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'listing' => array(
        'selection' => $selection_ids
      ),
      'action' => 'delete'
    ));
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
    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'action' => 'trash',
      'listing' => array('group' => 'all')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);
  }

  function testItCannotRunAnInvalidBulkAction() {
    $router = new Subscribers();
    $response = $router->bulkAction(array(
      'action' => 'invalidAction',
      'listing' => array()
    ));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->contains('has no method');
  }

  function testItFailsWithEmailFilled() {
    $router = new Subscribers();
    $response = $router->subscribe(array(
      'form_id' => $this->form->id,
      'email' => 'toto@mailpoet.com'
      // no form ID specified
    ));

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please leave the first field empty.');
  }

  function testItCannotSubscribeWithoutFormID() {
    $router = new Subscribers();
    $response = $router->subscribe(array(
      'form_field_ZW1haWw' => 'toto@mailpoet.com'
      // no form ID specified
    ));

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a valid form ID.');
  }

  function testItCannotSubscribeWithoutSegmentsIfTheyAreSelectedByUser() {
    $router = new Subscribers();
    $response = $router->subscribe(array(
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id
      // no segments specified
    ));

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please select a list.');
  }

  function testItCanSubscribe() {
    $router = new Subscribers();
    $response = $router->subscribe(array(
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => array($this->segment_1->id, $this->segment_2->id)
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
  }

  function testItCannotSubscribeWithoutCaptchaWhenEnabled() {
    Setting::setValue('re_captcha', array('enabled' => true));
    $router = new Subscribers();
    $response = $router->subscribe(array(
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => array($this->segment_1->id, $this->segment_2->id)
    ));
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please check the captcha.');
    Setting::setValue('re_captcha', array());
  }

  function testItCanSubscribeWithoutSegmentsIfTheyAreSelectedByAdmin() {
    $form = $this->form->asArray();
    $form['settings']['segments_selected_by'] = 'admin';
    $this->form->settings = $form['settings'];
    $this->form->save();

    $router = new Subscribers();
    $response = $router->subscribe(array(
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id
      // no segments specified
    ));

    expect($response->status)->equals(APIResponse::STATUS_OK);
    $subscriber = Subscriber::findOne($response->data['id']);
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

    $router = new Subscribers();
    $response = $router->subscribe(array(
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => array($this->segment_1->id, $this->segment_2->id)
    ));

    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please select a list.');
  }

  function testItCanFilterOutNonFormFieldsWhenSubscribing() {
    $router = new Subscribers();
    $response = $router->subscribe(array(
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => array($this->segment_1->id, $this->segment_2->id),
      // exists in table and in the form
      'first_name' => 'aaa',
      // exists in table, but not in the form
      'last_name' => 'bbb',
      // doesn't exist
      'bogus' => 'hahaha'
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['first_name'])->equals('aaa');
    expect($response->data['last_name'])->isEmpty();
    expect(isset($response->data['bogus']))->false();
  }

  function testItCannotMassSubscribe() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $router = new Subscribers();
    $response = $router->subscribe(array(
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => array($this->segment_1->id, $this->segment_2->id)
    ));

    try {
      $response = $router->subscribe(array(
        $this->obfuscatedEmail => 'tata@mailpoet.com',
        'form_id' => $this->form->id,
        $this->obfuscatedSegments => array($this->segment_1->id, $this->segment_2->id)
      ));
      $this->fail('It should not be possible to subscribe a second time so soon');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('You need to wait 60 seconds before subscribing again.');
    }
  }

  function testItCannotMassResubscribe() {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $router = new Subscribers();
    $response = $router->subscribe(array(
      $this->obfuscatedEmail => 'toto@mailpoet.com',
      'form_id' => $this->form->id,
      $this->obfuscatedSegments => array($this->segment_1->id, $this->segment_2->id)
    ));

    // Try to resubscribe an existing subscriber that was updated just now
    $subscriber = Subscriber::findOne($response->data['id']);
    $subscriber->created_at = Carbon::yesterday();
    $subscriber->updated_at = Carbon::now();
    $subscriber->save();

    try {
      $response = $router->subscribe(array(
        $this->obfuscatedEmail => $subscriber->email,
        'form_id' => $this->form->id,
        $this->obfuscatedSegments => array($this->segment_1->id, $this->segment_2->id)
      ));
      $this->fail('It should not be possible to resubscribe a second time so soon');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('You need to wait 60 seconds before subscribing again.');
    }
  }

  function testItSchedulesWelcomeEmailNotificationWhenSubscriberIsAdded() {
    $this->_createWelcomeNewsletter();
    $subscriber_data = array(
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => array(
        $this->segment_1->id
      )
    );

    $router = new Subscribers();
    $router->save($subscriber_data);
    expect(SendingQueue::findMany())->count(1);
  }

  function testItSchedulesWelcomeEmailNotificationWhenExistedSubscriberIsUpdated() {
    $this->_createWelcomeNewsletter();
    $subscriber_data = array(
      'email' => 'raul.doe@mailpoet.com',
      'first_name' => 'Raul',
      'last_name' => 'Doe',
      'segments' => array(
        $this->segment_2->id
      )
    );

    // welcome notification is created only for segment #1
    $router = new Subscribers();
    $router->save($subscriber_data);
    expect(SendingQueue::findMany())->isEmpty();

    $subscriber_data['segments'] = array($this->segment_1->id);
    $router->save($subscriber_data);
    expect(SendingQueue::findMany())->count(1);
  }

  private function _createWelcomeNewsletter() {
    $welcome_newsletter = Newsletter::create();
    $welcome_newsletter->type = Newsletter::TYPE_WELCOME;
    $welcome_newsletter->status = Newsletter::STATUS_ACTIVE;
    $welcome_newsletter->save();
    expect($welcome_newsletter->getErrors())->false();

    $welcome_newsletter_options = array(
      'event' => 'segment',
      'segment' => $this->segment_1->id,
      'schedule' => '* * * * *'
    );

    foreach($welcome_newsletter_options as $option => $value) {
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
    \ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberIP::$_table);
  }
}
