<?php

use MailPoet\Models\Subscriber;
use MailPoet\Models\Segment;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Import\Import;
use MailPoet\Util\Helpers;

class ImportTest extends MailPoetTest {
  function _before() {
    $this->data = array(
      'subscribers' => array(
        array(
          'Adam',
          'Smith',
          'adam@smith.com',
          'France'
        ),
        array(
          'Mary',
          'Jane',
          'mary@jane.com',
          'Brazil'
        )
      ),
      'columns' => array(
        'first_name' => 0,
        'last_name' => 1,
        'email' => 2,
        777 => 3
      ),
      'segments' => array(
        195
      ),
      'timestamp' => time(),
      'updateSubscribers' => true
    );
    $this->subscriber_fields = array(
      'first_name',
      'last_name',
      'email'
    );
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    $this->subscriber_custom_fields = array(777);
    $this->import = new Import($this->data);
    $this->subscribers_data = $this->import->transformSubscribersData(
      $this->data['subscribers'],
      $this->data['columns']
    );
  }

  function testItCanConstruct() {
    expect(is_array($this->import->subscribers_data))->true();
    expect($this->import->segments)->equals($this->data['segments']);
    expect(is_array($this->import->subscriber_fields))->true();
    expect(is_array($this->import->subscriber_custom_fields))->true();
    expect($this->import->subscribers_count)->equals(2);
    expect($this->import->created_at)->notEmpty();
    expect($this->import->updated_at)->notEmpty();
  }

  function testItCanTransformSubscribers() {
    expect($this->import->subscribers_data['first_name'][0])
      ->equals($this->data['subscribers'][0][0]);
    expect($this->import->subscribers_data['last_name'][0])
      ->equals($this->data['subscribers'][0][1]);
    expect($this->import->subscribers_data['email'][0])
      ->equals($this->data['subscribers'][0][2]);
    expect($this->import->subscribers_data['777'][0])
      ->equals($this->data['subscribers'][0][3]);
  }

