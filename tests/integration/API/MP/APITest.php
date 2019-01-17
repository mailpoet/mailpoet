<?php

namespace MailPoet\Test\API\MP;

use AspectMock\Test as Mock;
use Codeception\Util\Fixtures;
use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Models\CustomField;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Segment;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Tasks\Sending;

class APITest extends \MailPoetTest {
  const VERSION = 'v1';

  private function getApi() {
    return new \MailPoet\API\MP\v1\API(
      Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
      Stub::makeEmpty(ConfirmationEmailMailer::class, ['sendConfirmationEmail']),
      Stub::makeEmptyExcept(RequiredCustomFieldValidator::class, 'validate')
    );
  }

  function testItReturnsSubscriberFields() {
    $custom_field = CustomField::create();
    $custom_field->name = 'test custom field';
    $custom_field->type = CustomField::TYPE_TEXT;
    $custom_field->save();

    $response = $this->getApi()->getSubscriberFields();

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

  function testItDoesNotSubscribeMissingSubscriberToLists() {
    try {
      $this->getApi()->subscribeToLists(false, array(1,2,3));
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  function testItDoesNotSubscribeSubscriberToMissingLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    // multiple lists error message
    try {
      $this->getApi()->subscribeToLists($subscriber->id, array(1,2,3));
      $this->fail('Missing segments exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('These lists do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->subscribeToLists($subscriber->id, array(1));
      $this->fail('Missing segments exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('This list does not exist.');
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
      $this->getApi()->subscribeToLists($subscriber->id, array($segment->id));
      $this->fail('WP Users segment exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WordPress Users list with ID {$segment->id}.");
    }
  }

  function testItDoesNotSubscribeSubscriberToWooCommerceCustomersList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_WC_USERS
      )
    );
    try {
      $this->getApi()->subscribeToLists($subscriber->id, array($segment->id));
      $this->fail('WooCommerce Customers segment exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WooCommerce Customers list with ID {$segment->id}.");
    }
  }

  function testItDoesNotSubscribeSubscriberToListsWhenOneOrMoreListsAreMissing() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    // multiple lists error message
    try {
      $this->getApi()->subscribeToLists($subscriber->id, array($segment->id, 90, 100));
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Lists with IDs 90, 100 do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->subscribeToLists($subscriber->id, array($segment->id, 90));
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('List with ID 90 does not exist.');
    }
  }

  function testItUsesMultipleListsSubscribeMethodWhenSubscribingToSingleList() {
    // subscribing to single list = converting list ID to an array and using
    // multiple lists subscription method
    $API = Stub::make($this->getApi(), array(
      'subscribeToLists' => function() {
        return func_get_args();
      }
    ));
    expect($API->subscribeToList(1, 2))->equals(
      array(
        1,
        array(
          2
        ),
        array()
      )
    );
  }

  function testItSubscribesSubscriberToMultipleLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );

    // test if segments are specified
    try {
      $this->getApi()->subscribeToLists($subscriber->id, array());
      $this->fail('Segments are required exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('At least one segment ID is required.');
    }

    $result = $this->getApi()->subscribeToLists($subscriber->id, array($segment->id));
    expect($result['id'])->equals($subscriber->id);
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->id);
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
    $result = $this->getApi()->subscribeToList($subscriber->email, $segment->id);
    expect($result['id'])->equals($subscriber->id);
    expect($result['subscriptions'])->notEmpty();
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->id);
  }

  function testItSchedulesWelcomeNotificationByDefaultAfterSubscriberSubscriberToLists() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'subscribeToLists',
      array(
        '_scheduleWelcomeNotification' => Expected::once()
      ), $this);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $API->subscribeToLists($subscriber->id, array($segment->id));
  }

  function testItDoesNotScheduleWelcomeNotificationAfterSubscribingSubscriberToListsIfStatusIsNotSubscribed() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'subscribeToLists',
      array(
        '_scheduleWelcomeNotification' => Expected::never()
      ), $this);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $API->subscribeToLists($subscriber->id, array($segment->id));
  }

  function testItDoesNotScheduleWelcomeNotificationAfterSubscribingSubscriberToListsWhenDisabledByOption() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'subscribeToLists',
      array(
        '_scheduleWelcomeNotification' => Expected::never()
      ), $this);
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $options = array('schedule_welcome_email' => false);
    $API->subscribeToLists($subscriber->id, array($segment->id), $options);
  }

  function testItGetsSegments() {
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $result = $this->getApi()->getLists();
    expect($result)->count(1);
    expect($result[0]['id'])->equals($segment->id);
  }

  function testItExcludesWPUsersAndWooCommerceCustomersSegmentsWhenGettingSegments() {
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
    $wc_segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_WC_USERS
      )
    );
    $result = $this->getApi()->getLists();
    expect($result)->count(1);
    expect($result[0]['id'])->equals($default_segment->id);
  }

  function testItRequiresEmailAddressToAddSubscriber() {
    try {
      $this->getApi()->addSubscriber(array());
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
      $this->getApi()->addSubscriber(array('email' => $subscriber->email));
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
      $this->getApi()->addSubscriber($subscriber);
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

    $result = $this->getApi()->addSubscriber($subscriber);
    expect($result['id'])->greaterThan(0);
    expect($result['email'])->equals($subscriber['email']);
    expect($result['cf_' . $custom_field->id])->equals('test');
    expect($result['source'])->equals('api');
  }

  function testItChecksForMandatoryCustomFields() {
    CustomField::createOrUpdate([
      'name' => 'custom field',
      'type' => 'text',
      'params' => ['required' => '1']
    ]);

    $subscriber = array(
      'email' => 'test@example.com',
    );

    $this->setExpectedException('Exception');
    $this->getApi()->addSubscriber($subscriber);
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

    $result = $this->getApi()->addSubscriber($subscriber, array($segment->id));
    expect($result['id'])->greaterThan(0);
    expect($result['email'])->equals($subscriber['email']);
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->id);
  }

  function testItSchedulesWelcomeNotificationByDefaultAfterAddingSubscriber() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      array(
        'new_subscribe_notification_mailer'=> Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
        'required_custom_field_validator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
        '_scheduleWelcomeNotification' => Expected::once(),
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED
    );
    $segments = [1];
    $API->addSubscriber($subscriber, $segments);
  }

  function testItThrowsIfWelcomeEmailFails() {
    $task = ScheduledTask::create();
    $task->type = 'sending';
    $task->setError("Big Error");
    $sendingStub = Sending::create($task, SendingQueue::create());
    Mock::double('MailPoet\Newsletter\Scheduler\Scheduler', array(
      'scheduleSubscriberWelcomeNotification' => array($sendingStub),
    ));
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $API = $this->getApi();
    $subscriber = array(
      'email' => 'test@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED
    );
    $segments = array($segment->id());
    $this->setExpectedException('\Exception');
    $API->addSubscriber($subscriber, $segments, array('schedule_welcome_email' => true, 'send_confirmation_email' => false));
  }

  function testItDoesNotScheduleWelcomeNotificationAfterAddingSubscriberIfStatusIsNotSubscribed() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      array(
        '_scheduleWelcomeNotification' => Expected::never(),
        'new_subscribe_notification_mailer'=> Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
        'required_custom_field_validator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $segments = array(1);
    $API->addSubscriber($subscriber, $segments);
  }

  function testItDoesNotScheduleWelcomeNotificationAfterAddingSubscriberWhenDisabledByOption() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      array(
        '_scheduleWelcomeNotification' => Expected::never(),
        'new_subscribe_notification_mailer'=> Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send']),
        'required_custom_field_validator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate'])
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED
    );
    $segments = array(1);
    $options = array('schedule_welcome_email' => false);
    $API->addSubscriber($subscriber, $segments, $options);
  }

  function testByDefaultItSendsConfirmationEmailAfterAddingSubscriber() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      array(
        '_sendConfirmationEmail' => Expected::once(),
        'required_custom_field_validator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
        'new_subscribe_notification_mailer'=> Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send'])
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $segments = array(1);
    $API->addSubscriber($subscriber, $segments);
  }

  function testItThrowsWhenConfirmationEmailFailsToSend() {
    $confirmation_mailer = $this->createMock(ConfirmationEmailMailer::class);
    $confirmation_mailer->expects($this->once())
      ->method('sendConfirmationEmail')
      ->willReturnCallback(function (Subscriber $subscriber) {
        $subscriber->setError('Big Error');
        return false;
      });

    $API = Stub::copy($this->getApi(), [
      'confirmation_email_mailer' => $confirmation_mailer,
    ]);
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $this->setExpectedException('\Exception', 'Subscriber added, but confirmation email failed to send: big error');
    $API->addSubscriber($subscriber, array($segment->id), array('send_confirmation_email' => true));
  }

  function testItDoesNotSendConfirmationEmailAfterAddingSubscriberWhenOptionIsSet() {
    $API = Stub::makeEmptyExcept(
      \MailPoet\API\MP\v1\API::class,
      'addSubscriber',
      array(
        '__sendConfirmationEmail' => Expected::never(),
        'required_custom_field_validator' => Stub::makeEmpty(RequiredCustomFieldValidator::class, ['validate']),
        'new_subscribe_notification_mailer'=> Stub::makeEmpty(NewSubscriberNotificationMailer::class, ['send'])
      ), $this);
    $subscriber = array(
      'email' => 'test@example.com'
    );
    $segments = array(1);
    $options = array('send_confirmation_email' => false);
    $API->addSubscriber($subscriber, $segments, $options);
  }

  function testItRequiresNameToAddList() {
    try {
      $this->getApi()->addList(array());
      $this->fail('List name required exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('List name is required.');
    }
  }

  function testItDoesNotAddExistingList() {
    $segment = Segment::create();
    $segment->name = 'Test segment';
    $segment->save();
    try {
      $this->getApi()->addList(array('name' => $segment->name));
      $this->fail('List exists exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('This list already exists.');
    }
  }

  function testItAddsList() {
    $segment = array(
      'name' => 'Test segment'
    );

    $result = $this->getApi()->addList($segment);
    expect($result['id'])->greaterThan(0);
    expect($result['name'])->equals($segment['name']);
  }

  function testItDoesNotUnsubscribeMissingSubscriberFromLists() {
    try {
      $this->getApi()->unsubscribeFromLists(false, array(1,2,3));
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  function testItDoesNotUnsubscribeSubscriberFromMissingLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    // multiple lists error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, array(1,2,3));
      $this->fail('Missing segments exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('These lists do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, array(1));
      $this->fail('Missing segments exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('This list does not exist.');
    }
  }

  function testItDoesNotUnsubscribeSubscriberFromListsWhenOneOrMoreListsAreMissing() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    // multiple lists error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, array($segment->id, 90, 100));
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('Lists with IDs 90, 100 do not exist.');
    }
    // single list error message
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, array($segment->id, 90));
      $this->fail('Missing segments with IDs exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('List with ID 90 does not exist.');
    }
  }

  function testItDoesNotUnsubscribeSubscriberFromWPUsersList() {
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
      $this->getApi()->unsubscribeFromLists($subscriber->id, array($segment->id));
      $this->fail('WP Users segment exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WordPress Users list with ID {$segment->id}.");
    }
  }

  function testItDoesNotUnsubscribeSubscriberFromWooCommerceCustomersList() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_WC_USERS
      )
    );
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, array($segment->id));
      $this->fail('WooCommerce Customers segment exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals("Can't subscribe to a WooCommerce Customers list with ID {$segment->id}.");
    }
  }

  function testItUsesMultipleListsUnsubscribeMethodWhenUnsubscribingFromSingleList() {
    // unsubscribing from single list = converting list ID to an array and using
    // multiple lists unsubscribe method
    $API = Stub::make(\MailPoet\API\MP\v1\API::class, array(
      'unsubscribeFromLists' => function() {
        return func_get_args();
      }
    ));
    expect($API->unsubscribeFromList(1, 2))
      ->equals(array(
        1,
        array(
          2
        )
      )
    );
  }

  function testItUnsubscribesSubscriberFromMultipleLists() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );

    // test if segments are specified
    try {
      $this->getApi()->unsubscribeFromLists($subscriber->id, array());
      $this->fail('Segments are required exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('At least one segment ID is required.');
    }

    $result = $this->getApi()->subscribeToLists($subscriber->id, array($segment->id));
    expect($result['subscriptions'][0]['status'])->equals(Subscriber::STATUS_SUBSCRIBED);
    $result = $this->getApi()->unsubscribeFromLists($subscriber->id, array($segment->id));
    expect($result['subscriptions'][0]['status'])->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  function testItGetsSubscriber() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $segment = Segment::createOrUpdate(
      array(
        'name' => 'Default',
        'type' => Segment::TYPE_DEFAULT
      )
    );
    $this->getApi()->subscribeToList($subscriber->id, $segment->id);

    // successful response
    $result = $this->getApi()->getSubscriber($subscriber->email);
    expect($result['email'])->equals($subscriber->email);
    expect($result['subscriptions'][0]['segment_id'])->equals($segment->id);

    // error response
    try {
      $this->getApi()->getSubscriber('some_fake_email');
      $this->fail('Subscriber does not exist exception should have been thrown.');
    } catch(\Exception $e) {
      expect($e->getMessage())->equals('This subscriber does not exist.');
    }
  }

  function _after() {
    Mock::clean();
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
  }
}
