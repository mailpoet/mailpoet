<?php

use Codeception\Util\Fixtures;
use MailPoet\API\API;
use MailPoet\Models\CustomField;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterOption;
use MailPoet\Models\NewsletterOptionField;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;

class MPAPITest extends MailPoetTest {
  const VERSION = 'v1';

  function testItReturnsSubscriberFields() {
    $custom_field = CustomField::create();
    $custom_field->name = 'test custom field';
    $custom_field->type = CustomField::TYPE_TEXT;
    $custom_field->save();

    $response = API::MP(self::VERSION)->getSubscriberFields();

    expect($response)->equals(
      array(
        array(
          'id' => 'email',
          'name' => __('Email', 'mailpoet')
        ),
        array(
          'id' => 'first_name',
          'name' => __('First name', 'mailpoet')
        ),
        array(
          'id' => 'last_name',
          'name' => __('Last name', 'mailpoet')
        ),
        array(
          'id' => 'cf_' . $custom_field->id,
          'name' => $custom_field->name
        )
      )
    );
  }

  function testItDoesNotSubscribeMissingSusbcriberToLists() {
    try {
      API::MP(self::VERSION)->subscribeToLists(false, array(1,2,3));
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  function testItDoesNotSubscribeSubscriberToMissingLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    try {
      API::MP(self::VERSION)->subscribeToLists($subscriber->id, array(1,2,3));
      $this->fail('Missing segments exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('These lists do not exist.');
    }
  }

  function testItDoesNotSubscribeSubscriberToWPUsersList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_WP_USERS
      )
    );
    try {
      API::MP(self::VERSION)->subscribeToLists($subscriber->id, array($segment->id));
      $this->fail('WP Users segment exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WordPress Users list with ID {$segment->id}.");
    }
  }

  function testItDoesNotSubscribeSubscriberToListsWhenOneOrMostListsAreMissing() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    try {
      API::MP(self::VERSION)->subscribeToLists($subscriber->id, array($segment->id, 90, 100));
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Lists with ID 90, 100 do not exist.');
    }
  }

  function testItSubscriberSubscriberToMultupleLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $result = API::MP(self::VERSION)->subscribeToLists($subscriber->id, array($segment->id));
    expect($result)->true();
    $subscriber_segment = SubscriberSegment::where('subscriber_id', $subscriber->id)
      ->where('segment_id', $segment->id)
      ->findOne();
    expect($subscriber_segment)->notEmpty();
  }

  function testItSubscribesSubscriberToSingleList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $result = API::MP(self::VERSION)->subscribeToList($subscriber->id, $segment->id);
    expect($result)->true();
    $subscriber_segment = SubscriberSegment::where('subscriber_id', $subscriber->id)
      ->where('segment_id', $segment->id)
      ->findOne();
    expect($subscriber_segment)->notEmpty();
  }

  function testItGetsSegments() {
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $result = API::MP(self::VERSION)->getLists();
    expect($result)->count(1);
    expect($result[0]['id'])->equals($segment->id);
  }

  function testItExcludesWPUsersSegmentWhenGettingSegments() {
    $default_segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $wp_segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_WP_USERS
      )
    );
    $result = API::MP(self::VERSION)->getLists();
    expect($result)->count(1);
    expect($result[0]['id'])->equals($default_segment->id);
  }

  function testItRequiresEmailAddressToAddSubscriber() {
    try {
      API::MP(self::VERSION)->addSubscriber(array());
      $this->fail('Subscriber email address required exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Subscriber email address is required.');
    }
  }

  function testItDoesNotAddExistingSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    try {
      API::MP(self::VERSION)->addSubscriber(array('email' => $subscriber->email));
      $this->fail('Subscriber exists exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('This subscriber already exists.');
    }
  }

  function testItThrowsExceptionWhenSubscriberCannotBeAdded() {
    $subscriber = array(
      'email' => 'test' // invalid email
    );
    try {
      API::MP(self::VERSION)->addSubscriber($subscriber);
      $this->fail('Failed to add subscriber exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->contains('Failed to add subscriber:');
      // error message (converted to lowercase) returned by the model
      expect($e->getMessage())->contains('your email address is invalid!');
    }
  }

  function testItAddsSubscriber() {
    $custom_field = CustomField::create();
    $custom_field->name = 'test custom field';
    $custom_field->type = CustomField::TYPE_TEXT;
    $custom_field->save();

    $subscriber = array(
    'email' => 'test@example.com',
    'cf_' . $custom_field->id => 'test'
    );

    $result = API::MP(self::VERSION)->addSubscriber($subscriber);
    expect($result['id'])->greaterThan(0);
    expect($result['email'])->equals($subscriber['email']);
    expect($result['cf_' . $custom_field->id])->equals('test');
  }

  function testItSubscribesToSegmentsWhenAddingSubscriber() {
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $subscriber = array(
      'email' => 'test@example.com'
    );

    $result = API::MP(self::VERSION)->addSubscriber($subscriber, array($segment->id));
    expect($result['id'])->greaterThan(0);
    expect($result['email'])->equals($subscriber['email']);
    expect($result['subscriptions'][0]['id'])->equals($segment->id);
  }

  function testItSendsConfirmationEmailByDefaultAfterAddingSubscriber() {
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $subscriber = array(
      'email' => 'test@example.com'
    );
    // create newsletter with associated options to send welcome email one day after subscription to segment
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    $newsletter_options = array(
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1
    );
    foreach($newsletter_options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = $newsletter->type;
      $newsletter_option_field->save();
      expect($newsletter_option_field->getErrors())->false();

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter->id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }

    expect(SendingQueue::findArray())->count(0);
    API::MP(self::VERSION)->addSubscriber($subscriber, array($segment->id));
    expect(SendingQueue::findArray())->count(1);
  }

  function testItDoesNotSendConfirmationEmailAfterAddingSubscriberWhenOptionIsSet() {
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $options = array('schedule_welcome_email' => false);

    // create newsletter with associated options to send welcome email one day after subscription to segment
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_WELCOME;
    $newsletter->status = Newsletter::STATUS_ACTIVE;
    $newsletter->save();
    $newsletter_options = array(
      'event' => 'segment',
      'segment' => $segment->id,
      'afterTimeType' => 'days',
      'afterTimeNumber' => 1
    );
    foreach($newsletter_options as $option => $value) {
      $newsletter_option_field = NewsletterOptionField::create();
      $newsletter_option_field->name = $option;
      $newsletter_option_field->newsletter_type = $newsletter->type;
      $newsletter_option_field->save();
      expect($newsletter_option_field->getErrors())->false();

      $newsletter_option = NewsletterOption::create();
      $newsletter_option->option_field_id = $newsletter_option_field->id;
      $newsletter_option->newsletter_id = $newsletter->id;
      $newsletter_option->value = $value;
      $newsletter_option->save();
      expect($newsletter_option->getErrors())->false();
    }

    expect(SendingQueue::findArray())->count(0);
    API::MP(self::VERSION)->addSubscriber($subscriber, array($segment->id), $options);
    expect(SendingQueue::findArray())->count(0);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOption::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterOptionField::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}