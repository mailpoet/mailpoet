<?php

use Codeception\Util\Fixtures;
use Codeception\Util\Stub;
use MailPoet\API\API;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;

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
    expect($result['id'])->equals($subscriber->id);
    expect($result['subscriptions'][0]['id'])->equals($segment->id);
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
    expect($result['id'])->equals($subscriber->id);
    expect($result['subscriptions'])->notEmpty();
    expect($result['subscriptions'][0]['id'])->equals($segment->id);
  }

  function testItSubscribesSubscriberWithEmailIdentifier() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $result = API::MP(self::VERSION)->subscribeToList($subscriber->email, $segment->id);
    expect($result['id'])->equals($subscriber->id);
    expect($result['subscriptions'])->notEmpty();
    expect($result['subscriptions'][0]['id'])->equals($segment->id);
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

  function testItSchedulesWelcomeNotificationByDefaultAfterAddingSubscriber() {
    $API = Stub::makeEmptyExcept(
      new MailPoet\API\MP\v1\API(),
      'addSubscriber',
      array(
        '_scheduleWelcomeNotification' => Stub::once()
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED
    );
    $segments = array(1);
    $API->addSubscriber($subscriber, $segments);
  }

  function testItDoesNotScheduleWelcomeNotificationAfterAddingSubscriberIfStatusIsNotSubscribed() {
    $API = Stub::makeEmptyExcept(
      new MailPoet\API\MP\v1\API(),
      'addSubscriber',
      array(
        '_scheduleWelcomeNotification' => Stub::never()
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $segments = array(1);
    $API->addSubscriber($subscriber, $segments);
  }

  function testItDoesNotScheduleWelcomeNotificationAfterAddingSubscriberWhenDisabledByOption() {
    $API = Stub::makeEmptyExcept(
      new MailPoet\API\MP\v1\API(),
      'addSubscriber',
      array(
        '_scheduleWelcomeNotification' => Stub::never()
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $segments = array(1);
    $options = array('schedule_welcome_notification' => false);
    $API->addSubscriber($subscriber, $segments, $options);
  }

  function testByDefaultItSendsConfirmationEmailAfterAddingSubscriber() {
    $API = Stub::makeEmptyExcept(
      new MailPoet\API\MP\v1\API(),
      'addSubscriber',
      array(
        '_sendConfirmationEmail' => Stub::once()
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $segments = array(1);
    $API->addSubscriber($subscriber, $segments);
  }

  function testItDoesNotSendConfirmationEmailAfterAddingSubscriberWhenOptionIsSet() {
    $API = Stub::makeEmptyExcept(
      new MailPoet\API\MP\v1\API(),
      'addSubscriber',
      array(
        '_sendConfirmationEmail' => Stub::never()
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $segments = array(1);
    $options = array('send_confirmation_email' => false);
    $API->addSubscriber($subscriber, $segments, $options);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}