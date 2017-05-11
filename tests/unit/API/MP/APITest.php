<?php

use Codeception\Util\Fixtures;
use MailPoet\API\API;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
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
      expect($e->getMessage())->equals('These lists do not exists.');
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
      API::MP(self::VERSION)->subscribeToLists($subscriber->id, array(1));
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

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . CustomField::$_table);
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
  }
}