  function testItCanFilterExistingAndNewSubscribers() {
    $subscribers_data = $this->subscribers_data;
    $subscriber = Subscriber::create();
    $subscriber->hydrate(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com',
        'wp_user_id' => 1
      ));
    $subscriber->save();
    list($existing, $wp_users, $new) = $this->import->filterExistingAndNewSubscribers(
      $subscribers_data
    );
    expect($existing['email'][0])->equals($subscribers_data['email'][0]);
    expect($wp_users[0])->equals($subscriber->wp_user_id);
    expect($new['email'][0])->equals($subscribers_data['email'][1]);
  }

  function testItCanExtendSubscribersAndFields() {
    expect(in_array('created_at', $this->import->subscriber_fields))->false();
    expect(isset($this->import->subscriber_fields['created_at']))->false();
    list($subscribers, $fields) = $this->import->extendSubscribersAndFields(
      $this->import->subscribers_data,
      $this->import->subscriber_fields
    );
    expect(in_array('created_at', $fields))->true();
    expect(isset($this->import->subscriber_fields['created_at']))->false();
    expect(count($subscribers['created_at']))
      ->equals($this->import->subscribers_count);
  }

  function testItCanGetSubscriberFields() {
    $data = array(
      'one',
      'two',
      39
    );
    $fields = $this->import->getSubscriberFields($data);
    expect($fields)->equals(
      array(
        'one',
        'two'
      ));
  }

  function testItCanGetCustomSubscriberFields() {
    $data = array(
      'one',
      'two',
      39
    );
    $fields = $this->import->getCustomSubscriberFields($data);
    expect($fields)->equals(array(39));
  }

  function testItCanFilterSubscriberStatus() {
    $subscribers_data = $this->subscribers_data;
    $subscriber_fields = $this->subscriber_fields;
    list($subscribers_data, $subsciber_fields) =
      $this->import->filterSubscriberStatus($subscribers_data, $subscriber_fields);
    // subscribers' status was set to "subscribed" & status column was added
    // to subscribers fields
    expect(array_pop($subsciber_fields))->equals('status');
    expect($subscribers_data['status'][0])->equals('subscribed');
    expect(count($subscribers_data['status']))->equals(2);
    $subscriber_fields[] = 'status';
    $subscribers_data = array(
      'status' => array(
        #subscribed
        'subscribed',
        'confirmed',
        1,
        '1',
        'true',
        #unconfirmed
        'unconfirmed',
        0,
        "0",
        #unsubscribed
        'unsubscribed',
        -1,
        '-1',
        'false'
      ),
    );
    list($subscribers_data, $subsciber_fields) =
      $this->import->filterSubscriberStatus($subscribers_data, $subscriber_fields);
    expect($subscribers_data)->equals(
      array(
        'status' => array(
          'subscribed',
          'subscribed',
          'subscribed',
          'subscribed',
          'subscribed',
          'unconfirmed',
          'unconfirmed',
          'unconfirmed',
          'unsubscribed',
          'unsubscribed',
          'unsubscribed',
          'unsubscribed'
        )
      )
    );
  }

  function testItCanAddOrUpdateSubscribers() {
    $subscribers_data = $this->subscribers_data;
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $this->subscriber_fields,
      false
    );
    $subscribers = Subscriber::findArray();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]['email'])
      ->equals($subscribers_data['email'][0]);
    $data['first_name'][1] = 'MaryJane';
    $this->import->createOrUpdateSubscribers(
      'update',
      $subscribers_data,
      $this->subscriber_fields,
      false
    );
    $subscribers = Subscriber::findArray();
    expect($subscribers[1]['first_name'])
      ->equals($subscribers_data['first_name'][1]);
  }

  function testItCanDeleteTrashedSubscribers() {
    $subscribers_data = $this->subscribers_data;
    $subscriber_fields = $this->subscriber_fields;
    $subscribers_data['deleted_at'] = array(
      null,
      date('Y-m-d H:i:s')
    );
    $subscriber_fields[] = 'deleted_at';
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $subscriber_fields,
      false
    );
    $db_subscribers = Helpers::arrayColumn(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    expect(count($db_subscribers))->equals(2);
    $this->import->addSubscribersToSegments(
      $db_subscribers,
      array($this->segment_1->id, $this->segment_2->id)
    );
    $subscribers_segments = SubscriberSegment::findArray();
    expect(count($subscribers_segments))->equals(4);
    $this->import->deleteExistingTrashedSubscribers(
      $subscribers_data
    );
    $subscribers_segments = SubscriberSegment::findArray();
    $db_subscribers = Subscriber::findArray();
    expect(count($subscribers_segments))->equals(2);
    expect(count($db_subscribers))->equals(1);
  }

  function testItCanCreateOrUpdateCustomFields() {
    $subscribers_data = $this->subscribers_data;
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $this->subscriber_fields,
      false
    );
    $db_subscribers = Helpers::arrayColumn(
      Subscriber::selectMany(
        array(
          'id',
          'email'
        ))
        ->findArray(),
      'email', 'id'
    );
    $this->import->createOrUpdateCustomFields(
      'create',
      $db_subscribers,
      $subscribers_data,
      $this->subscriber_custom_fields
    );
    $subscriber_custom_fields = SubscriberCustomField::findArray();
    expect(count($subscriber_custom_fields))->equals(2);
    expect($subscriber_custom_fields[0]['value'])
      ->equals($subscribers_data[777][0]);
    $subscribers_data[777][1] = 'Rio';
    $this->import->createOrUpdateCustomFields(
      'update',
      $db_subscribers,
      $subscribers_data,
      $this->subscriber_custom_fields
    );
    $subscriber_custom_fields = SubscriberCustomField::findArray();
    expect($subscriber_custom_fields[1]['value'])
      ->equals($subscribers_data[777][1]);
  }


  function testItCanAddSubscribersToSegments() {
    $subscribers_data = $this->subscribers_data;
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $this->subscriber_fields,
      false
    );
    $db_subscribers = Helpers::arrayColumn(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    $this->import->addSubscribersToSegments(
      $db_subscribers,
      array($this->segment_1->id, $this->segment_2->id)
    );
    $subscribers_segments = SubscriberSegment::findArray();
    // 2 subscribers * 2 segments
    expect(count($subscribers_segments))->equals(4);
  }

  function testItCanDeleteExistingTrashedSubscribers() {
    $subscribers_data = $this->subscribers_data;
    $subscriber_fields = $this->subscriber_fields;
    $subscriber_fields[] = 'deleted_at';
    $subscribers_data['deleted_at'] = array(
      null,
      date('Y-m-d H:i:s')
    );
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribers_data,
      $subscriber_fields,
      false
    );
  }

  function testItCanUpdateSubscribers() {
    $result = $this->import->process();
    expect($result['data']['updated'])->equals(0);
    $result = $this->import->process();
    expect($result['data']['updated'])->equals(2);
    $this->import->update_subscribers = false;
    $result = $this->import->process();
    expect($result['data']['updated'])->equals(0);
  }

  function testItCanProcess() {
    $result = $this->import->process();
    expect($result['data']['created'])->equals(2);
    Subscriber::where('email', 'mary@jane.com')
      ->findOne()
      ->delete();
    $timestamp = time() + 1;
    $this->import->created_at = date('Y-m-d H:i:s', $timestamp);
    $this->import->updated_at = date('Y-m-d H:i:s', $timestamp + 1);
    $result = $this->import->process();
    expect($result['data']['created'])->equals(1);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}