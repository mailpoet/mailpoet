<?php

use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Import\Import;
use MailPoet\Util\Helpers;

class ImportCest {
  function __construct() {
    $this->JSON_data = json_decode(file_get_contents(dirname(__FILE__) . '/ImportTestData.json'), true);
    $this->subscribers_data = array(
      'first_name' => array(
        'Adam',
        'Mary'
      ),
      'last_name' => array(
        'Smith',
        'Jane'
      ),
      'email' => array(
        'adam@smith.com',
        'mary@jane.com'
      ),
      777 => array(
        'France',
        'Brazil'
      )
    );
    $this->subscriber_fields = array(
      'first_name',
      'last_name',
      'email'
    );
    $this->segments = range(0, 1);
    $this->subscriber_custom_fields = array(777);
    $this->import = new Import($this->JSON_data);
  }

  function itCanConstruct() {
    expect($this->import->subscribers_data)->equals($this->JSON_data['subscribers']);
    expect($this->import->segments)->equals($this->JSON_data['segments']);
    expect(is_array($this->import->subscriber_fields))->true();
    expect(is_array($this->import->subscriber_custom_fields))->true();
    expect($this->import->subscribers_count)->equals(
      count($this->JSON_data['subscribers']['email'])
    );
    expect(
      preg_match(
        '/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/',
        $this->import->import_time)
    )->equals(1);
  }

  function itCanFilterExistingAndNewSubscribers() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com'
      ));
    $subscriber->save();
    list($existing, $new) = $this->import->filterExistingAndNewSubscribers(
      $this->subscribers_data
    );
    expect($existing['email'][0])->equals($this->subscribers_data['email'][0]);
    expect($new['email'][0])->equals($this->subscribers_data['email'][1]);
  }

  function itCanExtendSubscribersAndFields() {
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

  function itCanGetSubscriberFields() {
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

  function itCanGetCustomSubscriberFields() {
    $data = array(
      'one',
      'two',
      39
    );
    $fields = $this->import->getCustomSubscriberFields($data);
    expect($fields)->equals(array(39));
  }

  function itCanFilterSubscriberStatus() {
    $subscibers_data = $this->subscribers_data;
    $subscriber_fields = $this->subscriber_fields;
    list($subscibers_data, $subsciber_fields) =
      $this->import->filterSubscriberStatus($subscibers_data, $subscriber_fields);
    // subscribers' status was set to "subscribed" & status column was added
    // to subscribers fields
    expect(array_pop($subsciber_fields))->equals('status');
    expect($subscibers_data['status'][0])->equals('subscribed');
    expect(count($subscibers_data['status']))->equals(2);
    $subscriber_fields[] = 'status';
    $subscibers_data = array(
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
    list($subscibers_data, $subsciber_fields) =
      $this->import->filterSubscriberStatus($subscibers_data, $subscriber_fields);
    expect($subscibers_data)->equals(
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

  function itCanAddOrUpdateSubscribers() {
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

  function itCanDeleteTrashedSubscribers() {
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
      $this->segments
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

  function itCanCreateOrUpdateCustomFields() {
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

  function itCanaddSubscribersToSegments() {
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
      $this->segments
    );
    $subscribers_segments = SubscriberSegment::findArray();
    // 2 subscribers * 2 segments
    expect(count($subscribers_segments))->equals(4);
  }

  function itCanDeleteExistingTrashedSubscribers() {
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

  function itCanProcess() {
    $import = clone($this->import);
    $result = $import->process();
    expect($result['data']['created'])->equals(997);
    expect($result['data']['updated'])->equals(0);
    $result = $import->process();
    expect($result['data']['created'])->equals(0);
    expect($result['data']['updated'])->equals(997);
    Subscriber::where('email', 'mbanks4@blinklist.com')
      ->findOne()
      ->delete();
    // TODO: find a more elegant way to test this
    $import->import_time = date('Y-m-d 12:i:s');
    $result = $import->process();
    expect($result['data']['created'])->equals(1);
    expect($result['data']['updated'])->equals(996);
    $import->update_subscribers = false;
    $result = $import->process();
    expect($result['data']['created'])->equals(0);
    expect($result['data']['updated'])->equals(0);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberCustomField::$_table);
  }
}